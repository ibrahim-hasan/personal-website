<?php

namespace App\Filament\Resources\IntellectualLibraries\Pages;

use App\Filament\Resources\IntellectualLibraries\IntellectualLibraryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ShowIntellectualLibrary extends ViewRecord
{
    protected static string $resource = IntellectualLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
