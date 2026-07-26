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
        Schema::table('contact_inquiries', function (Blueprint $table): void {
            $table->string('public_reference', 15)->nullable()->unique();
            $table->string('role', 160)->nullable();
            $table->string('timing', 500)->nullable();
            $table->string('submission_hash', 64)->nullable()->unique();
            $table->string('company', 160)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_inquiries', function (Blueprint $table): void {
            $table->dropUnique(['public_reference']);
            $table->dropUnique(['submission_hash']);
            $table->dropColumn(['public_reference', 'role', 'timing', 'submission_hash']);
            $table->string('company', 120)->nullable()->change();
        });
    }
};
