<?php

use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{slug}', [RoomController::class, 'show']);
Route::get('/meal-plans', [RoomController::class, 'mealPlans']);
Route::get('/addons', [RoomController::class, 'addons']);

Route::post('/availability', [AvailabilityController::class, 'search']);

Route::get('/bookings/policies', [BookingController::class, 'policies']);
Route::post('/bookings/quote', [BookingController::class, 'quote'])->middleware('throttle:60,1');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:20,1');
Route::get('/bookings/{bookingNumber}', [BookingController::class, 'show'])->middleware('throttle:30,1');
Route::get('/bookings/{bookingNumber}/invoice', [BookingController::class, 'invoice'])->middleware('throttle:30,1');

Route::post('/bookings/{bookingNumber}/payments', [PaymentController::class, 'start']);
Route::post('/payments/callback', [PaymentController::class, 'callback']);

Route::get('/pages/{slug}', [ContentController::class, 'page']);
Route::get('/gallery', [ContentController::class, 'gallery']);
Route::get('/faqs', [ContentController::class, 'faqs']);
Route::get('/blog', [ContentController::class, 'blog']);
Route::get('/blog/{slug}', [ContentController::class, 'blogShow']);
Route::get('/offers', [ContentController::class, 'offers']);
Route::get('/packages', [ContentController::class, 'packages']);
Route::get('/reviews', [ContentController::class, 'reviews']);
Route::get('/testimonials', [ContentController::class, 'testimonials']);
Route::get('/announcements', [ContentController::class, 'announcements']);
Route::get('/site', [ContentController::class, 'site']);
Route::get('/homepage', [ContentController::class, 'homepage']);

Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:8,1');
Route::post('/inquiries', [ContactController::class, 'inquiry'])->middleware('throttle:8,1');
