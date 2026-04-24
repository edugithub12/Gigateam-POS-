<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\PdfService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function __construct(protected PdfService $pdf) {}

    /**
     * Stream the quotation PDF in the browser.
     * Route: GET /documents/quotation/{id}
     */
    public function pdf(int $id, Request $request)
    {
        $quotation = Quotation::with('items')->findOrFail($id);

        return $this->pdf->generate(
            view:     'pdf.quotation',
            data:     compact('quotation'),
            filename: 'QUO-' . $quotation->quotation_number,
            download: $request->boolean('download'),
        );
    }
}