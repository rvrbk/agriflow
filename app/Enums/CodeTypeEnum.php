<?php

namespace App\Enums;

enum CodeTypeEnum: string
{
    case QR = 'qr';
    case BARCODE = 'barcode';
}