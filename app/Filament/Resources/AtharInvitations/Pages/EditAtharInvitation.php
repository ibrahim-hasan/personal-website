<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Actions\Athar\DeleteAtharInvitation;
use App\Actions\Athar\ResendAtharInvitation;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use App\Models\AtharInvitation;
use Filament\Actions\Action;
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
            Action::make('delete_permanently')
                ->label(__('admin.actions.athar_delete_invitation'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.confirmations.athar_delete_invitation_heading'))
                ->modalDescription(__('admin.confirmations.athar_delete_invitation_description'))
                ->modalSubmitActionLabel(__('admin.actions.athar_delete_invitation'))
                ->authorize('purge')
                ->successRedirectUrl(AtharInvitationResource::getUrl('index'))
                ->action(function (AtharInvitation $record, DeleteAtharInvitation $delete): void {
                    $delete->handle($record);
                    Notification::make()->title(__('admin.messages.athar_invitation_deleted'))->success()->send();
                }),
        ];
    }
}
