<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string ENGLISH_FALLBACK = 'My path began in engineering and grew through software development, product building, delivery leadership, and company founding. I draw on that connected experience to bring the business decision and the technical work together—and take responsibility for making it real.';

    private const string ARABIC_FALLBACK = 'بدأ مساري في الهندسة، ثم امتد إلى تطوير البرمجيات وبناء المنتجات وقيادة التنفيذ وتأسيس الشركات. وأعمل اليوم من هذه الخبرة المترابطة: ربط القرار التجاري بالعمل التقني، وتحمل مسؤولية تحويله إلى واقع.';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')
            || ! Schema::hasColumn('settings', 'group')
            || ! Schema::hasColumn('settings', 'key')) {
            return;
        }

        $valueColumn = Schema::hasColumn('settings', 'value')
            ? 'value'
            : (Schema::hasColumn('settings', 'val') ? 'val' : null);

        if ($valueColumn === null) {
            return;
        }

        $query = DB::table('settings')
            ->where('group', 'website_content')
            ->where('key', 'about_biography');

        if (Schema::hasColumn('settings', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $setting = $query->first();

        if ($setting === null) {
            return;
        }

        $stored = $setting->{$valueColumn} ?? null;

        if (! is_string($stored) || trim($stored) === '') {
            return;
        }

        $normalized = $this->normalizedValue($stored);

        if ($normalized === null || $normalized === $stored) {
            return;
        }

        $attributes = [$valueColumn => $normalized];

        if (Schema::hasColumn('settings', 'updated_at')) {
            $attributes['updated_at'] = now();
        }

        DB::table('settings')
            ->where('id', $setting->id)
            ->update($attributes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('The stale Code Moments founder claim normalization is irreversible.');
    }

    private function normalizedValue(string $stored): ?string
    {
        $decoded = json_decode($stored, true);

        if (is_array($decoded)) {
            $changed = false;

            foreach (['ar', 'en'] as $locale) {
                $biography = $decoded[$locale] ?? null;

                if (! is_string($biography) || ! $this->directlyClaimsCodeMomentsFounderRole($biography)) {
                    continue;
                }

                $decoded[$locale] = $locale === 'ar'
                    ? self::ARABIC_FALLBACK
                    : self::ENGLISH_FALLBACK;
                $changed = true;
            }

            return $changed
                ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                : $stored;
        }

        if (! $this->directlyClaimsCodeMomentsFounderRole($stored)) {
            return $stored;
        }

        return preg_match('/\p{Arabic}/u', $stored) === 1
            ? self::ARABIC_FALLBACK
            : self::ENGLISH_FALLBACK;
    }

    private function directlyClaimsCodeMomentsFounderRole(string $biography): bool
    {
        $normalized = html_entity_decode(strip_tags($biography), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}&-]+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);

        if ($normalized === '') {
            return false;
        }

        $englishTarget = '(?:the\s+)?(?:company\s+)?code\s+moments';
        $englishFounder = '(?:co[- ]?founder|founder)';
        $englishExecutive = '(?:chief\s+executive\s+officer|ceo)';

        if (preg_match(
            '/\b'.$englishFounder.'(?:\s*(?:&|and)\s*'.$englishExecutive.')?\s*(?:of|at)?\s+'.$englishTarget.'\b/iu',
            $normalized,
        ) === 1
            || preg_match(
                '/\b(?:i|ibrahim(?:\s+hasan)?)\s+(?:co[- ]?founded|founded)\s+(?:the\s+)?(?:company\s+)?code\s+moments\b/iu',
                $normalized,
            ) === 1) {
            return true;
        }

        $arabicTarget = '(?:ل|ب)?(?:شركة\s+)?(?:كود\s+مومنتس|code\s+moments)';
        $arabicFounder = '(?:(?:ال)?شريك\s+(?:ال)?مؤسس|(?:ال)?مؤسس(?:\s+(?:ال)?مشارك)?)';
        $arabicExecutive = '(?:(?:و)?(?:ال)?رئيس\s+(?:ال)?تنفيذي|(?:و)?(?:ال)?مدير\s+(?:ال)?تنفيذي)';
        $arabicCompanyPrefix = '(?:(?:في\s+)?(?:شركة\s+)?)';

        return preg_match(
            '/(?:^|\s)'.$arabicFounder.'(?:\s+'.$arabicExecutive.')?\s+'.$arabicCompanyPrefix.$arabicTarget.'(?:\s|$)/iu',
            $normalized,
        ) === 1
            || preg_match(
                '/(?:^|\s)(?:اسست|أسست|اسس|أسس|شاركت\s+في\s+تاسيس|شاركت\s+في\s+تأسيس)\s+(?:شركة\s+)?'.$arabicTarget.'(?:\s|$)/iu',
                $normalized,
            ) === 1;
    }
};
