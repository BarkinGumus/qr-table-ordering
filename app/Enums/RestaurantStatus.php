<?php

namespace App\Enums;

enum RestaurantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
