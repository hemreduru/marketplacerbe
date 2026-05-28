<?php

namespace App\Support\Enums;

enum EInvoiceProvider: string
{
    case Parasut = 'parasut';
    case BizimHesap = 'bizim_hesap';
    case GibDirect = 'gib_direct';
}
