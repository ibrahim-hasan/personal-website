<?php

namespace App\Filament\Resources\AtharInvitations\RelationManagers;

use App\Actions\Athar\HideAtharPublication;
use App\Actions\Athar\UnhideAtharPublication;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PublicationVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'publicationVersions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->label('#'),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (AtharPublicationStatus $state): string => $state->label())
                    ->color(fn (AtharPublicationStatus $state): string => $state->color()),
                TextColumn::make('origin')
                    ->label(__('admin.fields.origin'))
                    ->badge()
                    ->formatStateUsing(fn (AtharPublicationOrigin $state): string => $state->label()),
                TextColumn::make('identity_display')
                    ->label(__('admin.fields.identity_display'))
                    ->formatStateUsing(fn (AtharIdentityDisplay $state): string => $state->label()),
                TextColumn::make('placement')
                    ->label(__('admin.fields.placement'))
                    ->formatStateUsing(fn (AtharPlacement $state): string => $state->adminLabel()),
                TextColumn::make('published_at')
                    ->label(__('admin.fields.published_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('withdrawn_at')
                    ->label(__('admin.fields.withdrawn_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('reviewer.name')
                    ->label(__('admin.fields.reviewed_by'))
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('hide')
                    ->label(__('admin.actions.athar_hide'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->authorize('hide')
                    ->visible(fn ($record): bool => $record->status === AtharPublicationStatus::Published)
                    ->action(function ($record, HideAtharPublication $hide): void {
                        $hide->handle($record);
                        Notification::make()->title(__('admin.messages.athar_hidden'))->success()->send();
                    }),
                Action::make('unhide')
                    ->label(__('admin.actions.athar_unhide'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize('hide')
                    ->visible(fn ($record): bool => $record->status === AtharPublicationStatus::Hidden)
                    ->action(function ($record, UnhideAtharPublication $unhide): void {
                        $unhide->handle($record);
                        Notification::make()->title(__('admin.messages.athar_unhidden'))->success()->send();
                    }),
            ]);
    }
}
