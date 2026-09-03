<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('number');
            $table->string('status')->default('pending'); // pending / running / success / failed
            $table->string('command')->default('deploy');
            $table->string('commit')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->text('logs')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
