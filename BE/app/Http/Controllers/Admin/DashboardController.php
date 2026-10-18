<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Revenue\RevenueCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected RevenueCalculationService $revenueService;

    public function __construct(RevenueCalculationService $revenueService)
    {
        $this->revenueService = $revenueService;
    }

    public function getStatistics(Request $request)
    {
        $admin = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();

        $options = [
            'date_by'    => $request->query('date_by', RevenueCalculationService::DATE_BY_PAYMENT),
            'start_date' => $request->query('start_date'),
            'end_date'   => $request->query('end_date'),
            'months'     => $request->query('months', 6),
        ];

        $data = $this->revenueService->getDashboardRevenueStats($options, $admin);

        return response()->json([
            'status'             => 1,
            'can_view_financial' => $data['can_view_financial'] ?? true,
            'data'               => $data,
        ]);
    }
}
