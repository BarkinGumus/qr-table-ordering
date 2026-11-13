<?php

namespace App\Enums;

enum Role: string
{
    case PlatformAdmin = 'platform_admin';
    case Owner = 'owner';
    case Manager = 'manager';
    case Waiter = 'waiter';
    case Kitchen = 'kitchen';
    case Bar = 'bar';

    /** Roles that may open the admin panel. */
    public function canManage(): bool
    {
        return in_array($this, [self::PlatformAdmin, self::Owner, self::Manager], true);
    }

    /** Roles that belong to a restaurant and use the staff screens. */
    public function isRestaurantStaff(): bool
    {
        return $this !== self::PlatformAdmin;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
