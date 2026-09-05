<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('input_url');
            $table->string('normalized_url');
            $table->string('hostname')->index();
            $table->enum('status', ['pending', 'active', 'blocked', 'retired'])->default('pending');
            $table->timestamps();

            $table->index(['project_id', 'hostname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
