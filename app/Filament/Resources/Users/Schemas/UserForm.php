<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.main_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.fields.email_address'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active_flag'))
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make(__('admin.sections.security'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->label(__('admin.auth.new_password'))
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->confirmed()
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label(__('admin.auth.confirm_new_password'))
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
