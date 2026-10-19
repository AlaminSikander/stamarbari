<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Confirmed = 'confirmed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
