<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -1;
    protected static string $view = 'filament.pages.dashboard';

    public function getViewData(): array
    {
        $today     = today();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // ── Today's sales ────────────────────────────────────────────────────
        $todaySales      = Sale::whereDate('created_at', $today)->get();
        $todayRevenue    = $todaySales->sum('total');
        $todayCount      = $todaySales->count();

        // ── This month ───────────────────────────────────────────────────────
        $monthSales      = Sale::where('created_at', '>=', $thisMonth)->get();
        $monthRevenue    = $monthSales->sum('total');

        // ── Last month (for comparison) ───────────────────────────────────────
        $lastMonthRev    = Sale::whereBetween('created_at', [
            $lastMonth, now()->subMonth()->endOfMonth()
        ])->sum('total');

        $revenueGrowth = $lastMonthRev > 0
            ? round((($monthRevenue - $lastMonthRev) / $lastMonthRev) * 100, 1)
            : 0;

        // ── Outstanding invoices ──────────────────────────────────────────────
        $unpaidInvoices  = Invoice::whereIn('status', ['unpaid', 'partial'])->get();
        $unpaidTotal     = $unpaidInvoices->sum('total') - $unpaidInvoices->sum('amount_paid');
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->count();

        // ── Pending quotations ────────────────────────────────────────────────
        $pendingQuotations = Quotation::where('status', 'pending_approval')->count();
        $draftQuotations   = Quotation::where('status', 'draft')->count();

        // ── Active job cards ──────────────────────────────────────────────────
        $activeJobs      = JobCard::whereIn('status', ['scheduled', 'in_progress'])->count();
        $completedToday  = JobCard::whereDate('completed_at', $today)->count();

        // ── Low stock ─────────────────────────────────────────────────────────
        $lowStockItems   = Product::lowStock()->with('category')->get();

        // ── Recent sales (last 10) ────────────────────────────────────────────
        $recentSales     = Sale::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // ── Revenue chart (last 14 days) ──────────────────────────────────────
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $rev  = Sale::whereDate('created_at', $date)->sum('total');
            $chartData[] = [
                'date'    => now()->subDays($i)->format('d M'),
                'revenue' => (float) $rev,
            ];
        }

        // ── Top products this month ───────────────────────────────────────────
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.created_at', '>=', $thisMonth)
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

        // ── Customer count ────────────────────────────────────────────────────
        $totalCustomers  = Customer::active()->count();
        $newThisMonth    = Customer::where('created_at', '>=', $thisMonth)->count();

        return compact(
            'todayRevenue', 'todayCount',
            'monthRevenue', 'revenueGrowth',
            'unpaidTotal', 'overdueInvoices', 'unpaidInvoices',
            'pendingQuotations', 'draftQuotations',
            'activeJobs', 'completedToday',
            'lowStockItems',
            'recentSales',
            'chartData',
            'topProducts',
            'totalCustomers', 'newThisMonth'
        );
    }
}