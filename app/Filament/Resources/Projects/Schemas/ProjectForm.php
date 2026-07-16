<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Components\TranslatableTabs;
use App\Models\Project;
use App\Support\LocaleSlugger;
use App\Support\PortfolioAtlas;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        $translationTabs = [];

        foreach (config('translatable.locales', ['ar', 'en']) as $locale) {
            $translationTabs[$locale] = [
                TextInput::make("title.{$locale}")
                    ->label(__('admin.fields.title'))
                    ->required()
                    ->maxLength(160)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, callable $set) use ($locale): void {
                        if (filled($state) && blank($get("slug.{$locale}"))) {
                            $set("slug.{$locale}", LocaleSlugger::generate((string) $state, $locale));
                        }
                    }),
                TextInput::make("slug.{$locale}")
                    ->label(__('admin.fields.slug'))
                    ->required()
                    ->unique(Project::class, "slug_{$locale}", ignoreRecord: true)
                    ->regex('/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
                    ->maxLength(180),
                TextInput::make("sector.{$locale}")
                    ->label(__('admin.fields.sector'))
                    ->required()
                    ->maxLength(120),
                Textarea::make("summary.{$locale}")
                    ->label(__('admin.fields.summary'))
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("challenge.{$locale}")
                    ->label(__('admin.fields.challenge'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make("response.{$locale}")
                    ->label(__('admin.fields.response'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make("outcome.{$locale}")
                    ->label(__('admin.fields.outcome'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make("image_alt.{$locale}")
                    ->label(__('admin.fields.image_alt'))
                    ->required()
                    ->maxLength(180),
                TextInput::make("logo_alt.{$locale}")
                    ->label(__('admin.fields.logo_alt'))
                    ->maxLength(180),
            ];
        }

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make(__('admin.sections.project_story'))
                        ->schema([
                            TranslatableTabs::make($translationTabs),
                        ]),
                    Section::make(__('admin.sections.project_tags'))
                        ->schema([
                            Repeater::make('tags')
                                ->label(__('admin.fields.tags'))
                                ->schema([
                                    TextInput::make('ar')
                                        ->label(__('admin.locales.ar'))
                                        ->required(),
                                    TextInput::make('en')
                                        ->label(__('admin.locales.en'))
                                        ->required(),
                                ])
                                ->columns(2)
                                ->minItems(1)
                                ->reorderable()
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(2),
                Group::make([
                    Section::make(__('admin.sections.publishing'))
                        ->schema([
                            TextInput::make('key')
                                ->label(__('admin.fields.key'))
                                ->required()
                                ->alphaDash()
                                ->maxLength(80)
                                ->disabledOn('edit')
                                ->unique(ignoreRecord: true),
                            Select::make('lens')
                                ->label(__('admin.fields.lens'))
                                ->options(fn (): array => collect(PortfolioAtlas::lenses())->pluck('label', 'id')->all())
                                ->required(),
                            TextInput::make('sort_order')
                                ->label(__('admin.fields.sort_order'))
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                            Toggle::make('featured')
                                ->label(__('admin.fields.featured')),
                            Toggle::make('is_active')
                                ->label(__('admin.fields.active'))
                                ->default(true),
                        ]),
                    Section::make(__('admin.sections.media'))
                        ->schema([
                            SpatieMediaLibraryFileUpload::make(Project::IMAGE_COLLECTION)
                                ->label(__('admin.fields.project_image'))
                                ->collection(Project::IMAGE_COLLECTION)
                                ->conversion(Project::THUMBNAIL_CONVERSION)
                                ->image()
                                ->imageEditor()
                                ->responsiveImages()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                ->maxSize(8192)
                                ->helperText(__('admin.hints.project_image_upload'))
                                ->required(fn (?Project $record): bool => $record === null || (! $record->hasMedia(Project::IMAGE_COLLECTION) && blank($record->image))),
                            SpatieMediaLibraryFileUpload::make(Project::LOGO_COLLECTION)
                                ->label(__('admin.fields.project_logo'))
                                ->collection(Project::LOGO_COLLECTION)
                                ->conversion(Project::LOGO_CONVERSION)
                                ->image()
                                ->imageEditor()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                ->maxSize(4096)
                                ->helperText(__('admin.hints.project_logo_upload')),
                        ]),
                ])->columnSpan(1),
            ]);
    }
}
