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
        Schema::create('athar_access_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained('athar_invitations')->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['invitation_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athar_access_challenges');
    }
};
