<x-filament-panels::page>
<style>
.report-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:16px; }
.report-card h3 { font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; }
.kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
.kpi { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; text-align:center; }
.kpi .val { font-size:22px; font-weight:700; color:#111827; }
.kpi .lbl { font-size:11px; color:#9ca3af; margin-top:2px; text-transform:uppercase; letter-spacing:.05em; }
.kpi.red .val { color:#dc2626; }
.kpi.green .val { color:#059669; }
.filter-bar { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.filter-bar label { font-size:12px; color:#6b7280; font-weight:500; margin-bottom:4px; display:block; }
.filter-bar input, .filter-bar select {
    border:1px solid #d1d5db; border-radius:6px; padding:6px 10px;
    font-size:13px; color:#111; background:#fff; outline:none;
}
table { width:100%; border-collapse:collapse; font-size:13px; }
table th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:8px 12px; text-align:left; border-bottom:1px solid #e5e7eb; }
table td { padding:8px 12px; border-bottom:1px solid #f3f4f6; color:#374151; }
table tr:hover td { background:#fef2f2; }
.badge { display:inline-block; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-yellow { background:#fef3c7; color:#92400e; }
.badge-red { background:#fee2e2; color:#991b1b; }
.stock-bar-wrap { width:80px; background:#f3f4f6; border-radius:4px; height:6px; display:inline-block; vertical-align:middle; overflow:hidden; }
.stock-bar { height:6px; border-radius:4px; }
.section-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:4px; }
.section-sub { font-size:13px; color:#6b7280; margin-bottom:20px; }
</style>

<div style="padding:4px 0;">
    <div class="section-title">Stock Report</div>
    <div class="section-sub">Current inventory levels and stock movement history</div>

    @php
        $summary    = $this->getStockSummary();
        $stock      = $this->getCurrentStock();
        $movements  = $this->getRecentMovements();
        $categories = $this->getCategories();
    @endphp

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi">
            <div class="val">{{ number_format($summary['total_products']) }}</div>
            <div class="lbl">Total Products</div>
        </div>
        <div class="kpi red">
            <div class="val">{{ $summary['out_of_stock'] }}</div>
            <div class="lbl">Out of Stock</div>
        </div>
        <div class="kpi" style="border-color:#fbbf24;">
            <div class="val" style="color:#d97706;">{{ $summary['low_stock'] }}</div>
            <div class="lbl">Low Stock</div>
        </div>
        <div class="kpi">
            <div class="val" style="font-size:16px;">KES {{ number_format($summary['total_value'], 0) }}</div>
            <div class="lbl">Stock Cost Value</div>
        </div>
        <div class="kpi green">
            <div class="val" style="font-size:16px;">KES {{ number_format($summary['total_selling_value'], 0) }}</div>
            <div class="lbl">Stock Retail Value</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="report-card" style="padding:16px; margin-bottom:20px;">
        <div class="filter-bar">
            <div>
                <label>Category</label>
                <select wire:model.live="categoryFilter">
                    <option value="">All Categories</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Stock Filter</label>
                <select wire:model.live="stockFilter">
                    <option value="all">All Items</option>
                    <option value="low">Low Stock Only</option>
                    <option value="out">Out of Stock Only</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Stock Table --}}
    <div class="report-card">
        <h3>Current Stock Levels ({{ count($stock) }} items)</h3>
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th style="text-align:center">Stock</th>
                    <th style="text-align:center">Min Level</th>
                    <th>Level</th>
                    <th style="text-align:right">Cost Value</th>
                    <th style="text-align:right">Selling Price</th>
                    <th style="text-align:center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $p)
                @php
                    $pct = $p['threshold'] > 0 ? min(100, round(($p['stock'] / ($p['threshold'] * 3)) * 100)) : ($p['stock'] > 0 ? 100 : 0);
                    $barColor = $p['status'] === 'out' ? '#ef4444' : ($p['status'] === 'low' ? '#f59e0b' : '#10b981');
                @endphp
                <tr>
                    <td style="font-family:monospace; font-size:12px; color:#6b7280;">{{ $p['sku'] }}</td>
                    <td style="font-weight:500;">{{ $p['name'] }}</td>
                    <td style="font-size:12px; color:#6b7280;">{{ $p['category'] }}</td>
                    <td style="text-align:center; font-weight:700; {{ $p['status'] === 'out' ? 'color:#dc2626;' : ($p['status'] === 'low' ? 'color:#d97706;' : '') }}">
                        {{ number_format($p['stock']) }} {{ $p['unit'] }}
                    </td>
                    <td style="text-align:center; color:#9ca3af;">{{ $p['threshold'] }}</td>
                    <td>
                        <div class="stock-bar-wrap">
                            <div class="stock-bar" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                        </div>
                    </td>
                    <td style="text-align:right; font-size:12px;">KES {{ number_format($p['stock_value'], 0) }}</td>
                    <td style="text-align:right; font-weight:600;">KES {{ number_format($p['selling_price'], 0) }}</td>
                    <td style="text-align:center;">
                        @if($p['status'] === 'out')
                            <span class="badge badge-red">Out</span>
                        @elseif($p['status'] === 'low')
                            <span class="badge badge-yellow">Low</span>
                        @else
                            <span class="badge badge-green">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; color:#9ca3af; padding:20px;">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Stock Movements --}}
    <div class="report-card">
        <h3>Recent Stock Movements (last 30)</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Source</th>
                    <th style="text-align:center">Type</th>
                    <th style="text-align:center">Qty</th>
                    <th style="text-align:center">Before</th>
                    <th style="text-align:center">After</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                <tr>
                    <td style="font-size:12px; color:#6b7280;">{{ $m['date'] }}</td>
                    <td style="font-weight:500;">{{ $m['product'] }}</td>
                    <td style="font-size:12px;">{{ ucfirst($m['source']) }}</td>
                    <td style="text-align:center;">
                        @if($m['type'] === 'in')
                            <span class="badge badge-green">IN</span>
                        @else
                            <span class="badge badge-red">OUT</span>
                        @endif
                    </td>
                    <td style="text-align:center; font-weight:700; {{ $m['type'] === 'in' ? 'color:#059669;' : 'color:#dc2626;' }}">
                        {{ $m['type'] === 'in' ? '+' : '' }}{{ $m['quantity'] }}
                    </td>
                    <td style="text-align:center; color:#9ca3af;">{{ $m['before'] }}</td>
                    <td style="text-align:center; font-weight:600;">{{ $m['after'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; color:#9ca3af; padding:20px;">No stock movements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-filament-panels::page>