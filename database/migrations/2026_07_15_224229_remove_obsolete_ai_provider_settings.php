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
            ->whereIn('key', [
                'ai_seo_expert_enabled',
                'openai_api_key',
                'openai_base_url',
                'openai_custom_url',
                'openai_provider',
            ])
            ->delete();

        DB::table('settings')
            ->where('key', 'openai_model')
            ->where(function ($query): void {
                $query
                    ->whereNull('value')
                    ->orWhereNotIn('value', [
                        'gpt-4o-mini',
                        'gpt-4.1-mini',
                        'gpt-4.1',
                    ]);
            })
            ->update([
                'value' => 'gpt-4o-mini',
                'updated_at' => now(),
            ]);
    }

    /** Removed secrets and obsolete provider settings cannot be restored safely. */
    public function down(): void {}
};
