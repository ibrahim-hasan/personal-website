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
        Schema::create('athar_publication_consent_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_id')->constrained('athar_contributions')->cascadeOnDelete();
            $table->foreignId('publication_version_id')->constrained('athar_publication_versions')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('snapshot_hash', 64)->index();
            $table->json('approved_locales')->nullable();
            $table->string('placement', 40);
            $table->string('placement_key')->nullable();
            $table->string('identity_display', 40)->default('anonymous');
            $table->string('privacy_notice_version', 80);
            $table->string('verification_method', 40)->default('email_code');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['publication_version_id', 'event_type', 'occurred_at'], 'athar_consent_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athar_publication_consent_events');
    }
};
