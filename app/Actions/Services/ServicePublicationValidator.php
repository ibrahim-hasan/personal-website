<?php

namespace App\Actions\Services;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use Illuminate\Validation\ValidationException;

final class ServicePublicationValidator
{
    public function __construct(
        private readonly ServiceIntrinsicPublicationValidator $intrinsicValidator,
        private readonly ProjectCaseStudyPublicationValidator $projectPublicationValidator,
        private readonly ArticlePublicationValidator $articlePublicationValidator,
    ) {}

    /** @return array<string, list<string>> */
    public function violations(Service $service, bool $requirePublicState = true): array
    {
        $violations = $this->intrinsicValidator->violations($service, $requirePublicState);

        if ($requirePublicState) {
            $this->validateRelatedProjects($service, $violations);
            $this->validateRelatedArticles($service, $violations);
        }

        return $violations;
    }

    public function isPublishable(Service $service): bool
    {
        return $this->violations($service) === [];
    }

    /**
     * Validate a Service selected from a draft Article without letting that
     * Article make the Service ineligible for its own relation update.
     *
     * All other related Projects and Articles remain subject to the ordinary
     * public-relation gate.
     *
     * @return array<string, list<string>>
     */
    public function violationsForArticleRelation(Service $service, ?Article $sourceArticle): array
    {
        $violations = $this->intrinsicValidator->violations($service);

        $this->validateRelatedProjects($service, $violations, $sourceArticle);
        $this->validateRelatedArticles($service, $violations, $sourceArticle);

        return $violations;
    }

    public function isEligibleForArticleRelation(Service $service, ?Article $sourceArticle): bool
    {
        return $this->violationsForArticleRelation($service, $sourceArticle) === [];
    }

    /**
     * The non-recursive Service eligibility path used when a Project validates
     * a directly selected Service relation.
     */
    public function isIntrinsicallyPublishable(Service $service): bool
    {
        return $this->intrinsicValidator->isPublishable($service);
    }

    public function hasCompleteContent(Service $service): bool
    {
        return $this->intrinsicValidator->hasCompleteContent($service);
    }

    public function assertPublishable(Service $service): void
    {
        $violations = $this->violations($service);

        if ($violations !== []) {
            throw ValidationException::withMessages($this->localizedMessages($violations));
        }
    }

    /**
     * @param  array<string, list<string>>  $violations
     */
    private function validateRelatedProjects(Service $service, array &$violations, ?Article $sourceArticle = null): void
    {
        $projects = $service->relationLoaded('projects')
            ? $service->getRelation('projects')
            : $service->projects()
                ->with(['evidence', 'services', 'articles'])
                ->get();

        $projects
            ->filter(fn (mixed $project): bool => $project instanceof Project)
            ->each(function (Project $project) use (&$violations, $sourceArticle): void {
                if (! $this->projectPublicationValidator->isEligibleForArticleRelation($project, $sourceArticle)) {
                    $violations["relation.project.{$project->key}"][] = 'The selected Project is not publicly publishable.';

                    return;
                }

                if ($project->isAnonymizedForPublic()) {
                    $violations["relation.project.{$project->key}"][] = 'An anonymized Project cannot be exposed through a Service relation.';
                }
            });
    }

    /**
     * @param  array<string, list<string>>  $violations
     */
    private function validateRelatedArticles(Service $service, array &$violations, ?Article $sourceArticle = null): void
    {
        $articles = $service->relationLoaded('articles')
            ? $service->getRelation('articles')
            : $service->articles()->get();

        $articles
            ->filter(fn (mixed $article): bool => $article instanceof Article)
            ->reject(fn (Article $article): bool => $sourceArticle !== null && $article->is($sourceArticle))
            ->each(function (Article $article) use (&$violations): void {
                if (! $this->articlePublicationValidator->isPubliclyEligible($article)) {
                    $violations["relation.article.{$article->key}"][] = 'The selected Article is not publicly publishable.';
                }
            });
    }

    /**
     * @param  array<string, list<string>>  $violations
     * @return array<string, list<string>>
     */
    private function localizedMessages(array $violations): array
    {
        return collect($violations)
            ->map(function (array $messages, string $field): array {
                return array_values(array_unique(array_map(
                    fn (string $message): string => $this->localizedMessage($field, $message),
                    $messages,
                )));
            })
            ->all();
    }

    private function localizedMessage(string $field, string $message): string
    {
        [$attribute, $locale] = array_pad(explode('.', $field, 2), 2, null);
        $replacements = [
            'field' => __('service_admin.fields.'.$attribute),
            'locale' => is_string($locale) ? __('service_admin.locales.'.$locale) : '',
        ];

        return match (true) {
            str_starts_with($field, 'relation.project.') => __('service_admin.publication.related_project'),
            str_starts_with($field, 'relation.article.') => __('service_admin.publication.related_article'),
            $field === 'status' && str_contains($message, 'deleted') => __('service_admin.publication.deleted'),
            $field === 'status' => __('service_admin.publication.status'),
            $field === 'key' => __('service_admin.publication.key'),
            $attribute === 'slug' && str_contains($message, 'unique') => __('service_admin.publication.slug_unique', $replacements),
            $attribute === 'slug' => __('service_admin.publication.slug_required', $replacements),
            str_contains($message, 'placeholder or prohibited') => __('service_admin.publication.prohibited_content', $replacements),
            $attribute === 'fit_signals' => __('service_admin.publication.fit_signals', $replacements),
            $attribute === 'deliverables' => __('service_admin.publication.deliverables', $replacements),
            default => __('service_admin.publication.required_field', $replacements),
        };
    }
}
