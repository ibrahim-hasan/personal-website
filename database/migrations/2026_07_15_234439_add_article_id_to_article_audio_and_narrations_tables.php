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
        Schema::table('article_audio', function (Blueprint $table): void {
            $table->foreignId('article_id')
                ->nullable()
                ->after('id')
                ->constrained('articles')
                ->cascadeOnDelete();
        });

        Schema::table('article_narrations', function (Blueprint $table): void {
            $table->foreignId('article_id')
                ->nullable()
                ->after('id')
                ->constrained('articles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_narrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('article_id');
        });

        Schema::table('article_audio', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('article_id');
        });
    }
};
