<?php

namespace App\Tenancy;

use RuntimeException;

class TenantMismatchException extends RuntimeException
{
    public static function forModel(string $model): self
    {
        return new self("[{$model}] was given a restaurant_id that differs from the current restaurant.");
    }
}
