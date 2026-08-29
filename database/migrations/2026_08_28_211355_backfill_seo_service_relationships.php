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
        if (! Schema::hasTable('services')
            || ! Schema::hasTable('articles')
            || ! Schema::hasTable('projects')
            || ! Schema::hasTable('article_service')
            || ! Schema::hasTable('article_project')
            || ! Schema::hasTable('project_service')) {
            return;
        }

        $articleRelationships = [
            'transformation' => ['transformation-before-software', 'measure-digital-impact'],
            'ai-adoption' => ['first-ai-use-case', 'ai-value'],
            'data-governance' => ['data-readiness', 'ai-governance'],
            'systems' => ['automation-assistant-agent', 'ai-not-answer'],
        ];
        $projectRelationships = [
            'transformation' => ['wafaa', 'bosalty'],
            'ai-adoption' => ['digi-pedia', 'maazim'],
            'data-governance' => ['rafid-360', 'digi-pedia'],
            'systems' => ['2060-investments', 'wafaa'],
        ];
        $articleProjectRelationships = [
            'transformation-before-software' => ['wafaa', 'bosalty'],
            'measure-digital-impact' => ['wafaa'],
            'first-ai-use-case' => ['digi-pedia', 'maazim'],
            'ai-value' => ['digi-pedia'],
            'data-readiness' => ['rafid-360', 'digi-pedia'],
            'ai-governance' => ['rafid-360'],
            'automation-assistant-agent' => ['2060-investments', 'wafaa'],
            'ai-not-answer' => ['2060-investments'],
        ];
        $now = now();

        DB::transaction(function () use ($articleProjectRelationships, $articleRelationships, $projectRelationships, $now): void {
            $serviceIds = DB::table('services')->pluck('id', 'key');
            $articleIds = DB::table('articles')->pluck('id', 'key');
            $projectIds = DB::table('projects')->pluck('id', 'key');

            foreach ($articleRelationships as $serviceKey => $articleKeys) {
                $serviceId = $serviceIds->get($serviceKey);

                if ($serviceId === null) {
                    continue;
                }

                foreach ($articleKeys as $sortOrder => $articleKey) {
                    $articleId = $articleIds->get($articleKey);

                    if ($articleId === null) {
                        continue;
                    }

                    DB::table('article_service')->insertOrIgnore([
                        'article_id' => $articleId,
                        'service_id' => $serviceId,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($projectRelationships as $serviceKey => $projectKeys) {
                $serviceId = $serviceIds->get($serviceKey);

                if ($serviceId === null) {
                    continue;
                }

                foreach ($projectKeys as $sortOrder => $projectKey) {
                    $projectId = $projectIds->get($projectKey);

                    if ($projectId === null) {
                        continue;
                    }

                    DB::table('project_service')->insertOrIgnore([
                        'project_id' => $projectId,
                        'service_id' => $serviceId,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($articleProjectRelationships as $articleKey => $projectKeys) {
                $articleId = $articleIds->get($articleKey);

                if ($articleId === null) {
                    continue;
                }

                foreach ($projectKeys as $sortOrder => $projectKey) {
                    $projectId = $projectIds->get($projectKey);

                    if ($projectId === null) {
                        continue;
                    }

                    DB::table('article_project')->insertOrIgnore([
                        'article_id' => $articleId,
                        'project_id' => $projectId,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
