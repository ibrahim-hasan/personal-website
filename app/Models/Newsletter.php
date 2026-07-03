<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Newsletter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'is_disabled',
        'unsubscribe_token',
    ];

    protected $attributes = [
        'is_disabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_disabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => DashboardCache::bust());
        static::deleted(fn () => DashboardCache::bust());
        static::restored(fn () => DashboardCache::bust());
        static::forceDeleted(fn () => DashboardCache::bust());
    }

    public function ensureUnsubscribeToken(): string
    {
        if (is_string($this->unsubscribe_token) && $this->unsubscribe_token !== '') {
            return $this->unsubscribe_token;
        }

        $token = Str::random(64);

        $this->forceFill([
            'unsubscribe_token' => $token,
        ])->save();

        return $token;
    }
}
