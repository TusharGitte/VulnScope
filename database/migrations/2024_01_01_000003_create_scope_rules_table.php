<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Inclusion / exclusion lists (JSON arrays of strings/CIDRs)
            $table->json('allowed_domains')->nullable();      // e.g. ["example.com","*.staging.example.com"]
            $table->json('allowed_ip_ranges')->nullable();    // CIDRs, only if applicable
            $table->json('excluded_hosts')->nullable();
            $table->json('allowed_ports')->nullable();        // e.g. [80,443]
            $table->json('allowed_endpoints')->nullable();    // optional path allow-list / regex

            // Time window
            $table->timestamp('window_start');
            $table->timestamp('window_end');

            // Hard technical ceilings for this project (must be <= platform-wide env ceilings;
            // enforced server-side in ScopeEnforcementService, never trusted from the client)
            $table->unsignedInteger('max_request_rate')->default(5);       // req/sec
            $table->unsignedInteger('max_concurrency')->default(5);
            $table->unsignedInteger('max_duration_seconds')->default(300);
            $table->unsignedInteger('max_total_requests')->default(5000);

            $table->boolean('authenticated_testing_allowed')->default(false);

            $table->text('authorization_notes')->nullable();
            $table->foreignId('confirmed_by')->constrained('users');
            $table->timestamp('confirmed_at');

            $table->timestamps();

            $table->index(['project_id', 'window_start', 'window_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_rules');
    }
};
