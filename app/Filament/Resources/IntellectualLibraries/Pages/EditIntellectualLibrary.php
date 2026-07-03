<?php

namespace App\Filament\Resources\IntellectualLibraries\Pages;

use App\Filament\Resources\IntellectualLibraries\IntellectualLibraryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditIntellectualLibrary extends EditRecord
{
    protected static string $resource = IntellectualLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
