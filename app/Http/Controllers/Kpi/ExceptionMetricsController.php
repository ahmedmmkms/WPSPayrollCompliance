<?php

namespace App\Http\Controllers\Kpi;

use App\Support\Metrics\TenantKpiMetrics;
use Illuminate\Http\JsonResponse;

class ExceptionMetricsController
{
    public function __invoke(TenantKpiMetrics $metrics): JsonResponse
    {
        return response()->json($metrics->exceptionFlowTrend());
    }
}
