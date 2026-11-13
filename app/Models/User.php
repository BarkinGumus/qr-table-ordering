<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Staff and admin accounts. Guests never get a row here.
 *
 * This model deliberately has no tenant scope: the login has to find a user
 * by email before any restaurant is known. Code that lists users must filter
 * by restaurant itself (see scopeForRestaurant).
 */
#[Fillable(['restaurant_id', 'name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function scopeForRestaurant($query, Restaurant|int $restaurant)
    {
        return $query->where('restaurant_id', $restaurant instanceof Restaurant ? $restaurant->id : $restaurant);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === Role::PlatformAdmin;
    }

    /**
     * True when the account itself and its restaurant are both usable.
     * Checked at login and again on every request.
     */
    public function mayUseTheApp(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isPlatformAdmin()) {
            return $this->restaurant_id === null;
        }

        return $this->restaurant !== null && $this->restaurant->isActive();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->mayUseTheApp() && $this->role->canManage();
    }
}
