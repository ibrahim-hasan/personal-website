<?php

namespace App\Filament\Resources\AtharInvitations\Pages;

use App\Actions\Athar\DeleteAtharPrivateMessage;
use App\Enums\AtharContributionStatus;
use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use App\Models\AtharInvitation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAtharInvitation extends ViewRecord
{
    protected static string $resource = AtharInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label(__('filament-actions::edit.single.label')),
            Action::make('delete_private_message')
                ->label(__('admin.actions.athar_delete_private_message'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.confirmations.athar_delete_private_message_heading'))
                ->modalDescription(__('admin.confirmations.athar_delete_private_message_description'))
                ->authorize('deletePrivateMessage')
                ->visible(fn (AtharInvitation $record): bool => $record->contribution !== null
                    && $record->contribution->status !== AtharContributionStatus::DeletionRequested)
                ->action(function (AtharInvitation $record, DeleteAtharPrivateMessage $delete): void {
                    $delete->handle($record->contribution);
                    Notification::make()->title(__('admin.messages.athar_private_message_deleted'))->success()->send();
                }),
        ];
    }
}
