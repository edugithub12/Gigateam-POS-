<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(protected PdfService $pdf) {}

    /**
     * Stream the invoice PDF in the browser.
     * Route: GET /documents/invoice/{id}
     */
    public function pdf(int $id, Request $request)
    {
        $invoice = Invoice::with('items')->findOrFail($id);

        return $this->pdf->generate(
            view:     'pdf.invoice',
            data:     compact('invoice'),
            filename: 'INV-' . $invoice->invoice_number,
            download: $request->boolean('download'),
        );
    }
}