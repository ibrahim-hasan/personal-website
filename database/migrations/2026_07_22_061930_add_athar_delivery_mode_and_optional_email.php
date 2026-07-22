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
        Schema::table('athar_invitations', function (Blueprint $table): void {
            $table->string('delivery_mode', 20)->default('email')->index()->after('email');
            $table->text('email')->nullable()->change();
            $table->string('email_hash', 64)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('athar_invitations', function (Blueprint $table): void {
            $table->dropColumn('delivery_mode');
            $table->text('email')->nullable(false)->change();
            $table->string('email_hash', 64)->nullable(false)->change();
        });
    }
};
