<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use App\Support\AdminTableEmptyState;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return AdminTableEmptyState::apply($table, 'services', 'heroicon-o-squares-plus')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->getStateUsing(fn (Service $record): ?string => localized_model_attribute($record, 'name'))
                    ->description(fn (Service $record): string => $record->key)
                    ->searchable(),
                TextColumn::make('publication_status')
                    ->label(__('admin.fields.status'))
                    ->getStateUsing(fn (Service $record): string => self::publicationLabel($record))
                    ->badge()
                    ->color(fn (Service $record): string => $record->is_draft ? 'gray' : ($record->is_active ? 'success' : 'warning')),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('admin.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('order')
            ->authorizeReorder(fn (): bool => auth()->user()?->can('update services') === true)
            ->defaultSort('order', 'asc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
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

    private static function publicationLabel(Service $service): string
    {
        if ($service->is_draft) {
            return __('service_admin.status.draft');
        }

        return $service->is_active
            ? __('service_admin.status.published')
            : __('service_admin.status.unpublished');
    }
}
