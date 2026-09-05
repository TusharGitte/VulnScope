<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::with(['targets', 'scopeRules', 'scanRuns', 'findings'])
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('owner_id', $request->user()->id))
            ->latest()
            ->paginate(15);

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project = Project::create([
            'owner_id' => $request->user()->id,
            'name' => $validated['name'],
            'client_name' => $validated['client_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
            'current_step' => Project::STEP_NONE,
        ]);

        AuditLog::record('project.created', 'success', $request->user()->id, $project->id, [
            'name' => $project->name,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Project created successfully. Next, configure target and scope.');
    }

    public function show(Project $project): View
    {
        $project->load(['targets', 'scopeRules', 'scanRuns', 'findings', 'loadTests', 'reports']);
        $activeScope = $project->activeScopeRule();

        return view('projects.show', compact('project', 'activeScope'));
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,completed,archived'],
        ]);

        $project->update($validated);

        AuditLog::record('project.updated', 'success', $request->user()->id, $project->id);

        return redirect()->route('projects.show', $project)->with('success', 'Project details updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        if ($project->hasActiveRun('recon') || $project->hasActiveRun('scan') || $project->hasActiveRun('load_test')) {
            return back()->with('error', 'Projects cannot be deleted while an assessment job is running.');
        }

        AuditLog::record('project.deleted', 'success', $request->user()->id, $project->id, [
            'name' => $project->name,
        ]);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project removed.');
    }
}
