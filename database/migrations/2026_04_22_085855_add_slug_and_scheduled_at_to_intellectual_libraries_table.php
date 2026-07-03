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
        Schema::table('intellectual_libraries', function (Blueprint $table) {
            $table->json('slug')->nullable()->after('name');
            $table->timestamp('scheduled_at')->nullable()->after('is_active');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intellectual_libraries', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['slug', 'scheduled_at']);
        });
    }
};
