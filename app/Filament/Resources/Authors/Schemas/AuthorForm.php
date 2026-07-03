<?php

namespace App\Filament\Resources\Authors\Schemas;

use App\Filament\Components\TranslatableTabs;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);
        $translationsTabsSchema = [];

        foreach ($locales as $locale) {
            $translationsTabsSchema[$locale] = [
                TextInput::make("name.{$locale}")
                    ->label(__('admin.fields.name'))
                    ->placeholder(__('Enter field', ['name' => __('Name')]))
                    ->required()
                    ->maxLength(255),
                TextInput::make("position.{$locale}")
                    ->label(__('admin.fields.position'))
                    ->placeholder(__('Enter field', ['name' => __('Position')]))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->maxLength(255),
                TextInput::make("description.{$locale}")
                    ->label(__('admin.fields.description'))
                    ->placeholder(__('Enter field', ['name' => __('Description')]))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->maxLength(255),
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
                        Toggle::make('is_active')
                            ->label(__('admin.fields.active'))
                            ->required()
                            ->default(true),
                        Toggle::make('is_draft')
                            ->label(__('admin.fields.draft'))
                            ->required()
                            ->default(true),
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->label(__('admin.fields.avatar'))
                            ->collection('avatar')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
