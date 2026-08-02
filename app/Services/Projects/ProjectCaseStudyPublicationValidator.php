<?php

namespace App\Services\Projects;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Actions\Services\ServicePublicationValidator;
use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceKind;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectEvidenceState;
use App\Enums\ProjectPermissionStatus;
use App\Models\Article;
use App\Models\Project;
use App\Models\ProjectEvidence;
use App\Models\Service;
use Illuminate\Support\Collection;

final class ProjectCaseStudyPublicationValidator
{
    public function __construct(
        private readonly ServicePublicationValidator $servicePublicationValidator,
        private readonly ArticlePublicationValidator $articlePublicationValidator,
    ) {}

    /** @var list<string> */
    private const LOCALES = ['ar', 'en'];

    /** @var list<string> */
    private const REQUIRED_TRANSLATED_FIELDS = [
        'slug',
        'title',
        'sector',
        'summary',
        'challenge',
        'response',
        'outcome',
        'ibrahim_role',
        'delivery_period',
        'confidentiality_note',
    ];

    /** @var list<string> */
    private const REQUIRED_STRING_SECTIONS = [
        'executive_summary',
        'context',
        'solution',
        'implementation',
        'adoption',
    ];

    /** @var list<string> */
    private const REQUIRED_LIST_SECTIONS = [
        'constraints',
        'lessons',
    ];

    /** @var list<string> */
    private const CHANGE_AREAS = [
        'workflow',
        'ownership',
        'data',
        'system',
        'controls',
    ];

    /** @var list<string> */
    private const EVIDENCE_DIRECTIONS = [
        'increase',
        'decrease',
        'maintain',
    ];

    public function validate(Project $project): ProjectCaseStudyPublicationEligibility
    {
        return $this->validateForPublicRelation($project);
    }

    /**
     * Validate a Project for direct public placement without traversing back
     * through its Services' own Project and Article relations.
     *
     * Directly selected Services and Articles are still validated. This keeps
     * the public relation gate strict while making the Service–Project graph
     * finite and deterministic.
     */
    public function validateForPublicRelation(Project $project): ProjectCaseStudyPublicationEligibility
    {
        return $this->validateForArticleRelation($project, null);
    }

    /**
     * Validate a Project selected from a draft Article without letting that
     * Article invalidate the Project while its own relation is updated.
     */
    public function validateForArticleRelation(Project $project, ?Article $sourceArticle): ProjectCaseStudyPublicationEligibility
    {
        $violations = [];
        $this->validateProjectState($project, $violations);
        $this->validateTranslations($project, $violations);
        $this->validateCaseStudySections($project, $violations);
        $this->validatePermissionAndDisclosure($project, $violations);
        $this->validateDirectRelations($project, $violations, $sourceArticle);

        $mayRenderImage = $this->validateAsset($project, 'image', $violations);
        $mayRenderLogo = $this->validateAsset($project, 'logo', $violations);

        $evidence = $this->evidenceFor($project);
        $publicEvidence = $evidence
            ->filter(fn (ProjectEvidence $item): bool => $this->isPublicApproved($item))
            ->values();

        $this->validateEvidence($project, $evidence, $publicEvidence, $violations);

        return new ProjectCaseStudyPublicationEligibility(
            array_values(array_unique($violations)),
            $publicEvidence,
            $mayRenderImage,
            $mayRenderLogo,
        );
    }

    public function isEligibleForPublicRelation(Project $project): bool
    {
        return $this->validateForPublicRelation($project)->isEligible();
    }

    public function isEligibleForArticleRelation(Project $project, ?Article $sourceArticle): bool
    {
        return $this->validateForArticleRelation($project, $sourceArticle)->isEligible();
    }

    /** @param list<string> $violations */
    private function validateProjectState(Project $project, array &$violations): void
    {
        if (! $project->is_detailed_case_study) {
            $violations[] = 'project.not_detailed';
        }

        if (! $project->is_active) {
            $violations[] = 'project.inactive';
        }

        if ($project->trashed()) {
            $violations[] = 'project.deleted';
        }

        if ($project->case_study_reviewed_at === null) {
            $violations[] = 'project.review_missing';
        }

        if ($project->delivery_entity === null) {
            $violations[] = 'project.delivery_entity_missing';
        }

        if ($project->evidence_level === null) {
            $violations[] = 'project.evidence_level_missing';
        }
    }

