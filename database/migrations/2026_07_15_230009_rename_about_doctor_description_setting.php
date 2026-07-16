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

        $legacySetting = DB::table('settings')
            ->where('group', 'website_content')
            ->where('key', 'about_doctor_description')
            ->first();

        if ($legacySetting === null) {
            return;
        }

        $currentSetting = DB::table('settings')
            ->where('group', 'website_content')
            ->where('key', 'about_biography')
            ->first();

        if ($currentSetting !== null && $currentSetting->deleted_at === null) {
            DB::table('settings')->where('id', $legacySetting->id)->delete();

            return;
        }

        if ($currentSetting !== null) {
            DB::table('settings')->where('id', $currentSetting->id)->delete();
        }

        DB::table('settings')
            ->where('id', $legacySetting->id)
            ->update([
                'key' => 'about_biography',
                'label' => 'About Ibrahim Hasan',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $currentSetting = DB::table('settings')
            ->where('group', 'website_content')
            ->where('key', 'about_biography')
            ->first();

        if ($currentSetting === null) {
            return;
        }

        $legacySetting = DB::table('settings')
            ->where('group', 'website_content')
            ->where('key', 'about_doctor_description')
            ->first();

        if ($legacySetting !== null && $legacySetting->deleted_at === null) {
            DB::table('settings')->where('id', $currentSetting->id)->delete();

            return;
        }

        if ($legacySetting !== null) {
            DB::table('settings')->where('id', $legacySetting->id)->delete();
        }

        DB::table('settings')
            ->where('id', $currentSetting->id)
            ->update([
                'key' => 'about_doctor_description',
                'label' => 'About Ibrahim Hasan',
                'updated_at' => now(),
            ]);
    }
};
