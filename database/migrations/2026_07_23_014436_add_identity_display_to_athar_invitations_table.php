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
        Schema::table('athar_invitations', function (Blueprint $table) {
            $table->string('identity_display', 20)->default('anonymous')->after('placement_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('athar_invitations', function (Blueprint $table) {
            $table->dropColumn('identity_display');
        });
    }
};
