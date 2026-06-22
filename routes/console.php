<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\JobCard;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\TemporaryPermission;
use App\Mail\LowStockAlert;
use App\Mail\DailySalesSummary;
use App\Mail\WeeklyBusinessReport;
use App\Mail\OverdueInvoiceAlert;
use Carbon\Carbon;

// ── Admin email(s) ─────────────────────────────────────────────────
$adminEmail   = config('mail.admin_email',   'edwinwambugu52@gmail.com');
$managerEmail = config('mail.manager_email', 'edwinwambugu52@gmail.com');

// ══════════════════════════════════════════════════════════════════
// EMAIL SCHEDULES
// ══════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────
// 1. LOW STOCK ALERT — Every day at 8:00 AM
// ──────────────────────────────────────────────────────────────────
Schedule::call(function () use ($adminEmail) {
    $lowStock = Product::whereColumn('stock_quantity', '<=', 'reorder_level')
        ->where('reorder_level', '>', 0)
        ->orderBy('stock_quantity')
        ->get();

    if ($lowStock->count() > 0) {
        Mail::to($adminEmail)->send(new LowStockAlert($lowStock));
    }
})->dailyAt('08:00')->name('email.low-stock')->withoutOverlapping();

// ──────────────────────────────────────────────────────────────────
// 2. DAILY SALES SUMMARY — Every day at 6:00 PM
// ──────────────────────────────────────────────────────────────────
Schedule::call(function () use ($adminEmail, $managerEmail) {
    $today     = Carbon::today();
    $yesterday = Carbon::yesterday();

    // Today's figures
    $todaySales        = Sale::whereDate('created_at', $today)->get();
    $todayRevenue      = $todaySales->sum('total');
    $todayTransactions = $todaySales->count();

    // Yesterday's figures
    $yesterdaySales        = Sale::whereDate('created_at', $yesterday)->get();
    $yesterdayRevenue      = $yesterdaySales->sum('total');
    $yesterdayTransactions = $yesterdaySales->count();

    // Month to date
    $monthRevenue = Sale::whereMonth('created_at', $today->month)
        ->whereYear('created_at', $today->year)
        ->sum('total');
    $monthTransactions = Sale::whereMonth('created_at', $today->month)
        ->whereYear('created_at', $today->year)
        ->count();

    // Payment breakdown
    $paymentBreakdown = Payment::whereHas('sale', fn($q) => $q->whereDate('created_at', $today))
        ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
        ->groupBy('method')
        ->orderByDesc('total')
        ->get();

    // Top products today
    $topProducts = SaleItem::whereHas('sale', fn($q) => $q->whereDate('created_at', $today))
        ->join('products', 'sale_items.product_id', '=', 'products.id')
        ->select(
            'products.name',
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue')
        )
        ->groupBy('products.id', 'products.name')
        ->orderByDesc('total_revenue')
        ->limit(5)
        ->get();

    $data = [
        'today_revenue'          => $todayRevenue,
        'today_transactions'     => $todayTransactions,
        'yesterday_revenue'      => $yesterdayRevenue,
        'yesterday_transactions' => $yesterdayTransactions,
        'month_revenue'          => $monthRevenue,
        'month_transactions'     => $monthTransactions,
        'payment_breakdown'      => $paymentBreakdown,
        'top_products'           => $topProducts,
    ];

    Mail::to($adminEmail)->cc($managerEmail)->send(new DailySalesSummary($data));
})->dailyAt('18:00')->name('email.daily-sales')->withoutOverlapping();

