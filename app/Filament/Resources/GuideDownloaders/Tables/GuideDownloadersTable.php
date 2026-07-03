<?php

namespace App\Filament\Resources\GuideDownloaders\Tables;

use App\Models\GuideDownloader;
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

class GuideDownloadersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->searchable(),
                IconColumn::make('is_mail_sent')
                    ->label(__('admin.fields.is_mail_sent'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_mail_sent')
                    ->label(__('admin.fields.is_mail_sent'))
                    ->trueLabel(__('Sent'))
                    ->falseLabel(__('Not sent')),
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
                Action::make('resend')
                    ->label(__('admin.actions.resend'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (GuideDownloader $record): bool => ! (bool) $record->is_mail_sent)
                    ->requiresConfirmation()
                    ->action(function (GuideDownloader $record): void {
                        $record->update([
                            'is_mail_sent' => true,
                        ]);

                        Notification::make()
                            ->title(__('admin.messages.guide_resend_success'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
