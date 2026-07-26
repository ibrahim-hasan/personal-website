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
        Schema::table('editorial_api_audit_logs', function (Blueprint $table) {
            $table->json('related_content_keys')->nullable()->after('article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('editorial_api_audit_logs', function (Blueprint $table) {
            $table->dropColumn('related_content_keys');
        });
    }
};
