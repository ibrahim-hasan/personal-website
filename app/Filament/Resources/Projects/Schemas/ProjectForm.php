<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectPermissionStatus;
use App\Filament\Components\TranslatableTabs;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use App\Support\LocaleSlugger;
use App\Support\PortfolioAtlas;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        $storyTabs = [];
        $caseStudyTabs = [];
        $disclosureTabs = [];
        $mediaTabs = [];

        foreach (config('translatable.locales', ['ar', 'en']) as $locale) {
            $storyTabs[$locale] = self::storyFields($locale);
            $caseStudyTabs[$locale] = self::caseStudyFields($locale);
            $disclosureTabs[$locale] = self::disclosureFields($locale);
            $mediaTabs[$locale] = self::mediaFields($locale);
        }

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make(__('project_admin.sections.story'))
                        ->schema([
                            TranslatableTabs::make($storyTabs),
                        ]),
                    Section::make(__('project_admin.sections.case_study_details'))
                        ->description(__('project_admin.hints.case_study_sections'))
                        ->schema([
                            TranslatableTabs::make($caseStudyTabs),
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
                    Section::make(__('project_admin.sections.disclosure_permission'))
                        ->columns(2)
                        ->schema([
                            Select::make('delivery_entity')
                                ->label(__('project_admin.fields.delivery_entity'))
                                ->options(self::deliveryEntityOptions()),
                            Select::make('disclosure_level')
                                ->label(__('project_admin.fields.disclosure_level'))
                                ->options(self::disclosureLevelOptions()),
                            Select::make('evidence_level')
                                ->label(__('project_admin.fields.evidence_level'))
                                ->options(self::evidenceLevelOptions()),
                            Select::make('permission_status')
                                ->label(__('project_admin.fields.permission_status'))
                                ->options(self::permissionStatusOptions())
                                ->default(ProjectPermissionStatus::Unreviewed->value),
                            DateTimePicker::make('case_study_reviewed_at')
                                ->label(__('project_admin.fields.case_study_reviewed_at'))
                                ->seconds(false),
                            Textarea::make('permission_reference')
                                ->label(__('project_admin.fields.permission_reference'))
                                ->rows(2)
                                ->maxLength(2000)
                                ->visible(fn (): bool => self::mayManagePrivateReferences())
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                            TranslatableTabs::make($disclosureTabs),
                        ]),
                    Section::make(__('project_admin.sections.evidence_relations'))
                        ->description(__('project_admin.hints.related_content'))
                        ->schema([
                            Select::make('services')
                                ->label(__('project_admin.fields.related_services'))
                                ->relationship(name: 'services', titleAttribute: 'key')
                                ->getOptionLabelFromRecordUsing(fn (Service $record): string => sprintf('%s — %s', localized_model_attribute($record, 'name'), $record->key))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->saveRelationshipsUsing(function (Project $record, ?array $state): void {
                                    $relationships = collect($state ?? [])
                                        ->values()
                                        ->mapWithKeys(fn (mixed $serviceId, int $index): array => [(int) $serviceId => ['sort_order' => $index]])
                                        ->all();

                                    $record->services()->sync($relationships);
                                }),
                            Select::make('articles')
                                ->label(__('project_admin.fields.related_articles'))
                                ->relationship(name: 'articles', titleAttribute: 'key')
                                ->getOptionLabelFromRecordUsing(fn (Article $record): string => sprintf('%s — %s', localized_model_attribute($record, 'title'), $record->key))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->saveRelationshipsUsing(function (Project $record, ?array $state): void {
                                    $relationships = collect($state ?? [])
                                        ->values()
                                        ->mapWithKeys(fn (mixed $articleId, int $index): array => [(int) $articleId => ['sort_order' => $index]])
                                        ->all();

                                    $record->articles()->sync($relationships);
                                }),
                        ]),
                    Section::make(__('project_admin.sections.media'))
                        ->columns(2)
                        ->schema([
                            Select::make('image_permission_status')
                                ->label(__('project_admin.fields.image_permission_status'))
                                ->options(self::assetPermissionStatusOptions())
                                ->default(ProjectAssetPermissionStatus::Unreviewed->value),
                            Select::make('logo_permission_status')
                                ->label(__('project_admin.fields.logo_permission_status'))
                                ->options(self::assetPermissionStatusOptions())
                                ->default(ProjectAssetPermissionStatus::Unreviewed->value),
                            Textarea::make('image_permission_reference')
                                ->label(__('project_admin.fields.image_permission_reference'))
                                ->rows(2)
                                ->maxLength(2000)
                                ->visible(fn (): bool => self::mayManagePrivateReferences())
                                ->dehydratedWhenHidden(false),
                            Textarea::make('logo_permission_reference')
                                ->label(__('project_admin.fields.logo_permission_reference'))
                                ->rows(2)
                                ->maxLength(2000)
                                ->visible(fn (): bool => self::mayManagePrivateReferences())
                                ->dehydratedWhenHidden(false),
                            SpatieMediaLibraryFileUpload::make(Project::IMAGE_COLLECTION)
                                ->label(__('admin.fields.project_image'))
                                ->collection(Project::IMAGE_COLLECTION)
                                ->conversion(Project::THUMBNAIL_CONVERSION)
                                ->visibility('public')
                                ->image()
                                ->imageEditor()
                                ->responsiveImages()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                ->maxSize(8192)
                                ->helperText(__('admin.hints.project_image_upload'))
                                ->required(fn (?Project $record): bool => $record === null || (! $record->hasMedia(Project::IMAGE_COLLECTION) && blank($record->image)))
                                ->columnSpanFull(),
                            SpatieMediaLibraryFileUpload::make(Project::LOGO_COLLECTION)
                                ->label(__('admin.fields.project_logo'))
                                ->collection(Project::LOGO_COLLECTION)
                                ->conversion(Project::LOGO_CONVERSION)
                                ->visibility('public')
                                ->image()
                                ->imageEditor()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                ->maxSize(4096)
                                ->helperText(__('admin.hints.project_logo_upload'))
                                ->columnSpanFull(),
                            TranslatableTabs::make($mediaTabs),
                        ]),
                    Section::make(__('project_admin.sections.publishing'))
                        ->description(__('project_admin.hints.case_study_status'))
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
                                ->helperText(__('project_admin.hints.active_project'))
                                ->default(true),
                        ]),
                ])->columnSpan(1),
            ]);
    }

    /** @return array<int, Component> */
    private static function storyFields(string $locale): array
    {
        return [
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
        ];
    }

    /** @return array<int, Component> */
    private static function caseStudyFields(string $locale): array
    {
        return [
            Textarea::make("case_study_sections.{$locale}.executive_summary")
                ->label(__('project_admin.fields.executive_summary'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
            Textarea::make("case_study_sections.{$locale}.context")
                ->label(__('project_admin.fields.context'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
            Repeater::make("case_study_sections.{$locale}.constraints")
                ->label(__('project_admin.fields.constraints'))
                ->simple(
                    Textarea::make('constraint')
                        ->rows(2)
                        ->maxLength(1000),
                )
                ->reorderable()
                ->columnSpanFull(),
            Repeater::make("case_study_sections.{$locale}.changes")
                ->label(__('project_admin.fields.changes'))
                ->schema([
                    Select::make('area')
                        ->label(__('project_admin.fields.change_area'))
                        ->options(self::changeAreaOptions())
                        ->required(),
                    Textarea::make('body')
                        ->label(__('project_admin.fields.change_body'))
                        ->rows(3)
                        ->maxLength(2000)
                        ->required(),
                ])
                ->columns(2)
                ->reorderable()
                ->columnSpanFull(),
            Textarea::make("case_study_sections.{$locale}.solution")
                ->label(__('project_admin.fields.solution'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
            Textarea::make("case_study_sections.{$locale}.implementation")
                ->label(__('project_admin.fields.implementation'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
            Textarea::make("case_study_sections.{$locale}.adoption")
                ->label(__('project_admin.fields.adoption'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
            Repeater::make("case_study_sections.{$locale}.lessons")
                ->label(__('project_admin.fields.lessons'))
                ->simple(
                    Textarea::make('lesson')
                        ->rows(2)
                        ->maxLength(1000),
                )
                ->reorderable()
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Component> */
    private static function disclosureFields(string $locale): array
    {
        return [
            Textarea::make("ibrahim_role.{$locale}")
                ->label(__('project_admin.fields.ibrahim_role'))
                ->rows(2)
                ->maxLength(1000)
                ->columnSpanFull(),
            TextInput::make("delivery_period.{$locale}")
                ->label(__('project_admin.fields.delivery_period'))
                ->maxLength(160),
            Textarea::make("confidentiality_note.{$locale}")
                ->label(__('project_admin.fields.confidentiality_note'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Component> */
    private static function mediaFields(string $locale): array
    {
        return [
            TextInput::make("image_alt.{$locale}")
                ->label(__('admin.fields.image_alt'))
                ->required()
                ->maxLength(180),
            TextInput::make("logo_alt.{$locale}")
                ->label(__('admin.fields.logo_alt'))
                ->maxLength(180),
        ];
    }

    /** @return array<string, string> */
    private static function deliveryEntityOptions(): array
    {
        return collect(ProjectDeliveryEntity::cases())
            ->mapWithKeys(fn (ProjectDeliveryEntity $entity): array => [$entity->value => __('project_admin.delivery_entities.'.$entity->value)])
            ->all();
    }

    /** @return array<string, string> */
    private static function disclosureLevelOptions(): array
    {
        return collect(ProjectDisclosureLevel::cases())
            ->mapWithKeys(fn (ProjectDisclosureLevel $level): array => [$level->value => __('project_admin.disclosure_levels.'.$level->value)])
            ->all();
    }

    /** @return array<string, string> */
    private static function evidenceLevelOptions(): array
    {
        return collect(ProjectEvidenceLevel::cases())
            ->mapWithKeys(fn (ProjectEvidenceLevel $level): array => [$level->value => __('project_admin.evidence_levels.'.$level->value)])
            ->all();
    }

    /** @return array<string, string> */
    private static function permissionStatusOptions(): array
    {
        return collect(ProjectPermissionStatus::cases())
            ->mapWithKeys(fn (ProjectPermissionStatus $status): array => [$status->value => __('project_admin.permission_statuses.'.$status->value)])
            ->all();
    }

    /** @return array<string, string> */
    private static function assetPermissionStatusOptions(): array
    {
        return collect(ProjectAssetPermissionStatus::cases())
            ->mapWithKeys(fn (ProjectAssetPermissionStatus $status): array => [$status->value => __('project_admin.asset_permission_statuses.'.$status->value)])
            ->all();
    }

    /** @return array<string, string> */
    private static function changeAreaOptions(): array
    {
        return [
            'workflow' => __('project_admin.change_areas.workflow'),
            'ownership' => __('project_admin.change_areas.ownership'),
            'data' => __('project_admin.change_areas.data'),
            'system' => __('project_admin.change_areas.system'),
            'controls' => __('project_admin.change_areas.controls'),
        ];
    }

    private static function mayManagePrivateReferences(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('publish projects') || $user->can('approve project_evidence'));
    }
}
