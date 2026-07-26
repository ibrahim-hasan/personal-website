<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Components\TranslatableTabs;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Support\LocaleSlugger;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, callable $set) use ($locale): void {
                        if (filled($state) && blank($get("slug.{$locale}"))) {
                            $set("slug.{$locale}", LocaleSlugger::generate((string) $state, $locale));
                        }
                    }),
                TextInput::make("slug.{$locale}")
                    ->label(__('admin.fields.slug'))
                    ->required()
                    ->unique(Service::class, "slug_{$locale}", ignoreRecord: true)
                    ->regex('/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
                    ->maxLength(180),
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
                Repeater::make("fit_signals.{$locale}")
                    ->label(__('service_admin.fields.fit_signals'))
                    ->simple(
                        TextInput::make('signal')
                            ->required()
                            ->maxLength(240),
                    )
                    ->maxItems(4)
                    ->reorderable()
                    ->helperText(__('service_admin.hints.fit_signals'))
                    ->columnSpanFull(),
                Textarea::make("engagement_note.{$locale}")
                    ->label(__('service_admin.fields.engagement_note'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
                TextInput::make("seo_title.{$locale}")
                    ->label(__('service_admin.fields.seo_title'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->maxLength(160)
                    ->columnSpanFull(),
                Textarea::make("seo_description.{$locale}")
                    ->label(__('service_admin.fields.seo_description'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(2)
                    ->maxLength(320)
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
                            ->maxItems(5)
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
                        Hidden::make('is_draft')
                            ->default(true)
                            ->dehydrated(false),
                        Hidden::make('is_active')
                            ->default(false)
                            ->dehydrated(false),
                    ]),
                Section::make(__('service_admin.sections.relationships'))
                    ->description(__('service_admin.hints.related_content'))
                    ->schema([
                        Select::make('projects')
                            ->label(__('service_admin.fields.related_projects'))
                            ->relationship(name: 'projects', titleAttribute: 'key')
                            ->getOptionLabelFromRecordUsing(
                                fn (Project $record): string => sprintf(
                                    '%s — %s',
                                    localized_model_attribute($record, 'title'),
                                    $record->key,
                                ),
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->saveRelationshipsUsing(function (Service $record, ?array $state): void {
                                $relationships = collect($state ?? [])
                                    ->values()
                                    ->mapWithKeys(
                                        fn (mixed $projectId, int $index): array => [(int) $projectId => ['sort_order' => $index]],
                                    )
                                    ->all();

                                $record->projects()->sync($relationships);
                            }),
                        Select::make('articles')
                            ->label(__('service_admin.fields.related_articles'))
                            ->relationship(name: 'articles', titleAttribute: 'key')
                            ->getOptionLabelFromRecordUsing(
                                fn (Article $record): string => sprintf(
                                    '%s — %s',
                                    localized_model_attribute($record, 'title'),
                                    $record->key,
                                ),
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->saveRelationshipsUsing(function (Service $record, ?array $state): void {
                                $relationships = collect($state ?? [])
                                    ->values()
                                    ->mapWithKeys(
                                        fn (mixed $articleId, int $index): array => [(int) $articleId => ['sort_order' => $index]],
                                    )
                                    ->all();

                                $record->articles()->sync($relationships);
                            }),
                    ]),
            ]);
    }
}
