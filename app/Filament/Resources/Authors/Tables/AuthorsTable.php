<?php

namespace App\Filament\Resources\Authors\Tables;

use App\Filament\Tables\Columns\UserColumn;
use App\Models\Author;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class AuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                UserColumn::make('user')
                    ->label(__('admin.fields.author'))
                    ->userName(fn (Author $record): ?string => $record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en'))
                    ->userTitle(fn (Author $record): ?string => $record->getTranslation('position', app()->getLocale()) ?: $record->getTranslation('position', 'en'))
                    ->userImage(fn (Author $record): string => $record->getFirstMediaUrl('avatar') ?: asset('images/placeholder.png')),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->getStateUsing(fn (Author $record): ?string => $record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_draft')
                    ->label(__('admin.fields.draft'))
                    ->disabled(fn (Author $record): bool => ! Gate::allows('update', $record))
                    ->afterStateUpdated(function (bool $state): void {
                        Notification::make()
                            ->title($state ? __('admin.messages.status_enabled') : __('admin.messages.status_disabled'))
                            ->success()
                            ->send();
                    }),
                ToggleColumn::make('is_active')
                    ->label(__('admin.fields.active'))
                    ->disabled(fn (Author $record): bool => ! Gate::allows('update', $record))
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
            ->defaultSort('created_at', 'desc')
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
