<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\VulnerabilityFinding;
use Illuminate\Support\Facades\Storage;

function csrf(string $html): ?string {
    return preg_match('/name="_token" value="([^"]+)"/', $html, $m) ? html_entity_decode($m[1]) : null;
}

$client = new GuzzleHttp\Client(['base_uri'=>'http://127.0.0.1:8000','cookies'=>true,'http_errors'=>false,'allow_redirects'=>true]);
$verifyEmail = getenv('VAPT_VERIFY_EMAIL') ?: ('crud-' . bin2hex(random_bytes(5)) . '@example.test');
$verifyPassword = getenv('VAPT_VERIFY_PASSWORD') ?: ('CRUD-' . bin2hex(random_bytes(12)) . '-Only!');
$verifyUser = User::updateOrCreate(
    ['email' => $verifyEmail],
    ['name' => 'CRUD Verification User', 'password' => Hash::make($verifyPassword)]
);
$verifyUser->forceFill(['role' => 'analyst', 'is_active' => true, 'email_verified_at' => now()])->save();

try {
    echo "=== CRUD VERIFICATION ===\n";
    $res=$client->get('/login'); $token=csrf((string)$res->getBody());
    $res=$client->post('/login',['form_params'=>['_token'=>$token,'email'=>$verifyEmail,'password'=>$verifyPassword]]);
    if ($res->getStatusCode() !== 200) throw new RuntimeException('login failed');
    $token=csrf((string)$res->getBody()) ?? $token;

    $res=$client->get('/projects/create'); $token=csrf((string)$res->getBody()) ?? $token;
    $name='CRUD VAPT '.bin2hex(random_bytes(4));
    $client->post('/projects',['form_params'=>['_token'=>$token,'name'=>$name,'client_name'=>'CRUD Client','description'=>'Disposable CRUD test']]);
    $project=Project::where('name',$name)->firstOrFail();
    echo "CREATE project #{$project->id}\n";

    $client->put("/projects/{$project->id}",['form_params'=>['_token'=>$token,'name'=>$name.' updated','client_name'=>'CRUD Client Updated','description'=>'updated','status'=>'active']]);
    $project->refresh(); if ($project->name !== $name.' updated') throw new RuntimeException('project update failed');
    echo "UPDATE project\n";

    $client->delete("/projects/{$project->id}",['form_params'=>['_token'=>$token]]);
    if (Project::find($project->id)) throw new RuntimeException('project delete failed');
    echo "DELETE project\n";
    User::where('email', $verifyEmail)->delete();
    echo "ALL CRUD CHECKS PASSED.\n";
} catch (Throwable $e) {
    fwrite(STDERR,"FAILED: {$e->getMessage()}\n"); exit(1);
}
