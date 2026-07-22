<?php

namespace App\Filament\Resources\AtharInvitations;

use App\Filament\Resources\AtharInvitations\Pages\CreateAtharInvitation;
use App\Filament\Resources\AtharInvitations\Pages\EditAtharInvitation;
use App\Filament\Resources\AtharInvitations\Pages\ListAtharInvitations;
use App\Filament\Resources\AtharInvitations\Pages\ViewAtharInvitation;
use App\Filament\Resources\AtharInvitations\Schemas\AtharInvitationForm;
use App\Filament\Resources\AtharInvitations\Schemas\AtharInvitationInfolist;
use App\Filament\Resources\AtharInvitations\Tables\AtharInvitationsTable;
use App\Models\AtharInvitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AtharInvitationResource extends Resource
{
    protected static ?string $model = AtharInvitation::class;

    protected static ?string $recordTitleAttribute = 'recipient_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.engagement');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.athar.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.athar_invitation.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.athar_invitation.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AtharInvitation::query()->whereIn('status', ['sent', 'verified'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin.athar.navigation_badge_tooltip');
    }

    public static function form(Schema $schema): Schema
    {
        return AtharInvitationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AtharInvitationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AtharInvitationsTable::configure($table);
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
            'index' => ListAtharInvitations::route('/'),
            'create' => CreateAtharInvitation::route('/create'),
            'view' => ViewAtharInvitation::route('/{record}'),
            'edit' => EditAtharInvitation::route('/{record}/edit'),
        ];
    }
}
