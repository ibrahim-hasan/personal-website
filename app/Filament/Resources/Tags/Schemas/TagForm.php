<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Components\TranslatableTabs;
use App\Support\LocaleSlugger;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagForm
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
                // ->live(onBlur: true)
                // ->afterStateUpdated(function ($state, callable $set) use ($locale): void {
                //     if (filled($state)) {
                //         $set("slug.{$locale}", LocaleSlugger::generate((string) $state, $locale));
                //     }
                // }),
                // TextInput::make("slug.{$locale}")
                //     ->label(__('admin.fields.slug'))
                //     ->required()
                //     ->maxLength(255)
                //     ->disabled()
                //     ->dehydrated(),
            ];
        }

        return $schema
            ->columns(3)
            ->components([
                Section::make(__('admin.sections.translations'))
                    ->schema([
                        TranslatableTabs::make($translationsTabsSchema, columns: 2),
                    ])->columnSpan(2),

                Section::make(__('admin.sections.main_details'))
                    ->schema([
                        ColorPicker::make('color')
                            ->label(__('admin.fields.color'))
                            ->default('#E8F5E9'),
                        TextInput::make('order_column')
                            ->label(__('admin.fields.order_column'))
                            ->numeric(),
                    ]),
            ]);
    }
}
