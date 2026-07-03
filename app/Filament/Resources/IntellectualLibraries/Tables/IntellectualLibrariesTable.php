<?php

namespace App\Filament\Resources\IntellectualLibraries\Tables;

use App\Enums\IntellectualLibraryType;
use App\Filament\Tables\Columns\LimitedManyToManyBadgesColumn;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Author;
use App\Models\IntellectualLibrary;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class IntellectualLibrariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover_image')
                    ->label(__('admin.fields.cover'))
                    ->collection('cover_image')
                    ->defaultImageUrl(asset('images/placeholder.png'))
                    ->circular(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (?IntellectualLibraryType $state): ?string => $state?->label())
                    ->color(fn (?IntellectualLibraryType $state): ?string => $state?->color()),
                UserColumn::make('user')
                    ->label(__('admin.fields.author'))
                    ->userName(fn (IntellectualLibrary $record): ?string => $record->author?->getTranslation('name', app()->getLocale()) ?: $record->author?->getTranslation('name', 'en'))
                    ->userTitle(fn (IntellectualLibrary $record): ?string => $record->author?->getTranslation('position', app()->getLocale()) ?: $record->author?->getTranslation('position', 'en'))
                    ->userImage(fn (IntellectualLibrary $record): string => $record->author?->getFirstMediaUrl('avatar') ?: asset('images/placeholder.png')),
                TextColumn::make('author.name')
                    ->label(__('admin.fields.author'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reading_time')
                    ->label(__('admin.fields.reading_time'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('video_length')
                    ->label(__('admin.fields.video_length'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('views')
                    ->label(__('admin.fields.views'))
                    ->numeric()
                    ->sortable(),
                LimitedManyToManyBadgesColumn::make('tags', __('Tags'), maxVisible: 1),
                ToggleColumn::make('is_draft')
                    ->label(__('admin.fields.draft'))
                    ->disabled(fn (IntellectualLibrary $record): bool => ! Gate::allows('update', $record))
                    ->afterStateUpdated(function (bool $state): void {
                        Notification::make()
                            ->title($state ? __('admin.messages.status_enabled') : __('admin.messages.status_disabled'))
                            ->success()
                            ->send();
                    }),
                ToggleColumn::make('is_active')
                    ->label(__('admin.fields.active'))
                    ->disabled(fn (IntellectualLibrary $record): bool => ! Gate::allows('update', $record))
                    ->afterStateUpdated(function (bool $state): void {
                        Notification::make()
                            ->title($state ? __('admin.messages.status_enabled') : __('admin.messages.status_disabled'))
                            ->success()
                            ->send();
                    }),
                TextColumn::make('scheduled_at')
                    ->label(__('admin.fields.scheduled_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('admin.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.fields.type'))
                    ->options(collect(IntellectualLibraryType::cases())->mapWithKeys(
                        fn (IntellectualLibraryType $type): array => [$type->value => $type->label()],
                    )),
                TernaryFilter::make('is_draft')
                    ->label(__('admin.fields.draft')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.active')),
                SelectFilter::make('author_id')
                    ->label(__('admin.fields.author'))
                    ->relationship('author', 'name')
                    ->getOptionLabelFromRecordUsing(
                        fn (Author $record): string => (string) ($record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en') ?: $record->id)
                    )
                    ->searchable()
                    ->preload(),
                Filter::make('scheduled_at')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false),
                        DatePicker::make('until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $builder): Builder => $builder->whereDate('scheduled_at', '>=', $data['from']),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $builder): Builder => $builder->whereDate('scheduled_at', '<=', $data['until']),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
