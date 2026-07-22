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
        Schema::create('athar_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('email_hash', 64)->index();
            $table->text('email');
            $table->text('recipient_name')->nullable();
            $table->string('relationship', 40);
            $table->string('preferred_locale', 5)->default('ar');
            $table->text('personal_reason')->nullable();
            $table->json('prompt_snapshot')->nullable();
            $table->string('placement', 40)->default('about');
            $table->string('placement_key')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athar_invitations');
    }
};
