<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\JobCard;
use App\Models\DeliveryNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    // ── Invoice PDF ──────────────────────────────────────────────────────────

    public function invoice(Invoice $invoice): Response
    {
        $invoice->load(['items', 'customer', 'createdBy']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $this->companyDetails(),
            'logo'    => $this->logoBase64(),
            'vatRate' => 0.16,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 150,
        ]);

        return $pdf->stream('Invoice-' . $invoice->invoice_number . '.pdf');
    }

    // ── Quotation PDF ─────────────────────────────────────────────────────────

    public function quotation(Quotation $quotation): Response
    {
        $quotation->load(['items', 'customer', 'createdBy', 'approvedBy']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'company'   => $this->companyDetails(),
            'logo'      => $this->logoBase64(),
            'vatRate'   => 0.16,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 150,
        ]);

        return $pdf->stream('Quotation-' . $quotation->quotation_number . '.pdf');
    }

    // ── Job Card PDF ──────────────────────────────────────────────────────────

    public function jobCard(JobCard $jobCard): Response
    {
        $jobCard->load(['items', 'customer', 'technician', 'createdBy']);

        $pdf = Pdf::loadView('pdf.job-card', [
            'job'     => $jobCard,
            'company' => $this->companyDetails(),
            'logo'    => $this->logoBase64(),
            'vatRate' => 0.16,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 150,
        ]);

        return $pdf->stream('JobCard-' . $jobCard->job_number . '.pdf');
    }

    // ── Delivery Note PDF ─────────────────────────────────────────────────────

    public function deliveryNote(DeliveryNote $deliveryNote): Response
    {
        $deliveryNote->load(['items', 'customer', 'technician', 'createdBy']);

        $pdf = Pdf::loadView('pdf.delivery-note', [
            'dn'      => $deliveryNote,
            'company' => $this->companyDetails(),
            'logo'    => $this->logoBase64(),
            'vatRate' => 0.16,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 150,
        ]);

        return $pdf->stream('DeliveryNote-' . $deliveryNote->delivery_number . '.pdf');
    }

    // ── Shared helpers ────────────────────────────────────────────────────────

    private function companyDetails(): array
    {
        return [
            'name'     => 'Gigateam Solutions Limited',
            'tagline'  => 'Secured & Connected',
            'kra_pin'  => 'P051892936Q',
            'address'  => 'White Angle House, 1st Floor – Suite 62',
            'address1' => 'White Angle House, 1st Floor – Suite 62',
            'po_box'   => 'P.O. Box 47271-00100, Nairobi, Kenya',
            'phone1'   => '+254 111292948',
            'phone2'   => '+254 718811661',
            'email1'   => 'sales@gigateamltd.com',
            'email2'   => 'gigateamsolutions@gmail.com',
            'website'  => 'www.gigateamsolutions.co.ke',
            'footer'   => 'SECURED AND CONNECTED',
        ];
    }

    private function logoBase64(): string
    {
        $path = public_path('images/gigateam-logo.png');
        if (!file_exists($path)) return '';
        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }
}