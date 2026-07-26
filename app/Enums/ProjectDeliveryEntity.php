<?php

namespace App\Enums;

enum ProjectDeliveryEntity: string
{
    case Direct = 'direct';
    case FromScratch = 'from_scratch';
    case CodeMoments = 'code_moments';
    case Collaborative = 'collaborative';
    case Undisclosed = 'undisclosed';

    public function label(): string
    {
        return __("admin.project_delivery_entities.{$this->value}");
    }
}
