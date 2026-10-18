<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CarCategory;
use Illuminate\Http\JsonResponse;

class CarCategoryController extends Controller
{
    /**
     * Lấy danh sách danh mục xe công khai cho khách hàng.
     */
    public function index(): JsonResponse
    {
        $categories = CarCategory::withCount(['cars' => function ($query) {
            $query->where('is_active', true);
        }])->orderBy('name')->get();

        return response()->json([
            'status' => 1,
            'data'   => $categories,
        ]);
    }
}
