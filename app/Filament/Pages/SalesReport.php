<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Sales Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.sales-report';

    public string $dateFrom = '';
    public string $dateTo = '';
    public string $groupBy = 'day';

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo   = Carbon::now()->format('Y-m-d');
    }

    public function getSalesData(): array
    {
        $from = $this->dateFrom ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $to   = $this->dateTo   ?: Carbon::now()->format('Y-m-d');

        $format = match($this->groupBy) {
            'month' => '%Y-%m',
            'week'  => '%Y-%u',
            default => '%Y-%m-%d',
        };

        $sales = Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(vat_amount) as vat'),
                DB::raw('SUM(discount_amount) as discounts'),
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $sales->toArray();
    }

    public function getSummary(): array
    {
        $from = $this->dateFrom ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $to   = $this->dateTo   ?: Carbon::now()->format('Y-m-d');

        $sales = Sale::whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        return [
            'total_revenue'    => (clone $sales)->sum('total'),
            'total_vat'        => (clone $sales)->sum('vat_amount'),
            'total_discounts'  => (clone $sales)->sum('discount_amount'),
            'transaction_count'=> (clone $sales)->count(),
            'average_sale'     => (clone $sales)->count() > 0
                ? (clone $sales)->sum('total') / (clone $sales)->count()
                : 0,
        ];
    }

    public function getPaymentBreakdown(): array
    {
        $from = $this->dateFrom ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $to   = $this->dateTo   ?: Carbon::now()->format('Y-m-d');

        return Payment::whereHas('sale', function($q) use ($from, $to) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);
            })
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function getTopProducts(): array
    {
        $from = $this->dateFrom ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $to   = $this->dateTo   ?: Carbon::now()->format('Y-m-d');

        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->select(
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as qty_sold'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('sale_items.product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getRecentSales(): array
    {
        $from = $this->dateFrom ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $to   = $this->dateTo   ?: Carbon::now()->format('Y-m-d');

        return Sale::with('customer')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($s) => [
                'sale_number'    => $s->sale_number,
                'customer'       => $s->customer?->name ?? 'Walk-in',
                'total'          => $s->total,
                'payment_status' => $s->payment_status,
                'created_at'     => $s->created_at->format('d M Y H:i'),
            ])
            ->toArray();
    }
}