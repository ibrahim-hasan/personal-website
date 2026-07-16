<?php

namespace App\Filament\Resources\Services;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\Services\Pages\ShowService;
use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Filament\Resources\Services\Tables\ServicesTable;
use App\Models\Service;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $recordRouteKeyName = 'id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.service.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.service.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $inactiveServices = Service::query()
            ->where('is_active', false)
            ->count();

        return $inactiveServices > 0
            ? __('badge.inactive_services', ['count' => $inactiveServices])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.content'))
                    ->schema([
                        TranslatableInfolistTabs::make([
                            'name' => [],
                            'slug' => [],
                            'summary' => ['columnSpanFull' => true],
                            'problem' => ['columnSpanFull' => true],
                            'approach' => ['columnSpanFull' => true],
                            'result' => ['columnSpanFull' => true],
                        ], columns: 2),
                    ]),
                Section::make(__('admin.sections.publishing'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('key')
                            ->label(__('admin.fields.key')),
                        TextEntry::make('is_draft')
                            ->label(__('admin.fields.is_draft'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state)),
                        TextEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state)),
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
            'view' => ShowService::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
