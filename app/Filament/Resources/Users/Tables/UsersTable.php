<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminTableEmptyState;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return AdminTableEmptyState::apply($table, 'users', 'heroicon-o-users')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('admin.fields.roles'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->placeholder('-'),
                ToggleColumn::make('is_active')
                    ->label(__('admin.fields.is_active_flag'))
                    ->disabled(fn (User $record): bool => ! UserResource::canChangeStatus($record))
                    ->beforeStateUpdated(function (User $record): void {
                        abort_unless(UserResource::canChangeStatus($record), 403);
                    })
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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (User $record): bool => UserResource::canManageRecord($record)),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
