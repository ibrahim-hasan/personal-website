<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_downloaders', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->foreignId('guide_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['email', 'guide_id']);
        });
    }

    public function down(): void
    {
        Schema::table('guide_downloaders', function (Blueprint $table) {
            $table->dropUnique(['email', 'guide_id']);
            $table->dropConstrainedForeignId('guide_id');
            $table->unique('email');
        });
    }
};
