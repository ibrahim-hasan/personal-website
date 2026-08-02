<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Components\TranslatableTabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                    ->maxLength(255)
                    ->live(onBlur: true),
                Textarea::make("summary.{$locale}")
                    ->label(__('admin.fields.summary'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("problem.{$locale}")
                    ->label(__('admin.fields.problem'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("approach.{$locale}")
                    ->label(__('admin.fields.approach'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("result.{$locale}")
                    ->label(__('admin.fields.result'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make("fit_signals.{$locale}")
                    ->label(__('service_admin.fields.fit_signals'))
                    ->helperText(__('service_admin.hints.fit_signals'))
                    ->formatStateUsing(fn (mixed $state): string => is_array($state)
                        ? collect($state)->filter(fn (mixed $signal): bool => is_string($signal) && trim($signal) !== '')->implode(PHP_EOL)
                        : '')
                    ->dehydrateStateUsing(fn (mixed $state): array => collect(preg_split('/\R/u', (string) $state) ?: [])
                        ->map(fn (string $signal): string => trim($signal))
                        ->filter()
                        ->values()
                        ->all())
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make("engagement_note.{$locale}")
                    ->label(__('service_admin.fields.engagement_note'))
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
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),
                Section::make(__('admin.sections.main_details'))
                    ->schema([
                        TextInput::make('key')
                            ->label(__('admin.fields.key'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(80)
                            ->disabledOn('edit')
                            ->unique(ignoreRecord: true),
                        TextInput::make('order')
                            ->label(__('admin.fields.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default(0),
                    ]),
            ]);
    }
}
