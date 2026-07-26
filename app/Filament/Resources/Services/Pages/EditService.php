<?php

namespace App\Filament\Resources\Services\Pages;

use App\Actions\Services\ServicePublicationValidator;
use App\Filament\Resources\Services\Actions\ServicePublicationActions;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['key'], $data['is_draft'], $data['is_active']);

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record instanceof Service && $record->is_active && ! $record->is_draft) {
            $candidate = clone $record;
            $candidate->forceFill($data);

            app(ServicePublicationValidator::class)->assertPublishable($candidate);
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ServicePublicationActions::preview(),
            ServicePublicationActions::publish(),
            ServicePublicationActions::unpublish(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
