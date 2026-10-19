<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\GroupInquiry;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'website' => ['nullable', 'max:0'],
        ]);

        unset($data['website']);
        $data['ip_address'] = $request->ip();
        ContactMessage::query()->create($data);

        return ApiResponse::success([], 'Thank you. We will get back to you soon.', 201);
    }

    public function inquiry(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'group_size' => ['nullable', 'integer', 'min:1'],
            'adults' => ['nullable', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'preferred_check_in' => ['nullable', 'date'],
            'preferred_check_out' => ['nullable', 'date', 'after:preferred_check_in'],
            'rooms_needed' => ['nullable', 'integer', 'min:1'],
            'meal_preference' => ['nullable', 'string', 'max:80'],
            'requirements' => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
        ]);

        $inquiry = GroupInquiry::query()->create($data);

        return ApiResponse::success(['id' => $inquiry->id], 'Your group inquiry has been received.', 201);
    }
}
