<?php

namespace App\Http\Controllers\Kpi;

use App\Support\Metrics\TenantKpiMetrics;
use Illuminate\Http\JsonResponse;

class ThroughputMetricsController
{
    public function __invoke(TenantKpiMetrics $metrics): JsonResponse
    {
        return response()->json($metrics->throughputTrend());
    }
}
