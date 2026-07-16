<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ShowUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.administration');
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
                        TextEntry::make('roles.name')
                            ->label(__('admin.fields.roles'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Str::headline($state))
                            ->placeholder('-'),
                    ]),
            ]);
    }

    public static function canManageRecord(User $record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canChangeStatus(User $record): bool
    {
        $actor = auth()->user();

        return $actor instanceof User
            && ! $actor->is($record)
            && Gate::forUser($actor)->allows('update', $record);
    }

    public static function canManageRoles(?User $record): bool
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return false;
        }

        if ($record === null) {
            return $actor->hasRole('super_admin') || $actor->can('update users');
        }

        return ! $actor->is($record) && Gate::forUser($actor)->allows('update', $record);
    }

    /** @param  Builder<Role>  $query */
    public static function scopeAssignableRoles(Builder $query): Builder
    {
        $actor = auth()->user();

        if (! $actor instanceof User || ! $actor->hasRole('super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        return $query;
    }

    /** @return array<int, string> */
    public static function assignableRoleIds(): array
    {
        return self::scopeAssignableRoles(Role::query())
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();
    }

    public static function roleLabel(Role $role): string
    {
        return $role->getTranslation('display_name', app()->getLocale(), false)
            ?: Str::headline($role->name);
    }

    /** @param  array<int, int|string>  $roleIds */
    public static function syncAssignableRoles(User $record, array $roleIds): void
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        if ($actor->is($record)) {
            return;
        }

        Gate::forUser($actor)->authorize('update', $record);

        /** @var Collection<int, Role> $roles */
        $roles = Role::query()->whereKey($roleIds)->get();

        abort_if($roles->count() !== count(array_unique($roleIds)), 422);
        abort_if(! $actor->hasRole('super_admin') && $roles->contains('name', 'super_admin'), 403);

        $record->syncRoles($roles);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->id() !== $record->getKey() && parent::canDelete($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->id() !== $record->getKey() && parent::canForceDelete($record);
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
