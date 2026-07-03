<?php

namespace App\Filament\Resources\Guides\Schemas;

use App\Filament\Components\TranslatableTabs;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GuideForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);
        $translationsTabsSchema = [];

        foreach ($locales as $locale) {
            $translationsTabsSchema[$locale] = [
                TextInput::make("title.{$locale}")
                    ->label(__('admin.fields.title'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make("description.{$locale}")
                    ->label(__('admin.fields.description'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(4)
                    ->columnSpanFull(),
            ];
        }

        return $schema
            ->columns(3)
            ->components([
                Section::make(__('admin.sections.translations'))
                    ->schema([
                        TranslatableTabs::make($translationsTabsSchema, columns: 2),
                    ])
                    ->columnSpan(2),
                Section::make(__('admin.sections.main_details'))
                    ->schema([
                        Toggle::make('is_draft')
                            ->label(__('admin.fields.draft'))
                            ->required()
                            ->default(true),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.active'))
                            ->required()
                            ->default(true),
                        SpatieMediaLibraryFileUpload::make('cover_image')
                            ->label(__('admin.fields.cover_image'))
                            ->collection('cover_image')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->helperText(__('admin.hints.guide_cover_ratio'))
                            ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('guide_file')
                            ->label(__('admin.fields.guide_file'))
                            ->collection('guide_file')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->helperText(__('admin.hints.guide_file'))
                            ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
