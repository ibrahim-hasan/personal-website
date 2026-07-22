<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Actions\Athar\CreateAtharInvitation as CreateAction;
use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAtharInvitation extends CreateRecord
{
    protected static string $resource = AtharInvitationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return app(CreateAction::class)->handle($user, $data)['invitation'];
    }
}
