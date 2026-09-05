<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\VulnerabilityFinding;
use App\Services\ScopeEnforcementService;
use App\Services\TargetUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FindingController extends Controller
{
    public function __construct(private ScopeEnforcementService $scope)
    {
    }

    public function index(Project $project): View
    {
        $findings = $project->findings()->with(['target', 'evidence'])->latest()->paginate(20);
        $counts = collect(VulnerabilityFinding::SEVERITIES)->mapWithKeys(fn ($s) => [$s => $project->findings()->where('severity', $s)->count()]);
        return view('findings.index', compact('project', 'findings', 'counts'));
    }

    public function create(Project $project): View
    {
        $targets = $project->targets()->get();
        return view('findings.create', compact('project', 'targets'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:' . implode(',', VulnerabilityFinding::SEVERITIES)],
            'category' => ['required', 'string', 'max:255'],
            'confidence' => ['required', 'in:low,medium,high'],
            'target_id' => ['required', 'integer'],
            'url' => ['required', 'url', 'max:2000'],
            'parameter' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'impact' => ['nullable', 'string'],
            'remediation' => ['nullable', 'string'],
            'reproduction_guidance' => ['nullable', 'string'],
            'status' => ['required', 'in:' . implode(',', VulnerabilityFinding::STATUSES)],
            'analyst_notes' => ['nullable', 'string'],
            'references' => ['nullable', 'array'],
            'references.*' => ['url', 'max:2000'],
            'references_text' => ['nullable', 'string', 'max:10000'],
        ]);
        $target = $project->targets()->findOrFail($validated['target_id']);
        $findingUrl = TargetUrlService::normalize($validated['url']);
        $parts = parse_url($findingUrl);
        $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
        $this->scope->assertInScope($project, strtolower($parts['host'] ?? ''), $port, url: $findingUrl);

        $scanRun = $project->scanRuns()->where('stage', 'scan')->latest()->firstOrFail();
        $references = collect(preg_split('/\R/', $validated['references_text'] ?? ''))->map(fn ($value) => trim($value))->filter()->values()->all();
        validator(['references' => $references], ['references' => 'array', 'references.*' => 'url|max:2000'])->validate();

        $finding = $project->findings()->create([
            'scan_run_id' => $scanRun->id,
            'target_id' => $target->id,
            'title' => $validated['title'],
            'severity' => $validated['severity'],
            'confidence' => $validated['confidence'],
            'category' => $validated['category'],
            'url' => $findingUrl,
            'parameter' => $validated['parameter'] ?? null,
            'description' => $validated['description'],
            'impact' => $validated['impact'] ?? null,
            'remediation' => $validated['remediation'] ?? null,
            'reproduction_guidance' => $validated['reproduction_guidance'] ?? null,
            'status' => $validated['status'],
            'references' => $references,
            'analyst_notes' => $validated['analyst_notes'] ?? null,
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        AuditLog::record('finding.created', 'success', $request->user()->id, $project->id, $target->hostname, ['finding_id' => $finding->id, 'severity' => $finding->severity]);
        return redirect()->route('findings.show', [$project, $finding])->with('success', 'Finding created.');
    }

    public function show(Project $project, VulnerabilityFinding $finding): View
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);
        $finding->load(['target', 'evidence', 'validatedBy']);
        return view('findings.show', compact('project', 'finding'));
    }

    public function edit(Project $project, VulnerabilityFinding $finding): View
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);
        $targets = $project->targets()->get();
        return view('findings.edit', compact('project', 'finding', 'targets'));
    }

    public function update(Request $request, Project $project, VulnerabilityFinding $finding): RedirectResponse
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);
        if (!$request->has('title')) {
            $validated = $request->validate([
                'status' => ['required', 'in:' . implode(',', VulnerabilityFinding::STATUSES)],
            ]);
            $finding->update([
                'status' => $validated['status'],
                'validated_by' => $request->user()->id,
                'validated_at' => now(),
                'last_seen_at' => now(),
            ]);
            AuditLog::record('finding.status_updated', 'success', $request->user()->id, $project->id, optional($finding->target)->hostname, [
                'finding_id' => $finding->id,
                'status' => $finding->status,
            ]);
            return redirect()->route('findings.show', [$project, $finding])->with('success', 'Finding status updated.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:' . implode(',', VulnerabilityFinding::SEVERITIES)],
            'confidence' => ['required', 'in:low,medium,high'],
            'category' => ['required', 'string', 'max:255'],
            'target_id' => ['required', 'integer'],
            'url' => ['required', 'url', 'max:2000'],
            'parameter' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'impact' => ['nullable', 'string'],
            'remediation' => ['nullable', 'string'],
            'reproduction_guidance' => ['nullable', 'string'],
            'status' => ['required', 'in:' . implode(',', VulnerabilityFinding::STATUSES)],
            'analyst_notes' => ['nullable', 'string'],
            'references' => ['nullable', 'array'],
            'references.*' => ['url', 'max:2000'],
            'references_text' => ['nullable', 'string', 'max:10000'],
        ]);
        $target = $project->targets()->findOrFail($validated['target_id']);
        $findingUrl = TargetUrlService::normalize($validated['url']);
        $parts = parse_url($findingUrl);
        $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
        $this->scope->assertInScope($project, strtolower($parts['host'] ?? ''), $port, url: $findingUrl);
        $references = collect(preg_split('/\R/', $validated['references_text'] ?? ''))->map(fn ($value) => trim($value))->filter()->values()->all();
        validator(['references' => $references], ['references' => 'array', 'references.*' => 'url|max:2000'])->validate();
        $validated['references'] = $references;
        $finding->update(array_merge($validated, ['url' => $findingUrl, 'validated_by' => $request->user()->id, 'validated_at' => now(), 'last_seen_at' => now(), 'target_id' => $target->id]));
        AuditLog::record('finding.updated', 'success', $request->user()->id, $project->id, $target->hostname, ['finding_id' => $finding->id, 'status' => $finding->status]);
        return redirect()->route('findings.show', [$project, $finding])->with('success', 'Finding updated successfully.');
    }

    public function destroy(Request $request, Project $project, VulnerabilityFinding $finding): RedirectResponse
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);
        AuditLog::record('finding.deleted', 'success', $request->user()->id, $project->id, context: ['finding_id' => $finding->id, 'title' => $finding->title]);
        $finding->delete();
        return redirect()->route('findings.index', $project)->with('success', 'Finding deleted.');
    }
}
