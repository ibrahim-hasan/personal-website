<?php

namespace App\Services\EditorialApi;

use App\Actions\Services\ServicePublicationValidator;
use App\Models\Article;
use App\Models\EditorialArticleRevisionSnapshot;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

final class EditorialArticleRelations
{
    private const int MAXIMUM_KEYS = 30;

    public function __construct(
        private readonly ServicePublicationValidator $servicePublicationValidator,
        private readonly ProjectCaseStudyPublicationValidator $projectCaseStudyPublicationValidator,
    ) {}

    /**
     * Validate only relation fields supplied by an API or MCP request.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function validate(array $attributes, ?Article $sourceArticle = null): void
    {
        $errors = [];

        $this->validateServices($attributes, $errors);
        $this->validateProjects($attributes, $errors, $sourceArticle);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Replace only relation sets supplied by the caller, preserving omitted sets.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function sync(Article $article, array $attributes): void
    {
        if (array_key_exists('service_keys', $attributes)) {
            $article->services()->sync($this->orderedIds(Service::class, $attributes['service_keys']));
            $article->unsetRelation('services');
        }

        if (array_key_exists('project_keys', $attributes)) {
            $article->projects()->sync($this->orderedIds(Project::class, $attributes['project_keys']));
            $article->unsetRelation('projects');
        }
    }

    /**
     * Persist a compact revision representation that can prove the exact stable
     * relationships without duplicating the article's editorial content.
     */
    public function captureRevisionSnapshot(Article $article, string $action): void
    {
        EditorialArticleRevisionSnapshot::query()->updateOrCreate(
            [
                'article_id' => $article->getKey(),
                'revision' => $article->editorial_revision,
            ],
            [
                'action' => $action,
                'service_keys' => $this->serviceKeys($article),
                'project_keys' => $this->projectKeys($article),
            ],
        );
    }

    /**
     * @return array{service_keys: list<string>, project_keys: list<string>}
     */
    public function auditRepresentation(Article $article): array
    {
        return [
            'service_keys' => $this->serviceKeys($article),
            'project_keys' => $this->projectKeys($article),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function withoutRelationKeys(array $attributes): array
    {
        return Arr::except($attributes, ['service_keys', 'project_keys']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, list<string>>  $errors
     */
    private function validateServices(array $attributes, array &$errors): void
    {
        if (! array_key_exists('service_keys', $attributes)) {
            return;
        }

        $keys = $this->normalizedKeys($attributes['service_keys'], 'service_keys', $errors);

        if ($keys === []) {
            return;
        }

        $services = Service::withTrashed()
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        foreach ($keys as $index => $key) {
            $service = $services->get($key);

            if (! $service instanceof Service) {
                $errors["service_keys.{$index}"][] = 'The selected service key is not available.';

                continue;
            }

            if (! $this->isAuthorized($service)) {
                $errors["service_keys.{$index}"][] = 'You are not authorized to relate this service.';

                continue;
            }

            if (! $this->servicePublicationValidator->isPublishable($service)) {
                $errors["service_keys.{$index}"][] = 'The selected service is not publicly available.';
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, list<string>>  $errors
     */
    private function validateProjects(array $attributes, array &$errors, ?Article $sourceArticle): void
    {
        if (! array_key_exists('project_keys', $attributes)) {
            return;
        }

        $keys = $this->normalizedKeys($attributes['project_keys'], 'project_keys', $errors);

        if ($keys === []) {
            return;
        }

        $projects = Project::withTrashed()
            ->with(['evidence', 'services', 'articles'])
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        foreach ($keys as $index => $key) {
            $project = $projects->get($key);

            if (! $project instanceof Project) {
                $errors["project_keys.{$index}"][] = 'The selected project key is not available.';

                continue;
            }

            if (! $this->isAuthorized($project)) {
                $errors["project_keys.{$index}"][] = 'You are not authorized to relate this project.';

                continue;
            }

            if (! $this->projectCaseStudyPublicationValidator->isEligibleForArticleRelation($project, $sourceArticle)) {
                $errors["project_keys.{$index}"][] = 'The selected project is not publicly available.';
            }
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return list<string>
     */
    private function normalizedKeys(mixed $value, string $field, array &$errors): array
    {
        if (! is_array($value)) {
            return [];
        }

        if (! array_is_list($value)) {
            $errors[$field][] = 'Related content keys must be supplied as an ordered list.';

            return [];
        }

        if (count($value) > self::MAXIMUM_KEYS) {
            $errors[$field][] = 'No more than thirty related content keys may be supplied.';
        }

        $keys = [];

        foreach ($value as $index => $key) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $key = trim($key);

            if (in_array($key, $keys, true)) {
                $errors["{$field}.{$index}"][] = 'Duplicate related content keys are not allowed.';

                continue;
            }

            $keys[] = $key;
        }

        return $keys;
    }

    /** @param class-string<Service|Project> $model */
    private function orderedIds(string $model, mixed $keys): array
    {
        $normalizedKeys = is_array($keys)
            ? collect($keys)
                ->filter(fn (mixed $key): bool => is_string($key) && trim($key) !== '')
                ->map(fn (string $key): string => trim($key))
                ->values()
                ->all()
            : [];

        if ($normalizedKeys === []) {
            return [];
        }

        $records = $model::query()
            ->whereIn('key', $normalizedKeys)
            ->get(['id', 'key'])
            ->keyBy('key');

        return collect($normalizedKeys)
            ->mapWithKeys(fn (string $key, int $sortOrder): array => [
                $records->get($key)->getKey() => ['sort_order' => $sortOrder],
            ])
            ->all();
    }

    /** @return list<string> */
    private function serviceKeys(Article $article): array
    {
        return $article->relatedServiceKeys();
    }

    /** @return list<string> */
    private function projectKeys(Article $article): array
    {
        return $article->relatedProjectKeys();
    }

    private function isAuthorized(Service|Project $record): bool
    {
        $user = request()->user('api');

        if (! $user instanceof User) {
            return true;
        }

        try {
            return Gate::forUser($user)->allows('view', $record);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
