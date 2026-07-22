<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAtharInvitation extends EditRecord
{
    protected static string $resource = AtharInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label(__('filament-actions::view.single.label')),
            DeleteAction::make()->label(__('filament-actions::delete.single.label')),
        ];
    }
}
