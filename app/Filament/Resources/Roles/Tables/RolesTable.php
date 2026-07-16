<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\Role;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('admin.fields.display_name'))
                    ->getStateUsing(fn (Role $record): ?string => $record->getTranslation('display_name', app()->getLocale()) ?: $record->getTranslation('display_name', 'en'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.code_name'))
                    ->searchable(),
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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Role $record): bool => $record->name !== 'super_admin'),
                    DeleteAction::make()
                        ->visible(fn (Role $record): bool => $record->name !== 'super_admin'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
