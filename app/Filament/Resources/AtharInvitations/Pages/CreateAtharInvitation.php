<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Actions\Athar\CreateAtharInvitation as CreateAction;
use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAtharInvitation extends CreateRecord
{
    protected static string $resource = AtharInvitationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $result = app(CreateAction::class)->handle($user, $data);

        Notification::make()
            ->title(__('admin.messages.athar_share_link_copied'))
            ->body($result['url'])
            ->success()
            ->persistent()
            ->send();

        return $result['invitation'];
    }
}
