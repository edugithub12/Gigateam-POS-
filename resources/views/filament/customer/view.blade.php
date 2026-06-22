<x-filament-panels::page>

<style>
.crm-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.crm-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.crm-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1.5rem;margin-bottom:1.5rem}
.crm-grid-2-1{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
.crm-card{background:#fff;border-radius:1rem;border:1px solid #f0f0f0;padding:1.25rem;margin-bottom:0}
.crm-kpi-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.25rem}
.crm-kpi-value{font-size:1.5rem;font-weight:700;color:#111827;line-height:1.2}
.crm-kpi-sub{font-size:.72rem;color:#9ca3af;margin-top:.2rem}
.crm-icon{width:2.5rem;height:2.5rem;border-radius:.75rem;display:flex;align-items:center;justify-content:center;float:right}
.crm-green{background:#d1fae5;color:#059669}
.crm-blue{background:#dbeafe;color:#2563eb}
.crm-orange{background:#ffedd5;color:#ea580c}
.crm-red{background:#fee2e2;color:#dc2626}
.crm-purple{background:#ede9fe;color:#7c3aed}
.crm-section{font-size:.85rem;font-weight:600;color:#374151;margin-bottom:1rem}
.crm-row{display:flex;align-items:center;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid #f9fafb}
.crm-row:last-child{border-bottom:none}
.crm-badge{font-size:.65rem;padding:.2rem .5rem;border-radius:9999px;font-weight:600}
.crm-badge-green{background:#d1fae5;color:#065f46}
.crm-badge-yellow{background:#fef9c3;color:#854d0e}
.crm-badge-red{background:#fee2e2;color:#991b1b}
.crm-badge-blue{background:#dbeafe;color:#1e40af}
.crm-badge-purple{background:#ede9fe;color:#5b21b6}
.crm-badge-gray{background:#f3f4f6;color:#374151}
.crm-timeline-dot{width:.65rem;height:.65rem;border-radius:50%;flex-shrink:0;margin-top:.3rem}
.crm-profile-row{display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem}
.crm-profile-label{font-size:.72rem;color:#9ca3af;width:7rem;flex-shrink:0}
.crm-profile-value{font-size:.82rem;color:#111827;font-weight:500}
.clearfix::after{content:'';display:table;clear:both}
@media(max-width:900px){
    .crm-grid-4,.crm-grid-3{grid-template-columns:repeat(2,1fr)}
    .crm-grid-2,.crm-grid-2-1{grid-template-columns:1fr}
}
</style>

@php
    $badgeColor = fn($type, $status) => match(true) {
        $type === 'sale' && $status === 'paid'       => 'crm-badge-green',
        $type === 'sale' && $status === 'partial'    => 'crm-badge-yellow',
        $type === 'sale' && $status === 'unpaid'     => 'crm-badge-red',
        $type === 'invoice' && $status === 'paid'    => 'crm-badge-green',
        $type === 'invoice' && $status === 'unpaid'  => 'crm-badge-red',
        $type === 'invoice' && $status === 'partial' => 'crm-badge-yellow',
        $type === 'quotation'                        => 'crm-badge-blue',
        $type === 'job' && $status === 'completed'   => 'crm-badge-green',
        $type === 'job' && $status === 'in_progress' => 'crm-badge-yellow',
        $type === 'job' && $status === 'cancelled'   => 'crm-badge-red',
        default                                      => 'crm-badge-gray',
    };

    $dotColor = fn($type) => match($type) {
        'sale'      => '#2563eb',
        'invoice'   => '#059669',
        'quotation' => '#ca8a04',
        'job'       => '#7c3aed',
        default     => '#9ca3af',
    };

    $typeLabel = fn($type) => match($type) {
        'sale'      => 'Sale',
        'invoice'   => 'Invoice',
        'quotation' => 'Quote',
        'job'       => 'Job Card',
        default     => ucfirst($type),
    };
@endphp

{{-- ── Profile Header ── --}}
<div class="crm-card" style="margin-bottom:1.5rem;display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap">
    {{-- Avatar --}}
    <div style="width:4rem;height:4rem;border-radius:1rem;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;color:#2563eb;flex-shrink:0">
        {{ strtoupper(substr($customer->name, 0, 1)) }}
    </div>

    {{-- Basic info --}}
    <div style="flex:1;min-width:200px">
        <div style="font-size:1.2rem;font-weight:700;color:#111827">{{ $customer->name }}</div>
        @if($customer->company_name)
        <div style="font-size:.85rem;color:#6b7280;margin-bottom:.5rem">{{ $customer->company_name }}</div>
        @endif
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.4rem">
            <span class="crm-badge crm-badge-blue">{{ ucfirst($customer->type) }}</span>
            <span class="crm-badge {{ $customer->is_active ? 'crm-badge-green' : 'crm-badge-red' }}">
                {{ $customer->is_active ? 'Active' : 'Inactive' }}
            </span>
            @if($customer->area)
            <span class="crm-badge crm-badge-gray">📍 {{ $customer->area }}</span>
            @endif
        </div>
    </div>

    {{-- Contact details --}}
    <div style="min-width:200px">
        @if($customer->phone)
        <div class="crm-profile-row">
            <span class="crm-profile-label">📞 Phone</span>
            <span class="crm-profile-value">{{ $customer->phone }}</span>
        </div>
        @endif
        @if($customer->phone_alt)
        <div class="crm-profile-row">
            <span class="crm-profile-label">📞 Alt Phone</span>
            <span class="crm-profile-value">{{ $customer->phone_alt }}</span>
        </div>
        @endif
        @if($customer->email)
        <div class="crm-profile-row">
            <span class="crm-profile-label">✉️ Email</span>
            <span class="crm-profile-value">{{ $customer->email }}</span>
        </div>
        @endif
        @if($customer->address)
        <div class="crm-profile-row">
            <span class="crm-profile-label">🏠 Address</span>
            <span class="crm-profile-value">{{ $customer->address }}</span>
        </div>
        @endif
    </div>

    {{-- Account stats --}}
    <div style="min-width:160px">
        @if($firstPurchase)
        <div class="crm-profile-row">
            <span class="crm-profile-label">First purchase</span>
            <span class="crm-profile-value">{{ $firstPurchase->format('d M Y') }}</span>
        </div>
        <div class="crm-profile-row">
            <span class="crm-profile-label">Last purchase</span>
            <span class="crm-profile-value">{{ $lastPurchase->format('d M Y') }}</span>
        </div>
        @endif
        @if($customer->credit_limit > 0)
        <div class="crm-profile-row">
            <span class="crm-profile-label">Credit limit</span>
            <span class="crm-profile-value">KES {{ number_format($customer->credit_limit, 0) }}</span>
        </div>
        @endif
        @if($customer->notes)
        <div class="crm-profile-row">
            <span class="crm-profile-label">Notes</span>
            <span class="crm-profile-value" style="font-size:.75rem;color:#6b7280">{{ $customer->notes }}</span>
        </div>
        @endif
    </div>
</div>

{{-- ── KPI Cards ── --}}
<div class="crm-grid-4">
    <div class="crm-card clearfix">
        <div class="crm-icon crm-green">💰</div>
        <div class="crm-kpi-label">Total Spent</div>
        <div class="crm-kpi-value">KES {{ number_format($totalSpent, 0) }}</div>
        <div class="crm-kpi-sub">Avg KES {{ number_format($avgOrderValue, 0) }} / order</div>
    </div>
    <div class="crm-card clearfix">
        <div class="crm-icon crm-red">⚠️</div>
        <div class="crm-kpi-label">Outstanding</div>
        <div class="crm-kpi-value" style="color:{{ $outstandingBalance > 0 ? '#dc2626' : '#059669' }}">
            KES {{ number_format($outstandingBalance, 0) }}
        </div>
        <div class="crm-kpi-sub">{{ $outstandingBalance > 0 ? 'Balance due' : 'Fully paid' }}</div>
    </div>
    <div class="crm-card clearfix">
        <div class="crm-icon crm-blue">🧾</div>
        <div class="crm-kpi-label">Transactions</div>
        <div class="crm-kpi-value">{{ $totalSales + $totalInvoices }}</div>
        <div class="crm-kpi-sub">{{ $totalSales }} sales · {{ $totalInvoices }} invoices</div>
    </div>
    <div class="crm-card clearfix">
        <div class="crm-icon crm-purple">🔧</div>
        <div class="crm-kpi-label">Job Cards</div>
        <div class="crm-kpi-value">{{ $totalJobs }}</div>
        <div class="crm-kpi-sub">{{ $totalQuotes }} quotations</div>
    </div>
</div>

{{-- ── Spend Chart + Payment Behaviour ── --}}
<div class="crm-grid-2" style="margin-bottom:1.5rem">
    <div class="crm-card">
        <div class="crm-section">Monthly Spend — Last 6 Months</div>
        <div style="position:relative;height:180px">
            <canvas id="spendChart"></canvas>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-section">Payment Behaviour</div>
        @if($totalSales > 0)
        <div style="position:relative;height:140px;margin-bottom:1rem">
            <canvas id="paymentChart"></canvas>
        </div>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
            <div style="text-align:center">
                <div style="font-size:1.1rem;font-weight:700;color:#059669">{{ $paidSales }}</div>
                <div style="font-size:.7rem;color:#6b7280">Paid</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:1.1rem;font-weight:700;color:#ca8a04">{{ $partialSales }}</div>
                <div style="font-size:.7rem;color:#6b7280">Partial</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:1.1rem;font-weight:700;color:#dc2626">{{ $unpaidSales }}</div>
                <div style="font-size:.7rem;color:#6b7280">Unpaid</div>
            </div>
        </div>
        @else
        <p style="font-size:.85rem;color:#9ca3af;text-align:center;padding:2rem 0">No sales yet</p>
        @endif
    </div>
</div>

{{-- ── Timeline + Top Products ── --}}
<div class="crm-grid-2-1">
    {{-- Transaction timeline --}}
    <div class="crm-card">
        <div class="crm-section">Transaction History</div>
        @if($timeline->isEmpty())
        <p style="font-size:.85rem;color:#9ca3af;text-align:center;padding:2rem 0">No transactions yet</p>
        @else
        <div style="position:relative;padding-left:1.25rem">
            {{-- Vertical line --}}
            <div style="position:absolute;left:.3rem;top:0;bottom:0;width:2px;background:#f3f4f6"></div>

            @foreach($timeline as $item)
            <div style="display:flex;gap:1rem;margin-bottom:1rem;position:relative">
                <div class="crm-timeline-dot" style="background:{{ $dotColor($item['type']) }};position:absolute;left:-1.1rem;top:.3rem"></div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
                        <div>
                            <span style="font-size:.7rem;font-weight:600;color:#9ca3af;text-transform:uppercase">{{ $typeLabel($item['type']) }}</span>
                            <a href="{{ $item['url'] }}" style="font-size:.85rem;font-weight:600;color:#111827;margin-left:.4rem;text-decoration:none;hover:underline">
                                {{ $item['ref'] }}
                            </a>
                        </div>
                        <div style="display:flex;align-items:center;gap:.5rem">
                            <span style="font-size:.85rem;font-weight:700;color:#111827">KES {{ number_format($item['amount'], 0) }}</span>
                            <span class="crm-badge {{ $badgeColor($item['type'], $item['status']) }}">{{ ucfirst(str_replace('_',' ',$item['status'])) }}</span>
                        </div>
                    </div>
                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">{{ \Carbon\Carbon::parse($item['date'])->format('d M Y H:i') }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Top products + quick links --}}
    <div>
        <div class="crm-card" style="margin-bottom:1rem">
            <div class="crm-section">Most Purchased Products</div>
            @if($topProducts->isEmpty())
            <p style="font-size:.8rem;color:#9ca3af;text-align:center;padding:1rem 0">No data</p>
            @else
            @foreach($topProducts as $p)
            <div class="crm-row">
                <div style="font-size:.8rem;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:.5rem">{{ $p->name }}</div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:.8rem;font-weight:700">KES {{ number_format($p->revenue, 0) }}</div>
                    <div style="font-size:.65rem;color:#9ca3af">{{ $p->qty }} units</div>
                </div>
            </div>
            @endforeach
            @endif
        </div>

        <div class="crm-card">
            <div class="crm-section">Quick Links</div>
            <a href="/admin/sales?tableFilters[customer_id][value]={{ $customer->id }}"
               style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f9fafb;text-decoration:none">
                <span style="font-size:.82rem;color:#374151">🧾 All Sales</span>
                <span class="crm-badge crm-badge-blue">{{ $totalSales }}</span>
            </a>
            <a href="/admin/invoices?tableFilters[customer_id][value]={{ $customer->id }}"
               style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f9fafb;text-decoration:none">
                <span style="font-size:.82rem;color:#374151">📋 All Invoices</span>
                <span class="crm-badge crm-badge-blue">{{ $totalInvoices }}</span>
            </a>
            <a href="/admin/quotations?tableFilters[customer_id][value]={{ $customer->id }}"
               style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #f9fafb;text-decoration:none">
                <span style="font-size:.82rem;color:#374151">📄 All Quotations</span>
                <span class="crm-badge crm-badge-blue">{{ $totalQuotes }}</span>
            </a>
            <a href="/admin/job-cards?tableFilters[customer_id][value]={{ $customer->id }}"
               style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;text-decoration:none">
                <span style="font-size:.82rem;color:#374151">🔧 All Job Cards</span>
                <span class="crm-badge crm-badge-blue">{{ $totalJobs }}</span>
            </a>
        </div>
    </div>
</div>

{{-- ── Chart.js ── --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly spend chart
const spendLabels  = @json(array_column($monthlySpend, 'month'));
const spendData    = @json(array_column($monthlySpend, 'revenue'));

new Chart(document.getElementById('spendChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: spendLabels,
        datasets: [{
            label: 'Spend (KES)',
            data: spendData,
            backgroundColor: 'rgba(37,99,235,0.75)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => 'KES '+(v>=1000?(v/1000).toFixed(0)+'k':v) },
                grid: { color: 'rgba(0,0,0,0.04)' },
            },
            x: { grid: { display: false } }
        }
    }
});

// Payment behaviour donut
@if($totalSales > 0)
new Chart(document.getElementById('paymentChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Paid', 'Partial', 'Unpaid'],
        datasets: [{
            data: [{{ $paidSales }}, {{ $partialSales }}, {{ $unpaidSales }}],
            backgroundColor: ['#10b981','#f59e0b','#ef4444'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { font: { size: 10 }, boxWidth: 10, padding: 8 } }
        },
        cutout: '65%',
    }
});
@endif
</script>

</x-filament-panels::page>