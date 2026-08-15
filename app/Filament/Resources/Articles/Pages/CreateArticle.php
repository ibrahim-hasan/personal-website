<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Actions\Editorial\CreateEditorialArticle;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /** @var list<string> */
    private const array FORM_ATTRIBUTES = [
        'key',
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
        'featured',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Arr::only($data, self::FORM_ATTRIBUTES);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        Gate::authorize('create', Article::class);

        return app(CreateEditorialArticle::class)->handle($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('edit', ['record' => $this->getRecord()]);
    }
}
