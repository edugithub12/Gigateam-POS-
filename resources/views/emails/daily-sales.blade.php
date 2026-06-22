@extends('emails.layout', ['badgeText' => 'Daily Sales'])

@section('content')

<div class="alert-box green">
    <div class="alert-title">📊 Sales Summary — {{ now()->format('l, d M Y') }}</div>
    <div class="alert-sub">Here is your end-of-day sales report for today.</div>
</div>

{{-- Stat cards --}}
<div class="stat-row">
    <div class="stat-card green">
        <div class="stat-label">Today's Revenue</div>
        <div class="stat-value">KES {{ number_format($data['today_revenue'], 2) }}</div>
        <div class="stat-sub">{{ $data['today_transactions'] }} transaction(s)</div>
    </div>
    <div class="stat-card {{ $data['today_revenue'] >= $data['yesterday_revenue'] ? 'green' : '' }}">
        <div class="stat-label">Yesterday</div>
        <div class="stat-value">KES {{ number_format($data['yesterday_revenue'], 2) }}</div>
        <div class="stat-sub">{{ $data['yesterday_transactions'] }} transaction(s)</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">This Month</div>
        <div class="stat-value">KES {{ number_format($data['month_revenue'], 2) }}</div>
        <div class="stat-sub">{{ $data['month_transactions'] }} transaction(s)</div>
    </div>
</div>

{{-- Comparison --}}
@php
    $diff = $data['today_revenue'] - $data['yesterday_revenue'];
    $pct  = $data['yesterday_revenue'] > 0
        ? round(($diff / $data['yesterday_revenue']) * 100, 1)
        : 0;
@endphp
@if($data['today_revenue'] >= $data['yesterday_revenue'])
    <div class="success-row">
        📈 Today's revenue is <strong>KES {{ number_format(abs($diff), 2) }}</strong> ({{ abs($pct) }}%) higher than yesterday.
    </div>
@else
    <div class="warning-row">
        📉 Today's revenue is <strong>KES {{ number_format(abs($diff), 2) }}</strong> ({{ abs($pct) }}%) lower than yesterday.
    </div>
@endif

{{-- Payment breakdown --}}
@if(count($data['payment_breakdown'] ?? []) > 0)
<div class="section-heading">Payment Method Breakdown</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Payment Method</th>
            <th class="text-right">Transactions</th>
            <th class="text-right">Amount (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['payment_breakdown'] as $payment)
        <tr>
            <td><strong>{{ ucfirst($payment->method) }}</strong></td>
            <td class="text-right">{{ $payment->count }}</td>
            <td class="text-right">{{ number_format($payment->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Top products --}}
@if(count($data['top_products'] ?? []) > 0)
<div class="section-heading">Top Selling Products Today</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Product</th>
            <th class="text-center">Qty Sold</th>
            <th class="text-right">Revenue (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['top_products'] as $product)
        <tr>
            <td>{{ $product->name }}</td>
            <td class="text-center">{{ $product->total_qty }}</td>
            <td class="text-right">{{ number_format($product->total_revenue, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($data['today_transactions'] == 0)
<div class="alert-box">
    <div class="alert-title">No sales recorded today</div>
    <div class="alert-sub">There were no transactions processed today. If this is unexpected, please check the POS system.</div>
</div>
@endif

<hr class="divider">

<a href="{{ config('app.url') }}/admin/sales" class="btn" style="color:#fff; text-decoration:none;">
    View Full Sales Report →
</a>

@endsection