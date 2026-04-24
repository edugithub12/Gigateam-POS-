<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Stream or download a branded PDF.
     *
     * @param  string  $view      Blade path  e.g. 'pdf.reports.sales'
     * @param  array   $data      Model data — do NOT include logo or company
     * @param  string  $filename  e.g. 'gigateam-sales-report' (no .pdf extension needed)
     * @param  bool    $download  false = open in browser tab, true = force download
     */
    public function generate(
        string $view,
        array  $data,
        string $filename,
        bool   $download = true
    ): Response {
        // Merge shared branding — logo + company + vatRate injected automatically
        $data = array_merge($data, $this->sharedData());

        $pdf = Pdf::loadView($view, $data)
            ->setPaper(config('pdf.paper', 'a4'), config('pdf.orientation', 'portrait'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'dejavu sans',
                'dpi'                  => 150,
                'margin_top'           => 10,
                'margin_bottom'        => 15,
                'margin_left'          => 10,
                'margin_right'         => 10,
            ]);

        $safeName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $filename) . '.pdf';

        return $download
            ? $pdf->download($safeName)
            : $pdf->stream($safeName);
    }

    /**
     * Shared branding data injected into every PDF automatically.
     * Converts logo to base64 data URI so DomPDF can embed it reliably.
     */
    protected function sharedData(): array
    {
        $logoPath = config('pdf.logo_path');
        $logo     = null;

        if ($logoPath && file_exists($logoPath)) {
            $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        return [
            'logo'    => $logo,
            'company' => config('pdf.company'),
            'vatRate' => config('pdf.vat_rate', 0.16),
        ];
    }
}