<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function getData()
    {
        $data = Car::join('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->select('cars.*', 'car_categories.name as category_name')
            ->orderByDesc('cars.id')
            ->get();
            
        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    public function createData(Request $request)
    {
        Car::create([
            'name'          => $request->name,
            'price_per_day' => $request->price_per_day,
            'type'          => $request->type,
            'city'          => $request->city,
            'status'        => 'available',
            'image'         => $request->image,
            'category_id'   => $request->category_id,
        ]);

        return response()->json([
            'status'  => 1,
            'message' => 'Tạo mới xe thành công!',
        ]);
    }

    public function updateData(Request $request)
    {
        $car = Car::find($request->id);
        if ($car) {
            $car->update([
                'name'          => $request->name,
                'price_per_day' => $request->price_per_day,
                'type'          => $request->type,
                'city'          => $request->city,
                'image'         => $request->image,
                'category_id'   => $request->category_id,
            ]);
            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật xe thành công!',
            ]);
        }
    }

    public function deleteData(Request $request)
    {
        $car = Car::find($request->id);
        if ($car) {
            $car->delete();
            return response()->json([
                'status'  => 1,
                'message' => 'Xóa xe thành công!',
            ]);
        }
    }

    public function statusData(Request $request)
    {
        $car = Car::find($request->id);
        if ($car) {
            $car->status = ($car->status == 'available' ? 'maintenance' : 'available');
            $car->save();
            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật trạng thái thành công!',
            ]);
        }
    }
}
