<?php

namespace App\Filament\Resources\ContactInquiries\Schemas;

use App\Enums\ContactInquiryStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.handling'))
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label(__('admin.fields.status'))
                            ->options(self::statusOptions())
                            ->required(),
                        DateTimePicker::make('replied_at')
                            ->label(__('admin.fields.replied_at'))
                            ->seconds(false),
                        Textarea::make('notes')
                            ->label(__('admin.fields.internal_notes'))
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
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
