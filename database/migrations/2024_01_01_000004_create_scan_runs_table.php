<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['recon', 'scan', 'load_test'])->index();
            $table->enum('status', ['queued', 'running', 'completed', 'failed', 'cancelled', 'interrupted'])
                ->default('queued')->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->foreignId('started_by')->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('config')->nullable(); // snapshot of the parameters used for this run
            $table->timestamps();

            $table->index(['project_id', 'stage', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_runs');
    }
};
