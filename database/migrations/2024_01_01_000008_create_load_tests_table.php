<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('load_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_run_id')->constrained()->cascadeOnDelete();

            $table->string('endpoint');
            $table->string('http_method', 10)->default('GET');
            $table->longText('request_body_template')->nullable();

            $table->unsignedInteger('virtual_users');
            $table->unsignedInteger('concurrency');
            $table->unsignedInteger('ramp_up_seconds')->default(0);
            $table->unsignedInteger('duration_seconds');
            $table->unsignedInteger('max_rps');
            $table->unsignedInteger('max_total_requests');
            $table->unsignedInteger('request_timeout_ms')->default(5000);

            $table->unsignedInteger('error_rate_threshold_percent')->default(25);
            $table->unsignedInteger('latency_threshold_ms')->default(10000);

            $table->boolean('explicitly_confirmed')->default(false);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->enum('stop_reason', [
                'completed', 'manual_stop', 'rate_limit_breach', 'error_threshold_breach',
                'latency_threshold_breach', 'duration_reached', 'total_requests_reached', 'cancelled',
            ])->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('load_tests');
    }
};
