<?php

namespace App\Filament\Resources\AtharInvitations\Schemas;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharRelationship;
use App\Models\AtharInvitation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AtharInvitationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.athar_invitation'))->schema([
                    TextEntry::make('email')
                        ->label(__('admin.fields.email_address'))
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('delivery_mode')
                        ->label(__('admin.fields.delivery_mode'))
                        ->badge()
                        ->helperText(__('admin.hints.athar_link_warning'))
                        ->formatStateUsing(fn (AtharInvitationDeliveryMode $state): string => $state->label())
                        ->color(fn (AtharInvitationDeliveryMode $state): string => $state->color()),
                    TextEntry::make('recipient_name')->label(__('admin.fields.recipient_name')),
                    TextEntry::make('relationship')
                        ->label(__('admin.fields.relationship'))
                        ->formatStateUsing(fn (AtharRelationship $state): string => $state->label()),
                    TextEntry::make('status')
                        ->label(__('admin.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (AtharInvitationStatus $state): string => $state->label())
                        ->color(fn (AtharInvitationStatus $state): string => $state->color()),
                    TextEntry::make('placement')
                        ->label(__('admin.fields.placement'))
                        ->formatStateUsing(fn (AtharPlacement $state): string => $state->adminLabel()),
                    TextEntry::make('expires_at')
                        ->label(__('admin.fields.expires_at'))
                        ->dateTime(),
                    TextEntry::make('created_at')
                        ->label(__('admin.fields.created_at'))
                        ->dateTime(),
                    TextEntry::make('share_url')
                        ->label(__('admin.fields.share_link'))
                        ->getStateUsing(fn (AtharInvitation $record): ?string => filled($record->token_ciphertext)
                            ? localized_route(
                                'athar.show',
                                ['token' => $record->token_ciphertext],
                                true,
                                $record->preferred_locale,
                            )
                            : null)
                        ->placeholder(__('admin.values.not_available'))
                        ->copyable()
                        ->copyMessage(__('admin.messages.athar_share_link_copied'))
                        ->url(fn (?string $state): ?string => $state, true)
                        ->columnSpanFull(),
                ])->columns(2),
                Section::make(__('admin.sections.athar_private'))->schema([
                    TextEntry::make('contribution.sealed_payload')
                        ->label(__('admin.fields.private_source'))
                        ->formatStateUsing(fn ($state): string => is_array($state) ? (string) ($state['freeform'] ?? '') : (string) $state)
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('contribution.status')
                        ->label(__('admin.fields.contribution_status'))
                        ->badge()
                        ->formatStateUsing(fn (AtharContributionStatus $state): string => $state->label())
                        ->color(fn (AtharContributionStatus $state): string => $state->color()),
                ]),
            ]);
    }
}
