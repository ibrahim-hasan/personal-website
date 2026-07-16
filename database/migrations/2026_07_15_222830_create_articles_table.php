<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('slugs');
            $table->json('title');
            $table->json('summary');
            $table->json('seo_title');
            $table->json('seo_description');
            $table->json('type');
            $table->json('lead');
            $table->json('sections');
            $table->json('closing');
            $table->date('published_at')->index();
            $table->date('modified_at');
            $table->string('image');
            $table->json('read_minutes');
            $table->json('topic_keys');
            $table->boolean('featured')->default(false)->index();
            $table->text('source_url')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
