<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportRequest;
use App\Services\ReportService;
use App\Support\TicketFilters;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    /** GET /api/reports?from=YYYY-MM-DD&to=YYYY-MM-DD */
    public function index(ReportRequest $request): JsonResponse
    {
        $report = $this->reports->build(
            $request->user(),
            $request->from(),
            $request->to(),
            TicketFilters::fromRequest($request),
        );

        return response()->json(['data' => $report]);
    }
}
