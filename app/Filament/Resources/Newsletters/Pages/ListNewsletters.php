<?php

namespace App\Filament\Resources\Newsletters\Pages;

use App\Filament\Resources\Newsletters\NewsletterResource;
use Filament\Resources\Pages\ListRecords;

class ListNewsletters extends ListRecords
{
    protected static string $resource = NewsletterResource::class;
}
