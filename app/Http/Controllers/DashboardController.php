<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\VulnerabilityFinding;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $projects = Project::with(['targets', 'findings', 'scanRuns', 'loadTests'])
            ->when(! $user->isAdmin(), fn ($query) => $query->where('owner_id', $user->id))
            ->latest()
            ->get();

        $stats = [
            'total_projects' => $projects->count(),
            'active_scans' => $projects->filter(fn ($project) => $project->scanRuns->contains(fn ($run) => in_array($run->status, ['queued', 'running'], true)))->count(),
            'total_findings' => VulnerabilityFinding::whereIn('project_id', $projects->pluck('id'))->count(),
            'critical_high_findings' => VulnerabilityFinding::whereIn('project_id', $projects->pluck('id'))
                ->whereIn('severity', ['critical', 'high'])
                ->count(),
        ];

        return view('dashboard', compact('projects', 'stats'));
    }
}
