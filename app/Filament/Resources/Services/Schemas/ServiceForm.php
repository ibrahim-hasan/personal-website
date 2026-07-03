<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Components\TranslatableTabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);
        $translationsTabsSchema = [];

        foreach ($locales as $locale) {
            $translationsTabsSchema[$locale] = [
                TextInput::make("name.{$locale}")
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make("problems_you_are_facing.{$locale}")
                    ->label(__('admin.fields.problems_you_are_facing'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("how_can_we_help.{$locale}")
                    ->label(__('admin.fields.how_can_we_help'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("type_of_intervention.{$locale}")
                    ->label(__('admin.fields.type_of_intervention'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("results.{$locale}")
                    ->label(__('admin.fields.results'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
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
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('admin.fields.active'))
                            ->required()
                            ->default(true),
                    ]),
            ]);
    }
}
