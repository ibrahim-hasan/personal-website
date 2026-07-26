<?php

use App\Support\Editorial\ArticleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These hashes freeze the exact bilingual rewrite payload shipped with this
     * migration. Future editorial changes must use a new forward migration.
     *
     * @var array<string, string>
     */
    private const array PAYLOAD_HASHES = [
        'ai-product-moat' => '1f83074226759a263dda322f8fe57ba5a4d857120c8599d8d0a4909515056c60',
        'ai-value' => 'c0d9eea7812e0f342be601e7df8820b9e413ef64b95921bc5d31938104b0d2ca',
        'ai-not-answer' => 'e8e634768790fff81e97c27d0e9507c1dca36de0dd2be939f0edc16698029ed7',
        'transformation-before-software' => 'c0e6e655f11b41ab708fbd641078b65203593ee3205783d69d168c1eb001a85d',
        'data-readiness' => '3df9f4c9ccf217b746d3ad83ff6b5a018cfbaa42fa14dbb0149ddf507649afd1',
        'human-in-loop' => '7d9d07bc7aaa7b7658cab5866fcf7e24e1ebb6a3759546402dbc77c6f77a8650',
        'first-ai-use-case' => 'ea253c973246775b53c045246b97df16cc1b2627b5bda0c6119e7010ebfe7c56',
        'automation-assistant-agent' => '6a26f943547b7ab8aa7c3b251b5b7e93a14511267ca822290ad944082af1d814',
        'measure-digital-impact' => '05197401272437362d84c5f3c7d4d7083773f0a1b983b2026c3d032c20720e9e',
        'ai-governance' => '3f55208a2f5ac6d7d65622d6bbc29ad58f1eef159676d20da8050162df0e5ad7',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('articles') || ! Schema::hasColumn('articles', 'body')) {
            return;
        }

        $records = ArticleCatalog::bootstrapRecords();
        $this->assertPayloadIsFrozen($records);

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                $existing = $this->findExisting($record);

                if ($existing === null) {
                    continue;
                }

                $payload = $this->payload($record);

                if ($this->isAlreadyRewritten($existing, $payload)) {
                    continue;
                }

                $updates = array_map($this->json(...), $payload);
                $updates['modified_at'] = '2026-07-25';
                $updates['updated_at'] = now();

                if (Schema::hasColumn('articles', 'editorial_revision')) {
                    $updates['editorial_revision'] = ((int) $existing->editorial_revision) + 1;
                }

                DB::table('articles')
                    ->where('id', $existing->id)
                    ->update($updates);
            }
        });
    }

    public function down(): void
    {
        throw new LogicException(
            'The article readability rewrite is intentionally irreversible because the previous editorial copy was not stored in the database migration.',
        );
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, array<string, mixed>>
     */
    private function payload(array $record): array
    {
        return [
            'title' => $record['title'],
            'summary' => $record['summary'],
            'seo_title' => $record['seo_title'],
            'seo_description' => $record['seo_description'],
            'type' => $record['type'],
            'body' => $record['body'],
            'image_alt' => $record['image_alt'],
            'image_caption' => $record['image_caption'],
            'read_minutes' => $record['read_minutes'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function assertPayloadIsFrozen(array $records): void
    {
        if (count($records) !== count(self::PAYLOAD_HASHES)) {
            throw new RuntimeException('The article readability rewrite payload no longer contains the expected ten articles.');
        }

        foreach ($records as $record) {
            $expectedHash = self::PAYLOAD_HASHES[$record['key']] ?? null;
            $actualHash = hash('sha256', $this->json($this->payload($record)));

            if ($expectedHash === null || ! hash_equals($expectedHash, $actualHash)) {
                throw new RuntimeException(
                    "The frozen article readability rewrite payload changed for [{$record['key']}]. Create a new forward migration instead.",
                );
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $payload
     */
    private function isAlreadyRewritten(object $existing, array $payload): bool
    {
        foreach ($payload as $field => $translations) {
            $stored = json_decode((string) $existing->{$field}, true, flags: JSON_THROW_ON_ERROR);

            if ($stored != $translations) {
                return false;
            }
        }

        return true;
    }

    /**
     * Match the live database record without ever choosing arbitrarily between
     * two conflicting stable identities.
     *
     * @param  array<string, mixed>  $record
     */
    private function findExisting(array $record): ?object
    {
        $columns = [
            'id',
            'editorial_revision',
            'title',
            'summary',
            'seo_title',
            'seo_description',
            'type',
            'body',
            'image_alt',
            'image_caption',
            'read_minutes',
        ];
        $keyMatch = DB::table('articles')
            ->where('key', $record['key'])
            ->first($columns);
        $slugMatch = Schema::hasColumn('articles', 'slug_en')
            ? DB::table('articles')->where('slug_en', $record['slug']['en'])->first($columns)
            : null;

        if ($keyMatch !== null && $slugMatch !== null && $keyMatch->id !== $slugMatch->id) {
            throw new RuntimeException("Article identity collision for [{$record['key']}].");
        }

        return $slugMatch ?? $keyMatch;
    }
};
