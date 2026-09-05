<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\ReconResult;
use App\Models\VulnerabilityFinding;
use App\Models\LoadTest;
use App\Models\Report;

function extractCsrf(string $html): ?string {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $m)) return html_entity_decode($m[1]);
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $m)) return html_entity_decode($m[1]);
    return null;
}

function processVaptJob(): void {
    $exit = Artisan::call('queue:work', [
        'connection' => 'database',
        '--queue' => 'vapt',
        '--once' => true,
        '--tries' => 1,
        '--timeout' => 1800,
    ]);
    if ($exit !== 0) {
        throw new RuntimeException("queue:work failed: {$exit}\n" . Artisan::output());
    }
}

$client = new GuzzleHttp\Client([
    'base_uri' => 'http://127.0.0.1:8000',
    'cookies' => true,
    'http_errors' => false,
    'allow_redirects' => true,
]);

$verifyEmail = getenv('VAPT_VERIFY_EMAIL') ?: ('e2e-' . bin2hex(random_bytes(5)) . '@example.test');
$verifyPassword = getenv('VAPT_VERIFY_PASSWORD') ?: ('E2E-' . bin2hex(random_bytes(12)) . '-Only!');
$verifyUser = User::updateOrCreate(
    ['email' => $verifyEmail],
    ['name' => 'E2E Verification User', 'password' => Hash::make($verifyPassword)]
);
$verifyUser->forceFill(['role' => 'admin', 'is_active' => true, 'email_verified_at' => now()])->save();

echo "=== VAPT PLATFORM END-TO-END VERIFICATION ===\n";

