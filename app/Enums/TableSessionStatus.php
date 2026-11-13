<?php

namespace App\Enums;

/**
 * A table session is one party's visit.
 *
 * pending  first scan; nothing confirmed by staff yet
 * open     staff confirmed the party is at the table
 * closed   bill settled
 * expired  pending session abandoned without a confirmed order
 */
enum TableSessionStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case Closed = 'closed';
    case Expired = 'expired';

    /** Live sessions occupy the table; a table has at most one. */
    public function isLive(): bool
    {
        return $this === self::Pending || $this === self::Open;
    }
}
