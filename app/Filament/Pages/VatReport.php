<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VatReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'VAT Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.vat-report';

    public string $month = '';
    public string $year  = '';

    public function mount(): void
    {
        $this->month = Carbon::now()->format('m');
        $this->year  = Carbon::now()->format('Y');
    }

    public function getVatFromSales(): array
    {
        $from = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        return Sale::where('include_vat', true)
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(subtotal) as subtotal'),
                DB::raw('SUM(discount_amount) as discounts'),
                DB::raw('SUM(vat_amount) as vat_collected'),
                DB::raw('SUM(total) as total_inclusive')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getVatFromInvoices(): array
    {
        $from = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        return Invoice::where('include_vat', true)
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('DATE(created_at) as date'),
                'invoice_number',
                'client_name',
                'subtotal',
                'discount_amount',
                'vat_amount',
                'total'
            )
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getVatSummary(): array
    {
        $from = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        $salesVat = Sale::where('include_vat', true)
            ->whereBetween('created_at', [$from, $to])
            ->sum('vat_amount');

        $invoiceVat = Invoice::where('include_vat', true)
            ->whereBetween('created_at', [$from, $to])
            ->sum('vat_amount');

        $salesSubtotal   = Sale::where('include_vat', true)->whereBetween('created_at', [$from, $to])->sum('subtotal');
        $invoiceSubtotal = Invoice::where('include_vat', true)->whereBetween('created_at', [$from, $to])->sum('subtotal');

        return [
            'period'          => Carbon::createFromDate($this->year, $this->month, 1)->format('F Y'),
            'sales_vat'       => $salesVat,
            'invoice_vat'     => $invoiceVat,
            'total_vat'       => $salesVat + $invoiceVat,
            'taxable_sales'   => $salesSubtotal,
            'taxable_invoices' => $invoiceSubtotal,
            'total_taxable'   => $salesSubtotal + $invoiceSubtotal,
            'vat_rate'        => '16%',
            'pin'             => 'P051892936Q',
        ];
    }

    public function getAvailableYears(): array
    {
        return collect(range(Carbon::now()->year, Carbon::now()->year - 3))
            ->mapWithKeys(fn($y) => [$y => $y])
            ->toArray();
    }

    public function getMonths(): array
    {
        return [
            '01' => 'January',   '02' => 'February', '03' => 'March',
            '04' => 'April',     '05' => 'May',       '06' => 'June',
            '07' => 'July',      '08' => 'August',    '09' => 'September',
            '10' => 'October',   '11' => 'November',  '12' => 'December',
        ];
    }
}