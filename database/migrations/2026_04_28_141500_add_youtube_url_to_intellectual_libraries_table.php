<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intellectual_libraries', function (Blueprint $table): void {
            $table->string('youtube_url')->nullable()->after('video_length');
        });
    }

    public function down(): void
    {
        Schema::table('intellectual_libraries', function (Blueprint $table): void {
            $table->dropColumn('youtube_url');
        });
    }
};
