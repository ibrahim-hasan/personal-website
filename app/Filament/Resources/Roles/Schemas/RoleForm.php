<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Components\TranslatableTabs;
use App\Models\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $tabsSchema = [];

        foreach (array_keys(config('app.supported_locales', ['ar' => [], 'en' => []])) as $locale) {
            $tabsSchema[$locale] = [
                TextInput::make("display_name.{$locale}")
                    ->label(__('admin.fields.display_name'))
                    ->required()
                    ->maxLength(100),
            ];
        }

        return $schema
            ->components([
                Section::make(__('admin.sections.main_details'))
                    ->schema([
                        TranslatableTabs::make($tabsSchema, columns: 1),
                        TextInput::make('name')
                            ->label(__('admin.fields.code_name'))
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('admin.hints.role_code_name')),
                    ])
                    ->columns(2),
                ...self::permissionSections(),
            ]);
    }

    private static function permissionSections(): array
    {
        $permissionsByGroup = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission): string {
                [, $resource] = self::parsePermissionName($permission->name);

                return $resource;
            });

        return $permissionsByGroup
            ->map(function ($permissions, string $resource): Section {
                return Section::make(__(self::permissionGroupLabel($resource)))
                    ->schema([
                        CheckboxList::make('group_permissions_'.Str::slug($resource, '_'))
                            ->label(__('admin.fields.permissions'))
                            ->options(
                                $permissions->mapWithKeys(fn (Permission $permission): array => [
                                    $permission->id => self::permissionLabel($permission->name),
                                ])->toArray()
                            )
                            ->formatStateUsing(function (?Role $record) use ($permissions): array {
                                if (! $record) {
                                    return [];
                                }

                                return $record->permissions()
                                    ->whereIn('id', $permissions->pluck('id'))
                                    ->pluck('id')
                                    ->map(fn ($id): string => (string) $id)
                                    ->toArray();
                            })
                            ->columns(3)
                            ->bulkToggleable(),
                    ])
                    ->collapsed(false);
            })
            ->values()
            ->all();
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function parsePermissionName(string $permissionName): array
    {
        $parts = preg_split('/\s+/', trim($permissionName), 2) ?: [];

        return [
            $parts[0] ?? $permissionName,
            $parts[1] ?? 'general',
        ];
    }

    private static function permissionLabel(string $permissionName): string
    {
        [$action, $resource] = self::parsePermissionName($permissionName);

        $actionKey = "admin.permissions.actions.{$action}";
        $translatedAction = __($actionKey);

        if ($translatedAction === $actionKey) {
            $translatedAction = Str::headline($action);
        }

        return "{$translatedAction} ".__(self::permissionGroupLabel($resource));
    }

    private static function permissionGroupLabel(string $resource): string
    {
        $candidateKey = "admin.resources.{$resource}.plural";
        $translated = __($candidateKey);

        if ($translated !== $candidateKey) {
            return $translated;
        }

        return Str::headline(str_replace('_', ' ', $resource));
    }
}
