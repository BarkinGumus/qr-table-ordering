<?php

namespace App\Enums;

/**
 * Preparation progress of an order. Payment is not part of it: the bill
 * belongs to the table session.
 */
enum OrderStatus: string
{
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Received = 'received';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';
}
