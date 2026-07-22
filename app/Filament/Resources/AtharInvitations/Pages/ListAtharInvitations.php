<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAtharInvitations extends ListRecords
{
    protected static string $resource = AtharInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
