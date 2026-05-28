<?php

namespace App\Support\Enums;

enum BulkOperationType: string
{
    case PriceUpdate = 'price_update';
    case StockUpdate = 'stock_update';
    case CsvImport = 'csv_import';
}
