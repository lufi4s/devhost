<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // wordpress / laravel / static / node
            $table->string('status')->default('provisioning');
            $table->string('runtime')->nullable();
            $table->string('runtime_version')->nullable();
            $table->string('subdomain');
            $table->string('domain');
            $table->string('hostname')->unique();
            $table->string('git_repository')->nullable();
            $table->string('git_branch')->nullable()->default('main');
            $table->unsignedInteger('storage_limit')->default(20480); // MB
            $table->string('memory_limit')->default('2048m');
            $table->unsignedInteger('cpu_limit')->default(2);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
