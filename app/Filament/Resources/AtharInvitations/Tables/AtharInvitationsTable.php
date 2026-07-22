<?php

namespace App\Filament\Resources\AtharInvitations\Tables;

use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Support\AdminTableEmptyState;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                TextColumn::make('expires_at')
                    ->label(__('admin.fields.expires_at'))
                    ->dateTime()
                    ->sortable(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('filament-actions::delete.multiple.label')),
                ]),
            ]);
    }
}
