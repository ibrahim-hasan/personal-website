<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorialApiIdempotencyKey extends Model
{
    protected $fillable = [
        'client_id',
        'idempotency_key',
        'method',
        'path',
        'request_hash',
        'response_status',
        'response_body',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
