<?php

namespace App\Filament\Resources\IntellectualLibraries\Pages;

use App\Filament\Resources\IntellectualLibraries\IntellectualLibraryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntellectualLibraries extends ListRecords
{
    protected static string $resource = IntellectualLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
