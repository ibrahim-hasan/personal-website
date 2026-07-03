<?php

namespace App\Filament\Resources\Newsletters;

use App\Filament\Resources\Newsletters\Pages\ListNewsletters;
use App\Filament\Resources\Newsletters\Tables\NewslettersTable;
use App\Models\Newsletter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NewsletterResource extends Resource
{
    protected static ?string $model = Newsletter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.newsletter.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.newsletter.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $unsubscribed = Newsletter::query()
            ->where('is_disabled', true)
            ->count();

        return $unsubscribed > 0
            ? __('badge.unsubscribed_newsletters', ['count' => $unsubscribed])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return NewslettersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletters::route('/'),
        ];
    }
}
