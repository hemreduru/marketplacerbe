<?php

namespace App\Support\Enums;

enum PriceEventType: string
{
    case ManualChange = 'manual_change';
    case StrategyRecompute = 'strategy_recompute';
    case MarketplaceSync = 'marketplace_sync';
}
