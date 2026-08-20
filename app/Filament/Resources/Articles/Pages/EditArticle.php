<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Actions\Editorial\PublishEditorialArticle;
use App\Actions\Editorial\SetEditorialArticlePublication;
use App\Actions\Editorial\UpdateEditorialArticle;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use App\Support\Editorial\ArticleBody;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    #[Locked]
    public int $expectedEditorialRevision = 1;

    /** @var list<string> */
    private const array FORM_ATTRIBUTES = [
        'title',
        'slug',
        'type',
        'summary',
        'body_ar',
        'body_en',
        'image_alt',
        'image_caption',
        'seo_title',
        'seo_description',
        'topic_keys',
        'source_url',
    ];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->expectedEditorialRevision = $this->getRecord()->editorial_revision;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $article = $this->getRecord();
        $articleBody = app(ArticleBody::class);

        foreach (['ar', 'en'] as $locale) {
            $data[Article::bodyAttribute($locale)] = $articleBody->editorDocumentForArticle($article, $locale);
        }

        return $data;
    }

    protected function beforeValidate(): void
    {
        $article = Article::query()
            ->lockForUpdate()
            ->findOrFail($this->getRecord()->getKey());

        if ($article->editorial_revision !== $this->expectedEditorialRevision) {
            throw $this->formArticleError(__('editorial_admin.feedback.stale_edit'));
        }

        if ($article->is_published) {
            throw $this->formArticleError(__('editorial_admin.feedback.published_locked'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, self::FORM_ATTRIBUTES);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        Gate::authorize('update', $record);

        try {
            $article = app(UpdateEditorialArticle::class)->handle(
                $record,
                $data,
                $this->expectedEditorialRevision,
                app()->getLocale(),
            );
        } catch (ValidationException $exception) {
            $this->rethrowArticleValidationException($exception);
        }

        $this->replaceRecord($article);
        $this->refreshFormData(['modified_at', 'read_minutes']);

        return $article;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label(__('editorial_admin.actions.publish'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Article $record): bool => ! $record->is_published && Gate::allows('publish', $record))
                ->disabled(fn (Article $record, ArticlePublicationValidator $publicationValidator): bool => ! $publicationValidator->isReadyToPublish($record))
                ->action(function (Article $record, PublishEditorialArticle $publishEditorialArticle): void {
                    Gate::authorize('publish', $record);
                    try {
                        $this->replaceRecord($publishEditorialArticle->handle(
                            $record,
                            $this->expectedEditorialRevision,
                            app()->getLocale(),
                        ));
                    } catch (ValidationException $exception) {
                        $this->rethrowArticleValidationException($exception);
                    }

                    $this->refreshFormData(['is_published', 'published_at', 'modified_at', 'editorial_revision']);
                }),
            Action::make('unpublish')
                ->label(__('editorial_admin.actions.unpublish'))
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Article $record): bool => $record->is_published && Gate::allows('publish', $record))
                ->action(function (Article $record, SetEditorialArticlePublication $setPublication): void {
                    Gate::authorize('publish', $record);
                    try {
                        $this->replaceRecord($setPublication->handle(
                            $record,
                            false,
                            $this->expectedEditorialRevision,
                            app()->getLocale(),
                        ));
                    } catch (ValidationException $exception) {
                        $this->rethrowArticleValidationException($exception);
                    }

                    $this->refreshFormData(['is_published', 'published_at', 'modified_at', 'editorial_revision']);
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function replaceRecord(Article $article): void
    {
        $this->record = $article;
        $this->expectedEditorialRevision = $article->editorial_revision;
    }

    private function formArticleError(string $message): ValidationException
    {
        return ValidationException::withMessages([
            'data.key' => [$message],
        ]);
    }

    private function rethrowArticleValidationException(ValidationException $exception): never
    {
        $errors = $exception->errors();

        if (! array_key_exists('article', $errors)) {
            throw $exception;
        }

        throw ValidationException::withMessages([
            'data.key' => $errors['article'],
        ]);
    }
}
