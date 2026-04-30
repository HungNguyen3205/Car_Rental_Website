<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ChatbotController as AdminChatbotController;
use App\Http\Controllers\Admin\AdminController as AdminRoleController;
use App\Http\Controllers\Client\CarController as ClientCarController;
use App\Http\Controllers\Client\BookingController as ClientBookingController;
use App\Http\Controllers\Auth\SocialAuthController;

// Guest Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/active/{hash}', [AuthController::class, 'active']);

Route::group(['prefix' => 'client'], function () {
    Route::get('/car/data', [ClientCarController::class, 'getDataClient']);
    Route::get('/car/detail/{id}', [ClientCarController::class, 'getDetailClient']);
    Route::get('/categories', [ClientCarController::class, 'getCategories']);
    Route::post('/kiem-tra-ma-dat-lich', [ClientBookingController::class, 'kiemTraMaDatLich']);
});

// Protected Routes
Route::group(['middleware' => 'auth:sanctum'], function () {
    
    // Auth Check & Logout
    Route::get('/check-login', [AuthController::class, 'checkLogin']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Group
    Route::group(['prefix' => 'admin', 'middleware' => 'auth:admin'], function () {
        
        Route::get('/statistics', [AdminDashboardController::class, 'getStatistics']);

        Route::group(['prefix' => 'cars'], function () {
            Route::get('/data', [AdminCarController::class, 'getData']);
            Route::post('/create', [AdminCarController::class, 'createData']);
            Route::post('/update', [AdminCarController::class, 'updateData']);
            Route::post('/delete', [AdminCarController::class, 'deleteData']);
            Route::post('/status', [AdminCarController::class, 'statusData']);
        });

        Route::group(['prefix' => 'bookings'], function () {
            Route::get('/data', [AdminBookingController::class, 'getDataAdmin']);
            Route::post('/status', [AdminBookingController::class, 'statusBooking']);
            Route::post('/delete', [AdminBookingController::class, 'deleteBooking']);
        });


        Route::get('/users/data', [AuthController::class, 'getAllUsers']);
        Route::post('/users/status', [AuthController::class, 'statusUser']);
        Route::post('/users/promote', [AdminRoleController::class, 'promoteUser']);

        Route::group(['prefix' => 'admins'], function () {
            Route::get('/data', [AdminRoleController::class, 'getData']);
            Route::post('/create', [AdminRoleController::class, 'createData']);
            Route::post('/update', [AdminRoleController::class, 'updateData']);
            Route::post('/delete', [AdminRoleController::class, 'deleteData']);
            Route::post('/status', [AdminRoleController::class, 'statusData']);
        });
    });

    // Client/User Group
    Route::group(['prefix' => 'user', 'middleware' => 'auth:sanctum,admin'], function () {
        
        Route::group(['prefix' => 'profile'], function () {
            Route::get('/data', [AuthController::class, 'getProfile']);
            Route::post('/update', [AuthController::class, 'updateProfile']);
            Route::post('/doi-mat-khau', [AuthController::class, 'doiMatKhau']);
        });

        Route::group(['prefix' => 'bookings'], function () {
            Route::get('/data', [ClientBookingController::class, 'getDataLichHenClient']);
            Route::post('/dat-lich', [ClientBookingController::class, 'datLichXe']);
            Route::post('/huy-lich', [ClientBookingController::class, 'huyLichXe']);
            Route::post('/upload-bill', [ClientBookingController::class, 'uploadBill']);
        });
    });
});

// Chatbot Assistant Routes (Public Search, Private Booking)
Route::group(['prefix' => 'chatbot'], function () {
    Route::get('/cars', [AdminChatbotController::class, 'getCars']);
    Route::get('/categories', [AdminChatbotController::class, 'getCategories']);
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/booking', [AdminChatbotController::class, 'assistantBooking']);
        Route::post('/check-payment', [AdminChatbotController::class, 'checkPayment']);
    });
});

// Social Authentication Routes
Route::group(['prefix' => 'auth'], function () {
    Route::get('/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
    Route::get('/facebook/redirect', [SocialAuthController::class, 'redirectToFacebook']);
    Route::get('/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);
});
