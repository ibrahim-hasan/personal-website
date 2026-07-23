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
        Schema::table('athar_contributions', function (Blueprint $table) {
            if (Schema::hasColumn('athar_contributions', 'response_mode')) {
                $table->dropColumn('response_mode');
            }
            if (Schema::hasColumn('athar_contributions', 'requested_suggestion')) {
                $table->dropColumn('requested_suggestion');
            }
        });

        Schema::table('athar_invitations', function (Blueprint $table) {
            if (Schema::hasColumn('athar_invitations', 'prompt_snapshot')) {
                $table->dropColumn('prompt_snapshot');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('athar_contributions', function (Blueprint $table) {
            $table->string('response_mode', 40)->default('freeform')->after('source_hash');
            $table->boolean('requested_suggestion')->default(false)->after('response_mode');
        });

        Schema::table('athar_invitations', function (Blueprint $table) {
            $table->json('prompt_snapshot')->nullable()->after('personal_reason');
        });
    }
};
