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
        Schema::create('athar_publication_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_id')->constrained('athar_contributions')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 40)->default('draft')->index();
            $table->string('origin', 40);
            $table->text('public_payload');
            $table->string('snapshot_hash', 64)->index();
            $table->string('placement', 40);
            $table->string('placement_key')->nullable();
            $table->string('identity_display', 40)->default('anonymous');
            $table->json('approved_locales')->nullable();
            $table->timestamp('sent_for_approval_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contribution_id', 'version']);
            $table->index(['status', 'placement', 'placement_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athar_publication_versions');
    }
};
