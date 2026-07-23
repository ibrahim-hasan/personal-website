<?php

namespace App\Filament\Resources\AtharInvitations\RelationManagers;

use App\Actions\Athar\EditAtharPublicationVersion;
use App\Actions\Athar\HideAtharPublication;
use App\Actions\Athar\UnhideAtharPublication;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use App\Support\AtharTextLimits;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PublicationVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'publicationVersions';

    protected static ?string $title = null;

    public static function getPluralModelLabel(): ?string
    {
        return __('admin.athar.publication_versions_title');
    }

    public static function getModelLabel(): ?string
    {
        return __('admin.athar.publication_version_title');
    }

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
                EditAction::make()
                    ->authorize('update')
                    ->label(__('filament-actions::edit.single.label'))
                    ->modalHeading(__('admin.athar.publication_version_title'))
                    ->fillForm(function ($record, RelationManager $livewire): array {
                        $payload = $record->public_payload;
                        $locale = is_array($payload) ? array_key_first($payload) : null;

                        return [
                            'text' => is_string($locale) && isset($payload[$locale]) ? (string) ($payload[$locale]['text'] ?? '') : '',
                        ];
                    })
                    ->schema([
                        Textarea::make('text')
                            ->label(__('admin.fields.public_text'))
                            ->helperText(__('admin.hints.athar_public_text'))
                            ->maxLength(AtharTextLimits::PUBLIC_MAX)
                            ->rows(5),
                    ])
                    ->using(function ($record, array $data, EditAtharPublicationVersion $edit): void {
                        $edit->handle($record, [
                            'text' => (string) ($data['text'] ?? ''),
                        ], request: request(), editor: auth()->user());
                        Notification::make()->title(__('admin.messages.athar_version_updated'))->success()->send();
                    }),
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
