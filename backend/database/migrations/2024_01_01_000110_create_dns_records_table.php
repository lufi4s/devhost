<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_domain_id')->constrained('customer_domains')->cascadeOnDelete();
            $table->string('name'); // record name e.g. www, api, @
            $table->string('type'); // A | AAAA | CNAME | MX | TXT | CAA
            $table->text('value');
            $table->unsignedInteger('ttl')->default(3600);
            $table->unsignedSmallInteger('priority')->nullable(); // for MX
            $table->timestamps();

            $table->index(['customer_domain_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
