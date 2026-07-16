<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Models\Project;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.project_story'))
                    ->schema([
                        TranslatableInfolistTabs::make([
                            'title' => [],
                            'sector' => [],
                            'summary' => ['columnSpanFull' => true],
                            'challenge' => ['columnSpanFull' => true],
                            'response' => ['columnSpanFull' => true],
                            'outcome' => ['columnSpanFull' => true],
                            'image_alt' => [],
                            'logo_alt' => [],
                        ]),
                    ]),
                Section::make(__('admin.sections.publishing'))
                    ->columns(4)
                    ->schema([
                        TextEntry::make('slug')->label(__('admin.fields.slug')),
                        TextEntry::make('lens')->label(__('admin.fields.lens'))->badge(),
                        TextEntry::make('featured')
                            ->label(__('admin.fields.featured'))
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state))
                            ->badge(),
                        TextEntry::make('is_active')
                            ->label(__('admin.fields.active'))
                            ->formatStateUsing(fn (bool $state): string => admin_yes_no_label($state))
                            ->badge(),
                        SpatieMediaLibraryImageEntry::make(Project::IMAGE_COLLECTION)
                            ->label(__('admin.fields.project_image'))
                            ->collection(Project::IMAGE_COLLECTION)
                            ->conversion(Project::IMAGE_CONVERSION)
                            ->imageSize(240)
                            ->columnSpan(2),
                        SpatieMediaLibraryImageEntry::make(Project::LOGO_COLLECTION)
                            ->label(__('admin.fields.project_logo'))
                            ->collection(Project::LOGO_COLLECTION)
                            ->conversion(Project::LOGO_CONVERSION)
                            ->imageSize(160)
                            ->columnSpan(2),
                    ]),
            ]);
    }
}
