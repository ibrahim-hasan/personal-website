<?php

namespace App\Filament\Resources\AtharInvitations\Schemas;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharRelationship;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AtharInvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.athar_invitation'))
                    ->schema([
                        TextInput::make('email')
                            ->label(__('admin.fields.email_address'))
                            ->helperText(__('admin.hints.athar_email_optional'))
                            ->email()
                            ->required(fn (Get $get): bool => (bool) $get('send_email'))
                            ->maxLength(255)
                            ->live(debounce: 300)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (! self::validEmail($state)) {
                                    $set('send_email', false);
                                }
                            })
                            ->disabledOn('edit'),
                        Toggle::make('send_email')
                            ->label(__('admin.fields.send_email'))
                            ->helperText(__('admin.hints.athar_send_email'))
                            ->default(false)
                            ->live()
                            ->visibleOn('create')
                            ->disabled(fn (Get $get): bool => ! self::validEmail($get('email'))),
                        TextInput::make('recipient_name')
                            ->label(__('admin.fields.recipient_name'))
                            ->maxLength(255),
                        Select::make('relationship')
                            ->label(__('admin.fields.relationship'))
                            ->options(collect(AtharRelationship::cases())->mapWithKeys(fn (AtharRelationship $case): array => [$case->value => $case->label()])->all())
                            ->required(),
                        Select::make('preferred_locale')
                            ->label(__('admin.fields.preferred_language'))
                            ->options([
                                'ar' => __('admin.locales.ar'),
                                'en' => __('admin.locales.en'),
                            ])
                            ->required()
                            ->default('ar'),
                        Select::make('placement')
                            ->label(__('admin.fields.placement'))
                            ->helperText(__('admin.hints.athar_placement'))
                            ->options(collect(AtharPlacement::cases())->mapWithKeys(fn (AtharPlacement $case): array => [$case->value => $case->adminLabel()])->all())
                            ->required(),
                        TextInput::make('placement_key')
                            ->label(__('admin.fields.placement_key'))
                            ->helperText(__('admin.hints.athar_placement_key'))
                            ->maxLength(120),
                        Select::make('identity_display')
                            ->label(__('admin.fields.identity_display'))
                            ->helperText(__('admin.hints.athar_identity_display'))
                            ->options(collect(AtharIdentityDisplay::cases())->mapWithKeys(fn (AtharIdentityDisplay $case): array => [$case->value => $case->label()])->all())
                            ->required()
                            ->default(AtharIdentityDisplay::Anonymous->value),
                        Textarea::make('personal_reason')
                            ->label(__('admin.fields.personal_reason'))
                            ->rows(4)
                            ->maxLength(2000),
                        DateTimePicker::make('expires_at')
                            ->label(__('admin.fields.expires_at'))
                            ->required()
                            ->default(now()->addDays(14))
                            ->minDate(now()->startOfMinute())
                            ->maxDate(now()->addDays(90))
                            ->seconds(false),
                    ])->columns(2),
            ]);
    }

    private static function validEmail(mixed $state): bool
    {
        return is_string($state)
            && filter_var(trim($state), FILTER_VALIDATE_EMAIL) !== false;
    }
}
