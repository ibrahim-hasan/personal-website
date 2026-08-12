<?php

namespace App\Services\WebsitePerformance;

use RuntimeException;

class WebsitePerformanceSourceException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
