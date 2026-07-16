<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.is_active_flag'))
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record !== null && ! UserResource::canChangeStatus($record))
                            ->inline(false),
                        Select::make('roles')
                            ->label(__('admin.fields.roles'))
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => UserResource::scopeAssignableRoles($query),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserResource::roleLabel($record))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->nestedRecursiveRule(fn () => Rule::in(UserResource::assignableRoleIds()))
                            ->saveRelationshipsUsing(function (User $record, ?array $state): void {
                                UserResource::syncAssignableRoles($record, $state ?? []);
                            })
                            ->disabled(fn (?User $record): bool => ! UserResource::canManageRoles($record))
                            ->helperText(__('admin.hints.role_assignment')),
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
                            ->rule(Password::defaults())
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
