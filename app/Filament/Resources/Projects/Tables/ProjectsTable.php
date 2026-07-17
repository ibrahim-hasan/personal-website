<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use App\Support\AdminTableEmptyState;
use App\Support\PortfolioAtlas;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTableEmptyState::apply($table, 'projects', 'heroicon-o-briefcase')
            ->columns([
                SpatieMediaLibraryImageColumn::make(Project::IMAGE_COLLECTION)
                    ->label(__('admin.fields.project_image'))
                    ->collection(Project::IMAGE_COLLECTION)
                    ->conversion(Project::THUMBNAIL_CONVERSION)
                    ->visibility('public')
                    ->square()
                    ->size(52),
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->getStateUsing(fn (Project $record): ?string => localized_model_attribute($record, 'title'))
                    ->description(fn (Project $record): string => $record->key)
                    ->searchable(['title'])
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->getStateUsing(fn (Project $record): ?string => localized_model_attribute($record, 'slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sector')
                    ->label(__('admin.fields.sector'))
                    ->getStateUsing(fn (Project $record): ?string => localized_model_attribute($record, 'sector'))
                    ->toggleable(),
                TextColumn::make('lens')
                    ->label(__('admin.fields.lens'))
                    ->badge(),
                IconColumn::make('featured')
                    ->label(__('admin.fields.featured'))
                    ->boolean(),
                ToggleColumn::make('is_active')
                    ->label(__('admin.fields.active'))
                    ->disabled(fn (Project $record): bool => Gate::denies('update', $record)),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lens')
                    ->label(__('admin.fields.lens'))
                    ->options(fn (): array => collect(PortfolioAtlas::lenses())->pluck('label', 'id')->all()),
                TrashedFilter::make(),
            ])
            ->reorderable('sort_order')
            ->authorizeReorder(fn (): bool => auth()->user()?->can('update projects') === true)
            ->defaultSort('sort_order')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
