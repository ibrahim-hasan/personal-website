<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label',
        'group',
        'key',
        'value',
        'val',
        'type',
        'order',
    ];

    protected $attributes = [
        'order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public static function setValue(string $key, mixed $value, ?string $group = null): self
    {
        $valueColumn = static::valueColumn();

        return static::updateOrCreate(
            [
                'key' => $key,
                'group' => $group,
            ],
            [
                'label' => str($key)->replace('_', ' ')->title()->toString(),
                'type' => is_array($value) ? 'json' : 'text',
                $valueColumn => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value,
            ],
        );
    }

    public static function getValue(string $key, ?string $group = null): ?string
    {
        $query = static::query()->where('key', $key);

        if ($group !== null) {
            $query->where('group', $group);
        }

        return $query->value(static::valueColumn());
    }

    public static function valueColumn(): string
    {
        return Schema::hasColumn((new static)->getTable(), 'value') ? 'value' : 'val';
    }

    public function getValueAttribute(mixed $value): mixed
    {
        if ($value !== null) {
            return $value;
        }

        return $this->attributes['val'] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => DashboardCache::bust());
        static::deleted(fn () => DashboardCache::bust());
        static::restored(fn () => DashboardCache::bust());
        static::forceDeleted(fn () => DashboardCache::bust());
    }
}
