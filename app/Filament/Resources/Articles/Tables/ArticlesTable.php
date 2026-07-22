<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Models\Article;
use App\Support\AdminTableEmptyState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return AdminTableEmptyState::apply($table, 'articles', 'heroicon-o-document-text')
            ->columns([
                SpatieMediaLibraryImageColumn::make(Article::IMAGE_COLLECTION)
                    ->label(__('editorial_admin.fields.image_path'))
                    ->collection(Article::IMAGE_COLLECTION)
                    ->conversion(Article::THUMBNAIL_CONVERSION)
                    ->visibility('public')
                    ->square()
                    ->size(52),
                TextColumn::make('title')
                    ->label(__('editorial_admin.fields.title'))
                    ->getStateUsing(fn (Article $record): string => localized_model_attribute($record, 'title') ?? $record->key)
                    ->description(fn (Article $record): string => $record->key)
                    ->searchable(query: fn ($query, string $search) => $query->where('key', 'like', "%{$search}%"))
                    ->wrap(),
                TextColumn::make('slug')
                    ->label(__('editorial_admin.fields.slug'))
                    ->getStateUsing(fn (Article $record): ?string => localized_model_attribute($record, 'slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label(__('editorial_admin.fields.published_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('appreciations_count')
                    ->label(__('editorial_admin.fields.appreciations'))
                    ->counts('appreciations')
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->label(__('editorial_admin.fields.comments'))
                    ->counts('comments')
                    ->sortable(),
                IconColumn::make('featured')
                    ->label(__('editorial_admin.fields.featured'))
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label(__('editorial_admin.fields.published'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('editorial_admin.fields.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published')->label(__('editorial_admin.fields.published')),
                TernaryFilter::make('featured')->label(__('editorial_admin.fields.featured')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
