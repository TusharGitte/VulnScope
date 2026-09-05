<?php

namespace App\Http\Controllers;

use App\Jobs\RunWebScanJob;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ScanRun;
use App\Services\ScopeEnforcementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function __construct(private ScopeEnforcementService $scope)
    {
    }

    public function show(Project $project): View
    {
        $project->load(['targets', 'findings']);
        $scanRuns = $project->scanRuns()->where('stage', 'scan')->latest()->get();
        $activeRun = $scanRuns->first(fn($run) => $run->isActive());
        $findings = $project->findings()->latest()->get();
        return view('scan.show', compact('project', 'scanRuns', 'activeRun', 'findings'));
    }

    public function start(Request $request, Project $project): RedirectResponse
    {
        $target = $project->targets()->first();
        if (! $target) return back()->with('error', 'No target configured for this project.');
        if ($project->hasAnyActiveRun()) return back()->with('error', 'Another assessment job is already running for this project.');

        $this->scope->assertInScope($project, $target->hostname, $this->port($target), url: $target->normalized_url);
        $this->scope->assertResolvedIpsInScope($project, $target->hostname);
        if ($project->current_step > 1) {
            $project->update(['current_step' => 1, 'status' => 'active']);
        }

        $run = $project->scanRuns()->create([
            'target_id' => $target->id,
            'stage' => 'scan',
            'status' => 'queued',
            'progress_percent' => 0,
            'started_by' => $request->user()->id,
            'config' => ['url' => $target->normalized_url],
        ]);
        RunWebScanJob::dispatch($run->id)->onQueue('vapt');
        AuditLog::record('scan.queued', 'success', $request->user()->id, $project->id, $target->hostname, ['scan_run_id' => $run->id]);
        return redirect()->route('scan.show', $project)->with('success', 'Security assessment queued. The backend worker will execute the bounded scan.');
    }

    public function cancel(Request $request, Project $project, ScanRun $scanRun): RedirectResponse
    {
        abort_unless($scanRun->project_id === $project->id && $scanRun->stage === 'scan', 404);
        if ($scanRun->isActive()) {
            $scanRun->update(['status' => 'cancelled', 'finished_at' => now()]);
            AuditLog::record('scan.cancelled', 'success', $request->user()->id, $project->id, context: ['scan_run_id' => $scanRun->id]);
        }
        return redirect()->route('scan.show', $project)->with('success', 'Scan cancellation requested.');
    }

    private function port($target): int
    {
        return (int) (parse_url($target->normalized_url, PHP_URL_PORT) ?: (parse_url($target->normalized_url, PHP_URL_SCHEME) === 'https' ? 443 : 80));
    }
}
