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
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->json('title');
            $table->json('sector');
            $table->json('summary');
            $table->json('challenge');
            $table->json('response');
            $table->json('outcome');
            $table->string('lens', 40)->index();
            $table->string('image');
            $table->json('image_alt');
            $table->string('logo')->nullable();
            $table->json('logo_alt')->nullable();
            $table->json('tags');
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
