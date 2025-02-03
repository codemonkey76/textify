<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Pending = 'PENDING';
    case Delivered = 'DELIVERED';
    case Failed = 'FAILED';
    case Unknown = 'UNKNOWN';
}
