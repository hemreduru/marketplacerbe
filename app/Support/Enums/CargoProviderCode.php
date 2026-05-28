<?php

namespace App\Support\Enums;

enum CargoProviderCode: string
{
    case Yurtici = 'yurtici';
    case Aras = 'aras';
    case Mng = 'mng';
    case Surat = 'surat';
    case Ptt = 'ptt';
    case Ups = 'ups';
    case Dhl = 'dhl';
}
