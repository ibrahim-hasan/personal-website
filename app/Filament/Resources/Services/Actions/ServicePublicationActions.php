<?php

namespace App\Filament\Resources\Services\Actions;

use App\Actions\Services\SetServicePublication;
use App\Models\Service;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;

final class ServicePublicationActions
{
    public static function preview(): Action
    {
        return Action::make('preview_service')
            ->label(__('service_admin.actions.preview'))
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->authorize('preview')
            ->url(fn (Service $record): string => route('filament.admin.services.preview', ['service' => $record->getKey()]))
            ->openUrlInNewTab();
    }

    public static function publish(): Action
    {
        return Action::make('publish_service')
            ->label(__('service_admin.actions.publish'))
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('service_admin.actions.publish_description'))
            ->authorize('publish')
            ->visible(fn (Service $record): bool => ! $record->trashed() && ($record->is_draft || ! $record->is_active))
            ->action(function (Service $record, SetServicePublication $publication): void {
                $publication->publish(self::actor(), $record);

                Notification::make()
                    ->title(__('service_admin.messages.published'))
                    ->success()
                    ->send();
            });
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish_service')
            ->label(__('service_admin.actions.unpublish'))
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('service_admin.actions.unpublish_description'))
            ->authorize('publish')
            ->visible(fn (Service $record): bool => ! $record->trashed() && ! $record->is_draft && $record->is_active)
            ->action(function (Service $record, SetServicePublication $publication): void {
                $publication->unpublish(self::actor(), $record);

                Notification::make()
                    ->title(__('service_admin.messages.unpublished'))
                    ->success()
                    ->send();
            });
    }

    private static function actor(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }
}
