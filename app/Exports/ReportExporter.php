<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a report array (from ReportService::build) to CSV / XLSX / PDF.
 * The spreadsheet formats stack every breakdown table into one sheet with
 * section headers so a single file carries the whole report.
 */
class ReportExporter
{
    public function __construct(private readonly SpreadsheetExporter $spreadsheet)
    {
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function export(array $report, string $format): Response
    {
        $stamp = now()->format('Ymd_His');
        $range = $report['range']['from'].'_to_'.$report['range']['to'];

        return match ($format) {
            'csv' => $this->spreadsheet->csv("report_{$range}_{$stamp}.csv", ['Section', 'Label', 'Count'], $this->rows($report)),
            'xlsx' => $this->spreadsheet->xlsx("report_{$range}_{$stamp}.xlsx", ['Section', 'Label', 'Count'], $this->rows($report)),
            'pdf' => Pdf::loadView('exports.report', ['report' => $report, 'generatedAt' => now()])
                ->setPaper('a4')
                ->download("report_{$range}_{$stamp}.pdf"),
            default => abort(400, 'Unsupported export format.'),
        };
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<int, string|int|null>>
     */
    private function rows(array $report): array
    {
        $rows = [
            ['Summary', 'Date range', $report['range']['from'].' to '.$report['range']['to']],
            ['Summary', 'Total tickets', $report['total']],
            ['Summary', 'Resolved in range', $report['resolution']['resolved_count']],
            ['Summary', 'Average resolution (hours)', $report['resolution']['avg_hours'] ?? 'n/a'],
            ['Summary', 'Median resolution (hours)', $report['resolution']['median_hours'] ?? 'n/a'],
        ];

        foreach (['by_status' => 'By status', 'by_priority' => 'By priority', 'by_tier' => 'By tier'] as $key => $section) {
            foreach ($report[$key] as $row) {
                $rows[] = [$section, $row['label'], $row['count']];
            }
        }

        foreach (['by_category' => 'By category', 'by_assignee' => 'By assignee/team'] as $key => $section) {
            foreach ($report[$key] as $row) {
                $rows[] = [$section, $row['label'], $row['count']];
            }
        }

        foreach ($report['created_vs_resolved'] as $row) {
            $rows[] = ['Created vs resolved', $row['date'], 'created='.$row['created'].' resolved='.$row['resolved']];
        }

        return $rows;
    }
}
