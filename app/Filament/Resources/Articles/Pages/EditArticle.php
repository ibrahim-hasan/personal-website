<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Actions\Editorial\PublishEditorialArticle;
use App\Actions\Editorial\SetEditorialArticlePublication;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Article $record): bool => ! $record->is_published && Gate::allows('publish', $record))
                ->action(function (Article $record, PublishEditorialArticle $publishEditorialArticle): void {
                    Gate::authorize('publish', $record);
                    $publishEditorialArticle->handle($record);
                    $this->refreshFormData(['is_published', 'published_at', 'editorial_revision']);
                }),
            Action::make('unpublish')
                ->label('Unpublish')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Article $record): bool => $record->is_published && Gate::allows('publish', $record))
                ->action(function (Article $record, SetEditorialArticlePublication $setPublication): void {
                    Gate::authorize('publish', $record);
                    $setPublication->handle($record, false);
                    $this->refreshFormData(['is_published', 'published_at', 'editorial_revision']);
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
