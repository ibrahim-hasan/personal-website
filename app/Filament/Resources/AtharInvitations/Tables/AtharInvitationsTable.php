<?php

namespace App\Filament\Resources\AtharInvitations\Tables;

use App\Actions\Athar\RevokeAtharInvitation;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Models\AtharInvitation;
use App\Support\AdminTableEmptyState;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AtharInvitationsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTableEmptyState::apply($table, 'athar_invitations', 'heroicon-o-sparkles')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contribution.publicationVersions']))
            ->columns([
                TextColumn::make('recipient_name')->label(__('admin.fields.recipient_name'))->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->placeholder(__('admin.values.not_available'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (AtharInvitationStatus $state): string => $state->label())
                    ->color(fn (AtharInvitationStatus $state): string => $state->color()),
                TextColumn::make('delivery_mode')
                    ->label(__('admin.fields.delivery_mode'))
                    ->badge()
                    ->formatStateUsing(fn (AtharInvitationDeliveryMode $state): string => $state->label())
                    ->color(fn (AtharInvitationDeliveryMode $state): string => $state->color()),
                TextColumn::make('placement')
                    ->label(__('admin.fields.placement'))
                    ->formatStateUsing(fn (AtharPlacement $state): string => $state->adminLabel()),
                TextColumn::make('contribution.status')
                    ->label(__('admin.fields.contribution_status'))
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (AtharContributionStatus $state): string => $state->label())
                    ->color(fn (AtharContributionStatus $state): string => $state->color()),
                TextColumn::make('expires_at')
                    ->label(__('admin.fields.expires_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('admin.fields.created_by'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(collect(AtharInvitationStatus::cases())->mapWithKeys(fn (AtharInvitationStatus $state): array => [$state->value => $state->label()])->all()),
                SelectFilter::make('delivery_mode')
                    ->label(__('admin.fields.delivery_mode'))
                    ->options(collect(AtharInvitationDeliveryMode::cases())->mapWithKeys(fn (AtharInvitationDeliveryMode $state): array => [$state->value => $state->label()])->all()),
                SelectFilter::make('placement')
                    ->label(__('admin.fields.placement'))
                    ->options(collect(AtharPlacement::cases())->mapWithKeys(fn (AtharPlacement $state): array => [$state->value => $state->adminLabel()])->all()),
            ])
            ->recordActions([
                ViewAction::make()->label(__('filament-actions::view.single.label')),
                EditAction::make()->label(__('filament-actions::edit.single.label')),
                Action::make('revoke')
                    ->label(__('admin.actions.athar_revoke'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('revoke')
                    ->visible(fn (AtharInvitation $record): bool => ! in_array($record->status, [AtharInvitationStatus::Revoked, AtharInvitationStatus::Completed], true))
                    ->action(function (AtharInvitation $record, RevokeAtharInvitation $revoke): void {
                        $revoke->handle($record);
                        Notification::make()->title(__('admin.messages.athar_revoked'))->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('filament-actions::delete.multiple.label')),
                ]),
            ]);
    }
}
