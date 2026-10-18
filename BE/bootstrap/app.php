<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureAdminHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 1. Chuẩn hóa lỗi Validation trong API trả về HTTP 422 nhất quán
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 0,
                    'message' => $e->validator->errors()->first() ?: 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại!',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 2. Bắt các ngoại lệ nội bộ, ghi log server, không trả stack trace / raw SQL cho client
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Ngoại lệ đã được xử lý riêng
                if ($e instanceof ValidationException) {
                    return null;
                }

                if ($e instanceof \App\Exceptions\CarBookingConflictException) {
                    return response()->json([
                        'status'     => 0,
                        'message'    => $e->getMessage(),
                        'error_type' => 'SCHEDULE_CONFLICT'
                    ], 409);
                }

                if ($e instanceof \App\Exceptions\CarBookingValidationException) {
                    return response()->json([
                        'status'  => 0,
                        'message' => $e->getMessage()
                    ], $e->getCode() ?: 400);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Bạn chưa đăng nhập hoặc phiên làm việc đã hết hạn!'
                    ], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Bạn không có quyền thực hiện thao tác này!'
                    ], 403);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Tài nguyên yêu cầu không tồn tại!'
                    ], 404);
                }

                if ($e instanceof HttpExceptionInterface) {
                    return response()->json([
                        'status'  => 0,
                        'message' => $e->getMessage() ?: 'Yêu cầu không hợp lệ!'
                    ], $e->getStatusCode());
                }

                // Ghi log chi tiết nội bộ tại server và che giấu stack trace đối với khách
                Log::error('API Internal Exception: ' . $e->getMessage(), [
                    'url'    => $request->fullUrl(),
                    'method' => $request->method(),
                    'trace'  => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'status'  => 0,
                    'message' => 'Có lỗi xảy ra trong quá trình xử lý hệ thống. Vui lòng thử lại sau!'
                ], 500);
            }
        });
    })->create();
