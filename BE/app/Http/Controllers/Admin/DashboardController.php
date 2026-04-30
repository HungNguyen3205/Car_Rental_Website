<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStatistics()
    {
        // General counts
        $totalCars = Car::count();
        $availableCars = Car::where('status', 'available')->count();
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('tinh_trang', 0)->count();
        $totalUsers = User::count();
        
        // Total Revenue (Confirmed and Paid)
        $totalRevenue = Booking::where('tinh_trang', 1)->where('is_thanh_toan', 1)->sum('tong_tien');

        // Revenue by month (last 6 months - ensuring all months exist)
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('m/Y');
            $total = Booking::where('tinh_trang', 1)
                ->where('is_thanh_toan', 1)
                ->whereMonth('created_at', Carbon::now()->subMonths($i)->month)
                ->whereYear('created_at', Carbon::now()->subMonths($i)->year)
                ->sum('tong_tien');
            
            $revenueData[] = [
                'month' => $month,
                'total' => (float)$total
            ];
        }

        // Booking status distribution
        $statusDistribution = Booking::select(
            DB::raw('count(*) as count'),
            'tinh_trang'
        )
        ->groupBy('tinh_trang')
        ->get();

        return response()->json([
            'status' => 1,
            'data' => [
                'summary' => [
                    'total_cars' => $totalCars,
                    'available_cars' => $availableCars,
                    'total_bookings' => $totalBookings,
                    'pending_bookings' => $pendingBookings,
                    'total_users' => $totalUsers,
                    'total_revenue' => $totalRevenue
                ],
                'revenue_chart' => $revenueData,
                'status_distribution' => $statusDistribution
            ]
        ]);
    }
}
