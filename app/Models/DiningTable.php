<?php

namespace App\Models;

use App\Tenancy\BelongsToRestaurant;
use Database\Factories\DiningTableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A physical table. qr_token is deliberately not fillable: it is only ever
 * set by generateToken(), never from a form.
 */
#[Fillable(['label', 'area', 'capacity', 'is_active', 'sort_order'])]
class DiningTable extends Model
{
    /** @use HasFactory<DiningTableFactory> */
    use BelongsToRestaurant, HasFactory;

    protected function casts(): array
    {
        return [
            'qr_rotated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DiningTable $table): void {
            $table->qr_token ??= self::generateToken();
        });
    }

    /** 16 random bytes (128 bits) as 22 URL-safe characters. */
    public static function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    /** Replace the token so every printed copy of the old QR stops working. */
    public function rotateToken(): void
    {
        $this->qr_token = self::generateToken();
        $this->qr_rotated_at = now();
        $this->save();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    /** The pending or open session, if the table is in use. */
    public function liveSession(): HasOne
    {
        return $this->hasOne(TableSession::class, 'live_table_id');
    }
}