try {
    echo "[1/10] Login + CSRF...\n";
    $res = $client->get('/login');
    if ($res->getStatusCode() !== 200) throw new RuntimeException('/login did not return 200');
    $csrf = extractCsrf((string) $res->getBody());
    if (!$csrf) throw new RuntimeException('Could not extract CSRF token');
    $res = $client->post('/login', ['form_params' => ['_token' => $csrf, 'email' => $verifyEmail, 'password' => $verifyPassword]]);
    if ($res->getStatusCode() !== 200) throw new RuntimeException('Login flow failed: HTTP ' . $res->getStatusCode());
    $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    echo "  PASS\n";

    echo "[2/10] Create project...\n";
    $res = $client->get('/projects/create'); $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $name = 'E2E VAPT ' . bin2hex(random_bytes(4));
    $res = $client->post('/projects', ['form_params' => ['_token'=>$csrf, 'name'=>$name, 'client_name'=>'E2E Client', 'description'=>'Disposable verification project.']]);
    $project = Project::where('name', $name)->firstOrFail();
    echo "  PASS #{$project->id}\n";

    echo "[3/10] Add target...\n";
    $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $res = $client->post("/projects/{$project->id}/targets", ['form_params'=>['_token'=>$csrf, 'input_url'=>'http://127.0.0.1:8000']]);
    $target = $project->targets()->firstOrFail();
    echo "  PASS {$target->normalized_url}\n";

    echo "[4/10] Configure authorization + scope...\n";
    $res = $client->get("/projects/{$project->id}/scope"); $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $res = $client->post("/projects/{$project->id}/scope", ['form_params'=>[
        '_token'=>$csrf,
        'allowed_domains'=>'127.0.0.1',
        'allowed_ip_ranges'=>'127.0.0.1/32',
        'allowed_ports'=>'8000',
        'allowed_endpoints'=>'/*',
        'window_start'=>date('Y-m-d\\TH:i', strtotime('-5 minutes')),
        'window_end'=>date('Y-m-d\\TH:i', strtotime('+2 hours')),
        'max_request_rate'=>5,
        'max_concurrency'=>2,
        'max_duration_seconds'=>60,
        'max_total_requests'=>20,
        'authenticated_testing_allowed'=>'0',
        'authorization_notes'=>'Disposable local E2E verification; do not use against real systems.',
        'explicit_authorization_confirm'=>'1',
    ]]);
    if (!$project->fresh()->activeScopeRule()) throw new RuntimeException('Scope was not activated');
    echo "  PASS\n";

    echo "[5/10] Queue + run Step 1 recon...\n";
    $res = $client->get("/projects/{$project->id}/recon"); $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $res = $client->post("/projects/{$project->id}/recon/start", ['form_params'=>['_token'=>$csrf]]);
    processVaptJob();
    $reconRun = $project->scanRuns()->where('stage','recon')->latest()->firstOrFail();
    if ($reconRun->status !== 'completed') throw new RuntimeException("Recon ended {$reconRun->status}: {$reconRun->error_message}");
    echo "  PASS " . ReconResult::where('scan_run_id',$reconRun->id)->count() . " artifacts\n";

    echo "[6/10] Queue + run Step 2 scan...\n";
    $res = $client->get("/projects/{$project->id}/scan"); $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $res = $client->post("/projects/{$project->id}/scan/start", ['form_params'=>['_token'=>$csrf]]);
    processVaptJob();
    $scanRun = $project->scanRuns()->where('stage','scan')->latest()->firstOrFail();
    if ($scanRun->status !== 'completed') throw new RuntimeException("Scan ended {$scanRun->status}: {$scanRun->error_message}");
    echo "  PASS " . VulnerabilityFinding::where('scan_run_id',$scanRun->id)->count() . " findings\n";

    echo "[7/10] Confirm + queue controlled Step 3 load test...\n";
    $res = $client->get("/projects/{$project->id}/load-test"); $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $res = $client->post("/projects/{$project->id}/load-test/start", ['form_params'=>[
        '_token'=>$csrf,
        'endpoint'=>'http://127.0.0.1:8000/',
        'http_method'=>'GET',
        'max_rps'=>2,
        'concurrency'=>2,
        'duration_seconds'=>10,
        'max_total_requests'=>10,
        'ramp_up_seconds'=>0,
        'request_timeout_ms'=>3000,
        'error_rate_threshold_percent'=>25,
        'latency_threshold_ms'=>10000,
        'confirm_authorization'=>'1',
    ]]);
    processVaptJob();
    $loadTest = $project->loadTests()->latest()->firstOrFail();
    $loadRun = $loadTest->scanRun;
    if (!in_array($loadRun->status, ['completed','interrupted'], true)) throw new RuntimeException("Load test ended {$loadRun->status}");
    echo "  PASS status={$loadRun->status}, reason={$loadTest->stop_reason}\n";

    echo "[8/10] Validate Step 4 workspace...\n";
    $res = $client->get("/projects/{$project->id}/report");
    if ($res->getStatusCode() !== 200) throw new RuntimeException('Report workspace did not unlock');
    echo "  PASS\n";

    echo "[9/10] Generate PDF...\n";
    $csrf = extractCsrf((string) $res->getBody()) ?? $csrf;
    $res = $client->post("/projects/{$project->id}/report/generate", ['form_params'=>['_token'=>$csrf]]);
    $report = $project->reports()->latest()->firstOrFail();
    if ($report->status !== 'ready' || !Storage::disk('local')->exists($report->storage_path)) throw new RuntimeException('Report file was not generated');
    echo "  PASS {$report->storage_path} (" . round(Storage::disk('local')->size($report->storage_path)/1024,2) . " KB)\n";

    echo "[10/10] Verify private report download...\n";
    $res = $client->get("/projects/{$project->id}/report/{$report->id}/download");
    if ($res->getStatusCode() !== 200 || stripos($res->getHeaderLine('Content-Type'),'application/pdf') === false) throw new RuntimeException('Report download failed');
    echo "  PASS\n";

    $project->delete();
    User::where('email', $verifyEmail)->delete();
    echo "\nALL E2E CHECKS PASSED.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "FAILED: {$e->getMessage()}\n");
    exit(1);
}
