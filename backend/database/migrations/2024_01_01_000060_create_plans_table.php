<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('currency')->default('USD');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly | quarterly | annually
            $table->unsignedBigInteger('storage_limit')->default(0); // MB
            $table->unsignedBigInteger('memory_limit')->default(0); // MB
            $table->unsignedInteger('cpu_limit')->default(0); // cores
            $table->unsignedBigInteger('bandwidth_limit')->default(0); // GB
            $table->unsignedInteger('websites_limit')->default(0);
            $table->unsignedInteger('databases_limit')->default(0);
            $table->unsignedInteger('mailboxes_limit')->default(0);
            $table->unsignedBigInteger('email_storage')->default(0); // MB per mailbox
            $table->boolean('node_enabled')->default(false);
            $table->boolean('laravel_enabled')->default(false);
            $table->boolean('wordpress_enabled')->default(false);
            $table->boolean('php_enabled')->default(false);
            $table->boolean('static_enabled')->default(false);
            $table->boolean('backup_enabled')->default(false);
            $table->boolean('sftp_enabled')->default(false);
            $table->boolean('redis_enabled')->default(false);
            $table->boolean('ssl_auto')->default(true);
            $table->unsignedInteger('websites_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
