<?php

namespace App\Filament\Resources\AtharInvitations\Tables;

use App\Actions\Athar\PrepareAtharPublicationVersion;
use App\Actions\Athar\SendAtharApproval;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharInvitation;
use App\Models\User;
use App\Support\AdminTableEmptyState;
use App\Support\AtharTextLimits;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                Action::make('prepare_publication')
                    ->label(__('admin.actions.athar_prepare'))
                    ->modalHeading(__('admin.actions.athar_prepare'))
                    ->modalSubmitActionLabel(__('admin.actions.athar_prepare_submit'))
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->authorize('update')
                    ->visible(function (AtharInvitation $record): bool {
                        if ($record->contribution?->sealed() !== true) {
                            return false;
                        }

                        $latestVersionStatus = $record->contribution->publicationVersions
                            ->sortByDesc('version')
                            ->first()?->status?->value;

                        return ! in_array($latestVersionStatus, [
                            AtharPublicationStatus::Draft->value,
                            AtharPublicationStatus::AwaitingApproval->value,
                            AtharPublicationStatus::Published->value,
                        ], true);
                    })
                    ->fillForm(function (AtharInvitation $record): array {
                        $sealedPayload = $record->contribution?->sealed_payload ?? [];

                        return [
                            'locale' => $record->preferred_locale,
                            'text' => (string) ($sealedPayload['freeform'] ?? ''),
                            'context' => '',
                            'identity_display' => AtharIdentityDisplay::Anonymous->value,
                        ];
                    })
                    ->schema([
                        Select::make('locale')
                            ->label(__('admin.fields.locale'))
                            ->options([
                                'ar' => __('admin.locales.ar'),
                                'en' => __('admin.locales.en'),
                            ])
                            ->required()
                            ->default(fn (AtharInvitation $record): string => $record->preferred_locale),
                        Textarea::make('text')
                            ->label(__('admin.fields.public_text'))
                            ->required()
                            ->minLength(3)
                            ->maxLength(AtharTextLimits::PUBLIC_MAX)
                            ->live(),
                        Textarea::make('context')
                            ->label(__('admin.fields.work_context'))
                            ->maxLength(AtharTextLimits::CONTEXT_MAX)
                            ->live(),
                        Select::make('identity_display')
                            ->label(__('admin.fields.identity_display'))
                            ->options(collect(AtharIdentityDisplay::cases())->mapWithKeys(fn (AtharIdentityDisplay $case): array => [$case->value => $case->label()])->all())
                            ->required()
                            ->default(AtharIdentityDisplay::Anonymous->value),
                    ])
                    ->action(function (AtharInvitation $record, array $data, PrepareAtharPublicationVersion $prepare, SendAtharApproval $send): void {
                        $reviewer = auth()->user();
                        abort_unless($reviewer instanceof User, 403);
                        $version = $prepare->handle($record->contribution, [(string) $data['locale'] => ['text' => (string) $data['text'], 'context' => (string) ($data['context'] ?? '')]], AtharPlacement::from($record->placement->value), $record->placement_key, AtharIdentityDisplay::from($data['identity_display']), $reviewer);
                        $send->handle($version);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('filament-actions::delete.multiple.label')),
                ]),
            ]);
    }
}
