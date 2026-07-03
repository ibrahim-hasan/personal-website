<?php

namespace App\Filament\Resources\Guides;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Filament\Resources\Guides\Pages\CreateGuide;
use App\Filament\Resources\Guides\Pages\EditGuide;
use App\Filament\Resources\Guides\Pages\ListGuides;
use App\Filament\Resources\Guides\Pages\ShowGuide;
use App\Filament\Resources\Guides\Schemas\GuideForm;
use App\Filament\Resources\Guides\Tables\GuidesTable;
use App\Models\Guide;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuideResource extends Resource
{
    protected static ?string $model = Guide::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.guides');
    }

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('admin.resources.guide.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.guide.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $inactiveGuides = Guide::query()
            ->where('is_active', false)
            ->count();

        return $inactiveGuides > 0
            ? __('badge.inactive_guides', ['count' => $inactiveGuides])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return GuideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuidesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.content'))
                    ->schema([
                        TranslatableInfolistTabs::make([
                            'title' => [],
                            'description' => ['columnSpanFull' => true],
                        ], columns: 2),
                    ]),
                Section::make(__('admin.sections.media'))
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('cover_image')
                            ->label(__('admin.fields.cover_image'))
                            ->getStateUsing(fn (Guide $record): string => $record->getFirstMediaUrl('cover_image') ?: asset('images/placeholder.png')),
                        TextEntry::make('guide_file')
                            ->label(__('admin.fields.guide_file'))
                            ->getStateUsing(fn (Guide $record): string => $record->getFirstMedia('guide_file')?->file_name ?? '')
                            ->url(fn (Guide $record): string => route('filament.admin.guides.guide-file', ['guide' => $record]))
                            ->openUrlInNewTab()
                            ->visible(fn (Guide $record): bool => $record->getFirstMedia('guide_file') !== null)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.sections.publishing'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('sort_order')
                            ->label(__('admin.fields.sort_order')),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuides::route('/'),
            'create' => CreateGuide::route('/create'),
            'edit' => EditGuide::route('/{record}/edit'),
            'view' => ShowGuide::route('/{record}'),
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
