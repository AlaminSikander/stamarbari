<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Model;

class GroupInquiry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'preferred_check_in' => 'date',
            'preferred_check_out' => 'date',
        ];
    }
}
