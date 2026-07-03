<?php

namespace App\Filament\Resources\Authors;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Filament\Resources\Authors\Pages\CreateAuthor;
use App\Filament\Resources\Authors\Pages\EditAuthor;
use App\Filament\Resources\Authors\Pages\ListAuthors;
use App\Filament\Resources\Authors\Pages\ShowAuthor;
use App\Filament\Resources\Authors\Schemas\AuthorForm;
use App\Filament\Resources\Authors\Tables\AuthorsTable;
use App\Models\Author;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.author.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.author.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $inactiveAuthors = Author::query()
            ->where('is_active', false)
            ->count();

        return $inactiveAuthors > 0
            ? __('badge.inactive_authors', ['count' => $inactiveAuthors])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return AuthorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuthorsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.main_details'))
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label(__('admin.fields.avatar'))
                            ->getStateUsing(fn (Author $record): string => $record->getFirstMediaUrl('avatar') ?: asset('images/placeholder.png'))
                            ->columnSpanFull(),
                        TranslatableInfolistTabs::make([
                            'name' => [],
                            'position' => [],
                            'description' => ['columnSpanFull' => true],
                        ], columns: 2),
                    ]),
                Section::make(__('admin.sections.publishing'))
                    ->columns(2)
                    ->schema([
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
            'index' => ListAuthors::route('/'),
            'create' => CreateAuthor::route('/create'),
            'edit' => EditAuthor::route('/{record}/edit'),
            'view' => ShowAuthor::route('/{record}'),
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
