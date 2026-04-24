<x-filament-panels::page>

<style>
.gt-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.gt-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.gt-grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
.gt-card { background: #fff; border-radius: 1rem; border: 1px solid #f0f0f0; padding: 1.25rem; }
.gt-card-dark { background: var(--fi-bg); border-radius: 1rem; border: 1px solid var(--fi-gray-200); padding: 1.25rem; }
.gt-kpi-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 0.25rem; }
.gt-kpi-value { font-size: 1.6rem; font-weight: 700; color: #111827; line-height: 1.2; }
.gt-kpi-sub { font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem; }
.gt-kpi-link { font-size: 0.7rem; font-weight: 600; margin-top: 0.5rem; display: inline-block; }
.gt-icon-wrap { width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; float: right; }
.gt-green { background: #d1fae5; color: #059669; }
.gt-blue { background: #dbeafe; color: #2563eb; }
.gt-orange { background: #ffedd5; color: #ea580c; }
.gt-purple { background: #ede9fe; color: #7c3aed; }
.gt-red { background: #fee2e2; color: #dc2626; }
.gt-yellow { background: #fef9c3; color: #ca8a04; }
.gt-alert { border-radius: 1rem; padding: 1rem; display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; }
.gt-alert-yellow { background: #fefce8; border: 1px solid #fde047; }
.gt-alert-red { background: #fff1f2; border: 1px solid #fecdd3; }
.gt-alert-orange { background: #fff7ed; border: 1px solid #fed7aa; }
.gt-alert-icon { width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gt-alert-btn { font-size: 0.7rem; padding: 0.35rem 0.75rem; border-radius: 0.5rem; color: #fff; font-weight: 600; text-decoration: none; flex-shrink: 0; }
.gt-section-title { font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 1rem; }
.gt-bar-wrap { display: flex; align-items: flex-end; gap: 3px; height: 8rem; }
.gt-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; }
.gt-bar { width: 100%; background: #3b82f6; border-radius: 3px 3px 0 0; min-height: 4px; }
.gt-bar-label { font-size: 7px; color: #9ca3af; white-space: nowrap; transform: rotate(45deg); transform-origin: left; }
.gt-action-btn { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 0.75rem; text-decoration: none; margin-bottom: 0.5rem; transition: opacity 0.15s; }
.gt-action-btn:hover { opacity: 0.85; }
.gt-action-icon { width: 2rem; height: 2rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gt-action-text { font-size: 0.85rem; font-weight: 500; color: #374151; }
.gt-sale-row { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #f9fafb; }
.gt-sale-row:last-child { border-bottom: none; }
.gt-badge { font-size: 0.65rem; padding: 0.2rem 0.5rem; border-radius: 9999px; font-weight: 600; }
.gt-badge-green { background: #d1fae5; color: #065f46; }
.gt-badge-yellow { background: #fef9c3; color: #854d0e; }
.gt-badge-red { background: #fee2e2; color: #991b1b; }
.gt-stat { text-align: center; }
.gt-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.gt-stat-label { font-size: 0.7rem; color: #6b7280; margin-top: 0.2rem; }
.gt-stat-sub { font-size: 0.65rem; margin-top: 0.15rem; }
.clearfix::after { content: ''; display: table; clear: both; }
@media (max-width: 900px) {
    .gt-grid-4, .gt-grid-3 { grid-template-columns: repeat(2, 1fr); }
    .gt-grid-2-1 { grid-template-columns: 1fr; }
}
</style>

{{-- ── KPI Cards ── --}}
<div class="gt-grid-4">

    <div class="gt-card clearfix">
        <div class="gt-icon-wrap gt-green">💰</div>
        <div class="gt-kpi-label">Today's Sales</div>
        <div class="gt-kpi-value">KES {{ number_format($todayRevenue, 0) }}</div>
        <div class="gt-kpi-sub">{{ $todayCount }} transaction(s)</div>
        <a href="/pos" class="gt-kpi-link" style="color:#059669;">Open POS →</a>
    </div>

    <div class="gt-card clearfix">
        <div class="gt-icon-wrap gt-blue">📊</div>
        <div class="gt-kpi-label">This Month</div>
        <div class="gt-kpi-value">KES {{ number_format($monthRevenue, 0) }}</div>
        <div class="gt-kpi-sub" style="color: {{ $revenueGrowth >= 0 ? '#059669' : '#dc2626' }}">
            {{ $revenueGrowth >= 0 ? '▲' : '▼' }} {{ abs($revenueGrowth) }}% vs last month
        </div>
        <a href="/admin/invoices" class="gt-kpi-link" style="color:#2563eb;">View Invoices →</a>
    </div>

    <div class="gt-card clearfix">
        <div class="gt-icon-wrap gt-orange">⚠️</div>
        <div class="gt-kpi-label">Outstanding</div>
        <div class="gt-kpi-value">KES {{ number_format($unpaidTotal, 0) }}</div>
        <div class="gt-kpi-sub" style="color: {{ $overdueInvoices > 0 ? '#dc2626' : '#9ca3af' }}">
            {{ $overdueInvoices > 0 ? $overdueInvoices . ' overdue' : 'None overdue' }}
        </div>
        <a href="/admin/invoices" class="gt-kpi-link" style="color:#ea580c;">Manage →</a>
    </div>

    <div class="gt-card clearfix">
        <div class="gt-icon-wrap gt-purple">🔧</div>
        <div class="gt-kpi-label">Active Jobs</div>
        <div class="gt-kpi-value">{{ $activeJobs }}</div>
        <div class="gt-kpi-sub">{{ $completedToday }} completed today</div>
        <a href="/admin/job-cards" class="gt-kpi-link" style="color:#7c3aed;">View Jobs →</a>
    </div>

</div>

{{-- ── Alerts ── --}}
@if($pendingQuotations > 0 || $overdueInvoices > 0 || $lowStockItems->count() > 0)
<div style="margin-bottom:1.5rem;">
    @if($pendingQuotations > 0)
    <div class="gt-alert gt-alert-yellow">
        <div class="gt-alert-icon" style="background:#fef9c3;">⏳</div>
        <div style="flex:1;">
            <div style="font-size:0.85rem;font-weight:600;color:#854d0e;">{{ $pendingQuotations }} Quotation{{ $pendingQuotations > 1 ? 's' : '' }} Awaiting Approval</div>
            <div style="font-size:0.75rem;color:#a16207;">Requires your attention</div>
        </div>
        <a href="/admin/quotations" class="gt-alert-btn" style="background:#ca8a04;">Review</a>
    </div>
    @endif
    @if($overdueInvoices > 0)
    <div class="gt-alert gt-alert-red">
        <div class="gt-alert-icon" style="background:#fee2e2;">🔴</div>
        <div style="flex:1;">
            <div style="font-size:0.85rem;font-weight:600;color:#991b1b;">{{ $overdueInvoices }} Overdue Invoice{{ $overdueInvoices > 1 ? 's' : '' }}</div>
            <div style="font-size:0.75rem;color:#b91c1c;">Past due date</div>
        </div>
        <a href="/admin/invoices" class="gt-alert-btn" style="background:#dc2626;">View</a>
    </div>
    @endif
    @if($lowStockItems->count() > 0)
    <div class="gt-alert gt-alert-orange">
        <div class="gt-alert-icon" style="background:#ffedd5;">📦</div>
        <div style="flex:1;">
            <div style="font-size:0.85rem;font-weight:600;color:#9a3412;">{{ $lowStockItems->count() }} Low Stock Item{{ $lowStockItems->count() > 1 ? 's' : '' }}</div>
            <div style="font-size:0.75rem;color:#c2410c;">Needs restocking</div>
        </div>
        <a href="/admin/products" class="gt-alert-btn" style="background:#ea580c;">View</a>
    </div>
    @endif
</div>
@endif

{{-- ── Revenue Chart + Quick Actions ── --}}
<div class="gt-grid-2-1">

    <div class="gt-card">
        <div class="gt-section-title">Revenue — Last 14 Days</div>
        @php $maxRev = max(array_column($chartData, 'revenue')) ?: 1; @endphp
        <div class="gt-bar-wrap">
            @foreach($chartData as $day)
            @php $h = max(4, round(($day['revenue'] / $maxRev) * 100)); @endphp
            <div class="gt-bar-col">
                <div class="gt-bar" style="height:{{ $h }}px;" title="KES {{ number_format($day['revenue'],0) }} on {{ $day['date'] }}"></div>
                <span class="gt-bar-label">{{ $day['date'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="gt-card">
        <div class="gt-section-title">Quick Actions</div>
        <a href="/pos" class="gt-action-btn" style="background:#fff1f2;">
            <div class="gt-action-icon" style="background:#dc2626;">🛒</div>
            <span class="gt-action-text">Open POS</span>
        </a>
        <a href="/admin/invoices/create" class="gt-action-btn" style="background:#eff6ff;">
            <div class="gt-action-icon" style="background:#2563eb;">🧾</div>
            <span class="gt-action-text">New Invoice</span>
        </a>
        <a href="/admin/quotations/create" class="gt-action-btn" style="background:#fefce8;">
            <div class="gt-action-icon" style="background:#ca8a04;">📄</div>
            <span class="gt-action-text">New Quotation</span>
        </a>
        <a href="/admin/job-cards/create" class="gt-action-btn" style="background:#f5f3ff;">
            <div class="gt-action-icon" style="background:#7c3aed;">📋</div>
            <span class="gt-action-text">New Job Card</span>
        </a>
        <a href="/admin/customers/create" class="gt-action-btn" style="background:#f0fdf4;">
            <div class="gt-action-icon" style="background:#059669;">👤</div>
            <span class="gt-action-text">New Customer</span>
        </a>
    </div>
</div>

{{-- ── Recent Sales + Top Products + Low Stock ── --}}
<div class="gt-grid-2-1">

    <div class="gt-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div class="gt-section-title" style="margin-bottom:0;">Recent Sales</div>
            <a href="/admin/sales" style="font-size:0.75rem;color:#2563eb;">View all →</a>
        </div>
        @if($recentSales->isEmpty())
        <p style="font-size:0.85rem;color:#9ca3af;text-align:center;padding:1.5rem 0;">No sales yet</p>
        @else
        @foreach($recentSales as $sale)
        <div class="gt-sale-row">
            <div>
                <div style="font-size:0.85rem;font-weight:600;color:#111827;">{{ $sale->sale_number }}</div>
                <div style="font-size:0.75rem;color:#9ca3af;">{{ $sale->customer?->name ?? 'Walk-in' }} · {{ $sale->created_at->format('d M H:i') }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:0.85rem;font-weight:700;color:#111827;">KES {{ number_format($sale->total, 0) }}</div>
                <span class="gt-badge {{ $sale->payment_status === 'paid' ? 'gt-badge-green' : ($sale->payment_status === 'partial' ? 'gt-badge-yellow' : 'gt-badge-red') }}">
                    {{ ucfirst($sale->payment_status) }}
                </span>
            </div>
        </div>
        @endforeach
        @endif
    </div>

    <div>
        <div class="gt-card" style="margin-bottom:1rem;">
            <div class="gt-section-title">Top Products This Month</div>
            @if($topProducts->isEmpty())
            <p style="font-size:0.75rem;color:#9ca3af;text-align:center;padding:0.75rem 0;">No sales this month</p>
            @else
            @foreach($topProducts as $p)
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                <div style="font-size:0.78rem;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:0.5rem;">{{ $p->name }}</div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:0.78rem;font-weight:700;">KES {{ number_format($p->revenue, 0) }}</div>
                    <div style="font-size:0.65rem;color:#9ca3af;">{{ $p->qty }} sold</div>
                </div>
            </div>
            @endforeach
            @endif
        </div>

        @if($lowStockItems->count() > 0)
        <div class="gt-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <div class="gt-section-title" style="margin-bottom:0;">Low Stock</div>
                <a href="/admin/products" style="font-size:0.7rem;color:#ea580c;">View all →</a>
            </div>
            @foreach($lowStockItems->take(5) as $item)
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem;">
                <div style="font-size:0.78rem;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:0.5rem;">{{ $item->name }}</div>
                <span class="gt-badge {{ $item->stock_quantity <= 0 ? 'gt-badge-red' : 'gt-badge-yellow' }}">
                    {{ $item->stock_quantity }} left
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Stats Footer ── --}}
<div class="gt-grid-4">
    <div class="gt-card gt-stat">
        <div class="gt-stat-value">{{ $totalCustomers }}</div>
        <div class="gt-stat-label">Total Customers</div>
        @if($newThisMonth > 0)
        <div class="gt-stat-sub" style="color:#059669;">+{{ $newThisMonth }} this month</div>
        @endif
    </div>
    <div class="gt-card gt-stat">
        <div class="gt-stat-value">{{ $pendingQuotations + $draftQuotations }}</div>
        <div class="gt-stat-label">Open Quotations</div>
        @if($pendingQuotations > 0)
        <div class="gt-stat-sub" style="color:#ca8a04;">{{ $pendingQuotations }} pending approval</div>
        @endif
    </div>
    <div class="gt-card gt-stat">
        <div class="gt-stat-value">{{ $activeJobs }}</div>
        <div class="gt-stat-label">Active Job Cards</div>
    </div>
    <div class="gt-card gt-stat">
        <div class="gt-stat-value">{{ $unpaidInvoices->count() }}</div>
        <div class="gt-stat-label">Unpaid Invoices</div>
        @if($overdueInvoices > 0)
        <div class="gt-stat-sub" style="color:#dc2626;">{{ $overdueInvoices }} overdue</div>
        @endif
    </div>
</div>

</x-filament-panels::page>