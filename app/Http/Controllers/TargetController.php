<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Target;
use App\Services\TargetUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class TargetController extends Controller
{
    public function index(Project $project): View
    {
        $targets = $project->targets()->latest()->get();
        return view('projects.targets.index', compact('project', 'targets'));
    }

    public function create(Project $project): View
    {
        return view('projects.targets.create', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate(['input_url' => ['required', 'string', 'max:2000']]);
        try {
            $targetData = TargetUrlService::normalize($validated['input_url']);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['input_url' => $e->getMessage()]);
        }

        if ($project->hasActiveRun('recon') || $project->hasActiveRun('scan') || $project->hasActiveRun('load_test')) {
            return back()->with('error', 'Targets cannot be changed while an assessment job is running.');
        }

        if ($project->targets()->exists()) {
            return back()->withInput()->withErrors(['input_url' => 'This simple workflow supports one primary target per project. Edit the existing target instead of adding another.']);
        }

        $target = $project->targets()->create([
            'input_url' => $targetData['input_url'],
            'normalized_url' => $targetData['normalized_url'],
            'hostname' => $targetData['hostname'],
            'status' => 'pending',
        ]);

        $project->update(['current_step' => Project::STEP_NONE, 'status' => 'draft']);
        AuditLog::record('target.created', 'success', $request->user()->id, $project->id, $target->hostname, ['target_id' => $target->id]);

        return redirect()->route('projects.show', $project)->with('success', 'Target added successfully. Define and confirm scope before any network activity.');
    }

    public function show(Project $project, Target $target): View
    {
        abort_unless((int) $target->project_id === (int) $project->id, 404);
        $target->load(['project', 'reconResults', 'findings', 'scanRuns']);
        return view('projects.targets.show', compact('target'));
    }

    public function edit(Project $project, Target $target): View
    {
        abort_unless((int) $target->project_id === (int) $project->id, 404);
        return view('projects.targets.edit', compact('target'));
    }

    public function update(Request $request, Project $project, Target $target): RedirectResponse
    {
        abort_unless((int) $target->project_id === (int) $project->id, 404);
        if ($project->hasActiveRun('recon') || $project->hasActiveRun('scan') || $project->hasActiveRun('load_test')) {
            return back()->with('error', 'Targets cannot be changed while an assessment job is running.');
        }

        $validated = $request->validate([
            'input_url' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:pending,active,blocked,retired'],
        ]);

        try {
            $targetData = TargetUrlService::normalize($validated['input_url']);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['input_url' => $e->getMessage()]);
        }

        $target->update([
            'input_url' => $targetData['input_url'],
            'normalized_url' => $targetData['normalized_url'],
            'hostname' => $targetData['hostname'],
            'status' => $validated['status'],
        ]);
        $project->update(['current_step' => Project::STEP_NONE, 'status' => 'draft']);

        AuditLog::record('target.updated', 'success', $request->user()->id, $project->id, $target->hostname, ['target_id' => $target->id, 'status' => $target->status]);
        return redirect()->route('projects.show', $project)->with('success', 'Target details updated. Workflow progress was reset so the new target is reassessed safely.');
    }

    public function destroy(Request $request, Project $project, Target $target): RedirectResponse
    {
        abort_unless((int) $target->project_id === (int) $project->id, 404);
        if ($project->hasActiveRun('recon') || $project->hasActiveRun('scan') || $project->hasActiveRun('load_test')) {
            return back()->with('error', 'Targets cannot be removed while an assessment job is running.');
        }

        $hostname = $target->hostname;
        $target->delete();
        $project->update(['current_step' => Project::STEP_NONE, 'status' => 'draft']);
        AuditLog::record('target.deleted', 'success', $request->user()->id, $project->id, $hostname);

        return redirect()->route('projects.show', $project)->with('success', 'Target removed and workflow reset.');
    }
}
