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
        Schema::table('services', function (Blueprint $table): void {
            $table->json('fit_signals')->nullable();
            $table->json('engagement_note')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn([
                'fit_signals',
                'engagement_note',
                'seo_title',
                'seo_description',
            ]);
        });
    }
};
