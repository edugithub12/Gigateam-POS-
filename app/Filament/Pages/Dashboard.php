<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort     = -1;
    protected static string $view             = 'filament.pages.dashboard';

    public function getViewData(): array
    {
        $today     = today();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $shopId    = activeShopId(); // null = all shops (super admin)

        // ── Today's sales ──────────────────────────────────────────────────────
        // NOTE: Sale, Invoice, Quotation already carry the global ShopScope via
        // BelongsToShop, so the explicit ->when($shopId, ...) below is now
        // redundant but harmless — kept for clarity and as a safety net for
        // contexts where the scope might be bypassed.
        $todaySales = Sale::whereDate('created_at', $today)->get();
        $todayRevenue = $todaySales->sum('total');
        $todayCount   = $todaySales->count();

        // ── This month ─────────────────────────────────────────────────────────
        $monthSales   = Sale::where('created_at', '>=', $thisMonth)->get();
        $monthRevenue = $monthSales->sum('total');

        // ── Last month ─────────────────────────────────────────────────────────
        $lastMonthRev = Sale::whereBetween('created_at', [
                $lastMonth, now()->subMonth()->endOfMonth(),
            ])->sum('total');

        $revenueGrowth = $lastMonthRev > 0
            ? round((($monthRevenue - $lastMonthRev) / $lastMonthRev) * 100, 1)
            : 0;

        // ── Outstanding invoices ───────────────────────────────────────────────
        $unpaidInvoices = Invoice::whereIn('status', ['unpaid', 'partial'])->get();

        $unpaidTotal     = $unpaidInvoices->sum('total') - $unpaidInvoices->sum('amount_paid');
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->count();

        // ── Pending quotations ─────────────────────────────────────────────────
        $pendingQuotations = Quotation::where('status', 'pending_approval')->count();
        $draftQuotations   = Quotation::where('status', 'draft')->count();

        // ── Active job cards ───────────────────────────────────────────────────
        // Job cards don't have location_id — scoped by who created them
        $activeJobs     = JobCard::whereIn('status', ['scheduled', 'in_progress'])->count();
        $completedToday = JobCard::whereDate('completed_at', $today)->count();

        // ── Low stock ──────────────────────────────────────────────────────────
        // Product now has location_id, quantity, and reorder_point directly
        // on the row (no more locationStocks join table). The ShopScope
        // global scope on Product already filters to the active shop, or
        // shows everything when shopId is null (super admin, all shops).
        $lowStockItems = Product::where('is_active', true)
            ->where('is_service', false)
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_point')
            ->with('category')
            ->get();

        // ── Recent sales ───────────────────────────────────────────────────────
        $recentSales = Sale::with(['customer', 'location'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // ── Revenue chart — last 30 days ───────────────────────────────────────
        $chartData   = [];
        $chartLabels = [];
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date  = now()->subDays($i)->format('Y-m-d');
            $rev   = Sale::whereDate('created_at', $date)->sum('total');
            $label         = now()->subDays($i)->format('d M');
            $chartData[]   = ['date' => $label, 'revenue' => (float) $rev];
            $chartLabels[] = $label;
            $chartValues[] = (float) $rev;
        }

        // ── Payment method breakdown (this month) ──────────────────────────────
        $paymentMethods = Payment::whereHas('sale', fn ($q) =>
                $q->where('created_at', '>=', $thisMonth)
            )
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $paymentLabels = $paymentMethods->pluck('method')
            ->map(fn ($m) => ucfirst(str_replace('_', ' ', $m)))->toArray();
        $paymentValues = $paymentMethods->pluck('total')
            ->map(fn ($v) => (float) $v)->toArray();

        // ── Job card status breakdown ──────────────────────────────────────────
        $jobStatusData = JobCard::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $jobStatusLabels = $jobStatusData->pluck('status')
            ->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->toArray();
        $jobStatusValues = $jobStatusData->pluck('count')->toArray();

        // ── Top products this month ────────────────────────────────────────────
        // sale_items.product_id now points to a shop-specific product row,
        // so this naturally stays within the active shop's own products
        // without needing an extra join condition.
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.created_at', '>=', $thisMonth)
            ->whereNull('sales.deleted_at')
            ->when($shopId, fn ($q) => $q->where('sales.location_id', $shopId))
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get();

        $topProductLabels = $topProducts->pluck('name')
            ->map(fn ($n) => strlen($n) > 20 ? substr($n, 0, 20) . '…' : $n)->toArray();
        $topProductValues = $topProducts->pluck('revenue')
            ->map(fn ($v) => (float) $v)->toArray();

        // ── Monthly revenue — last 6 months ───────────────────────────────────
        $monthlyLabels = [];
        $monthlyValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end   = now()->subMonths($i)->endOfMonth();
            $rev   = Sale::whereBetween('created_at', [$start, $end])->sum('total');
            $monthlyLabels[] = $start->format('M Y');
            $monthlyValues[] = (float) $rev;
        }

        // ── Hourly sales pattern (today) ───────────────────────────────────────
        $hourlyData = Sale::whereDate('created_at', $today)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $hourlyLabels = [];
        $hourlyValues = [];
        for ($h = 7; $h <= 20; $h++) {
            $hourlyLabels[] = $h . ':00';
            $hourlyValues[] = isset($hourlyData[$h]) ? (float) $hourlyData[$h]->revenue : 0;
        }

        // ── Customer count ─────────────────────────────────────────────────────
        // Customers are global (not per-shop) — totals across the system
        $totalCustomers = Customer::active()->count();
        $newThisMonth   = Customer::where('created_at', '>=', $thisMonth)->count();

        // ── Active shop context for the blade ─────────────────────────────────
        $activeShop     = activeShop();
        $activeShopName = $activeShop?->name ?? 'All Shops';

        return compact(
            'todayRevenue', 'todayCount',
            'monthRevenue', 'revenueGrowth',
            'unpaidTotal', 'overdueInvoices', 'unpaidInvoices',
            'pendingQuotations', 'draftQuotations',
            'activeJobs', 'completedToday',
            'lowStockItems',
            'recentSales',
            'chartData', 'chartLabels', 'chartValues',
            'paymentLabels', 'paymentValues',
            'jobStatusLabels', 'jobStatusValues',
            'topProducts', 'topProductLabels', 'topProductValues',
            'monthlyLabels', 'monthlyValues',
            'hourlyLabels', 'hourlyValues',
            'totalCustomers', 'newThisMonth',
            'activeShop', 'activeShopName'
        );
    }
}