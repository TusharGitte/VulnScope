<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ScopeRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScopeController extends Controller
{
    public function edit(Project $project): View
    {
        $project->load('targets');
        $rule = $project->scopeRules()->latest('confirmed_at')->first();
        return view('scope.edit', compact('project', 'rule'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        if ($project->hasActiveRun('recon') || $project->hasActiveRun('scan') || $project->hasActiveRun('load_test')) {
            return back()->with('error', 'Scope cannot be changed while an assessment job is running.');
        }

        $validated = $request->validate([
            'allowed_domains' => ['required', 'string'],
            'allowed_ip_ranges' => ['nullable', 'string'],
            'excluded_hosts' => ['nullable', 'string'],
            'allowed_ports' => ['required', 'string'],
            'allowed_endpoints' => ['nullable', 'string'],
            'window_start' => ['required', 'date'],
            'window_end' => ['required', 'date', 'after:window_start'],
            'max_request_rate' => ['required', 'integer', 'min:1', 'max:' . config('vapt.max_request_rate')],
            'max_concurrency' => ['required', 'integer', 'min:1', 'max:' . config('vapt.max_concurrency')],
            'max_duration_seconds' => ['required', 'integer', 'min:10', 'max:' . config('vapt.max_duration_seconds')],
            'max_total_requests' => ['required', 'integer', 'min:10', 'max:' . config('vapt.max_total_requests')],
            'authenticated_testing_allowed' => ['boolean'],
            'authorization_notes' => ['nullable', 'string', 'max:10000'],
            'explicit_authorization_confirm' => ['accepted'],
        ]);

        $parseLines = function (?string $raw): array {
            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $raw))));
        };

        $domains = $parseLines($validated['allowed_domains']);
        if ($domains === []) {
            return back()->withInput()->withErrors(['allowed_domains' => 'At least one allowed domain or host is required.']);
        }

        $ips = $parseLines($validated['allowed_ip_ranges'] ?? null);
        foreach ($ips as $cidr) {
            [$network, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
            if (! filter_var($network, FILTER_VALIDATE_IP) || ($bits !== null && (! ctype_digit($bits) || (int) $bits > (str_contains($network, ':') ? 128 : 32)))) {
                return back()->withInput()->withErrors(['allowed_ip_ranges' => "Invalid IP/CIDR: {$cidr}"]);
            }
        }

        $ports = $parseLines($validated['allowed_ports'] ?? null);
        if ($ports === []) {
            return back()->withInput()->withErrors(['allowed_ports' => 'At least one allowed TCP port is required. Include the target URL port (80 or 443 unless explicitly changed).']);
        }
        foreach ($ports as $port) {
            if (! ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
                return back()->withInput()->withErrors(['allowed_ports' => "Invalid port: {$port}"]);
            }
        }

        $target = $project->targets()->first();
        if (! $target) {
            return back()->withInput()->withErrors(['allowed_domains' => 'Add the primary target before defining scope.']);
        }
        $targetHost = strtolower($target->hostname);
        $targetPort = (int) (parse_url($target->normalized_url, PHP_URL_PORT) ?: (parse_url($target->normalized_url, PHP_URL_SCHEME) === 'https' ? 443 : 80));
        $domainAllowed = collect($domains)->contains(function ($pattern) use ($targetHost) {
            $pattern = strtolower(trim($pattern));
            if ($pattern === $targetHost) return true;
            if (str_starts_with($pattern, '*.')) {
                $base = substr($pattern, 2);
                return $targetHost !== $base && str_ends_with($targetHost, '.' . $base);
            }
            return false;
        });
        if (! $domainAllowed) {
            return back()->withInput()->withErrors(['allowed_domains' => 'The primary target hostname is not included in the allowed domain/host list.']);
        }
        if (! in_array($targetPort, array_map('intval', $ports), true)) {
            return back()->withInput()->withErrors(['allowed_ports' => "The primary target port ({$targetPort}) must be included in allowed ports."]);
        }

        $rule = $project->scopeRules()->create([
            'allowed_domains' => $domains,
            'allowed_ip_ranges' => $ips ?: null,
            'excluded_hosts' => $parseLines($validated['excluded_hosts'] ?? null) ?: null,
            'allowed_ports' => array_map('intval', $ports) ?: null,
            'allowed_endpoints' => $parseLines($validated['allowed_endpoints'] ?? null) ?: null,
            'window_start' => $validated['window_start'],
            'window_end' => $validated['window_end'],
            'max_request_rate' => (int) $validated['max_request_rate'],
            'max_concurrency' => (int) $validated['max_concurrency'],
            'max_duration_seconds' => (int) $validated['max_duration_seconds'],
            'max_total_requests' => (int) $validated['max_total_requests'],
            'authenticated_testing_allowed' => $request->boolean('authenticated_testing_allowed'),
            'authorization_notes' => $validated['authorization_notes'] ?? null,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        $project->update([
            'status' => 'active',
            'authorized_at' => now(),
            'authorized_by' => $request->user()->id,
            'current_step' => Project::STEP_NONE,
        ]);

        AuditLog::record('scope.confirmed', 'success', $request->user()->id, $project->id, context: [
            'scope_rule_id' => $rule->id,
            'domains' => $rule->allowed_domains,
            'window_start' => (string) $rule->window_start,
            'window_end' => (string) $rule->window_end,
        ]);

        return redirect()->route('recon.show', $project)->with('success', 'Authorization scope recorded. Step 1 is unlocked during the approved window.');
    }
}
