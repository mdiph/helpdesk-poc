<?php

namespace App\Http\Controllers\Web;

use App\Exports\ReportExporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportRequest;
use App\Services\ReportService;
use App\Support\TicketFilters;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportExporter $exporter,
    ) {
    }

    public function index(ReportRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->build(
            $request->user(),
            $request->from(),
            $request->to(),
            TicketFilters::fromRequest($request),
        );

        if ($format = $request->validated('format')) {
            return $this->exporter->export($report, $format);
        }

        return view('reports.index', [
            'report' => $report,
            'filters' => $request->only(['from', 'to', 'support_tier', 'status', 'priority', 'category_id', 'assigned_to']),
        ]);
    }
}
