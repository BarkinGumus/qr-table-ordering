<?php

namespace App\Enums;

/** How a bill was settled at the venue. Online methods come later. */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case CardTerminal = 'card_terminal';
}
