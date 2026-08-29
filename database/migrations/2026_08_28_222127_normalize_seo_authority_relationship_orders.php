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
            || ! Schema::hasTable('project_service')
            || ! Schema::hasTable('article_project')) {
            return;
        }

        $serviceArticles = [
            'transformation' => [
                'digital-transformation-roadmap-process-to-impact',
                'transformation-before-software',
                'measure-digital-impact',
            ],
            'ai-adoption' => [
                'ai-use-case-register-governance-template',
                'ai-adoption-roadmap-saudi-companies-2026',
                'first-ai-use-case',
                'ai-value',
            ],
            'data-governance' => [
                'data-governance-before-ai',
                'data-readiness',
                'ai-governance',
            ],
            'systems' => [
                'workflow-audit-what-should-be-automated',
                'automation-assistant-agent',
                'ai-not-answer',
            ],
        ];
        $serviceProjects = [
            'transformation' => ['wafaa', 'bosalty'],
            'ai-adoption' => ['digi-pedia', 'maazim'],
            'data-governance' => ['rafid-360', 'digi-pedia'],
            'systems' => ['2060-investments', 'wafaa'],
        ];
        $articleProjects = [
            'transformation-before-software' => ['wafaa', 'bosalty'],
            'measure-digital-impact' => ['wafaa'],
            'first-ai-use-case' => ['digi-pedia', 'maazim'],
            'ai-value' => ['digi-pedia'],
            'data-readiness' => ['rafid-360', 'digi-pedia'],
            'ai-governance' => ['rafid-360'],
            'automation-assistant-agent' => ['2060-investments', 'wafaa'],
            'ai-not-answer' => ['2060-investments'],
            'ai-adoption-roadmap-saudi-companies-2026' => ['digi-pedia'],
            'ai-use-case-register-governance-template' => ['digi-pedia'],
            'data-governance-before-ai' => ['rafid-360'],
            'digital-transformation-roadmap-process-to-impact' => ['wafaa', 'bosalty'],
            'workflow-audit-what-should-be-automated' => ['2060-investments', 'wafaa'],
        ];
        $now = now();

        DB::transaction(function () use ($articleProjects, $now, $serviceArticles, $serviceProjects): void {
            $serviceIds = DB::table('services')->pluck('id', 'key');
            $articleIds = DB::table('articles')->pluck('id', 'key');
            $projectIds = DB::table('projects')->pluck('id', 'key');

            foreach ($serviceArticles as $serviceKey => $articleKeys) {
                $serviceId = $serviceIds->get($serviceKey);

                if ($serviceId === null) {
                    continue;
                }

                foreach ($articleKeys as $sortOrder => $articleKey) {
                    $articleId = $articleIds->get($articleKey);

                    if ($articleId === null) {
                        continue;
                    }

                    $relationship = [
                        'article_id' => $articleId,
                        'service_id' => $serviceId,
                    ];

                    DB::table('article_service')->insertOrIgnore([
                        ...$relationship,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('article_service')->where($relationship)->update([
                        'sort_order' => $sortOrder,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($serviceProjects as $serviceKey => $projectKeys) {
                $serviceId = $serviceIds->get($serviceKey);

                if ($serviceId === null) {
                    continue;
                }

                foreach ($projectKeys as $sortOrder => $projectKey) {
                    $projectId = $projectIds->get($projectKey);

                    if ($projectId === null) {
                        continue;
                    }

                    $relationship = [
                        'project_id' => $projectId,
                        'service_id' => $serviceId,
                    ];

                    DB::table('project_service')->insertOrIgnore([
                        ...$relationship,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('project_service')->where($relationship)->update([
                        'sort_order' => $sortOrder,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($articleProjects as $articleKey => $projectKeys) {
                $articleId = $articleIds->get($articleKey);

                if ($articleId === null) {
                    continue;
                }

                foreach ($projectKeys as $sortOrder => $projectKey) {
                    $projectId = $projectIds->get($projectKey);

                    if ($projectId === null) {
                        continue;
                    }

                    $relationship = [
                        'article_id' => $articleId,
                        'project_id' => $projectId,
                    ];

                    DB::table('article_project')->insertOrIgnore([
                        ...$relationship,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('article_project')->where($relationship)->update([
                        'sort_order' => $sortOrder,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('SEO authority relationship order normalization is intentionally irreversible. Use a forward migration for later editorial ordering changes.');
    }
};
