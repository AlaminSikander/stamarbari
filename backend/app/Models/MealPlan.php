<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_per_adult_per_night' => 'decimal:2',
            'price_per_child_per_night' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
