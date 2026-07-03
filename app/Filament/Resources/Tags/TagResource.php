<?php

namespace App\Filament\Resources\Tags;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tags\Pages\ShowTag;
use App\Filament\Resources\Tags\Schemas\TagForm;
use App\Filament\Resources\Tags\Tables\TagsTable;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Spatie\Tags\Tag;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.tag.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.tag.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $unusedTags = Tag::query()
            ->leftJoin('taggables', 'taggables.tag_id', '=', 'tags.id')
            ->whereNull('taggables.tag_id')
            ->count();

        return $unusedTags > 0
            ? __('badge.unused_tags', ['count' => $unusedTags])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.main_details'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('color')
                            ->label(__('admin.fields.color'))
                            ->badge()
                            ->color(fn ($record): string => $record->color ?? '#E8F5E9'),
                        TranslatableInfolistTabs::make([
                            'name' => [],
                        ], columns: 1),
                        // TextEntry::make('slug')
                        //     ->label(__('admin.fields.slug'))
                        //     ->getStateUsing(fn (Tag $record): ?string => $record->getTranslation('slug', app()->getLocale()) ?: $record->getTranslation('slug', 'en')),
                        TextEntry::make('order_column')
                            ->label(__('admin.fields.order_column')),
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
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
            'view' => ShowTag::route('/{record}'),
        ];
    }
}
