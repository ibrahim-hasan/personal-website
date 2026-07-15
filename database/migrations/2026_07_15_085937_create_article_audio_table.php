<?php

use App\Enums\ArticleAudioStatus;
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
        Schema::create('article_audio', function (Blueprint $table) {
            $table->id();
            $table->string('article_key');
            $table->string('locale', 5);
            $table->string('status')->default(ArticleAudioStatus::Queued->value)->index();
            $table->string('disk')->default('public');
            $table->string('path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('character_count')->nullable();
            $table->unsignedSmallInteger('segment_count')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->string('voice_id')->nullable();
            $table->string('model_id')->nullable();
            $table->string('output_format', 50)->nullable();
            $table->json('voice_settings')->nullable();
            $table->json('request_ids')->nullable();
            $table->timestamp('generation_started_at')->nullable();
            $table->timestamp('generated_at')->nullable();
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
        Schema::dropIfExists('article_audio');
    }
};
