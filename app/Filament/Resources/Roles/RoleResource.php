<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ShowRole;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.access');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.role.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.role.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $rolesWithoutPermissions = Role::query()
            ->whereDoesntHave('permissions')
            ->count();

        return $rolesWithoutPermissions > 0
            ? __('badge.roles_without_permissions', ['count' => $rolesWithoutPermissions])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.main_details'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.fields.code_name')),
                        TextEntry::make('guard_name')
                            ->label(__('admin.fields.guard_name')),
                        TranslatableInfolistTabs::make([
                            'display_name' => [],
                        ], columns: 1),
                    ]),
            ]);
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
            'view' => ShowRole::route('/{record}'),
        ];
    }
}
