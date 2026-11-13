<?php

namespace App\Models;

use App\Tenancy\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One phone in a table session. No name, no account.
 */
#[Fillable(['table_session_id', 'key_hash', 'last_seen_at'])]
#[Hidden(['key_hash'])]
class SessionGuest extends Model
{
    use BelongsToRestaurant;

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    /** The cookie holds the plain key; the database only its SHA-256. */
    public static function hashKey(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }
}
