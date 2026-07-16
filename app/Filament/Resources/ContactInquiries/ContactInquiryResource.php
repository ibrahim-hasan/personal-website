<?php

namespace App\Filament\Resources\ContactInquiries;

use App\Filament\Resources\ContactInquiries\Pages\EditContactInquiry;
use App\Filament\Resources\ContactInquiries\Pages\ListContactInquiries;
use App\Filament\Resources\ContactInquiries\Pages\ViewContactInquiry;
use App\Filament\Resources\ContactInquiries\Schemas\ContactInquiryForm;
use App\Filament\Resources\ContactInquiries\Schemas\ContactInquiryInfolist;
use App\Filament\Resources\ContactInquiries\Tables\ContactInquiriesTable;
use App\Models\ContactInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ContactInquiryResource extends Resource
{
    protected static ?string $model = ContactInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.engagement');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.contact_inquiry.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.contact_inquiry.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactInquiry::query()->where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return ContactInquiryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactInquiryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactInquiriesTable::configure($table);
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
            'index' => ListContactInquiries::route('/'),
            'view' => ViewContactInquiry::route('/{record}'),
            'edit' => EditContactInquiry::route('/{record}/edit'),
        ];
    }
}
