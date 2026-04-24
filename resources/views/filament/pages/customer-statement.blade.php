<x-filament-panels::page>
<style>
.report-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:16px; }
.report-card h3 { font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; }
.filter-bar { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.filter-bar label { font-size:12px; color:#6b7280; font-weight:500; margin-bottom:4px; display:block; }
.filter-bar input, .filter-bar select {
    border:1px solid #d1d5db; border-radius:6px; padding:6px 10px;
    font-size:13px; color:#111; background:#fff; outline:none; min-width:180px;
}
table { width:100%; border-collapse:collapse; font-size:13px; }
table th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:8px 12px; text-align:left; border-bottom:1px solid #e5e7eb; }
table td { padding:8px 12px; border-bottom:1px solid #f3f4f6; color:#374151; }
table tr:hover td { background:#fef2f2; }
.badge { display:inline-block; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
.badge-green { background:#d1fae5; color:#065f46; }
.badge-blue { background:#dbeafe; color:#1e40af; }
.badge-red { background:#fee2e2; color:#991b1b; }
.customer-info-box { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:20px; }
.info-item { background:#f9fafb; border-radius:8px; padding:12px 16px; }
.info-item .lbl { font-size:11px; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; }
.info-item .val { font-size:16px; font-weight:700; color:#111; margin-top:2px; }
.balance-box { background:#111827; color:#fff; border-radius:12px; padding:20px; display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.balance-box .label { font-size:13px; color:#9ca3af; }
.balance-box .amount { font-size:28px; font-weight:700; }
.balance-box .amount.green { color:#34d399; }
.balance-box .amount.red { color:#f87171; }
.empty-state { text-align:center; padding:40px; color:#9ca3af; }
.empty-state .icon { font-size:40px; margin-bottom:12px; }
.section-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:4px; }
.section-sub { font-size:13px; color:#6b7280; margin-bottom:20px; }
</style>

<div style="padding:4px 0;">
    <div class="section-title">Customer Statement</div>
    <div class="section-sub">Full transaction history and account balance per customer</div>

    {{-- Filters --}}
    <div class="report-card" style="padding:16px; margin-bottom:20px;">
        <div class="filter-bar">
            <div>
                <label>Customer</label>
                <select wire:model.live="customerId" style="min-width:220px;">
                    <option value="">— Select Customer —</option>
                    @foreach($this->getCustomers() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>From Date</label>
                <input type="date" wire:model.live="dateFrom">
            </div>
            <div>
                <label>To Date</label>
                <input type="date" wire:model.live="dateTo">
            </div>
            @if($this->customerId)
            <div>
                <label>&nbsp;</label>
                <a href="{{ route('reports.statement.pdf', ['customer' => $this->customerId, 'from' => $this->dateFrom, 'to' => $this->dateTo]) }}"
                   target="_blank"
                   style="display:inline-block; background:#dc2626; color:#fff; border-radius:6px; padding:7px 16px; font-size:13px; font-weight:600; text-decoration:none;">
                    📄 Export PDF
                </a>
            </div>
            @endif
        </div>
    </div>

    @if(!$this->customerId)
    <div class="report-card">
        <div class="empty-state">
            <div class="icon">👤</div>
            <div style="font-size:15px; font-weight:600; color:#374151; margin-bottom:4px;">Select a Customer</div>
            <div>Choose a customer above to view their full account statement.</div>
        </div>
    </div>
    @else
    @php
        $customer = $this->getCustomer();
        $lines    = $this->getStatementLines();
        $summary  = $this->getStatementSummary();
    @endphp

    {{-- Customer Info --}}
    @if($customer)
    <div class="customer-info-box">
        <div class="info-item">
            <div class="lbl">Customer</div>
            <div class="val">{{ $customer->name }}</div>
        </div>
        <div class="info-item">
            <div class="lbl">Phone</div>
            <div class="val" style="font-size:14px;">{{ $customer->phone ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="lbl">Email</div>
            <div class="val" style="font-size:14px;">{{ $customer->email ?? '—' }}</div>
        </div>
    </div>
    @endif

    {{-- Balance Summary --}}
    <div class="balance-box">
        <div>
            <div class="label">Total Invoiced</div>
            <div style="font-size:20px; font-weight:700; color:#e5e7eb;">KES {{ number_format($summary['total_invoiced'], 2) }}</div>
        </div>
        <div>
            <div class="label">Total Paid</div>
            <div style="font-size:20px; font-weight:700; color:#34d399;">KES {{ number_format($summary['total_paid'], 2) }}</div>
        </div>
        <div>
            <div class="label">Balance Due</div>
            <div class="amount {{ $summary['balance_due'] > 0 ? 'red' : 'green' }}">
                KES {{ number_format($summary['balance_due'], 2) }}
            </div>
        </div>
        <div style="color:#6b7280; font-size:12px; text-align:right;">
            Period: {{ $this->dateFrom }} to {{ $this->dateTo }}
        </div>
    </div>

    {{-- Statement Lines --}}
    <div class="report-card">
        <h3>Transaction History</h3>
        @if(count($lines) > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th style="text-align:right">Debit (KES)</th>
                    <th style="text-align:right">Credit (KES)</th>
                    <th style="text-align:right">Balance (KES)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr>
                    <td style="font-size:12px; color:#6b7280; white-space:nowrap;">{{ $line['date'] }}</td>
                    <td>
                        @if($line['type'] === 'Invoice')
                            <span class="badge badge-blue">Invoice</span>
                        @elseif($line['type'] === 'Payment')
                            <span class="badge badge-green">Payment</span>
                        @else
                            <span class="badge" style="background:#f3f4f6; color:#374151;">Sale</span>
                        @endif
                    </td>
                    <td style="font-family:monospace; font-size:12px; color:#dc2626;">{{ $line['reference'] }}</td>
                    <td style="font-size:12px;">{{ $line['description'] }}</td>
                    <td style="text-align:right; font-weight:{{ $line['debit'] > 0 ? '600' : '400' }}; color:{{ $line['debit'] > 0 ? '#374151' : '#d1d5db' }}">
                        {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}
                    </td>
                    <td style="text-align:right; font-weight:{{ $line['credit'] > 0 ? '600' : '400' }}; color:{{ $line['credit'] > 0 ? '#059669' : '#d1d5db' }}">
                        {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}
                    </td>
                    <td style="text-align:right; font-weight:700; color:{{ $line['balance'] > 0 ? '#dc2626' : '#059669' }}">
                        {{ number_format($line['balance'], 2) }}
                    </td>
                    <td>
                        @if(in_array($line['status'], ['paid']))
                            <span class="badge badge-green">Paid</span>
                        @elseif($line['status'] === 'partial')
                            <span class="badge" style="background:#fef3c7; color:#92400e;">Partial</span>
                        @else
                            <span class="badge badge-red">Unpaid</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f9fafb; font-weight:700; font-size:13px;">
                    <td colspan="4">TOTALS</td>
                    <td style="text-align:right;">{{ number_format($summary['total_invoiced'], 2) }}</td>
                    <td style="text-align:right; color:#059669;">{{ number_format($summary['total_paid'], 2) }}</td>
                    <td style="text-align:right; color:{{ $summary['balance_due'] > 0 ? '#dc2626' : '#059669' }}">
                        {{ number_format($summary['balance_due'], 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="empty-state">
            <div class="icon">📋</div>
            <div>No transactions found for this customer in the selected period.</div>
        </div>
        @endif
    </div>
    @endif
</div>
</x-filament-panels::page>