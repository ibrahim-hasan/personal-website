<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Actions\Athar\ResendAtharInvitation;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use App\Models\AtharInvitation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAtharInvitation extends EditRecord
{
    protected static string $resource = AtharInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resend')
                ->label(__('admin.actions.athar_resend'))
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->authorize('send')
                ->visible(fn (AtharInvitation $record): bool => $record->delivery_mode === AtharInvitationDeliveryMode::Email
                    && ! in_array($record->status, [AtharInvitationStatus::Revoked, AtharInvitationStatus::Expired], true))
                ->action(function (AtharInvitation $record, ResendAtharInvitation $resend): void {
                    $resend->handle($record);
                    Notification::make()->title(__('admin.messages.athar_resent'))->success()->send();
                }),
            ViewAction::make()->label(__('filament-actions::view.single.label')),
            DeleteAction::make()->label(__('filament-actions::delete.single.label')),
        ];
    }
}
