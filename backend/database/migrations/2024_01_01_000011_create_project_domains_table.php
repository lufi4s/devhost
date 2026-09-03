<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('hostname');
            $table->string('type')->default('subdomain'); // subdomain / custom
            $table->string('ssl_status')->default('none'); // none / issuing / active
            $table->timestamps();

            $table->index(['project_id', 'hostname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_domains');
    }
};
