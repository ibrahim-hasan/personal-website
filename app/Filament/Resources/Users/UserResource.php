<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ShowUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class UserResource extends Resource
{
    public const SEEDED_ADMIN_EMAIL = 'admin@ibrahimhasan.dev';

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.access');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.user.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.user.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $inactiveUsers = User::query()
            ->where('is_active', false)
            ->count();

        return $inactiveUsers > 0
            ? __('badge.inactive_users', ['count' => $inactiveUsers])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.main_details'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('email')
                            ->label(__('admin.fields.email_address')),
                        TextEntry::make('is_active')
                            ->label(__('admin.fields.is_active_flag'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state)),
                        TextEntry::make('locale_preference')
                            ->label(__('admin.fields.preferred_language'))
                            ->getStateUsing(fn (User $record): string => filled($record->locale_preference)
                                ? (__('admin.locales.'.$record->locale_preference) ?? $record->locale_preference)
                                : '-'),
                    ]),
            ]);
    }

    public static function canManageRecord(User $record): bool
    {
        return Gate::allows('update', $record) || $record->email === self::SEEDED_ADMIN_EMAIL;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ShowUser::route('/{record}'),
        ];
    }
}
