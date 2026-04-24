<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerStatement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Customer Statement';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.customer-statement';

    public ?int $customerId = null;
    public string $dateFrom = '';
    public string $dateTo   = '';

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo   = Carbon::now()->format('Y-m-d');
    }

    public function getCustomers(): array
    {
        return Customer::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getCustomer(): ?Customer
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }

    public function getStatementLines(): array
    {
        if (!$this->customerId) return [];

        $from = $this->dateFrom;
        $to   = $this->dateTo;
        $lines = [];

        // Invoices
        $invoices = Invoice::where('customer_id', $this->customerId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->orderBy('created_at')
            ->get();

        foreach ($invoices as $inv) {
            $lines[] = [
                'date'        => $inv->created_at->format('d M Y'),
                'type'        => 'Invoice',
                'reference'   => $inv->invoice_number,
                'description' => 'Invoice - ' . ($inv->notes ?? 'Sale'),
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

        // Sales
        $sales = Sale::where('customer_id', $this->customerId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->orderBy('created_at')
            ->get();

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

        // Sort by date
        usort($lines, fn($a, $b) => strcmp($a['date'], $b['date']));

        // Running balance
        $balance = 0;
        foreach ($lines as &$line) {
            $balance += $line['debit'] - $line['credit'];
            $line['balance'] = $balance;
        }

        return $lines;
    }

    public function getStatementSummary(): array
    {
        $lines = $this->getStatementLines();
        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));
        return [
            'total_invoiced' => $totalDebit,
            'total_paid'     => $totalCredit,
            'balance_due'    => $totalDebit - $totalCredit,
        ];
    }
}