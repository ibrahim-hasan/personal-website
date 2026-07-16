<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Components\TranslatableTabs;
use Filament\Forms\Components\Repeater;
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
                Textarea::make("summary.{$locale}")
                    ->label(__('admin.fields.summary'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("problem.{$locale}")
                    ->label(__('admin.fields.problem'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("approach.{$locale}")
                    ->label(__('admin.fields.approach'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("result.{$locale}")
                    ->label(__('admin.fields.result'))
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
                        Repeater::make('deliverables')
                            ->label(__('admin.fields.deliverables'))
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
                    ])
                    ->columnSpan(2),
                Section::make(__('admin.sections.main_details'))
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(80)
                            ->unique(ignoreRecord: true),
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
