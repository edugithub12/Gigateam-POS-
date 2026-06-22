@extends('emails.layout', ['badgeText' => 'Weekly Report'])

@section('content')

<div class="alert-box green">
    <div class="alert-title">📈 Weekly Business Report</div>
    <div class="alert-sub">
        {{ now()->startOfWeek()->format('d M Y') }} — {{ now()->endOfWeek()->format('d M Y') }}
    </div>
</div>

{{-- Revenue stats --}}
<div class="stat-row">
    <div class="stat-card green">
        <div class="stat-label">Week Revenue</div>
        <div class="stat-value">KES {{ number_format($data['week_revenue'], 2) }}</div>
        <div class="stat-sub">{{ $data['week_transactions'] }} sales</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Last Week</div>
        <div class="stat-value">KES {{ number_format($data['last_week_revenue'], 2) }}</div>
        <div class="stat-sub">{{ $data['last_week_transactions'] }} sales</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Outstanding</div>
        <div class="stat-value">KES {{ number_format($data['outstanding_amount'], 2) }}</div>
        <div class="stat-sub">{{ $data['outstanding_count'] }} invoice(s)</div>
    </div>
</div>

{{-- Week comparison --}}
@php
    $diff = $data['week_revenue'] - $data['last_week_revenue'];
    $pct  = $data['last_week_revenue'] > 0
        ? round(($diff / $data['last_week_revenue']) * 100, 1)
        : 0;
@endphp
@if($diff >= 0)
    <div class="success-row">
        📈 This week's revenue is <strong>KES {{ number_format(abs($diff), 2) }}</strong> ({{ abs($pct) }}%) higher than last week.
    </div>
@else
    <div class="warning-row">
        📉 This week's revenue is <strong>KES {{ number_format(abs($diff), 2) }}</strong> ({{ abs($pct) }}%) lower than last week.
    </div>
@endif

{{-- Business summary --}}
<div class="section-heading">Week at a Glance</div>
<table class="data-table">
    <tbody>
        <tr>
            <td class="label-col">Total Revenue</td>
            <td class="value-col">KES {{ number_format($data['week_revenue'], 2) }}</td>
        </tr>
        <tr>
            <td class="label-col">Total Transactions</td>
            <td class="value-col">{{ $data['week_transactions'] }}</td>
        </tr>
        <tr>
            <td class="label-col">New Customers</td>
            <td class="value-col">{{ $data['new_customers'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Invoices Raised</td>
            <td class="value-col">{{ $data['invoices_raised'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Invoices Paid</td>
            <td class="value-col">{{ $data['invoices_paid'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Job Cards Created</td>
            <td class="value-col">{{ $data['jobs_created'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Job Cards Completed</td>
            <td class="value-col">{{ $data['jobs_completed'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Quotations Sent</td>
            <td class="value-col">{{ $data['quotations_sent'] }}</td>
        </tr>
        <tr>
            <td class="label-col">VAT Collected</td>
            <td class="value-col">KES {{ number_format($data['vat_collected'], 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- Top products --}}
@if(count($data['top_products'] ?? []) > 0)
<div class="section-heading">Top 5 Products This Week</div>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th class="text-center">Qty Sold</th>
            <th class="text-right">Revenue (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['top_products'] as $i => $product)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $product->name }}</td>
            <td class="text-center">{{ $product->total_qty }}</td>
            <td class="text-right">{{ number_format($product->total_revenue, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Overdue invoices warning --}}
@if($data['overdue_count'] > 0)
<div class="alert-box">
    <div class="alert-title">🔴 {{ $data['overdue_count'] }} Overdue Invoice(s) — KES {{ number_format($data['overdue_amount'], 2) }}</div>
    <div class="alert-sub">These invoices are past their due date. Follow up with clients to recover outstanding payments.</div>
</div>
@endif

{{-- Low stock warning --}}
@if($data['low_stock_count'] > 0)
<div class="alert-box orange">
    <div class="alert-title">⚠️ {{ $data['low_stock_count'] }} Product(s) Low on Stock</div>
    <div class="alert-sub">Check the stock report and place orders with your suppliers before you run out.</div>
</div>
@endif

<hr class="divider">

<a href="{{ config('app.url') }}/admin" class="btn" style="color:#fff; text-decoration:none; margin-right:8px;">
    Open Dashboard →
</a>
<a href="{{ config('app.url') }}/reports/sales/pdf" class="btn btn-green" style="color:#fff; text-decoration:none;">
    Download Sales PDF →
</a>

@endsection