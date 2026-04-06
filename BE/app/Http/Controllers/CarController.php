<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function getCategories()
    {
        $categories = CarCategory::all();
        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    public function getAllCars()
    {
        $cars = Car::with('category')->orderBy('id', 'desc')->get();
        return response()->json([
            'status' => true,
            'message' => 'Lấy dữ liệu xe thành công',
            'data' => $cars
        ]);
    }

    public function getCarsByCategory($categoryId)
    {
        $cars = Car::with('category')
            ->where('category_id', $categoryId)
            ->where('status', '!=', 'maintenance')
            ->get();

        $formattedData = $cars->map(function ($car) {
            $carArray = $car->toArray();
            $carArray['price_per_day_formatted'] = number_format($car->price_per_day, 0, ',', '.') . ' VNĐ';
            return $carArray;
        });

        return response()->json([
            'status' => true,
            'data' => $formattedData
        ]);
    }
}
