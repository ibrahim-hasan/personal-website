<?php

namespace App\Filament\Resources\ContactInquiries\Tables;

use App\Enums\ContactInquiryStatus;
use App\Models\ContactInquiry;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (ContactInquiry $record): ?string => $record->company)
                    ->searchable(['name', 'company'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('service_label')
                    ->label(__('admin.fields.service'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (ContactInquiryStatus $state): string => $state->label())
                    ->color(fn (ContactInquiryStatus $state): string => $state->color()),
                TextColumn::make('received_at')
                    ->label(__('admin.fields.received_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('locale')
                    ->label(__('admin.fields.locale'))
                    ->options([
                        'ar' => __('admin.locales.ar'),
                        'en' => __('admin.locales.en'),
                    ]),
            ])
            ->defaultSort('received_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return collect(ContactInquiryStatus::cases())
            ->mapWithKeys(fn (ContactInquiryStatus $status): array => [$status->value => $status->label()])
            ->all();
    }
}
