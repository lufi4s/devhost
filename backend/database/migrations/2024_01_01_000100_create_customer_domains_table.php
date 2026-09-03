<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // the registered domain e.g. customer.com or shop.customer.com
            $table->string('verification_token')->nullable();
            $table->boolean('verified')->default(false);
            $table->boolean('primary')->default(false);
            $table->boolean('nameserver_managed')->default(false);
            $table->string('nameserver_1')->nullable();
            $table->string('nameserver_2')->nullable();
            $table->string('ssl_status')->default('none'); // none | issuing | active | failed | renewal_due
            $table->timestamp('ssl_issued_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'name']);
            $table->index(['customer_id', 'verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_domains');
    }
};
