<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('load_test_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('load_test_id')->constrained()->cascadeOnDelete();

            // one row per sampling interval (e.g. every 1-5s) for live charting
            $table->timestamp('sampled_at')->index();
            $table->unsignedInteger('requests_per_sec');
            $table->unsignedInteger('throughput_bytes_per_sec')->nullable();
            $table->unsignedInteger('p50_latency_ms');
            $table->unsignedInteger('p95_latency_ms');
            $table->unsignedInteger('p99_latency_ms');
            $table->unsignedInteger('max_latency_ms');
            $table->float('error_percent');
            $table->float('timeout_percent');
            $table->json('status_code_distribution')->nullable(); // {"200": 950, "500": 12, ...}
            $table->unsignedInteger('concurrent_users');
            $table->timestamps();

            $table->index(['load_test_id', 'sampled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('load_test_metrics');
    }
};
