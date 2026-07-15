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
        if (! Schema::hasColumn('article_audio', 'requested_by_user_id')) {
            Schema::table('article_audio', function (Blueprint $table) {
                $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('article_audio', 'queued_at')) {
            Schema::table('article_audio', function (Blueprint $table) {
                $table->timestamp('queued_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('article_audio', 'requested_by_user_id')) {
            Schema::table('article_audio', function (Blueprint $table) {
                $table->dropConstrainedForeignId('requested_by_user_id');
            });
        }

        if (Schema::hasColumn('article_audio', 'queued_at')) {
            Schema::table('article_audio', function (Blueprint $table) {
                $table->dropColumn('queued_at');
            });
        }
    }
};
