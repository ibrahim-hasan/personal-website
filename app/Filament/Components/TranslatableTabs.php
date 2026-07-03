<?php

namespace App\Filament\Components;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TranslatableTabs
{
    /**
     * @param  array<string, array<Component>>  $tabsSchema
     */
    public static function make(array $tabsSchema, int $columns = 2): Tabs
    {
        $tabs = [];

        foreach ($tabsSchema as $locale => $schema) {
            $tabs[] = Tab::make(__('admin.locales.'.$locale))
                ->schema($schema)
                ->columns($columns)
                ->extraAttributes([
                    'class' => 'fi-tabs-item--locale-'.$locale,
                ]);
        }

        return Tabs::make(__('admin.sections.translations'))
            ->tabs($tabs)
            ->columnSpanFull()
            ->extraAttributes([
                'class' => 'fi-tabs-translatable',
            ]);
    }
}