    /** @param list<string> $violations */
    private function validateTranslations(Project $project, array &$violations): void
    {
        foreach (self::LOCALES as $locale) {
            foreach (self::REQUIRED_TRANSLATED_FIELDS as $field) {
                if (! $this->hasTranslatedText($project, $field, $locale)) {
                    $violations[] = "translation.{$locale}.{$field}.missing";
                }
            }
        }
    }

    /** @param list<string> $violations */
    private function validateCaseStudySections(Project $project, array &$violations): void
    {
        foreach (self::LOCALES as $locale) {
            $sections = $project->getTranslationWithoutFallback('case_study_sections', $locale);

            if (! is_array($sections)) {
                $violations[] = "sections.{$locale}.missing";

                continue;
            }

            foreach (self::REQUIRED_STRING_SECTIONS as $section) {
                if (! $this->hasText($sections[$section] ?? null)) {
                    $violations[] = "sections.{$locale}.{$section}.missing";
                }
            }

            foreach (self::REQUIRED_LIST_SECTIONS as $section) {
                if (! $this->hasNonEmptyStringList($sections[$section] ?? null)) {
                    $violations[] = "sections.{$locale}.{$section}.missing";
                }
            }

            $changes = $sections['changes'] ?? null;

            if (! is_array($changes) || ! array_is_list($changes) || $changes === []) {
                $violations[] = "sections.{$locale}.changes.missing";

                continue;
            }

            foreach ($changes as $index => $change) {
                if (! is_array($change)) {
                    $violations[] = "sections.{$locale}.changes.{$index}.invalid";

                    continue;
                }

                if (! in_array($change['area'] ?? null, self::CHANGE_AREAS, true)) {
                    $violations[] = "sections.{$locale}.changes.{$index}.area_invalid";
                }

                if (! $this->hasText($change['body'] ?? null)) {
                    $violations[] = "sections.{$locale}.changes.{$index}.body_missing";
                }
            }
        }
    }

    /** @param list<string> $violations */
    private function validatePermissionAndDisclosure(Project $project, array &$violations): void
    {
        if (! $this->hasText($project->permission_reference)) {
            $violations[] = 'project.permission_reference_missing';
        }

        if ($project->disclosure_level === null) {
            $violations[] = 'project.disclosure_missing';
        }

        match ($project->permission_status) {
            ProjectPermissionStatus::ApprovedAnonymized => $this->validateAnonymizedPermission($project, $violations),
            ProjectPermissionStatus::ApprovedNamed => null,
            default => $violations[] = 'project.permission_not_approved',
        };
    }

    /** @param list<string> $violations */
    private function validateAnonymizedPermission(Project $project, array &$violations): void
    {
        if ($project->disclosure_level !== ProjectDisclosureLevel::Anonymized) {
            $violations[] = 'project.disclosure_incompatible';
        }
    }

    /** @param list<string> $violations */
    private function validateDirectRelations(Project $project, array &$violations, ?Article $sourceArticle = null): void
    {
        $services = $project->relationLoaded('services')
            ? $project->getRelation('services')
            : $project->services()->get();

        $services
            ->filter(fn (mixed $service): bool => $service instanceof Service)
            ->each(function (Service $service) use (&$violations): void {
                if (! $this->servicePublicationValidator->isPublishable($service)) {
                    $violations[] = "relation.service.{$service->key}.not_public";
                }
            });

        $articles = $project->relationLoaded('articles')
            ? $project->getRelation('articles')
            : $project->articles()->get();

        $articles
            ->filter(fn (mixed $article): bool => $article instanceof Article)
            ->reject(fn (Article $article): bool => $sourceArticle !== null && $article->is($sourceArticle))
            ->each(function (Article $article) use (&$violations): void {
                if (! $this->articlePublicationValidator->isPubliclyEligible($article)) {
                    $violations[] = "relation.article.{$article->key}.not_public";
                }
            });
    }

