<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks entry into Step N unless the project has actually completed Step N-1.
 * This is enforced here (server-side, on every request to a step route) rather
 * than only hidden/disabled in the UI. Route example:
 *   Route::middleware('step:2')->group(...)   // requires recon complete
 */
class EnsureStepOrder
{
    public function handle(Request $request, Closure $next, string $requiredStep): Response
    {
        /** @var Project $project */
        $project = $request->route('project');
        $step = (int) $requiredStep;

        if (! $project || ! $project->canEnterStep($step)) {
            AuditLog::record(
                action: 'workflow.step_blocked',
                result: 'blocked',
                projectId: $project?->id,
                context: ['attempted_step' => $step, 'current_step' => $project?->current_step],
            );

            abort(403, 'This step is not yet unlocked for this project. Complete the prior step first.');
        }

        return $next($request);
    }
}
