<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Translatable\HasTranslations;

class Role extends SpatieRole
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
    ];

    public array $translatable = [
        'display_name',
    ];
}
