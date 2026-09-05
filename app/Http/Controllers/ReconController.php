<?php

namespace App\Http\Controllers;

use App\Jobs\RunReconJob;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ScanRun;
use App\Services\ScopeEnforcementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconController extends Controller
{
    public function __construct(private ScopeEnforcementService $scope)
    {
    }

    public function show(Project $project): View
    {
        $project->load(['targets', 'scopeRules']);
        $runs = $project->scanRuns()->where('stage', 'recon')->latest()->get();
        $latestRun = $runs->first();
        $reconResults = $latestRun ? $latestRun->reconResults()->get()->groupBy('section') : collect();
        return view('recon.show', compact('project', 'runs', 'latestRun', 'reconResults'));
    }

    public function start(Request $request, Project $project): RedirectResponse
    {
        $target = $project->targets()->first();
        if (! $target) return back()->with('error', 'Please define at least one target before starting reconnaissance.');
        if ($project->hasAnyActiveRun()) return back()->with('error', 'Another assessment job is already running for this project.');

        $this->scope->assertInScope($project, $target->hostname, $this->port($target), url: $target->normalized_url);
        $this->scope->assertResolvedIpsInScope($project, $target->hostname);
        if ($project->current_step > 0) {
            $project->update(['current_step' => 0, 'status' => 'active']);
        }

        $run = $project->scanRuns()->create([
            'target_id' => $target->id,
            'stage' => 'recon',
            'status' => 'queued',
            'progress_percent' => 0,
            'started_by' => $request->user()->id,
            'config' => ['hostname' => $target->hostname, 'url' => $target->normalized_url],
        ]);

        RunReconJob::dispatch($run->id)->onQueue('vapt');
        AuditLog::record('recon.queued', 'success', $request->user()->id, $project->id, $target->hostname, ['scan_run_id' => $run->id]);
        return redirect()->route('recon.show', $project)->with('success', 'Reconnaissance queued. The page will update when the worker processes the job.');
    }


    public function cancel(Request $request, Project $project, ScanRun $scanRun): RedirectResponse
    {
        abort_unless($scanRun->project_id === $project->id && $scanRun->stage === 'recon', 404);
        if ($scanRun->isActive()) {
            $scanRun->update(['status' => 'cancelled', 'finished_at' => now()]);
            AuditLog::record('recon.cancelled', 'success', $request->user()->id, $project->id, context: ['scan_run_id' => $scanRun->id]);
        }
        return redirect()->route('recon.show', $project)->with('success', 'Reconnaissance cancellation requested.');
    }
    private function port($target): int
    {
        return (int) (parse_url($target->normalized_url, PHP_URL_PORT) ?: (parse_url($target->normalized_url, PHP_URL_SCHEME) === 'https' ? 443 : 80));
    }
}
