<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\BookingController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/categories', [CarController::class, 'getCategories']);
Route::get('/cars', [CarController::class, 'getAllCars']);
Route::get('/categories/{category_id}/cars', [CarController::class, 'getCarsByCategory']);

// Auto-payment triggering
Route::post('/bookings/auto-payment', [BookingController::class, 'autoCheckPayment']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/profile', [AuthController::class, 'getProfile']);
    Route::post('/bookings', [BookingController::class, 'createBooking']);
    Route::get('/bookings/my-bookings', [BookingController::class, 'getMyBookings']);
});
