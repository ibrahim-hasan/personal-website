<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Posted
{
    /**
     * Public site visibility: status published, and either no scheduled moment
     * (published_at null) or the scheduled publication datetime has passed.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query
            ->where('is_draft', false)
            ->where('is_active', true)
            ->when($this->hasAttribute('scheduled_at'), function (Builder $q): void {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }
}
