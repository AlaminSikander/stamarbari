<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case VerificationFailed = 'verification_failed';
    case Duplicate = 'duplicate';
}

enum RefundStatus: string
{
    case Requested = 'requested';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}

enum InquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Confirmed = 'confirmed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}

enum BookingSource: string
{
    case Online = 'online';
    case WalkIn = 'walk_in';
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case Facebook = 'facebook';
    case Other = 'other';
}

enum DiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
}
