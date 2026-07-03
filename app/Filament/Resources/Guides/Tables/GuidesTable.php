<?php

namespace App\Filament\Resources\Guides\Tables;

use App\Models\Guide;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class GuidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover_image')
                    ->label(__('admin.fields.cover_image'))
                    ->collection('cover_image')
                    ->circular(false),
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->sortable(),
                ToggleColumn::make('is_draft')
                    ->label(__('admin.fields.draft'))
                    ->disabled(fn (Guide $record): bool => ! Gate::allows('update', $record))
                    ->afterStateUpdated(function (bool $state): void {
                        Notification::make()
                            ->title($state ? __('admin.messages.status_enabled') : __('admin.messages.status_disabled'))
                            ->success()
                            ->send();
                    }),
                ToggleColumn::make('is_active')
                    ->label(__('admin.fields.active'))
                    ->disabled(fn (Guide $record): bool => ! Gate::allows('update', $record))
                    ->afterStateUpdated(function (bool $state): void {
                        Notification::make()
                            ->title($state ? __('admin.messages.status_enabled') : __('admin.messages.status_disabled'))
                            ->success()
                            ->send();
                    }),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
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
}
