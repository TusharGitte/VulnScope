<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if ($project && $request->user()) {
            abort_unless(
                $request->user()->isAdmin() || (int) $project->owner_id === (int) $request->user()->id,
                403,
                'You are not authorized to access this project.'
            );

            foreach (['target', 'scanRun', 'finding', 'loadTest', 'report', 'evidence'] as $child) {
                $model = $request->route($child);
                if ($model && isset($model->project_id) && (int) $model->project_id !== (int) $project->id) {
                    abort(404);
                }
            }

            if ($evidence = $request->route('evidence')) {
                $finding = $request->route('finding');
                if ($finding && (int) $evidence->finding_id !== (int) $finding->id) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
