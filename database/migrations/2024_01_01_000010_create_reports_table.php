<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->constrained('users');
            $table->string('title');
            $table->enum('status', ['generating', 'ready', 'failed'])->default('generating');
            $table->string('storage_path')->nullable(); // PDF on private disk
            $table->json('summary_stats')->nullable();  // counts by severity etc, cached for dashboard
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
