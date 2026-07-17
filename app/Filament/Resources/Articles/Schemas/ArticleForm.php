<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Filament\Components\AiSeoAction;
use App\Filament\Components\TranslatableTabs;
use App\Models\Article;
use App\Support\LocaleSlugger;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        $translationTabs = [];

        foreach (config('translatable.locales', ['ar', 'en']) as $locale) {
            $translationTabs[$locale] = [
                TextInput::make("title.{$locale}")
                    ->label(__('editorial_admin.fields.title'))
                    ->required()
                    ->maxLength(180)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, callable $set) use ($locale): void {
                        if (filled($state) && blank($get("slug.{$locale}"))) {
                            $set("slug.{$locale}", LocaleSlugger::generate((string) $state, $locale));
                        }
                    }),
                TextInput::make("slug.{$locale}")
                    ->label(__('editorial_admin.fields.slug'))
                    ->required()
                    ->unique(Article::class, "slug_{$locale}", ignoreRecord: true)
                    ->regex('/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
                    ->maxLength(180),
                TextInput::make("type.{$locale}")
                    ->label(__('editorial_admin.fields.type'))
                    ->required()
                    ->maxLength(80),
                TextInput::make("read_minutes.{$locale}")
                    ->label(__('editorial_admin.fields.read_minutes'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120),
                Textarea::make("summary.{$locale}")
                    ->label(__('editorial_admin.fields.summary'))
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
                Textarea::make("lead.{$locale}")
                    ->label(__('editorial_admin.fields.lead'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Repeater::make("sections.{$locale}")
                    ->label(__('editorial_admin.fields.sections'))
                    ->schema([
                        TextInput::make('heading')
                            ->label(__('editorial_admin.fields.heading'))
                            ->required()
                            ->maxLength(180),
                        Repeater::make('paragraphs')
                            ->label(__('editorial_admin.fields.paragraphs'))
                            ->simple(
                                Textarea::make('paragraph')
                                    ->required()
                                    ->rows(3),
                            )
                            ->minItems(1)
                            ->columnSpanFull(),
                        Repeater::make('points')
                            ->label(__('editorial_admin.fields.points'))
                            ->simple(
                                TextInput::make('point')
                                    ->maxLength(500),
                            )
                            ->columnSpanFull(),
                        Textarea::make('note')
                            ->label(__('editorial_admin.fields.note'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->minItems(1)
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state): ?string => $state['heading'] ?? null)
                    ->columnSpanFull(),
                Textarea::make("closing.{$locale}")
                    ->label(__('editorial_admin.fields.closing'))
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make("seo_title.{$locale}")
                    ->label(__('editorial_admin.fields.seo_title'))
                    ->required()
                    ->maxLength(70),
                Textarea::make("seo_description.{$locale}")
                    ->label(__('editorial_admin.fields.seo_description'))
                    ->required()
                    ->rows(3)
                    ->maxLength(170),
            ];
        }

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make(__('editorial_admin.sections.content'))
                        ->key('article-content')
                        ->headerActions([
                            AiSeoAction::make(),
                        ])
                        ->schema([
                            TranslatableTabs::make($translationTabs),
                        ]),
                ])->columnSpan(2),
                Group::make([
                    Section::make(__('editorial_admin.sections.publishing'))
                        ->schema([
                            TextInput::make('key')
                                ->label(__('editorial_admin.fields.key'))
                                ->required()
                                ->alphaDash()
                                ->maxLength(80)
                                ->disabledOn('edit')
                                ->unique(ignoreRecord: true),
                            DatePicker::make('published_at')
                                ->label(__('editorial_admin.fields.published_at'))
                                ->required()
                                ->default(today())
                                ->native(false),
                            DatePicker::make('modified_at')
                                ->label(__('editorial_admin.fields.modified_at'))
                                ->required()
                                ->default(today())
                                ->native(false),
                            Toggle::make('is_published')
                                ->label(__('editorial_admin.fields.published'))
                                ->default(false),
                            Toggle::make('featured')
                                ->label(__('editorial_admin.fields.featured'))
                                ->default(false),
                        ]),
                    Section::make(__('editorial_admin.sections.discovery'))
                        ->schema([
                            SpatieMediaLibraryFileUpload::make(Article::IMAGE_COLLECTION)
                                ->label(__('editorial_admin.fields.image_path'))
                                ->collection(Article::IMAGE_COLLECTION)
                                ->conversion(Article::THUMBNAIL_CONVERSION)
                                ->visibility('public')
                                ->image()
                                ->imageEditor()
                                ->responsiveImages()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                ->maxSize(8192)
                                ->required(fn (?Article $record): bool => $record === null || (! $record->hasMedia(Article::IMAGE_COLLECTION) && blank($record->image)))
                                ->helperText(__('editorial_admin.hints.image_upload')),
                            TagsInput::make('topic_keys')
                                ->label(__('editorial_admin.fields.topics'))
                                ->required(),
                            TextInput::make('source_url')
                                ->label(__('editorial_admin.fields.source_url'))
                                ->url(),
                        ]),
                ])->columnSpan(1),
            ]);
    }
}
