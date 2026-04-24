<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportPdfController extends Controller
{
    public function __construct(protected PdfService $pdf) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  SALES REPORT
    // ─────────────────────────────────────────────────────────────────────────
    public function sales(Request $request)
    {
        $from = $request->get('from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to',   Carbon::now()->format('Y-m-d'));

        $sales = Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(vat_amount) as vat'),
                DB::raw('SUM(discount_amount) as discounts'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $summary = [
            'total_revenue'     => Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('total'),
            'total_vat'         => Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('vat_amount'),
            'total_discounts'   => Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->sum('discount_amount'),
            'transaction_count' => Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->count(),
        ];

        $payments = Payment::whereHas('sale', fn($q) => $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')->orderByDesc('total')->get();

        return $this->pdf->generate(
            'pdf.reports.sales',
            compact('sales', 'summary', 'payments', 'from', 'to'),
            "gigateam-sales-report-{$from}-to-{$to}"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  VAT REPORT
    // ─────────────────────────────────────────────────────────────────────────
    public function vat(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('m'));
        $year  = $request->get('year',  Carbon::now()->format('Y'));
        $from  = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to    = $from->copy()->endOfMonth();

        $salesVat = Sale::where('include_vat', true)
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(subtotal) as subtotal'),
                DB::raw('SUM(vat_amount) as vat_collected')
            )
            ->groupBy('date')->orderBy('date')->get();

        $invoiceVat = Invoice::where('include_vat', true)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')->get();

        $summary = [
            'period'           => $from->format('F Y'),
            'pin'              => config('pdf.company.kra_pin', 'P051892936Q'),
            'sales_vat'        => Sale::where('include_vat', true)->whereBetween('created_at', [$from, $to])->sum('vat_amount'),
            'invoice_vat'      => Invoice::where('include_vat', true)->whereBetween('created_at', [$from, $to])->sum('vat_amount'),
            'taxable_sales'    => Sale::where('include_vat', true)->whereBetween('created_at', [$from, $to])->sum('subtotal'),
            'taxable_invoices' => Invoice::where('include_vat', true)->whereBetween('created_at', [$from, $to])->sum('subtotal'),
        ];
        $summary['total_vat']     = $summary['sales_vat'] + $summary['invoice_vat'];
        $summary['total_taxable'] = $summary['taxable_sales'] + $summary['taxable_invoices'];

        return $this->pdf->generate(
            'pdf.reports.vat',
            compact('salesVat', 'invoiceVat', 'summary'),
            "gigateam-vat-report-{$from->format('Y-m')}"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CUSTOMER STATEMENT
    // ─────────────────────────────────────────────────────────────────────────
    public function statement(Request $request)
    {
        $customerId = $request->get('customer');
        $from = $request->get('from', Carbon::now()->startOfYear()->format('Y-m-d'));
        $to   = $request->get('to',   Carbon::now()->format('Y-m-d'));

        $customer = Customer::findOrFail($customerId);

        $lines = [];

        $invoices = Invoice::where('customer_id', $customerId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->orderBy('created_at')->get();

        foreach ($invoices as $inv) {
            $lines[] = [
                'date'        => $inv->created_at->format('d M Y'),
                'type'        => 'Invoice',
                'reference'   => $inv->invoice_number,
                'description' => 'Invoice',
                'debit'       => $inv->total,
                'credit'      => 0,
                'status'      => $inv->status,
            ];
            if ($inv->amount_paid > 0) {
                $lines[] = [
                    'date'        => $inv->updated_at->format('d M Y'),
                    'type'        => 'Payment',
                    'reference'   => 'PMT-' . $inv->invoice_number,
                    'description' => 'Payment received',
                    'debit'       => 0,
                    'credit'      => $inv->amount_paid,
                    'status'      => 'paid',
                ];
            }
        }

        $sales = Sale::where('customer_id', $customerId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->orderBy('created_at')->get();

        foreach ($sales as $sale) {
            $lines[] = [
                'date'        => $sale->created_at->format('d M Y'),
                'type'        => 'Sale',
                'reference'   => $sale->sale_number,
                'description' => 'POS Sale',
                'debit'       => $sale->total,
                'credit'      => $sale->amount_paid,
                'status'      => $sale->payment_status,
            ];
        }

        usort($lines, fn($a, $b) => strcmp($a['date'], $b['date']));

        $balance = 0;
        foreach ($lines as &$line) {
            $balance += $line['debit'] - $line['credit'];
            $line['balance'] = $balance;
        }

        $summary = [
            'total_invoiced' => array_sum(array_column($lines, 'debit')),
            'total_paid'     => array_sum(array_column($lines, 'credit')),
            'balance_due'    => $balance,
        ];

        return $this->pdf->generate(
            'pdf.reports.statement',
            compact('customer', 'lines', 'summary', 'from', 'to'),
            "gigateam-statement-{$customer->name}-{$from}"
        );
    }
}