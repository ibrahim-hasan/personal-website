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
        Schema::create('athar_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->unique()->constrained('athar_invitations')->cascadeOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->text('draft_payload')->nullable();
            $table->text('sealed_payload')->nullable();
            $table->string('source_hash', 64)->nullable()->index();
            $table->string('response_mode', 40)->default('freeform');
            $table->boolean('requested_suggestion')->default(false);
            $table->timestamp('draft_updated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athar_contributions');
    }
};
