<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('target')->nullable(); // hostname/url involved, if any
            $table->string('action')->index();    // e.g. "login", "scope.updated", "scan.blocked_out_of_scope"
            $table->enum('result', ['success', 'failure', 'blocked'])->default('success');
            $table->json('context')->nullable();   // structured extra detail, secrets pre-redacted
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index(['project_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
