<?php

namespace App\Enums;

enum AtharPlacement: string
{
    case Work = 'work';
    case Services = 'services';
    case About = 'about';

    public function label(): string
    {
        return match ($this) {
            self::Work => __('athar.placements.work'),
            self::Services => __('athar.placements.services'),
            self::About => __('athar.placements.about'),
        };
    }

    public function adminLabel(): string
    {
        return __("admin.athar.placements.{$this->value}");
    }
}
