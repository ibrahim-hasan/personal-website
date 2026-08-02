<?php

namespace App\Filament\Resources\Services\Pages;

use App\Actions\Services\SetServicePublication;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ShowService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label(__('service_admin.actions.publish'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Service $record): bool => ($record->is_draft || ! $record->is_active) && Gate::allows('publish', $record))
                ->action(function (Service $record, SetServicePublication $setServicePublication): void {
                    Gate::authorize('publish', $record);
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    $setServicePublication->publish($actor, $record);
                    $this->refreshFormData(['is_draft', 'is_active']);
                }),
            Action::make('unpublish')
                ->label(__('service_admin.actions.unpublish'))
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Service $record): bool => ! $record->is_draft && $record->is_active && Gate::allows('publish', $record))
                ->action(function (Service $record, SetServicePublication $setServicePublication): void {
                    Gate::authorize('publish', $record);
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    $setServicePublication->unpublish($actor, $record);
                    $this->refreshFormData(['is_draft', 'is_active']);
                }),
            EditAction::make(),
        ];
    }
}
