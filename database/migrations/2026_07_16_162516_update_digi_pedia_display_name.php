<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateProjectTranslations(
            title: ['ar' => 'الموسوعة الرقمية', 'en' => 'Digi Pedia'],
            imageAlt: ['ar' => 'واجهة الموسوعة الرقمية لتعلّم الذكاء الاصطناعي بالعربية', 'en' => 'Digi Pedia Arabic AI learning platform interface'],
            logoAlt: ['ar' => 'شعار الموسوعة الرقمية', 'en' => 'Digi Pedia logo'],
        );
    }

    public function down(): void
    {
        $this->updateProjectTranslations(
            title: ['ar' => 'ديجي بيديا', 'en' => 'Digi-Pedia'],
            imageAlt: ['ar' => 'تجربة ديجي بيديا لتعلّم الذكاء الاصطناعي بالعربية', 'en' => 'Digi-Pedia Arabic AI learning experience'],
            logoAlt: ['ar' => 'شعار ديجي بيديا', 'en' => 'Digi-Pedia logo'],
        );
    }

    /**
     * @param  array{ar: string, en: string}  $title
     * @param  array{ar: string, en: string}  $imageAlt
     * @param  array{ar: string, en: string}  $logoAlt
     */
    private function updateProjectTranslations(array $title, array $imageAlt, array $logoAlt): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'key')) {
            return;
        }

        DB::table('projects')
            ->where('key', 'digi-pedia')
            ->update([
                'title' => json_encode($title, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'image_alt' => json_encode($imageAlt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'logo_alt' => json_encode($logoAlt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
    }
};
