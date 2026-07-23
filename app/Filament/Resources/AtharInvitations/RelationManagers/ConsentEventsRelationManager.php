<?php

namespace App\Filament\Resources\AtharInvitations\RelationManagers;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ConsentEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'consentEvents';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.athar.consent_events_title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('event_type')
                    ->label(__('admin.fields.event_type'))
                    ->badge()
                    ->formatStateUsing(fn (AtharConsentEventType $state): string => $state->label()),
                TextColumn::make('verification_method')
                    ->label(__('admin.fields.verification_method'))
                    ->badge(),
                TextColumn::make('identity_display')
                    ->label(__('admin.fields.identity_display'))
                    ->formatStateUsing(fn (AtharIdentityDisplay $state): string => $state->label()),
                TextColumn::make('placement')
                    ->label(__('admin.fields.placement'))
                    ->formatStateUsing(fn (AtharPlacement $state): string => $state->adminLabel()),
                TextColumn::make('privacy_notice_version')
                    ->label(__('admin.fields.privacy_notice_version')),
                TextColumn::make('occurred_at')
                    ->label(__('admin.fields.occurred_at'))
                    ->dateTime(),
            ]);
    }
}
