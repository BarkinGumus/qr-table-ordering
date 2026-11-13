<?php

namespace App\Tenancy;

use RuntimeException;

/**
 * Thrown when restaurant-owned data is touched before a restaurant is known.
 * Failing loudly here is what stops an unscoped query from leaking data.
 */
class MissingTenantException extends RuntimeException
{
    public static function forModel(string $model): self
    {
        return new self("No restaurant is set for this request, so [{$model}] cannot be queried or saved.");
    }
}
