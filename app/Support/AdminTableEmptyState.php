<?php

namespace App\Support;

use Filament\Tables\Table;

class AdminTableEmptyState
{
    public static function apply(Table $table, string $resource, string $icon): Table
    {
        return $table
            ->emptyStateHeading(__("admin.empty_states.{$resource}.heading"))
            ->emptyStateDescription(__("admin.empty_states.{$resource}.description"))
            ->emptyStateIcon($icon);
    }
}
