<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Actions\Projects\TransitionProjectEvidence;
use App\Enums\ProjectEvidenceKind;
use App\Enums\ProjectEvidenceState;
use App\Filament\Components\TranslatableTabs;
use App\Models\ProjectEvidence;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class ProjectEvidenceRelationManager extends RelationManager
{
    protected static string $relationship = 'evidence';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('project_admin.sections.evidence');
    }

    public function form(Schema $schema): Schema
    {
        $translations = [];

        foreach (config('translatable.locales', ['ar', 'en']) as $locale) {
            $translations[$locale] = [
                TextInput::make("label.{$locale}")
                    ->label(__('project_admin.fields.evidence_label'))
                    ->required()
                    ->maxLength(160),
                Textarea::make("result_text.{$locale}")
                    ->label(__('project_admin.fields.result_text'))
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                TextInput::make("baseline_period.{$locale}")
                    ->label(__('project_admin.fields.baseline_period'))
                    ->maxLength(160),
                TextInput::make("result_period.{$locale}")
                    ->label(__('project_admin.fields.result_period'))
                    ->maxLength(160),
                Textarea::make("method.{$locale}")
                    ->label(__('project_admin.fields.method'))
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Textarea::make("scope.{$locale}")
                    ->label(__('project_admin.fields.scope'))
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ];
        }

        return $schema
            ->components([
                Section::make(__('project_admin.sections.evidence'))
                    ->columns(2)
                    ->schema([
                        Select::make('kind')
                            ->label(__('project_admin.fields.evidence_kind'))
                            ->options(self::kindOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                if (self::isQualitative($state)) {
                                    foreach (self::quantitativeAttributes() as $attribute) {
                                        $set($attribute, null);
                                    }

                                    return;
                                }

                                if (self::isKind($state, ProjectEvidenceKind::Exact)) {
                                    $set('range_min', null);
                                    $set('range_max', null);
                                    $set('threshold_value', null);

                                    return;
                                }

                                if (self::isKind($state, ProjectEvidenceKind::Range)) {
                                    $set('baseline_value', null);
                                    $set('result_value', null);
                                    $set('threshold_value', null);

                                    return;
                                }

                                if (self::isKind($state, ProjectEvidenceKind::Threshold)) {
                                    $set('baseline_value', null);
                                    $set('result_value', null);
                                    $set('range_min', null);
                                    $set('range_max', null);
                                }
                            }),
                        TextInput::make('sort_order')
                            ->label(__('project_admin.fields.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TranslatableTabs::make($translations),
                    ]),
                Section::make(__('project_admin.sections.measurement'))
                    ->description(__('project_admin.hints.measurement'))
                    ->columns(3)
                    ->visible(fn (Get $get): bool => self::isQuantitative($get('kind')))
                    ->schema([
                        TextInput::make('baseline_value')
                            ->label(__('project_admin.fields.baseline_value'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => self::isKind($get('kind'), ProjectEvidenceKind::Exact))
                            ->step('0.000001'),
                        TextInput::make('result_value')
                            ->label(__('project_admin.fields.result_value'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => self::isKind($get('kind'), ProjectEvidenceKind::Exact))
                            ->step('0.000001'),
                        TextInput::make('unit')
                            ->label(__('project_admin.fields.unit'))
                            ->maxLength(40),
                        TextInput::make('range_min')
                            ->label(__('project_admin.fields.range_min'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => self::isKind($get('kind'), ProjectEvidenceKind::Range))
                            ->step('0.000001'),
                        TextInput::make('range_max')
                            ->label(__('project_admin.fields.range_max'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => self::isKind($get('kind'), ProjectEvidenceKind::Range))
                            ->step('0.000001'),
                        TextInput::make('threshold_value')
                            ->label(__('project_admin.fields.threshold_value'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => self::isKind($get('kind'), ProjectEvidenceKind::Threshold))
                            ->step('0.000001'),
                        Select::make('direction')
                            ->label(__('project_admin.fields.direction'))
                            ->options(self::directionOptions()),
                    ]),
                Section::make(__('project_admin.sections.private_references'))
                    ->description(__('project_admin.hints.private_references'))
                    ->columns(2)
                    ->visible(fn (): bool => self::mayManagePrivateReferences())
                    ->schema([
                        Textarea::make('source_owner')
                            ->label(__('project_admin.fields.source_owner'))
                            ->rows(2)
                            ->maxLength(2000)
                            ->dehydratedWhenHidden(false),
                        Textarea::make('source_reference')
                            ->label(__('project_admin.fields.source_reference'))
                            ->rows(2)
                            ->maxLength(2000)
                            ->dehydratedWhenHidden(false),
                        Textarea::make('permission_reference')
                            ->label(__('project_admin.fields.evidence_permission_reference'))
                            ->rows(2)
                            ->maxLength(2000)
                            ->dehydratedWhenHidden(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->label(__('project_admin.fields.evidence_label'))
                    ->getStateUsing(fn (ProjectEvidence $record): ?string => localized_model_attribute($record, 'label'))
                    ->wrap()
                    ->searchable(['label']),
                TextColumn::make('kind')
                    ->label(__('project_admin.fields.evidence_kind'))
                    ->badge()
                    ->formatStateUsing(fn (ProjectEvidenceKind $state): string => self::kindLabel($state)),
                TextColumn::make('state')
                    ->label(__('project_admin.fields.evidence_state'))
                    ->badge()
                    ->formatStateUsing(fn (ProjectEvidenceState $state): string => self::stateLabel($state))
                    ->color(fn (ProjectEvidenceState $state): string => $state->color()),
                IconColumn::make('is_public')
                    ->label(__('project_admin.fields.publicly_visible'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->since()
                    ->sortable(),
            ])
            ->reorderable('sort_order')
            ->authorizeReorder(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) === true)
            ->headerActions([
                CreateAction::make()->authorize('create'),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize('update')
                    ->visible(fn (ProjectEvidence $record): bool => in_array($record->state, [ProjectEvidenceState::Draft, ProjectEvidenceState::Rejected], true)),
                Action::make('verify')
                    ->label(__('project_admin.actions.verify_evidence'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize('verify')
                    ->visible(fn (ProjectEvidence $record): bool => $record->state === ProjectEvidenceState::Draft)
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->verify($this->actor(), $record);
                        $this->notify(__('project_admin.messages.evidence_verified'));
                    }),
                Action::make('approve')
                    ->label(__('project_admin.actions.approve_evidence'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(fn (ProjectEvidence $record): bool => $record->state === ProjectEvidenceState::Verified)
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->approve($this->actor(), $record);
                        $this->notify(__('project_admin.messages.evidence_approved'));
                    }),
                Action::make('reject')
                    ->label(__('project_admin.actions.reject_evidence'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('reject')
                    ->visible(fn (ProjectEvidence $record): bool => in_array($record->state, [ProjectEvidenceState::Draft, ProjectEvidenceState::Verified], true))
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->reject($this->actor(), $record);
                        $this->notify(__('project_admin.messages.evidence_rejected'));
                    }),
                Action::make('return_to_draft')
                    ->label(__('project_admin.actions.return_evidence_to_draft'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->visible(fn (ProjectEvidence $record): bool => $record->state === ProjectEvidenceState::Rejected)
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->draft($this->actor(), $record);
                        $this->notify(__('project_admin.messages.evidence_drafted'));
                    }),
                Action::make('make_public')
                    ->label(__('project_admin.actions.make_evidence_public'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('setPublicVisibility')
                    ->visible(fn (ProjectEvidence $record): bool => $record->state === ProjectEvidenceState::Approved && ! $record->is_public)
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->setPublicVisibility($this->actor(), $record, true);
                        $this->notify(__('project_admin.messages.evidence_public'));
                    }),
                Action::make('hide_from_public')
                    ->label(__('project_admin.actions.hide_evidence_from_public'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->authorize('setPublicVisibility')
                    ->visible(fn (ProjectEvidence $record): bool => $record->is_public)
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->setPublicVisibility($this->actor(), $record, false);
                        $this->notify(__('project_admin.messages.evidence_hidden'));
                    }),
                Action::make('revoke')
                    ->label(__('project_admin.actions.revoke_evidence'))
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('revoke')
                    ->visible(fn (ProjectEvidence $record): bool => in_array($record->state, [ProjectEvidenceState::Verified, ProjectEvidenceState::Approved], true))
                    ->action(function (ProjectEvidence $record, TransitionProjectEvidence $transition): void {
                        $transition->revoke($this->actor(), $record);
                        $this->notify(__('project_admin.messages.evidence_revoked'));
                    }),
                DeleteAction::make()
                    ->authorize('delete')
                    ->visible(fn (ProjectEvidence $record): bool => in_array($record->state, [ProjectEvidenceState::Draft, ProjectEvidenceState::Rejected], true)),
            ]);
    }

    /** @return array<string, string> */
    private static function kindOptions(): array
    {
        return collect(ProjectEvidenceKind::cases())
            ->mapWithKeys(fn (ProjectEvidenceKind $kind): array => [$kind->value => self::kindLabel($kind)])
            ->all();
    }

    /** @return array<string, string> */
    private static function directionOptions(): array
    {
        return [
            'increase' => __('project_admin.directions.increase'),
            'decrease' => __('project_admin.directions.decrease'),
            'maintain' => __('project_admin.directions.maintain'),
        ];
    }

    /** @return list<string> */
    private static function quantitativeAttributes(): array
    {
        return [
            'baseline_value',
            'result_value',
            'range_min',
            'range_max',
            'threshold_value',
            'unit',
            'direction',
            'baseline_period',
            'result_period',
            'method',
            'scope',
        ];
    }

    private static function isQuantitative(mixed $kind): bool
    {
        $value = $kind instanceof ProjectEvidenceKind ? $kind->value : $kind;

        return in_array($value, [ProjectEvidenceKind::Exact->value, ProjectEvidenceKind::Range->value, ProjectEvidenceKind::Threshold->value], true);
    }

    private static function isQualitative(mixed $kind): bool
    {
        $value = $kind instanceof ProjectEvidenceKind ? $kind->value : $kind;

        return $value === ProjectEvidenceKind::Qualitative->value;
    }

    private static function isKind(mixed $kind, ProjectEvidenceKind $expected): bool
    {
        $value = $kind instanceof ProjectEvidenceKind ? $kind->value : $kind;

        return $value === $expected->value;
    }

    private static function mayManagePrivateReferences(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('approve project_evidence'));
    }

    private static function kindLabel(ProjectEvidenceKind $kind): string
    {
        return __('project_admin.evidence_kinds.'.$kind->value);
    }

    private static function stateLabel(ProjectEvidenceState $state): string
    {
        return __('project_admin.evidence_states.'.$state->value);
    }

    private function actor(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }

    private function notify(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }
}
