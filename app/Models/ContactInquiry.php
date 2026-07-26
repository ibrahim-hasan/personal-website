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
        'role',
        'service_key',
        'service_label',
        'challenge',
        'timing',
        'locale',
        'public_reference',
        'submission_hash',
        'status',
        'received_at',
        'replied_at',
        'notes',
    ];

    protected $attributes = [
        'status' => 'new',
        'notification_status' => 'pending',
        'notification_attempts' => 0,
    ];

    protected $hidden = [
        'submission_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactInquiryStatus::class,
            'received_at' => 'immutable_datetime',
            'replied_at' => 'immutable_datetime',
            'notification_last_attempted_at' => 'immutable_datetime',
            'notification_sent_at' => 'immutable_datetime',
            'notification_failed_at' => 'immutable_datetime',
            'notification_next_retry_at' => 'immutable_datetime',
        ];
    }
}
