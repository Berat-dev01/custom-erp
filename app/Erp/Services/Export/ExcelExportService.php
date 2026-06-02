<?php

namespace App\Erp\Services\Export;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;

class ExcelExportService
{
    /**
     * @param list<string>           $headers
     * @param iterable<list<scalar>> $rows
     */
    public function download(string $filename, array $headers, iterable $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $response = response()->streamDownload(function () use ($headers, $rows): void {
            $options = new Options();
            $writer  = new Writer($options);
            $writer->openToFile('php://output');

            $headerStyle = (new Style())->setFontBold();
            $writer->addRow(Row::fromValues($headers, $headerStyle));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_map(fn ($v) => $v ?? '', $row)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);

        return $response;
    }
}
