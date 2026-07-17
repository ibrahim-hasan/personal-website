<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceArabicProblem(
            from: 'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودود لا يكفي لقرارات موثوقة.',
            to: 'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودة لا تكفي لاتخاذ قرارات موثوقة.',
        );
    }

    public function down(): void
    {
        $this->replaceArabicProblem(
            from: 'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودة لا تكفي لاتخاذ قرارات موثوقة.',
            to: 'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودود لا يكفي لقرارات موثوقة.',
        );
    }

    private function replaceArabicProblem(string $from, string $to): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'problem')) {
            return;
        }

        $problem = DB::table('services')
            ->where('key', 'data-governance')
            ->value('problem');

        if (! is_string($problem)) {
            return;
        }

        $translations = json_decode($problem, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($translations) || ($translations['ar'] ?? null) !== $from) {
            return;
        }

        $translations['ar'] = $to;

        DB::table('services')
            ->where('key', 'data-governance')
            ->update([
                'problem' => json_encode($translations, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
    }
};