// ──────────────────────────────────────────────────────────────────
// 3. WEEKLY BUSINESS REPORT — Every Monday at 7:00 AM
// ──────────────────────────────────────────────────────────────────
Schedule::call(function () use ($adminEmail, $managerEmail) {
    $weekStart     = Carbon::now()->startOfWeek();
    $weekEnd       = Carbon::now()->endOfWeek();
    $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
    $lastWeekEnd   = Carbon::now()->subWeek()->endOfWeek();

    // This week
    $weekRevenue      = Sale::whereBetween('created_at', [$weekStart, $weekEnd])->sum('total');
    $weekTransactions = Sale::whereBetween('created_at', [$weekStart, $weekEnd])->count();
    $vatCollected     = Sale::whereBetween('created_at', [$weekStart, $weekEnd])->sum('vat_amount');

    // Last week
    $lastWeekRevenue      = Sale::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->sum('total');
    $lastWeekTransactions = Sale::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

    // Outstanding invoices
    $outstandingInvoices = Invoice::where('status', '!=', 'paid')
        ->where('balance_due', '>', 0)->get();

    // Overdue invoices
    $overdueInvoices = Invoice::where('status', '!=', 'paid')
        ->where('balance_due', '>', 0)
        ->where('due_date', '<', Carbon::today())
        ->get();

    // Jobs
    $jobsCreated   = JobCard::whereBetween('created_at', [$weekStart, $weekEnd])->count();
    $jobsCompleted = JobCard::where('status', 'completed')
        ->whereBetween('updated_at', [$weekStart, $weekEnd])->count();

    // New customers
    $newCustomers = Customer::whereBetween('created_at', [$weekStart, $weekEnd])->count();

    // Invoices
    $invoicesRaised = Invoice::whereBetween('created_at', [$weekStart, $weekEnd])->count();
    $invoicesPaid   = Invoice::where('status', 'paid')
        ->whereBetween('updated_at', [$weekStart, $weekEnd])->count();

    // Quotations
    $quotationsSent = \App\Models\Quotation::whereBetween('created_at', [$weekStart, $weekEnd])->count();

    // Top products
    $topProducts = SaleItem::whereHas('sale', fn($q) => $q->whereBetween('created_at', [$weekStart, $weekEnd]))
        ->join('products', 'sale_items.product_id', '=', 'products.id')
        ->select(
            'products.name',
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue')
        )
        ->groupBy('products.id', 'products.name')
        ->orderByDesc('total_revenue')
        ->limit(5)
        ->get();

    // Low stock count
    $lowStockCount = Product::whereColumn('stock_quantity', '<=', 'reorder_level')
        ->where('reorder_level', '>', 0)->count();

    $data = [
        'week_revenue'           => $weekRevenue,
        'week_transactions'      => $weekTransactions,
        'last_week_revenue'      => $lastWeekRevenue,
        'last_week_transactions' => $lastWeekTransactions,
        'vat_collected'          => $vatCollected,
        'outstanding_amount'     => $outstandingInvoices->sum('balance_due'),
        'outstanding_count'      => $outstandingInvoices->count(),
        'overdue_amount'         => $overdueInvoices->sum('balance_due'),
        'overdue_count'          => $overdueInvoices->count(),
        'jobs_created'           => $jobsCreated,
        'jobs_completed'         => $jobsCompleted,
        'new_customers'          => $newCustomers,
        'invoices_raised'        => $invoicesRaised,
        'invoices_paid'          => $invoicesPaid,
        'quotations_sent'        => $quotationsSent,
        'top_products'           => $topProducts,
        'low_stock_count'        => $lowStockCount,
    ];

    Mail::to($adminEmail)->cc($managerEmail)->send(new WeeklyBusinessReport($data));
})->weeklyOn(1, '07:00')->name('email.weekly-report')->withoutOverlapping();

// ──────────────────────────────────────────────────────────────────
// 4. OVERDUE INVOICE ALERT — Every day at 9:00 AM
// ──────────────────────────────────────────────────────────────────
Schedule::call(function () use ($adminEmail) {
    $overdueInvoices = Invoice::with('customer')
        ->where('status', '!=', 'paid')
        ->where('balance_due', '>', 0)
        ->where('due_date', '<', Carbon::today())
        ->orderBy('due_date')
        ->get();

    if ($overdueInvoices->count() > 0) {
        Mail::to($adminEmail)->send(new OverdueInvoiceAlert($overdueInvoices));
    }
})->dailyAt('09:00')->name('email.overdue-invoices')->withoutOverlapping();

// ──────────────────────────────────────────────────────────────────
// 5. DATABASE BACKUP — Every day at 11:00 PM
//    BackupDatabase command also sends the notification email
// ──────────────────────────────────────────────────────────────────
Schedule::command('backup:database')
    ->dailyAt('23:00')
    ->name('backup.database')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// ══════════════════════════════════════════════════════════════════
// TEMPORARY PERMISSIONS
// ══════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────
// 6. AUTO-EXPIRE TEMPORARY PERMISSIONS — Every 5 minutes
//    Marks any grants past their expires_at as 'expired'
// ──────────────────────────────────────────────────────────────────
Schedule::call(function () {
    $expired = TemporaryPermission::expireOverdue();

    if ($expired > 0) {
        Log::info("Gigateam: Auto-expired {$expired} temporary permission(s).");
    }
})->everyFiveMinutes()->name('permissions.expire')->withoutOverlapping();

// ──────────────────────────────────────────────────────────────────
// 7. PERMISSIONS EXPIRING SOON — Every day at 8:00 AM
//    Alerts admin of any permissions expiring within 24 hours
// ──────────────────────────────────────────────────────────────────
Schedule::call(function () use ($adminEmail) {
    $expiringSoon = TemporaryPermission::active()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', now()->addDay())
        ->with(['user', 'grantedBy'])
        ->get();

    if ($expiringSoon->count() > 0) {
        $lines = $expiringSoon->map(fn($p) =>
            "• {$p->user->name} — {$p->permission} — expires {$p->expires_at->format('d M Y H:i')}"
        )->join("\n");

        Mail::raw(
            "Gigateam POS — Permissions Expiring Within 24 Hours\n\n{$lines}\n\nLog in to Administration → Temp Permissions to extend or revoke.",
            fn($m) => $m
                ->to($adminEmail)
                ->subject('⏰ ' . $expiringSoon->count() . ' Permission(s) Expiring Soon — Gigateam POS')
        );

        Log::info("Gigateam: Sent expiry warning for {$expiringSoon->count()} permission(s).");
    }
})->dailyAt('08:00')->name('permissions.expiry-warning')->withoutOverlapping();

// ══════════════════════════════════════════════════════════════════
// MAINTENANCE
// ══════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────
// 8. DAILY MAINTENANCE — Clean up expired tokens and failed jobs
// ──────────────────────────────────────────────────────────────────
Schedule::command('auth:clear-resets')->daily();
Schedule::command('queue:prune-failed')->daily();