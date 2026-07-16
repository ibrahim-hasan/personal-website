<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class SeoGenerationException extends RuntimeException
{
    public static function fromThrowable(Throwable $exception): self
    {
        $code = is_int($exception->getCode()) ? $exception->getCode() : 0;

        return new self(__('AI SEO provider request failed.'), $code);
    }
}
