<?php

use App\Enums\ArticleNarrationStatus;
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
        Schema::create('article_narrations', function (Blueprint $table) {
            $table->id();
            $table->string('article_key');
            $table->string('locale', 5);
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default(ArticleNarrationStatus::Queued->value)->index();
            $table->string('source_hash', 64)->nullable();
            $table->longText('script')->nullable();
            $table->string('preparation_model')->nullable();
            $table->string('prompt_version', 50)->nullable();
            $table->json('preparation_notes')->nullable();
            $table->json('pronunciation_notes')->nullable();
            $table->json('samples')->nullable();
            $table->timestamp('preparation_started_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['article_key', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_narrations');
    }
};
