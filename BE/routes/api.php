<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\CarCalendarController as AdminCarCalendarController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ChatbotController as AdminChatbotController;
use App\Http\Controllers\Admin\AdminController as AdminRoleController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Client\CarController as ClientCarController;
use App\Http\Controllers\Client\BookingController as ClientBookingController;
use App\Http\Controllers\Auth\SocialAuthController;

// Guest Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/active/{hash}', [AuthController::class, 'active']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/contact', [\App\Http\Controllers\Client\ContactController::class, 'store']);
Route::get('/settings/public', [\App\Http\Controllers\Public\SettingController::class, 'index']);
Route::get('/car-categories', [\App\Http\Controllers\Client\CarCategoryController::class, 'index']);

Route::group(['prefix' => 'client'], function () {
    Route::get('/car/data', [ClientCarController::class, 'getDataClient']);
    Route::get('/car/detail/{id}', [ClientCarController::class, 'getDetailClient']);
    Route::post('/car/check-availability', [ClientCarController::class, 'checkAvailabilityAndQuote']);
    Route::get('/categories', [ClientCarController::class, 'getCategories']);
    Route::post('/kiem-tra-ma-dat-lich', [ClientBookingController::class, 'kiemTraMaDatLich']);
});

// Protected Routes
Route::group(['middleware' => ['auth:sanctum,admin', \App\Http\Middleware\EnsureAccountIsActive::class]], function () {
    
    // Auth Check & Session Management
    Route::get('/check-login', [AuthController::class, 'checkLogin']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/change-password', [AuthController::class, 'doiMatKhau']);

    // Secure Receipt Route
    Route::get('/bookings/{id}/receipt', [ClientBookingController::class, 'getReceipt']);

    // Secure Handover Image Route
    Route::get('/bookings/{id}/handovers/images/{filename}', [AdminBookingController::class, 'getHandoverImage']);

    // Admin Group
    Route::group(['prefix' => 'admin', 'middleware' => 'auth:admin'], function () {
        
        // Báo cáo & Thống kê Dashboard
        Route::get('/statistics', [AdminDashboardController::class, 'getStatistics'])
            ->middleware('permission:reports.view,bookings.view,cars.manage,handovers.manage,payments.manage');
        Route::get('/calendar/schedule', [AdminCarCalendarController::class, 'getSchedule'])
            ->middleware('permission:bookings.view,cars.manage');

        // Quản lý xe
        Route::group(['prefix' => 'cars'], function () {
            Route::get('/data', [AdminCarController::class, 'getData'])
                ->middleware('permission:bookings.view,cars.manage');
            Route::post('/create', [AdminCarController::class, 'createData'])
                ->middleware('permission:cars.manage');
            Route::post('/update', [AdminCarController::class, 'updateData'])
                ->middleware('permission:cars.manage');
            Route::post('/delete', [AdminCarController::class, 'deleteData'])
                ->middleware('permission:cars.manage');
            Route::post('/restore', [AdminCarController::class, 'restoreData'])
                ->middleware('permission:cars.manage');
            Route::post('/force-delete', [AdminCarController::class, 'forceDeleteData'])
                ->middleware('permission:cars.manage');
            Route::get('/detail/{id}', [AdminCarController::class, 'getDetail'])
                ->middleware('permission:bookings.view,cars.manage');
            Route::post('/upload-image', [AdminCarController::class, 'uploadImage'])
                ->middleware('permission:cars.manage');
            Route::post('/status', [AdminCarController::class, 'statusData'])
                ->middleware('permission:cars.manage');
            Route::post('/publication-status', [AdminCarController::class, 'publicationStatusData'])
                ->middleware('permission:cars.manage');
        });

        // Vận hành đơn & Thanh toán
        Route::group(['prefix' => 'bookings'], function () {
            Route::get('/data', [AdminBookingController::class, 'getDataAdmin'])
                ->middleware('permission:bookings.view');
            Route::get('/reconciliation', [AdminBookingController::class, 'getReconciliationList'])
                ->middleware('permission:reports.view');
            Route::post('/create-for-customer', [AdminBookingController::class, 'createBookingForCustomer'])
                ->middleware('permission:bookings.manage');
            Route::post('/status', [AdminBookingController::class, 'statusBooking'])
                ->middleware('permission:bookings.manage');
            Route::post('/delete', [AdminBookingController::class, 'deleteBooking'])
                ->middleware('permission:bookings.manage');

            // Quản lý thu/hoàn tiền & Giao dịch
            Route::post('/confirm-payment', [AdminBookingController::class, 'confirmPayment'])
                ->middleware('permission:payments.manage');
            Route::get('/{id}/transactions', [AdminBookingController::class, 'getTransactions'])
                ->middleware('permission:bookings.view,payments.manage');
            Route::post('/{id}/transactions', [AdminBookingController::class, 'recordTransaction'])
                ->middleware('permission:payments.manage');

            // Quản lý giao nhận xe
            Route::get('/{id}/handovers', [AdminBookingController::class, 'getHandovers'])
                ->middleware('permission:bookings.view,handovers.manage');
            Route::post('/{id}/handovers', [AdminBookingController::class, 'createHandover'])
                ->middleware('permission:handovers.manage');
        });

        // Quản lý khách hàng
        Route::get('/users/data', [AuthController::class, 'getAllUsers'])
            ->middleware('permission:bookings.view,admins.manage');
        Route::get('/users/search', [AuthController::class, 'searchCustomers'])
            ->middleware('permission:bookings.manage');
        Route::post('/users/quick-create', [AuthController::class, 'quickCreateCustomer'])
            ->middleware('permission:bookings.manage');
        Route::post('/users/status', [AuthController::class, 'statusUser'])
            ->middleware('permission:admins.manage');
        Route::post('/users/promote', [AdminRoleController::class, 'promoteUser'])
            ->middleware('permission:admins.manage');

        // Quản trị viên & Phân quyền
        Route::group(['prefix' => 'admins', 'middleware' => 'permission:admins.manage'], function () {
            Route::get('/data', [AdminRoleController::class, 'getData']);
            Route::post('/create', [AdminRoleController::class, 'createData']);
            Route::post('/update', [AdminRoleController::class, 'updateData']);
            Route::post('/delete', [AdminRoleController::class, 'deleteData']);
            Route::post('/status', [AdminRoleController::class, 'statusData']);
        });

        // Quản lý yêu cầu liên hệ / hỗ trợ
        Route::group(['prefix' => 'contacts', 'middleware' => 'permission:bookings.view,admins.manage'], function () {
            Route::get('/', [\App\Http\Controllers\Admin\ContactController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Admin\ContactController::class, 'show']);
            Route::put('/{id}/status', [\App\Http\Controllers\Admin\ContactController::class, 'updateStatus']);
        });

        // Nhật ký kiểm toán thao tác (Audit Logs - Chỉ đọc)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit_logs.view');

        // Cấu hình hệ thống (Settings)
        Route::group(['prefix' => 'settings', 'middleware' => 'permission:settings.manage'], function () {
            Route::get('/', [\App\Http\Controllers\Admin\SettingController::class, 'index']);
            Route::put('/', [\App\Http\Controllers\Admin\SettingController::class, 'update']);
        });

        // Quản lý danh mục xe (Car Categories CRUD)
        Route::group(['prefix' => 'car-categories', 'middleware' => 'permission:cars.manage,settings.manage'], function () {
            Route::get('/', [\App\Http\Controllers\Admin\CarCategoryController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Admin\CarCategoryController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Admin\CarCategoryController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Admin\CarCategoryController::class, 'destroy']);
        });

        // Xuất dữ liệu (CSV Export)
        Route::get('/bookings/export', [\App\Http\Controllers\Admin\ExportController::class, 'exportBookings'])
            ->middleware('permission:bookings.view,reports.view');
        Route::get('/reports/export', [\App\Http\Controllers\Admin\ExportController::class, 'exportRevenue'])
            ->middleware('permission:reports.view');
    });

    // Client/User Group
    Route::group(['prefix' => 'user', 'middleware' => 'auth:sanctum,admin'], function () {
        
        // Thông báo trong ứng dụng (In-app Notifications)
        Route::group(['prefix' => 'notifications'], function () {
            Route::get('/', [\App\Http\Controllers\Client\NotificationController::class, 'index']);
            Route::put('/read-all', [\App\Http\Controllers\Client\NotificationController::class, 'markAllAsRead']);
            Route::put('/{id}/read', [\App\Http\Controllers\Client\NotificationController::class, 'markAsRead']);
        });

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
            Route::get('/{id}/transactions', [ClientBookingController::class, 'getTransactionsClient']);
            Route::get('/{id}/handovers', [ClientBookingController::class, 'getHandoversClient']);
            Route::post('/{id}/handovers/confirm', [ClientBookingController::class, 'confirmHandoverClient']);
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
