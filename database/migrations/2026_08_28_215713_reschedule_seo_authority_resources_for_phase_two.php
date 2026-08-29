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
        $this->applySchedule([
            'ai-use-case-register-governance-template' => [
                'from' => '2026-09-05',
                'to' => '2026-10-14',
            ],
            'data-governance-before-ai' => [
                'from' => '2026-09-12',
                'to' => '2026-10-21',
            ],
            'digital-transformation-roadmap-process-to-impact' => [
                'from' => '2026-09-19',
                'to' => '2026-10-28',
            ],
            'workflow-audit-what-should-be-automated' => [
                'from' => '2026-09-26',
                'to' => '2026-11-04',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('The SEO authority resource schedule migration is intentionally irreversible. Use a forward migration to change publication dates.');
    }

    /** @param  array<string, array{from: string, to: string}>  $schedule */
    private function applySchedule(array $schedule): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        DB::transaction(function () use ($schedule): void {
            foreach ($schedule as $key => $publishedAt) {
                DB::table('articles')
                    ->where('key', $key)
                    ->where('is_published', false)
                    ->whereDate('published_at', $publishedAt['from'])
                    ->update([
                        'published_at' => $publishedAt['to'],
                        'updated_at' => now(),
                    ]);
            }
        });
    }
};
