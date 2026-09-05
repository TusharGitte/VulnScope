<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\Project;
use App\Models\VulnerabilityFinding;
use App\Services\SecretRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function store(Request $request, Project $project, VulnerabilityFinding $finding): RedirectResponse
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'type' => ['required', 'in:screenshot,http_request,http_response,log_excerpt,note,file'],
            'content' => ['nullable', 'string', 'max:50000'],
            'file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,txt'],
            'confirm_redacted' => ['required', 'accepted'],
        ]);
        if (empty($validated['content']) && ! $request->hasFile('file')) {
            return back()->withErrors(['content' => 'Provide text evidence or upload a file.']);
        }

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('evidence', 'local');
        }

        Evidence::create([
            'finding_id' => $finding->id,
            'type' => $validated['type'],
            'storage_path' => $path,
            'content' => isset($validated['content']) ? SecretRedactor::redactString($validated['content']) : null,
            'secrets_redacted' => true,
            'captured_by' => $request->user()->id,
        ]);
        AuditLog::record('evidence.added', 'success', $request->user()->id, $project->id, $finding->url, ['finding_id' => $finding->id, 'type' => $validated['type']]);
        return back()->with('success', 'Evidence added.');
    }

    public function destroy(Request $request, Project $project, VulnerabilityFinding $finding, Evidence $evidence): RedirectResponse
    {
        abort_unless((int) $finding->project_id === (int) $project->id && (int) $evidence->finding_id === (int) $finding->id, 404);
        if ($evidence->storage_path) Storage::disk('local')->delete($evidence->storage_path);
        $evidence->delete();
        AuditLog::record('evidence.deleted', 'success', $request->user()->id, $project->id, $finding->url, ['finding_id' => $finding->id, 'evidence_id' => $evidence->id]);
        return back()->with('success', 'Evidence removed.');
    }
}
