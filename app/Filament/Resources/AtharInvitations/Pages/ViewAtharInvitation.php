<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAtharInvitation extends ViewRecord
{
    protected static string $resource = AtharInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label(__('filament-actions::edit.single.label')),
        ];
    }
}
