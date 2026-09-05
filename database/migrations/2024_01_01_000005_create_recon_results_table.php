<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recon_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();

            // Section groups the payload for report rendering:
            // overview | network | dns | tls | hosting | tech_stack | http | endpoints | headers | historical
            $table->string('section')->index();
            $table->string('key');           // e.g. "mx_record", "server_header", "cms"
            $table->text('value');
            $table->enum('confidence', ['low', 'medium', 'high', 'certain'])->default('medium');
            $table->string('source')->nullable(); // e.g. "dns lookup", "http header", "cert transparency"
            $table->timestamps();

            $table->index(['scan_run_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recon_results');
    }
};
