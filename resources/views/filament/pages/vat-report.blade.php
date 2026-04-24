<x-filament-panels::page>
<style>
.report-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:16px; }
.report-card h3 { font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; }
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.kpi { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px; text-align:center; }
.kpi.highlight { border-color:#dc2626; background:#fef2f2; }
.kpi .val { font-size:22px; font-weight:700; color:#111827; }
.kpi.highlight .val { color:#dc2626; font-size:26px; }
.kpi .lbl { font-size:11px; color:#9ca3af; margin-top:2px; text-transform:uppercase; letter-spacing:.05em; }
.filter-bar { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.filter-bar label { font-size:12px; color:#6b7280; font-weight:500; margin-bottom:4px; display:block; }
.filter-bar select {
    border:1px solid #d1d5db; border-radius:6px; padding:6px 10px;
    font-size:13px; color:#111; background:#fff; outline:none;
}
table { width:100%; border-collapse:collapse; font-size:13px; }
table th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:8px 12px; text-align:left; border-bottom:1px solid #e5e7eb; }
table td { padding:8px 12px; border-bottom:1px solid #f3f4f6; color:#374151; }
table tr:hover td { background:#fef2f2; }
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.kra-box { background:#1f2937; color:#fff; border-radius:12px; padding:20px; margin-bottom:16px; }
.kra-box h3 { color:#9ca3af; font-size:12px; text-transform:uppercase; letter-spacing:.1em; margin-bottom:12px; }
.kra-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #374151; font-size:13px; }
.kra-row:last-child { border:none; font-size:16px; font-weight:700; color:#fca5a5; }
.kra-label { color:#d1d5db; }
.kra-value { color:#fff; font-weight:600; }
.section-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:4px; }
.section-sub { font-size:13px; color:#6b7280; margin-bottom:20px; }
</style>

<div style="padding:4px 0;">
    <div class="section-title">VAT Report</div>
    <div class="section-sub">Value Added Tax summary — KRA PIN: P051892936Q | Rate: 16%</div>

    {{-- Period Filter --}}
    <div class="report-card" style="padding:16px; margin-bottom:20px;">
        <div class="filter-bar">
            <div>
                <label>Month</label>
                <select wire:model.live="month">
                    @foreach($this->getMonths() as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Year</label>
                <select wire:model.live="year">
                    @foreach($this->getAvailableYears() as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <a href="{{ route('reports.vat.pdf', ['month' => $this->month, 'year' => $this->year]) }}"
                   target="_blank"
                   style="display:inline-block; background:#dc2626; color:#fff; border-radius:6px; padding:7px 16px; font-size:13px; font-weight:600; text-decoration:none;">
                    📄 Export PDF
                </a>
            </div>
        </div>
    </div>

    @php
        $summary  = $this->getVatSummary();
        $salesVat = $this->getVatFromSales();
        $invVat   = $this->getVatFromInvoices();
    @endphp

    {{-- KRA Summary Box --}}
    <div class="kra-box">
        <h3>🧾 KRA VAT Return Summary — {{ $summary['period'] }}</h3>
        <div class="kra-row">
            <span class="kra-label">Registered Business</span>
            <span class="kra-value">Gigateam Solutions Limited</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">KRA PIN</span>
            <span class="kra-value">{{ $summary['pin'] }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">VAT Rate</span>
            <span class="kra-value">{{ $summary['vat_rate'] }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">Taxable Sales (POS)</span>
            <span class="kra-value">KES {{ number_format($summary['taxable_sales'], 2) }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">Taxable Invoices</span>
            <span class="kra-value">KES {{ number_format($summary['taxable_invoices'], 2) }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">Total Taxable Turnover</span>
            <span class="kra-value">KES {{ number_format($summary['total_taxable'], 2) }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">VAT on POS Sales</span>
            <span class="kra-value">KES {{ number_format($summary['sales_vat'], 2) }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">VAT on Invoices</span>
            <span class="kra-value">KES {{ number_format($summary['invoice_vat'], 2) }}</span>
        </div>
        <div class="kra-row">
            <span class="kra-label">TOTAL VAT DUE</span>
            <span class="kra-value">KES {{ number_format($summary['total_vat'], 2) }}</span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi">
            <div class="val">KES {{ number_format($summary['total_taxable'], 0) }}</div>
            <div class="lbl">Total Taxable Turnover</div>
        </div>
        <div class="kpi highlight">
            <div class="val">KES {{ number_format($summary['total_vat'], 0) }}</div>
            <div class="lbl">Total VAT Due</div>
        </div>
        <div class="kpi">
            <div class="val">KES {{ number_format($summary['sales_vat'], 0) }}</div>
            <div class="lbl">VAT from POS Sales</div>
        </div>
        <div class="kpi">
            <div class="val">KES {{ number_format($summary['invoice_vat'], 0) }}</div>
            <div class="lbl">VAT from Invoices</div>
        </div>
    </div>

    <div class="two-col">
        {{-- VAT from POS Sales --}}
        <div class="report-card">
            <h3>Daily VAT — POS Sales</h3>
            @if(count($salesVat) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th style="text-align:right">Txns</th>
                        <th style="text-align:right">Taxable</th>
                        <th style="text-align:right">VAT (16%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesVat as $row)
                    <tr>
                        <td style="font-size:12px;">{{ $row['date'] }}</td>
                        <td style="text-align:right;">{{ $row['transactions'] }}</td>
                        <td style="text-align:right;">KES {{ number_format($row['subtotal'], 0) }}</td>
                        <td style="text-align:right; font-weight:700; color:#dc2626;">KES {{ number_format($row['vat_collected'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f9fafb; font-weight:700;">
                        <td colspan="3">TOTAL</td>
                        <td style="text-align:right; color:#dc2626;">KES {{ number_format($summary['sales_vat'], 0) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
            <p style="color:#9ca3af; text-align:center; padding:20px;">No VAT-inclusive POS sales this period.</p>
            @endif
        </div>

        {{-- VAT from Invoices --}}
        <div class="report-card">
            <h3>VAT — Invoices</h3>
            @if(count($invVat) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th style="text-align:right">Taxable</th>
                        <th style="text-align:right">VAT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invVat as $row)
                    <tr>
                        <td style="font-family:monospace; font-size:12px; color:#dc2626;">{{ $row['invoice_number'] }}</td>
                        <td style="font-size:12px;">{{ $row['client_name'] }}</td>
                        <td style="text-align:right;">KES {{ number_format($row['subtotal'], 0) }}</td>
                        <td style="text-align:right; font-weight:700; color:#dc2626;">KES {{ number_format($row['vat_amount'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f9fafb; font-weight:700;">
                        <td colspan="3">TOTAL</td>
                        <td style="text-align:right; color:#dc2626;">KES {{ number_format($summary['invoice_vat'], 0) }}</td>
                    </tr>
                </tfoot>
            </table>
            @else
            <p style="color:#9ca3af; text-align:center; padding:20px;">No VAT-inclusive invoices this period.</p>
            @endif
        </div>
    </div>
</div>
</x-filament-panels::page>