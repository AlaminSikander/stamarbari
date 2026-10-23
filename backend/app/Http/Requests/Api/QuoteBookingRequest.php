<?php

namespace App\Http\Requests\Api;

class QuoteBookingRequest extends StoreBookingRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_filter(
            parent::rules(),
            fn (string $key) => $key !== 'guest' && ! str_starts_with($key, 'guest.') && ! in_array($key, ['special_requests', 'request_key'], true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
