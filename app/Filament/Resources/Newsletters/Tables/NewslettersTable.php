<?php

namespace App\Filament\Resources\Newsletters\Tables;

use App\Models\Newsletter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewslettersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->searchable(),
                // IconColumn::make('is_disabled')
                //     ->label(__('admin.fields.is_disabled'))
                //     ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_disabled')
                    ->label(__('admin.fields.is_disabled')),
                Filter::make('created_at_range')
                    ->label(__('Date Range'))
                    ->schema([
                        DatePicker::make('from')
                            ->native(false)
                            ->label(__('From Date')),
                        DatePicker::make('until')
                            ->native(false)
                            ->label(__('To Date'))
                            ->afterOrEqual(fn (Get $get): ?string => $get('from')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $data['until'])
                            );
                    }),
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('enable')
                    ->label(__('admin.actions.enable'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->outlined()
                    ->visible(fn (Newsletter $record): bool => (bool) $record->is_disabled)
                    ->requiresConfirmation()
                    ->action(function (Newsletter $record): void {
                        $record->update(['is_disabled' => false]);
                        Notification::make()
                            ->title(__('admin.messages.newsletter_enabled'))
                            ->success()
                            ->send();
                    }),
                Action::make('disable')
                    ->label(__('admin.actions.disable'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->visible(fn (Newsletter $record): bool => ! (bool) $record->is_disabled)
                    ->requiresConfirmation()
                    ->action(function (Newsletter $record): void {
                        $record->update(['is_disabled' => true]);
                        Notification::make()
                            ->title(__('admin.messages.newsletter_disabled'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
