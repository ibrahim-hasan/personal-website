<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_downloaders', function (Blueprint $table): void {
            $table->string('download_token')->nullable()->after('is_mail_sent');
            $table->timestamp('token_expires_at')->nullable()->after('download_token');
            $table->timestamp('token_used_at')->nullable()->after('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('guide_downloaders', function (Blueprint $table): void {
            $table->dropColumn(['download_token', 'token_expires_at', 'token_used_at']);
        });
    }
};
