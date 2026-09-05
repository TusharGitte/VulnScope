<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('vulnerability_findings')->cascadeOnDelete();
            $table->enum('type', ['screenshot', 'http_request', 'http_response', 'log_excerpt', 'note', 'file']);
            $table->string('storage_path')->nullable();  // for files/screenshots, on private disk
            $table->longText('content')->nullable();     // for text-based evidence (redacted request/response)
            $table->boolean('secrets_redacted')->default(true);
            $table->foreignId('captured_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
