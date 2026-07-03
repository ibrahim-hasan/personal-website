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
            $table
                ->foreign('author_id')
                ->references('id')
                ->on('authors')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intellectual_libraries', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });
    }
};
