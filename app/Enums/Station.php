<?php

namespace App\Enums;

/** Where an item is prepared. Decides which staff screen shows it. */
enum Station: string
{
    case Kitchen = 'kitchen';
    case Bar = 'bar';
}
