<?php

namespace App\Models;

use App\Enums\TableSessionStatus;
use App\Tenancy\BelongsToRestaurant;
use Database\Factories\TableSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One party's visit to a table, from first scan to closed bill.
 */
#[Fillable([
    'dining_table_id', 'status', 'ordering_blocked', 'opened_at',
    'confirmed_by', 'confirmed_at', 'closed_at', 'closed_by',
])]
class TableSession extends Model
{
    /** @use HasFactory<TableSessionFactory> */
    use BelongsToRestaurant, HasFactory;

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'status' => TableSessionStatus::class,
            'ordering_blocked' => 'boolean',
            'opened_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep live_table_id in step with status on every save, so the
        // unique index can enforce "one live session per table".
        static::saving(function (TableSession $session): void {
            $session->live_table_id = $session->status->isLive() ? $session->dining_table_id : null;
        });
    }

    public function isLive(): bool
    {
        return $this->status->isLive();
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }

    public function guests(): HasMany
    {
        return $this->hasMany(SessionGuest::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