    /** @param list<string> $violations */
    private function validateAsset(Project $project, string $asset, array &$violations): bool
    {
        if (! $this->hasAsset($project, $asset)) {
            return false;
        }

        $statusAttribute = "{$asset}_permission_status";
        $referenceAttribute = "{$asset}_permission_reference";
        $status = $project->{$statusAttribute};

        if ($status !== ProjectAssetPermissionStatus::Approved) {
            return false;
        }

        if (! $this->hasText($project->{$referenceAttribute})) {
            $violations[] = "{$asset}.permission_reference_missing";

            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, ProjectEvidence>  $evidence
     * @param  Collection<int, ProjectEvidence>  $publicEvidence
     * @param  list<string>  $violations
     */
    private function validateEvidence(Project $project, Collection $evidence, Collection $publicEvidence, array &$violations): void
    {
        $evidence
            ->filter(fn (ProjectEvidence $item): bool => $item->is_public && ! $this->isPublicApproved($item))
            ->each(function (ProjectEvidence $item) use (&$violations): void {
                $violations[] = "evidence.{$item->getKey()}.public_not_approved";
            });

        $publicEvidence->each(function (ProjectEvidence $item) use (&$violations): void {
            $this->validatePublicEvidence($item, $violations);
        });

        match ($project->evidence_level) {
            ProjectEvidenceLevel::Qualitative => $this->validateQualitativeEvidenceLevel($publicEvidence, $violations),
            ProjectEvidenceLevel::Documented => $this->validateDocumentedEvidenceLevel($publicEvidence, $violations),
            ProjectEvidenceLevel::VerifiedQuantitative => $this->validateVerifiedQuantitativeEvidenceLevel($publicEvidence, $violations),
            default => null,
        };
    }

    /** @param list<string> $violations */
    private function validatePublicEvidence(ProjectEvidence $evidence, array &$violations): void
    {
        $prefix = "evidence.{$evidence->getKey()}";

        foreach (self::LOCALES as $locale) {
            if (! $this->hasTranslatedText($evidence, 'label', $locale)) {
                $violations[] = "{$prefix}.translation.{$locale}.label_missing";
            }

            if (! $this->hasTranslatedText($evidence, 'result_text', $locale)) {
                $violations[] = "{$prefix}.translation.{$locale}.result_text_missing";
            }
        }

        if ($evidence->kind === ProjectEvidenceKind::Qualitative) {
            if ($this->hasQuantitativeFields($evidence)) {
                $violations[] = "{$prefix}.qualitative_contains_measurement";
            }

            foreach (self::LOCALES as $locale) {
                foreach (['label', 'result_text'] as $field) {
                    $value = $evidence->getTranslationWithoutFallback($field, $locale);

                    if (is_string($value) && preg_match('/[0-9٠-٩]/u', $value) === 1) {
                        $violations[] = "{$prefix}.qualitative_contains_number";
                    }
                }
            }

            return;
        }

        $this->validateQuantitativeEvidence($evidence, $violations);
    }

    /** @param list<string> $violations */
    private function validateQuantitativeEvidence(ProjectEvidence $evidence, array &$violations): void
    {
        $prefix = "evidence.{$evidence->getKey()}";

        if (! $this->hasText($evidence->unit)) {
            $violations[] = "{$prefix}.unit_missing";
        }

        if ($evidence->direction !== null && ! in_array($evidence->direction, self::EVIDENCE_DIRECTIONS, true)) {
            $violations[] = "{$prefix}.direction_invalid";
        }

        if (! $this->hasTranslatedText($evidence, 'result_period', 'ar') || ! $this->hasTranslatedText($evidence, 'result_period', 'en')) {
            $violations[] = "{$prefix}.result_period_missing";
        }

        if (! $this->hasTranslatedText($evidence, 'method', 'ar') || ! $this->hasTranslatedText($evidence, 'method', 'en')) {
            $violations[] = "{$prefix}.method_missing";
        }

        if (! $this->hasTranslatedText($evidence, 'scope', 'ar') || ! $this->hasTranslatedText($evidence, 'scope', 'en')) {
            $violations[] = "{$prefix}.scope_missing";
        }

        if (! $this->hasText($evidence->source_owner) || ! $this->hasText($evidence->source_reference)) {
            $violations[] = "{$prefix}.source_missing";
        }

        if (! $this->hasText($evidence->permission_reference)) {
            $violations[] = "{$prefix}.permission_reference_missing";
        }

        if ($evidence->verified_by === null || $evidence->verified_at === null) {
            $violations[] = "{$prefix}.verification_missing";
        }

        if ($evidence->approved_by === null || $evidence->approved_at === null) {
            $violations[] = "{$prefix}.approval_missing";
        }

        match ($evidence->kind) {
            ProjectEvidenceKind::Exact => $this->validateExactEvidence($evidence, $violations),
            ProjectEvidenceKind::Range => $this->validateRangeEvidence($evidence, $violations),
            ProjectEvidenceKind::Threshold => $this->validateThresholdEvidence($evidence, $violations),
            default => null,
        };
    }

    /** @param list<string> $violations */
    private function validateExactEvidence(ProjectEvidence $evidence, array &$violations): void
    {
        if ($evidence->baseline_value === null && $evidence->result_value === null) {
            $violations[] = "evidence.{$evidence->getKey()}.exact_value_missing";
        }

        if ($evidence->baseline_value !== null && (! $this->hasTranslatedText($evidence, 'baseline_period', 'ar') || ! $this->hasTranslatedText($evidence, 'baseline_period', 'en'))) {
            $violations[] = "evidence.{$evidence->getKey()}.baseline_period_missing";
        }
    }

    /** @param list<string> $violations */
    private function validateRangeEvidence(ProjectEvidence $evidence, array &$violations): void
    {
        if ($evidence->range_min === null || $evidence->range_max === null) {
            $violations[] = "evidence.{$evidence->getKey()}.range_missing";

            return;
        }

        if ((float) $evidence->range_min > (float) $evidence->range_max) {
            $violations[] = "evidence.{$evidence->getKey()}.range_invalid";
        }
    }

    /** @param list<string> $violations */
    private function validateThresholdEvidence(ProjectEvidence $evidence, array &$violations): void
    {
        if ($evidence->threshold_value === null) {
            $violations[] = "evidence.{$evidence->getKey()}.threshold_missing";
        }
    }

    /**
     * @param  Collection<int, ProjectEvidence>  $publicEvidence
     * @param  list<string>  $violations
     */
    private function validateQualitativeEvidenceLevel(Collection $publicEvidence, array &$violations): void
    {
        if ($publicEvidence->contains(fn (ProjectEvidence $item): bool => $item->kind !== ProjectEvidenceKind::Qualitative)) {
            $violations[] = 'evidence.level.qualitative_requires_qualitative_only';
        }
    }

    /**
     * @param  Collection<int, ProjectEvidence>  $publicEvidence
     * @param  list<string>  $violations
     */
    private function validateDocumentedEvidenceLevel(Collection $publicEvidence, array &$violations): void
    {
        if ($publicEvidence->isEmpty()) {
            $violations[] = 'evidence.level.documented_requires_public_evidence';

            return;
        }

        $hasDocumentedEvidence = $publicEvidence->contains(function (ProjectEvidence $item): bool {
            return $this->hasText($item->source_owner)
                && $this->hasText($item->source_reference)
                && $this->hasText($item->permission_reference);
        });

        if (! $hasDocumentedEvidence) {
            $violations[] = 'evidence.level.documented_requires_source_and_permission';
        }
    }

    /**
     * @param  Collection<int, ProjectEvidence>  $publicEvidence
     * @param  list<string>  $violations
     */
    private function validateVerifiedQuantitativeEvidenceLevel(Collection $publicEvidence, array &$violations): void
    {
        $hasQuantitativeEvidence = $publicEvidence->contains(
            fn (ProjectEvidence $item): bool => $item->kind !== ProjectEvidenceKind::Qualitative,
        );

        if (! $hasQuantitativeEvidence) {
            $violations[] = 'evidence.level.verified_quantitative_requires_quantitative_evidence';
        }
    }

    /** @return Collection<int, ProjectEvidence> */
    private function evidenceFor(Project $project): Collection
    {
        if ($project->relationLoaded('evidence')) {
            /** @var Collection<int, ProjectEvidence> $evidence */
            $evidence = $project->getRelation('evidence');

            return $evidence;
        }

        /** @var Collection<int, ProjectEvidence> $evidence */
        $evidence = $project->evidence()->get();

        return $evidence;
    }

    private function isPublicApproved(ProjectEvidence $evidence): bool
    {
        return $evidence->state === ProjectEvidenceState::Approved && $evidence->is_public;
    }

    private function hasAsset(Project $project, string $asset): bool
    {
        $collection = $asset === 'image' ? Project::IMAGE_COLLECTION : Project::LOGO_COLLECTION;

        return $project->hasMedia($collection) || $this->hasText($project->getRawOriginal($asset));
    }

    private function hasTranslatedText(Project|ProjectEvidence $model, string $field, string $locale): bool
    {
        return $this->hasText($model->getTranslationWithoutFallback($field, $locale));
    }

    private function hasNonEmptyStringList(mixed $value): bool
    {
        return is_array($value)
            && $value !== []
            && array_is_list($value)
            && collect($value)->every(fn (mixed $item): bool => $this->hasText($item));
    }

    private function hasQuantitativeFields(ProjectEvidence $evidence): bool
    {
        return $evidence->baseline_value !== null
            || $evidence->result_value !== null
            || $evidence->range_min !== null
            || $evidence->range_max !== null
            || $evidence->threshold_value !== null
            || $this->hasText($evidence->unit)
            || $this->hasText($evidence->direction);
    }

    private function hasText(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
