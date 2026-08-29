<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string PAYLOAD_FINGERPRINT = '3ba8030546c4f830f47bc7513a63cf7fedcf3b31caf6c6b3a3423bda974db4c5';

    /** @var list<string> */
    private const array JSON_UPDATE_FIELDS = [
        'title',
        'summary',
        'seo_title',
        'seo_description',
        'type',
        'body',
        'image_alt',
        'image_caption',
        'read_minutes',
        'topic_keys',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('articles') || ! Schema::hasColumn('articles', 'body')) {
            return;
        }

        $records = require __DIR__.'/data/seo_authority_editorial_content_v1.php';

        $this->assertPayloadIsFrozen($records);

        if (! DB::table('articles')->exists()) {
            return;
        }

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                $existing = $this->findExisting($record);

                if ($existing === null) {
                    if (($record['operation'] ?? null) !== 'create') {
                        throw new RuntimeException(
                            "Required source article [{$record['key']}] is missing from the SEO authority update.",
                        );
                    }

                    $this->insert($record);

                    continue;
                }

                $updates = $this->updates($record);

                if ($this->isAlreadyApplied($existing, $updates)) {
                    continue;
                }

                if (Schema::hasColumn('articles', 'editorial_revision')) {
                    $updates['editorial_revision'] = ((int) $existing->editorial_revision) + 1;
                }

                $updates['updated_at'] = now();

                DB::table('articles')
                    ->where('id', $existing->id)
                    ->update($updates);
            }
        });
    }

    public function down(): void
    {
        throw new LogicException(
            'The SEO authority editorial release is intentionally irreversible. Future copy changes require a new forward migration.',
        );
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function assertPayloadIsFrozen(array $records): void
    {
        $expectedKeys = [
            'ai-governance',
            'first-ai-use-case',
            'data-readiness',
            'ai-adoption-roadmap-saudi-companies-2026',
            'ai-use-case-register-governance-template',
            'data-governance-before-ai',
            'digital-transformation-roadmap-process-to-impact',
            'workflow-audit-what-should-be-automated',
        ];
        $actualKeys = array_column($records, 'key');

        if ($actualKeys !== $expectedKeys
            || ! hash_equals(self::PAYLOAD_FINGERPRINT, hash('sha256', $this->json($records)))) {
            throw new RuntimeException(
                'The frozen SEO authority editorial payload changed. Create a new forward migration instead.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function findExisting(array $record): ?object
    {
        $columns = [
            'id',
            'key',
            ...self::JSON_UPDATE_FIELDS,
            'modified_at',
        ];

        if (Schema::hasColumn('articles', 'editorial_revision')) {
            $columns[] = 'editorial_revision';
        }
        $keyMatch = DB::table('articles')
            ->where('key', $record['key'])
            ->first($columns);
        $arabicSlugMatch = DB::table('articles')
            ->where('slug_ar', $record['slug']['ar'])
            ->first(['id', 'key']);
        $englishSlugMatch = DB::table('articles')
            ->where('slug_en', $record['slug']['en'])
            ->first(['id', 'key']);

        foreach ([$arabicSlugMatch, $englishSlugMatch] as $slugMatch) {
            if ($slugMatch !== null && ($keyMatch === null || $keyMatch->id !== $slugMatch->id)) {
                throw new RuntimeException("Article identity collision for [{$record['key']}].");
            }
        }

        return $keyMatch;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function insert(array $record): void
    {
        $timestamp = now();
        $values = [
            'key' => $record['key'],
            'slug' => $this->json($record['slug']),
            'slug_ar' => $record['slug']['ar'],
            'slug_en' => $record['slug']['en'],
            'title' => $this->json($record['title']),
            'summary' => $this->json($record['summary']),
            'seo_title' => $this->json($record['seo_title']),
            'seo_description' => $this->json($record['seo_description']),
            'type' => $this->json($record['type']),
            'lead' => null,
            'sections' => null,
            'closing' => null,
            'body' => $this->json($record['body']),
            'published_at' => $record['published_at'],
            'modified_at' => $record['modified_at'],
            'image' => $record['image'],
            'image_alt' => $this->json($record['image_alt']),
            'image_caption' => $this->json($record['image_caption']),
            'read_minutes' => $this->json($record['read_minutes']),
            'topic_keys' => $this->json($record['topic_keys']),
            'featured' => $record['featured'],
            'source_url' => $record['source_url'],
            'is_published' => $record['is_published'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        if (Schema::hasColumn('articles', 'editorial_revision')) {
            $values['editorial_revision'] = 1;
        }

        DB::table('articles')->insert($values);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function updates(array $record): array
    {
        $updates = [];

        foreach (self::JSON_UPDATE_FIELDS as $field) {
            $updates[$field] = $this->json($record[$field]);
        }

        $updates['modified_at'] = $record['modified_at'];

        return $updates;
    }

    /** @param array<string, mixed> $updates */
    private function isAlreadyApplied(object $existing, array $updates): bool
    {
        foreach (self::JSON_UPDATE_FIELDS as $field) {
            $stored = json_decode((string) $existing->{$field}, true, flags: JSON_THROW_ON_ERROR);
            $expected = json_decode((string) $updates[$field], true, flags: JSON_THROW_ON_ERROR);

            if ($stored != $expected) {
                return false;
            }
        }

        return substr((string) $existing->modified_at, 0, 10) === (string) $updates['modified_at'];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
};
