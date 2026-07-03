<?php

namespace App\Filament\Resources\IntellectualLibraries;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Filament\Resources\IntellectualLibraries\Pages\CreateIntellectualLibrary;
use App\Filament\Resources\IntellectualLibraries\Pages\EditIntellectualLibrary;
use App\Filament\Resources\IntellectualLibraries\Pages\ListIntellectualLibraries;
use App\Filament\Resources\IntellectualLibraries\Pages\ShowIntellectualLibrary;
use App\Filament\Resources\IntellectualLibraries\Schemas\IntellectualLibraryForm;
use App\Filament\Resources\IntellectualLibraries\Tables\IntellectualLibrariesTable;
use App\Models\IntellectualLibrary;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IntellectualLibraryResource extends Resource
{
    protected static ?string $model = IntellectualLibrary::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.intellectual_library.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.intellectual_library.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $inactiveLibraries = IntellectualLibrary::query()
            ->where('is_active', false)
            ->count();

        return $inactiveLibraries > 0
            ? __('badge.inactive_libraries', ['count' => $inactiveLibraries])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return IntellectualLibraryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntellectualLibrariesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['author', 'tags', 'media']);
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
                            'excert' => ['label' => __('admin.fields.excerpt'), 'columnSpanFull' => true],
                            'content' => ['html' => true, 'columnSpanFull' => true],
                        ], columns: 2),
                        TextEntry::make('type')
                            ->label(__('admin.fields.type'))
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state?->label() ?? ''),
                    ]),
                Section::make(__('admin.sections.seo'))
                    ->schema([
                        TranslatableInfolistTabs::make([
                            'seo_title' => [],
                            'seo_description' => ['columnSpanFull' => true],
                        ], columns: 2),
                        ImageEntry::make('og_image')
                            ->label(__('admin.fields.og_image'))
                            ->getStateUsing(fn (IntellectualLibrary $record): string => $record->getFirstMediaUrl('og_image') ?: asset('images/placeholder.png')),
                    ]),
                Section::make(__('admin.sections.media'))
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label(__('admin.fields.featured_image'))
                            ->getStateUsing(fn (IntellectualLibrary $record): string => $record->getFirstMediaUrl('featured_image') ?: asset('images/placeholder.png')),
                        ImageEntry::make('cover_image')
                            ->label(__('admin.fields.cover_image'))
                            ->getStateUsing(fn (IntellectualLibrary $record): string => $record->getFirstMediaUrl('cover_image') ?: asset('images/placeholder.png')),
                        TextEntry::make('tool_file')
                            ->label(__('admin.fields.tool_file'))
                            ->getStateUsing(fn (IntellectualLibrary $record): string => $record->getFirstMediaUrl('tool_file'))
                            ->url(fn (IntellectualLibrary $record): string => $record->getFirstMediaUrl('tool_file'))
                            ->visible(fn (IntellectualLibrary $record): bool => filled($record->getFirstMediaUrl('tool_file')))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.fields.author'))
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('author_avatar')
                            ->label(__('admin.fields.avatar'))
                            ->getStateUsing(fn (IntellectualLibrary $record): string => $record->author?->getFirstMediaUrl('avatar') ?: asset('images/placeholder.png'))
                            ->columnSpanFull(),
                        TranslatableInfolistTabs::make([
                            'name' => ['relation' => 'author'],
                            'position' => ['relation' => 'author'],
                        ], columns: 2),
                    ]),
                Section::make(__('admin.sections.publishing'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reading_time')
                            ->label(__('admin.fields.reading_time_minutes')),
                        TextEntry::make('video_length')
                            ->label(__('admin.fields.video_podcast_length')),
                        TextEntry::make('views')
                            ->label(__('admin.fields.views')),
                        TextEntry::make('is_draft')
                            ->label(__('admin.fields.is_draft'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state)),
                        TextEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state)),
                        TextEntry::make('scheduled_at')
                            ->label(__('admin.fields.scheduled_at'))
                            ->dateTime(),
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
            'index' => ListIntellectualLibraries::route('/'),
            'create' => CreateIntellectualLibrary::route('/create'),
            'edit' => EditIntellectualLibrary::route('/{record}/edit'),
            'view' => ShowIntellectualLibrary::route('/{record}'),
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
