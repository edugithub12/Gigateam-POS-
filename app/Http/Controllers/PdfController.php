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
    // ── Invoice PDF ───────────────────────────────────────────────────────────

    public function invoice(Invoice $invoice): Response
    {
        $invoice->load(['items', 'customer', 'createdBy']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->withProperties([
                'document'  => $invoice->invoice_number,
                'customer'  => $invoice->customer?->name,
                'total'     => $invoice->total,
                'ip'        => request()->ip(),
            ])
            ->log('Downloaded PDF — Invoice ' . $invoice->invoice_number);

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

        activity()
            ->causedBy(auth()->user())
            ->performedOn($quotation)
            ->withProperties([
                'document' => $quotation->quotation_number,
                'customer' => $quotation->customer?->name,
                'total'    => $quotation->total,
                'ip'       => request()->ip(),
            ])
            ->log('Downloaded PDF — Quotation ' . $quotation->quotation_number);

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

        activity()
            ->causedBy(auth()->user())
            ->performedOn($jobCard)
            ->withProperties([
                'document'   => $jobCard->job_number,
                'customer'   => $jobCard->customer?->name,
                'technician' => $jobCard->technician?->name,
                'ip'         => request()->ip(),
            ])
            ->log('Downloaded PDF — Job Card ' . $jobCard->job_number);

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

        activity()
            ->causedBy(auth()->user())
            ->performedOn($deliveryNote)
            ->withProperties([
                'document' => $deliveryNote->delivery_number,
                'customer' => $deliveryNote->customer?->name,
                'ip'       => request()->ip(),
            ])
            ->log('Downloaded PDF — Delivery Note ' . $deliveryNote->delivery_number);

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