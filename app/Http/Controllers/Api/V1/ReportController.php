<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service)
    {
        // 
    }

    public function financial(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:this_month,last_month,this_year,last_year',
        ]);

        $report = $this->service->financialSummary(
            workspace: app('current.workspace'),
            period: $request->period ?? 'this_month'
        );

        return response()->json(['data' => $report]);
    }

    public function time(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:this_month,last_month,this_year,last_year',
        ]);

        $report = $this->service->timeReport(
            workspace: app('current.workspace'),
            period: $request->period ?? 'this_month'
        );

        return response()->json(['data' => $report]);
    }
}
