<?php

namespace App\Filament\Resources\Tags\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('color')
                    ->label(__('admin.fields.color'))
                    ->formatStateUsing(fn ($state): string => '')
                    ->extraAttributes(fn ($record) => [
                        'style' => sprintf(
                            'background-color: %s; width: 28px; height: 28px; border-radius: 9999px; display: inline-block;',
                            $record->color ?? '#E8F5E9'
                        ),
                    ]),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->getStateUsing(fn ($record): ?string => $record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', 'en'))
                    ->searchable(),
                // TextColumn::make('slug')
                //     ->label(__('admin.fields.slug'))
                //     ->getStateUsing(fn ($record): ?string => $record->getTranslation('slug', app()->getLocale()) ?: $record->getTranslation('slug', 'en'))
                //     ->searchable(),
            ])
            ->reorderable('order_column')
            ->defaultSort('order_column', 'asc')
            ->filters([
                //
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
                ]),
            ]);
    }
}
