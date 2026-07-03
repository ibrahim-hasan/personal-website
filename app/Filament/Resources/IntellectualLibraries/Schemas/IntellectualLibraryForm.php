<?php

namespace App\Filament\Resources\IntellectualLibraries\Schemas;

use App\Enums\IntellectualLibraryType;
use App\Filament\Components\AiSeoAction;
use App\Filament\Components\TranslatableTabs;
use App\Models\Author;
use App\Support\LocaleSlugger;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Tags\Tag;

class IntellectualLibraryForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('translatable.locales', ['ar', 'en']);

        $contentTabsSchema = [];
        $seoTabsSchema = [];

        foreach ($locales as $locale) {
            $contentTabsSchema[$locale] = [
                TextInput::make("name.{$locale}")
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) use ($locale): void {
                        if (filled($state)) {
                            $set("slug.{$locale}", LocaleSlugger::generate((string) $state, $locale));
                        }
                    }),
                TextInput::make("slug.{$locale}")
                    ->label(__('admin.fields.slug'))
                    ->required()
                    ->maxLength(255)
                    ->disabled()
                    ->dehydrated(),
                Textarea::make("excert.{$locale}")
                    ->label(__('admin.fields.excerpt'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->rows(3)
                    ->columnSpanFull(),
                RichEditor::make("content.{$locale}")
                    ->label(__('admin.fields.content'))
                    ->required(fn (Get $get): bool => ! (bool) $get('is_draft'))
                    ->columnSpanFull(),
            ];

            $seoTabsSchema[$locale] = [
                TextInput::make("seo_title.{$locale}")
                    ->label(__('admin.fields.seo_title'))
                    ->maxLength(255),
                Textarea::make("seo_description.{$locale}")
                    ->label(__('admin.fields.seo_description'))
                    ->rows(3),
            ];
        }

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make(__('admin.sections.content'))
                        ->schema([
                            TranslatableTabs::make($contentTabsSchema, columns: 2),
                        ]),
                    Section::make(__('admin.sections.seo'))
                        ->schema([
                            TranslatableTabs::make($seoTabsSchema, columns: 2),
                            SpatieMediaLibraryFileUpload::make('og_image')
                                ->label(__('admin.fields.og_image'))
                                ->collection('og_image')
                                ->image()
                                ->imageEditor()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText(__('admin.hints.og_ratio'))
                                ->maxSize(2048)
                                ->columnSpanFull(),
                        ])
                        ->afterHeader([
                            AiSeoAction::make('admin'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed(),
                ])->columnSpan(2),
                Group::make([
                    Section::make(__('admin.sections.main_details'))
                        ->schema([
                            Select::make('type')
                                ->label(__('admin.fields.type'))
                                ->options(collect(IntellectualLibraryType::cases())->mapWithKeys(
                                    fn (IntellectualLibraryType $type): array => [$type->value => $type->label()],
                                ))
                                ->default(IntellectualLibraryType::Article->value)
                                ->required()
                                ->native(false)
                                ->live(),
                            Select::make('author_id')
                                ->label(__('admin.fields.author'))
                                ->relationship('author', 'name', fn ($query) => $query->with('media'))
                                ->getOptionLabelFromRecordUsing(
                                    fn (Author $record): string => sprintf(
                                        '<span class="flex items-center gap-2"><img src="%s" alt="%s" class="h-6 w-6 rounded-full object-cover" /> <span>%s</span></span>',
                                        $record->getFirstMediaUrl('avatar') ?: asset('images/placeholder.png'),
                                        e((string) ($record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en') ?: $record->id)),
                                        e((string) ($record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en') ?: $record->id))
                                    )
                                )
                                ->allowHtml()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false),
                            Select::make('tags')
                                ->label(__('admin.fields.tags'))
                                ->relationship('tags', 'name')
                                ->getOptionLabelFromRecordUsing(
                                    fn (Tag $record): string => (string) ($record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en') ?: $record->name)
                                )
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->native(false)
                                ->columnSpanFull(),
                            TextInput::make('reading_time')
                                ->label(__('admin.fields.reading_time_minutes'))
                                ->numeric()
                                ->required(fn (Get $get): bool => ! (bool) $get('is_draft')),
                            TextInput::make('video_length')
                                ->label(__('admin.fields.video_podcast_length'))
                                ->default('00:00')
                                ->required(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true) && ! (bool) $get('is_draft')),
                            TextInput::make('youtube_url')
                                ->label(__('admin.fields.youtube_url'))
                                ->url()
                                ->prefixIcon('phosphor-youtube-logo-light')
                                ->extraAttributes(['class' => 'ltr'])
                                ->placeholder('https://www.youtube.com/watch?v=...')
                                ->visible(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true))
                                ->required(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true) && ! (bool) $get('is_draft'))
                                ->columnSpanFull(),
                            TextInput::make('views')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2),
                    Section::make(__('admin.sections.publishing'))
                        ->schema([
                            Radio::make('publish_mode')
                                ->label(__('admin.fields.publish_mode'))
                                ->options([
                                    'immediate' => __('admin.options.immediate'),
                                    'scheduled' => __('admin.options.scheduled'),
                                ])
                                ->default('immediate')
                                ->live()
                                ->afterStateHydrated(function (Get $get, callable $set): void {
                                    $set('publish_mode', filled($get('scheduled_at')) ? 'scheduled' : 'immediate');
                                })
                                ->afterStateUpdated(function (?string $state, callable $set): void {
                                    if ($state === 'immediate') {
                                        $set('scheduled_at', null);
                                    }
                                })
                                ->inline(),
                            Placeholder::make('publish_hint')
                                ->label(__('admin.fields.publishing_date'))
                                ->content(__('admin.hints.scheduled_publishing'))
                                ->visible(fn (Get $get): bool => $get('publish_mode') !== 'scheduled'),
                            DateTimePicker::make('scheduled_at')
                                ->label(__('admin.fields.scheduled_at'))
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $set('publish_mode', filled($state) ? 'scheduled' : 'immediate');
                                })
                                ->visible(fn (Get $get): bool => $get('publish_mode') === 'scheduled'),
                            Toggle::make('is_draft')
                                ->label(__('admin.fields.is_draft'))
                                ->default(true)
                                ->required(),
                            Toggle::make('is_active')
                                ->label(__('admin.fields.is_active'))
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),
                    Section::make(__('admin.sections.media'))
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('featured_image')
                                ->label(__('admin.fields.featured_image'))
                                ->collection('featured_image')
                                ->image()
                                ->imageEditor()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(2048)
                                ->helperText(__('admin.hints.featured_ratio'))
                                ->visible(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Article->value, IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true))
                                ->required(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Article->value, IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true) && ! (bool) $get('is_draft'))
                                ->columnSpanFull(),
                            SpatieMediaLibraryFileUpload::make('cover_image')
                                ->label(__('admin.fields.cover_image'))
                                ->collection('cover_image')
                                ->image()
                                ->imageEditor()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(2048)
                                ->helperText(__('admin.hints.cover_ratio'))
                                ->visible(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Article->value, IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true))
                                ->required(fn (Get $get): bool => in_array((string) $get('type'), [IntellectualLibraryType::Article->value, IntellectualLibraryType::Video->value, IntellectualLibraryType::Podcast->value], true) && ! (bool) $get('is_draft'))
                                ->columnSpanFull(),
                            SpatieMediaLibraryFileUpload::make('tool_file')
                                ->collection('tool_file')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(10240)
                                ->helperText(__('admin.hints.tool_file'))
                                ->visible(fn (Get $get): bool => $get('type') === IntellectualLibraryType::Tool->value)
                                ->required(fn (Get $get): bool => $get('type') === IntellectualLibraryType::Tool->value && ! (bool) $get('is_draft'))
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])->columnSpan(1),
            ]);
    }
}
