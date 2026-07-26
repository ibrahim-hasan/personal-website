<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\Actions\ProjectCaseStudyPublicationActions;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ProjectCaseStudyPublicationActions::publish(),
            ProjectCaseStudyPublicationActions::unpublish(),
            DeleteAction::make(),
        ];
    }
}
