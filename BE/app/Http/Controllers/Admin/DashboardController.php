<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Revenue\RevenueCalculationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected RevenueCalculationService $revenueService;

    public function __construct(RevenueCalculationService $revenueService)
    {
        $this->revenueService = $revenueService;
    }

    public function getStatistics(Request $request)
    {
        $options = [
            'date_by'    => $request->query('date_by', RevenueCalculationService::DATE_BY_PAYMENT),
            'start_date' => $request->query('start_date'),
            'end_date'   => $request->query('end_date'),
            'months'     => $request->query('months', 6),
        ];

        $data = $this->revenueService->getDashboardRevenueStats($options);

        return response()->json([
            'status' => 1,
            'data'   => $data,
        ]);
    }
}
