<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('editorial_api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id');
            $table->string('idempotency_key', 255);
            $table->string('method', 10);
            $table->string('path', 255);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['client_id', 'idempotency_key', 'method', 'path'], 'editorial_api_idempotency_unique');
            $table->index(['client_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_api_idempotency_keys');
    }
};
