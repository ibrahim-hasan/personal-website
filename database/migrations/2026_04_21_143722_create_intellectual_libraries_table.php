<?php

use App\Enums\IntellectualLibraryType;
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
        Schema::create('intellectual_libraries', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('excert')->nullable();
            $table->json('content')->nullable();
            $table->enum('type', IntellectualLibraryType::cases())->default(IntellectualLibraryType::Article->value);
            $table->foreignId('author_id');
            $table->integer('reading_time')->nullable();
            $table->string('video_length')->max(20)->default('00:00')->nullable();
            $table->json('seo_title');
            $table->json('seo_description')->nullable();
            $table->integer('views')->default(0);
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_active')->default(true);
            $table->index('type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intellectual_libraries');
    }
};
