<?php

namespace App\Filament\Resources\GuideDownloaders;

use App\Filament\Resources\GuideDownloaders\Pages\ListGuideDownloaders;
use App\Filament\Resources\GuideDownloaders\Tables\GuideDownloadersTable;
use App\Models\GuideDownloader;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GuideDownloaderResource extends Resource
{
    protected static ?string $model = GuideDownloader::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.guides');
    }

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('admin.resources.guide_downloader.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.guide_downloader.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $unsentMails = GuideDownloader::query()
            ->where('is_mail_sent', false)
            ->count();

        return $unsentMails > 0
            ? __('badge.unsent_guide_mails', ['count' => $unsentMails])
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return GuideDownloadersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuideDownloaders::route('/'),
        ];
    }
}
