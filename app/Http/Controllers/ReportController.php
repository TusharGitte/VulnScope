<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function show(Project $project): View
    {
        $project->load(['targets', 'scopeRules', 'findings.evidence', 'loadTests.metrics', 'reports.generatedBy', 'scanRuns']);
        return view('report.show', [
            'project' => $project,
            'reports' => $project->reports()->latest()->get(),
            'severitySummary' => $this->severitySummary($project),
            'latestLoadTest' => $project->loadTests()->with('metrics')->latest()->first(),
        ]);
    }

    public function generate(Request $request, Project $project): RedirectResponse
    {
        $project->load(['targets', 'scopeRules', 'findings.evidence', 'scanRuns.reconResults', 'loadTests.metrics']);
        $reconRun = $project->scanRuns()->where('stage', 'recon')->where('status', 'completed')->latest()->first();
        $scanRun = $project->scanRuns()->where('stage', 'scan')->where('status', 'completed')->latest()->first();
        $loadRun = $project->scanRuns()->where('stage', 'load_test')->whereIn('status', ['completed', 'interrupted'])->latest()->first();
        if (! $reconRun || ! $scanRun || ! $loadRun) {
            return back()->with('error', 'A final report requires completed Step 1, Step 2, and a concluded Step 3 result.');
        }

        $severitySummary = $this->severitySummary($project);
        $reportTitle = 'VAPT Assessment Report - ' . $project->name;
        $report = Report::create([
            'project_id' => $project->id,
            'generated_by' => $request->user()->id,
            'title' => $reportTitle,
            'status' => 'generating',
            'summary_stats' => $severitySummary,
        ]);

        try {
            $pdf = Pdf::loadView('report.pdf', [
                'project' => $project,
                'scope' => $project->scopeRules()->latest('confirmed_at')->first(),
                'severitySummary' => $severitySummary,
                'reconRun' => $reconRun,
                'scanRun' => $scanRun,
                'loadRun' => $loadRun,
                'loadTest' => $project->loadTests()->where('scan_run_id', $loadRun->id)->with('metrics')->first(),
                'title' => $reportTitle,
            ])->setPaper('a4');

            $filename = 'reports/' . $project->id . '/' . $report->id . '_' . now()->format('Ymd_His') . '.pdf';
            Storage::disk('local')->put($filename, $pdf->output());
            $report->update(['status' => 'ready', 'storage_path' => $filename, 'generated_at' => now()]);

            $project->update(['current_step' => Project::STEP_REPORT, 'status' => 'completed']);
            AuditLog::record('report.generated', 'success', $request->user()->id, $project->id, ['report_id' => $report->id]);
            return redirect()->route('report.show', $project)->with('success', 'Professional PDF report generated successfully.');
        } catch (\Throwable $e) {
            $report->update(['status' => 'failed']);
            AuditLog::record('report.failed', 'failure', $request->user()->id, $project->id, context: ['report_id' => $report->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Report generation failed. Check the application log for technical details.');
        }
    }

    public function view(Project $project, Report $report)
    {
        abort_unless((int) $report->project_id === (int) $project->id, 404);
        abort_unless($report->status === 'ready', 404);
        if (! $report->storage_path || ! Storage::disk('local')->exists($report->storage_path)) {
            return back()->with('error', 'Report file could not be found.');
        }
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $report->title) . '.pdf';
        return response()->file(Storage::disk('local')->path($report->storage_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
        ]);
    }

    public function download(Project $project, Report $report)
    {
        abort_unless((int) $report->project_id === (int) $project->id, 404);
        abort_unless($report->status === 'ready', 404);
        if (! $report->storage_path || ! Storage::disk('local')->exists($report->storage_path)) {
            return back()->with('error', 'Report file could not be found.');
        }
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $report->title) . '.pdf';
        return response()->download(Storage::disk('local')->path($report->storage_path), $safeName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeName . '"; filename*=UTF-8\'\'' . rawurlencode($safeName),
        ]);
    }

    public function destroy(Request $request, Project $project, Report $report): RedirectResponse
    {
        abort_unless((int) $report->project_id === (int) $project->id, 404);
        if ($report->storage_path) Storage::disk('local')->delete($report->storage_path);
        $id = $report->id;
        $report->delete();
        if (! $project->reports()->where('status', 'ready')->exists() && $project->current_step === Project::STEP_REPORT) {
            $project->update(['current_step' => Project::STEP_LOAD_TEST, 'status' => 'active']);
        }
        AuditLog::record('report.deleted', 'success', $request->user()->id, $project->id, context: ['report_id' => $id]);
        return redirect()->route('report.show', $project)->with('success', 'Report deliverable removed.');
    }

    private function severitySummary(Project $project): array
    {
        return collect(['critical', 'high', 'medium', 'low', 'informational'])
            ->mapWithKeys(fn ($severity) => [$severity => $project->findings()->where('severity', $severity)->count()])
            ->all();
    }
}
