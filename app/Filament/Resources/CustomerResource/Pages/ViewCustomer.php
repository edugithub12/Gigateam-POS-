<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;
    protected static string $view = 'filament.customer.view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getViewData(): array
    {
        $customer = $this->record;
        $customer->load([
            'sales', 'invoices', 'quotations',
            'jobCards', 'deliveryNotes',
        ]);

        // ── Financial summary ──────────────────────────────────────────────────
        $totalSpent       = $customer->sales->sum('total');
        $totalPaid        = $customer->sales->sum('amount_paid')
                          + $customer->invoices->sum('amount_paid');
        $outstandingBalance = $customer->outstanding_balance;

        $totalSales    = $customer->sales->count();
        $totalInvoices = $customer->invoices->count();
        $totalJobs     = $customer->jobCards->count();
        $totalQuotes   = $customer->quotations->count();

        $avgOrderValue = $totalSales > 0
            ? round($totalSpent / $totalSales, 2)
            : 0;

        $firstPurchase = $customer->sales->sortBy('created_at')->first()?->created_at;
        $lastPurchase  = $customer->sales->sortByDesc('created_at')->first()?->created_at;

        // ── Payment behaviour ──────────────────────────────────────────────────
        $paidSales    = $customer->sales->where('payment_status', 'paid')->count();
        $partialSales = $customer->sales->where('payment_status', 'partial')->count();
        $unpaidSales  = $customer->sales->where('payment_status', 'unpaid')->count();

        // ── Monthly spend — last 6 months ──────────────────────────────────────
        $monthlySpend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthlySpend[] = [
                'month'   => $m->format('M Y'),
                'revenue' => (float) $customer->sales()
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->sum('total'),
            ];
        }

        // ── Top products bought ────────────────────────────────────────────────
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('sales.deleted_at')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // ── Recent transactions (combined timeline) ────────────────────────────
        $timeline = collect();

        foreach ($customer->sales->take(10) as $s) {
            $timeline->push([
                'type'   => 'sale',
                'ref'    => $s->sale_number,
                'date'   => $s->created_at,
                'amount' => $s->total,
                'status' => $s->payment_status,
                'url'    => '/admin/sales/' . $s->id . '/edit',
            ]);
        }

        foreach ($customer->invoices->take(10) as $inv) {
            $timeline->push([
                'type'   => 'invoice',
                'ref'    => $inv->invoice_number,
                'date'   => $inv->created_at,
                'amount' => $inv->total,
                'status' => $inv->status,
                'url'    => '/admin/invoices/' . $inv->id . '/edit',
            ]);
        }

        foreach ($customer->quotations->take(5) as $q) {
            $timeline->push([
                'type'   => 'quotation',
                'ref'    => $q->quotation_number,
                'date'   => $q->created_at,
                'amount' => $q->total,
                'status' => $q->status,
                'url'    => '/admin/quotations/' . $q->id . '/edit',
            ]);
        }

        foreach ($customer->jobCards->take(5) as $j) {
            $timeline->push([
                'type'   => 'job',
                'ref'    => $j->job_number,
                'date'   => $j->created_at,
                'amount' => $j->grandTotal(),
                'status' => $j->status,
                'url'    => '/admin/job-cards/' . $j->id . '/edit',
            ]);
        }

        $timeline = $timeline->sortByDesc('date')->values()->take(20);

        return compact(
            'customer',
            'totalSpent', 'totalPaid', 'outstandingBalance',
            'totalSales', 'totalInvoices', 'totalJobs', 'totalQuotes',
            'avgOrderValue', 'firstPurchase', 'lastPurchase',
            'paidSales', 'partialSales', 'unpaidSales',
            'monthlySpend', 'topProducts', 'timeline'
        );
    }
}