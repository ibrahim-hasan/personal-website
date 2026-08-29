<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeoServiceRelationshipSeeder extends Seeder
{
    public function run(): void
    {
        $articleRelationships = [
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
            'ai-adoption-roadmap-saudi-companies-2026' => ['digi-pedia'],
            'ai-use-case-register-governance-template' => ['digi-pedia'],
            'data-governance-before-ai' => ['rafid-360'],
            'digital-transformation-roadmap-process-to-impact' => ['wafaa', 'bosalty'],
            'workflow-audit-what-should-be-automated' => ['2060-investments', 'wafaa'],
        ];
        $now = now();

        DB::transaction(function () use ($articleProjectRelationships, $articleRelationships, $projectRelationships, $now): void {
            $serviceIds = DB::table('services')->pluck('id', 'key');
            $articleIds = DB::table('articles')->pluck('id', 'key');
            $projectIds = DB::table('projects')->pluck('id', 'key');

            foreach ($articleRelationships as $serviceKey => $articleKeys) {
                foreach ($articleKeys as $sortOrder => $articleKey) {
                    $serviceId = $serviceIds->get($serviceKey);
                    $articleId = $articleIds->get($articleKey);

                    if ($serviceId === null || $articleId === null) {
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
                }
            }

            foreach ($projectRelationships as $serviceKey => $projectKeys) {
                foreach ($projectKeys as $sortOrder => $projectKey) {
                    $serviceId = $serviceIds->get($serviceKey);
                    $projectId = $projectIds->get($projectKey);

                    if ($serviceId === null || $projectId === null) {
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
                }
            }

            foreach ($articleProjectRelationships as $articleKey => $projectKeys) {
                foreach ($projectKeys as $sortOrder => $projectKey) {
                    $articleId = $articleIds->get($articleKey);
                    $projectId = $projectIds->get($projectKey);

                    if ($articleId === null || $projectId === null) {
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
                }
            }
        });
    }
}
