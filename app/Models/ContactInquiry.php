<?php

namespace App\Models;

use App\Enums\ContactInquiryStatus;
use Database\Factories\ContactInquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    /** @use HasFactory<ContactInquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'company',
        'service_key',
        'service_label',
        'challenge',
        'locale',
        'status',
        'received_at',
        'replied_at',
        'notes',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactInquiryStatus::class,
            'received_at' => 'immutable_datetime',
            'replied_at' => 'immutable_datetime',
        ];
    }
}
