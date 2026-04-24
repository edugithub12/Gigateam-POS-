<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\PdfService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(protected PdfService $pdf) {}

    /**
     * Stream the receipt PDF in the browser.
     * Route: GET /documents/receipt/{id}
     */
    public function pdf(int $id, Request $request)
    {
        $receipt = Receipt::with('items')->findOrFail($id);

        return $this->pdf->generate(
            view:     'pdf.receipt',
            data:     compact('receipt'),
            filename: 'REC-' . $receipt->receipt_number,
            download: $request->boolean('download'),
        );
    }
}