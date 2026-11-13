<?php

namespace App\Tenancy;

use App\Models\Restaurant;
use Closure;

/**
 * Holds the restaurant the current request (or job) is working for.
 *
 * It is set exactly once per request by middleware: from the logged-in user
 * for staff, from the table's QR token for guests. It is never read from
 * request input.
 */
final class Tenant
{
    private ?Restaurant $current = null;

    private bool $bypassed = false;

    public function set(Restaurant $restaurant): void
    {
        $this->current = $restaurant;
    }

    public function forget(): void
    {
        $this->current = null;
    }

    public function check(): bool
    {
        return $this->current !== null;
    }

    public function get(): ?Restaurant
    {
        return $this->current;
    }

    public function id(): ?int
    {
        return $this->current?->id;
    }

    public function bypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * Run a callback as the given restaurant, then restore the previous one.
     * For seeders, console commands and tests.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Restaurant $restaurant, Closure $callback): mixed
    {
        $previous = $this->current;
        $this->current = $restaurant;

        try {
            return $callback();
        } finally {
            $this->current = $previous;
        }
    }

    /**
     * Run a callback with tenant filtering switched off. Only for
     * platform-level work that must see every restaurant.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function withoutScope(Closure $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }
}
