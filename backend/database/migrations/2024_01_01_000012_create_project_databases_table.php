<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('engine')->default('mariadb'); // mariadb / mysql / postgresql
            $table->string('user');
            $table->text('password'); // encrypted at rest
            $table->unsignedInteger('port')->default(3306);
            $table->timestamps();

            $table->index(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_databases');
    }
};
