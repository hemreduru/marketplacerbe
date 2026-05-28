<?php

namespace App\Support\Enums;

enum StockEventSource: string
{
    case Trendyol = 'trendyol';
    case Hepsiburada = 'hepsiburada';
    case N11 = 'n11';
    case Pazarama = 'pazarama';
    case Amazon = 'amazon';
    case User = 'user';
    case System = 'system';
}
