<?php

namespace App\Support\Enums;

/**
 * order_item_financials.source — kalemin verisinin geldiği kaynak.
 */
enum ProfitSource: string
{
    case Estimate = 'estimate';
    case Mixed = 'mixed';
    case Settlement = 'settlement';
}
