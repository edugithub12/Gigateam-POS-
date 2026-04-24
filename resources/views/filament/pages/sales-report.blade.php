<x-filament-panels::page>
<style>
.report-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:16px; }
.report-card h3 { font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; }
.kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
.kpi { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; text-align:center; }
.kpi .val { font-size:22px; font-weight:700; color:#111827; }
.kpi .lbl { font-size:11px; color:#9ca3af; margin-top:2px; text-transform:uppercase; letter-spacing:.05em; }
.kpi.red .val { color:#dc2626; }
.filter-bar { display:flex; gap:12px; align-items:flex-end; margin-bottom:20px; flex-wrap:wrap; }
.filter-bar label { font-size:12px; color:#6b7280; font-weight:500; margin-bottom:4px; display:block; }
.filter-bar input, .filter-bar select {
    border:1px solid #d1d5db; border-radius:6px; padding:6px 10px;
    font-size:13px; color:#111; background:#fff; outline:none;
}
.filter-bar input:focus, .filter-bar select:focus { border-color:#dc2626; }
.btn-red { background:#dc2626; color:#fff; border:none; border-radius:6px; padding:7px 16px; font-size:13px; font-weight:600; cursor:pointer; }
.btn-red:hover { background:#b91c1c; }
table { width:100%; border-collapse:collapse; font-size:13px; }
table th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:8px 12px; text-align:left; border-bottom:1px solid #e5e7eb; }
table td { padding:8px 12px; border-bottom:1px solid #f3f4f6; color:#374151; }
table tr:hover td { background:#fef2f2; }
.badge { display:inline-block; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-yellow { background:#fef3c7; color:#92400e; }
.badge-red { background:#fee2e2; color:#991b1b; }
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.method-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f3f4f6; }
.method-row:last-child { border:none; }
.method-label { font-size:13px; color:#374151; font-weight:500; }
.method-bar-wrap { flex:1; margin:0 12px; background:#f3f4f6; border-radius:4px; height:8px; overflow:hidden; }
.method-bar { height:8px; background:#dc2626; border-radius:4px; }
.method-amount { font-size:13px; font-weight:700; color:#111; }
.section-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:4px; }
.section-sub { font-size:13px; color:#6b7280; margin-bottom:20px; }
</style>

<div style="padding:4px 0;">
    <div class="section-title">Sales Report</div>
    <div class="section-sub">Revenue analysis and transaction breakdown — Gigateam Solutions Limited</div>

    {{-- Filter Bar --}}
    <div class="filter-bar report-card" style="padding:16px; margin-bottom:20px;">
        <div>
            <label>From Date</label>
            <input type="date" wire:model.live="dateFrom" value="{{ $this->dateFrom }}">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" wire:model.live="dateTo" value="{{ $this->dateTo }}">
        </div>
        <div>
            <label>Group By</label>
            <select wire:model.live="groupBy">
                <option value="day">Day</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <a href="{{ route('reports.sales.pdf', ['from' => $this->dateFrom, 'to' => $this->dateTo]) }}"
               target="_blank"
               style="display:inline-block; background:#dc2626; color:#fff; border-radius:6px; padding:7px 16px; font-size:13px; font-weight:600; text-decoration:none;">
                📄 Export PDF
            </a>
        </div>
    </div>

    @php
        $summary  = $this->getSummary();
        $data     = $this->getSalesData();
        $payments = $this->getPaymentBreakdown();
        $products = $this->getTopProducts();
        $recent   = $this->getRecentSales();
        $maxPayment = collect($payments)->max('total') ?: 1;
    @endphp

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi">
            <div class="val">KES {{ number_format($summary['total_revenue'], 0) }}</div>
            <div class="lbl">Total Revenue</div>
        </div>
        <div class="kpi">
            <div class="val">{{ number_format($summary['transaction_count']) }}</div>
            <div class="lbl">Transactions</div>
        </div>
        <div class="kpi">
            <div class="val">KES {{ number_format($summary['average_sale'], 0) }}</div>
            <div class="lbl">Avg. Sale Value</div>
        </div>
        <div class="kpi red">
            <div class="val">KES {{ number_format($summary['total_vat'], 0) }}</div>
            <div class="lbl">VAT Collected (16%)</div>
        </div>
        <div class="kpi">
            <div class="val">KES {{ number_format($summary['total_discounts'], 0) }}</div>
            <div class="lbl">Discounts Given</div>
        </div>
    </div>

    {{-- Revenue Table + Payment Breakdown --}}
    <div class="two-col">
        {{-- Revenue by Period --}}
        <div class="report-card">
            <h3>Revenue by {{ ucfirst($this->groupBy) }}</h3>
            @if(count($data) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Sales</th>
                        <th style="text-align:right">Revenue</th>
                        <th style="text-align:right">VAT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr>
                        <td>{{ $row['period'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td style="text-align:right; font-weight:600;">KES {{ number_format($row['revenue'], 0) }}</td>
                        <td style="text-align:right; color:#dc2626;">KES {{ number_format($row['vat'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f9fafb; font-weight:700;">
                        <td colspan="2">TOTAL</td>
                        <td style="text-align:right;">KES {{ number_format($summary['total_revenue'], 0) }}</td>
                        <td style="text-align:right; color:#dc2626;">KES {{ number_format($summary['total_vat'], 0) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
            <p style="color:#9ca3af; text-align:center; padding:20px;">No sales in this period.</p>
            @endif
        </div>

        {{-- Payment Methods --}}
        <div class="report-card">
            <h3>Payment Methods</h3>
            @foreach($payments as $pm)
            <div class="method-row">
                <div class="method-label">{{ ucfirst(str_replace('_',' ', $pm['method'])) }}</div>
                <div class="method-bar-wrap">
                    <div class="method-bar" style="width:{{ round(($pm['total']/$maxPayment)*100) }}%"></div>
                </div>
                <div class="method-amount">KES {{ number_format($pm['total'], 0) }}</div>
            </div>
            @endforeach
            @if(empty($payments))
            <p style="color:#9ca3af; text-align:center; padding:20px;">No payment data.</p>
            @endif
        </div>
    </div>

    {{-- Top Products --}}
    <div class="report-card">
        <h3>Top 10 Products by Revenue</h3>
        @if(count($products) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th style="text-align:right">Qty Sold</th>
                    <th style="text-align:right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $i => $p)
                <tr>
                    <td style="color:#9ca3af;">{{ $i + 1 }}</td>
                    <td style="font-weight:500;">{{ $p->product_name ?? $p['product_name'] }}</td>
                    <td style="text-align:right;">{{ number_format($p->qty_sold ?? $p['qty_sold']) }}</td>
                    <td style="text-align:right; font-weight:700;">KES {{ number_format($p->revenue ?? $p['revenue'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#9ca3af; text-align:center; padding:20px;">No product sales in this period.</p>
        @endif
    </div>

    {{-- Recent Sales --}}
    <div class="report-card">
        <h3>Recent Transactions (last 20)</h3>
        @if(count($recent) > 0)
        <table>
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th style="text-align:right">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent as $s)
                <tr>
                    <td style="font-family:monospace; font-weight:600; color:#dc2626;">{{ $s['sale_number'] }}</td>
                    <td>{{ $s['customer'] }}</td>
                    <td style="color:#6b7280; font-size:12px;">{{ $s['created_at'] }}</td>
                    <td style="text-align:right; font-weight:700;">KES {{ number_format($s['total'], 0) }}</td>
                    <td>
                        @if($s['payment_status'] === 'paid')
                            <span class="badge badge-green">Paid</span>
                        @elseif($s['payment_status'] === 'partial')
                            <span class="badge badge-yellow">Partial</span>
                        @else
                            <span class="badge badge-red">Unpaid</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#9ca3af; text-align:center; padding:20px;">No sales in this period.</p>
        @endif
    </div>
</div>
</x-filament-panels::page>