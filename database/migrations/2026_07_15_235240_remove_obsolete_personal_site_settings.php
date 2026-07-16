<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where(function ($query): void {
                $query
                    ->where('group', 'seo')
                    ->whereIn('key', ['default_seo_title', 'default_seo_description']);
            })
            ->orWhere(function ($query): void {
                $query
                    ->where('group', 'contact')
                    ->where('key', 'strategic_consultation_url');
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These settings had no public consumer, so there is no meaningful value to recreate.
    }
};
