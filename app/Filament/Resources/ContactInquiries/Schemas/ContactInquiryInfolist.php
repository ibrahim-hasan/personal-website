<?php

namespace App\Filament\Resources\ContactInquiries\Schemas;

use App\Enums\ContactInquiryStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.inquiry'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label(__('admin.fields.name')),
                        TextEntry::make('email')
                            ->label(__('admin.fields.email_address'))
                            ->copyable(),
                        TextEntry::make('company')
                            ->label(__('admin.fields.company'))
                            ->placeholder('—'),
                        TextEntry::make('service_label')->label(__('admin.fields.service')),
                        TextEntry::make('challenge')
                            ->label(__('admin.fields.challenge'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.sections.handling'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('admin.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (ContactInquiryStatus $state): string => $state->label())
                            ->color(fn (ContactInquiryStatus $state): string => $state->color()),
                        TextEntry::make('locale')
                            ->label(__('admin.fields.locale'))
                            ->formatStateUsing(fn (string $state): string => __("admin.locales.{$state}")),
                        TextEntry::make('received_at')
                            ->label(__('admin.fields.received_at'))
                            ->dateTime(),
                        TextEntry::make('replied_at')
                            ->label(__('admin.fields.replied_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->label(__('admin.fields.internal_notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
