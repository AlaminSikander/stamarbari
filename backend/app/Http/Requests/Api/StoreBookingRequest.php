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
            'room_category_id' => ['required_without:rooms', 'nullable', 'integer', 'exists:room_categories,id'],
            'rooms' => ['nullable', 'array', 'max:20'],
            'rooms.*.room_category_id' => ['required', 'integer', 'distinct', 'exists:room_categories,id'],
            'rooms.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'meal_plan_id' => ['nullable', 'integer', 'exists:meal_plans,id'],
            'addons' => ['nullable', 'array'],
            'addons.*.id' => ['required', 'integer', 'exists:addons,id'],
            'addons.*.quantity' => ['nullable', 'integer', 'min:1'],
            'rooms_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'in:en,bn'],
            'request_key' => ['nullable', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9-]+$/'],
            'guest' => ['required', 'array'],
            'guest.full_name' => ['required', 'string', 'min:2', 'max:120'],
            'guest.phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{5,28}$/'],
            'guest.whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{5,28}$/'],
            'guest.email' => ['nullable', 'email'],
            'guest.id_type' => ['nullable', 'string', 'max:30'],
            'guest.id_number' => ['nullable', 'string', 'max:50'],
            'guest.country' => ['nullable', 'string', 'max:80'],
            'guest.address' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_category_id.required_without' => 'Please select at least one room.',
            'rooms.*.quantity.min' => 'Each selected room needs a quantity of at least 1.',
            'rooms.*.room_category_id.distinct' => 'Each room can only be listed once.',
            'guest.full_name.required' => 'Please enter your full name.',
            'guest.phone.required' => 'Please enter your phone number.',
            'guest.phone.regex' => 'Please enter a valid phone number.',
            'guest.whatsapp.regex' => 'Please enter a valid WhatsApp number.',
            'guest.email.email' => 'Please enter a valid email address.',
            'check_in.after_or_equal' => 'Check-in date cannot be in the past.',
            'check_out.after' => 'Check-out date must be after check-in. Minimum stay is 1 night.',
        ];
    }
}
