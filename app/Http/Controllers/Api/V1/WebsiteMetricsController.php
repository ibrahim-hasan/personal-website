<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\WebsiteMetrics\AggregateConsultationMetrics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebsiteMetricsRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class WebsiteMetricsController extends Controller
{
    public function __invoke(WebsiteMetricsRequest $request, AggregateConsultationMetrics $metrics): JsonResponse
    {
        $inquiries = $metrics->handle($request->startDate(), $request->endDate());

        return response()->json([
            'data' => [
                'timezone' => WebsiteMetricsRequest::Timezone,
                'start_date' => $request->startDate()->toDateString(),
                'end_date' => $request->endDate()->toDateString(),
                'inquiries' => $inquiries,
            ],
            'meta' => [
                'generated_at' => CarbonImmutable::now(WebsiteMetricsRequest::Timezone)->toIso8601String(),
                'privacy' => 'aggregate_only',
            ],
        ]);
    }
}
