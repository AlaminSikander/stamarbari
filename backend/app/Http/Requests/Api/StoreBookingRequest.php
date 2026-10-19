<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'child_ages' => ['nullable', 'array'],
            'room_category_id' => ['required', 'integer', 'exists:room_categories,id'],
            'meal_plan_id' => ['nullable', 'integer', 'exists:meal_plans,id'],
            'addons' => ['nullable', 'array'],
            'addons.*.id' => ['required', 'integer', 'exists:addons,id'],
            'addons.*.quantity' => ['nullable', 'integer', 'min:1'],
            'extra_bed' => ['nullable', 'boolean'],
            'extra_beds' => ['nullable', 'integer', 'min:0', 'max:8'],
            'rooms_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'in:en,bn'],
            'guest.full_name' => ['required', 'string', 'max:120'],
            'guest.phone' => ['required', 'string', 'max:30'],
            'guest.whatsapp' => ['nullable', 'string', 'max:30'],
            'guest.email' => ['nullable', 'email'],
            'guest.id_type' => ['nullable', 'string', 'max:30'],
            'guest.id_number' => ['nullable', 'string', 'max:50'],
            'guest.country' => ['nullable', 'string', 'max:80'],
            'guest.address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
