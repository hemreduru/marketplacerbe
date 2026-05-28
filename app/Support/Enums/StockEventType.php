<?php

namespace App\Support\Enums;

enum StockEventType: string
{
    case Sale = 'sale';
    case Return = 'return';
    case ManualAdjust = 'manual_adjust';
    case SyncIn = 'sync_in';
    case Correction = 'correction';
}
