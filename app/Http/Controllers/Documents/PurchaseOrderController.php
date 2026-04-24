<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\PdfService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(protected PdfService $pdf) {}

    /**
     * Stream the purchase order PDF in the browser.
     * Route: GET /documents/po/{id}
     */
    public function pdf(int $id, Request $request)
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        return $this->pdf->generate(
            view:     'pdf.purchase_order',
            data:     compact('purchaseOrder'),
            filename: 'PO-' . $purchaseOrder->po_number,
            download: $request->boolean('download'),
        );
    }
}