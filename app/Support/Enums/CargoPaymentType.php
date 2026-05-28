<?php

namespace App\Support\Enums;

enum CargoPaymentType: string
{
    case SenderPays = 'sender_pays';
    case ReceiverPays = 'receiver_pays';
    case MarketplacePays = 'marketplace_pays';
}
