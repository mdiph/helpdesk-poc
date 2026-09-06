<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Small wrapper around openspout that turns a header row + data rows into a
 * downloadable CSV or XLSX file. Used by both ticket and report exports.
 *
 * Files are written to the system temp directory and removed after the
 * response is sent (deleteFileAfterSend).
 */
class SpreadsheetExporter
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public function csv(string $filename, array $headings, iterable $rows): BinaryFileResponse
    {
        return $this->write($filename, 'text/csv', new CsvWriter, $headings, $rows);
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public function xlsx(string $filename, array $headings, iterable $rows): BinaryFileResponse
    {
        return $this->write(
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            new XlsxWriter,
            $headings,
            $rows,
        );
    }

    private function write(string $filename, string $contentType, CsvWriter|XlsxWriter $writer, array $headings, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'export_');

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headings));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(
                fn ($v) => $v instanceof \BackedEnum ? $v->value : $v,
                array_values($row)
            )));
        }

        $writer->close();

        return response()
            ->download($path, $filename, ['Content-Type' => $contentType])
            ->deleteFileAfterSend();
    }
}
