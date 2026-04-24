@extends('pdf.layout')
@section('doc-title', 'Sales Report')
@section('content')
<h2 style="color:#dc2626; font-size:18px; margin:0 0 4px;">Sales Report</h2>
<p style="color:#666; font-size:12px; margin:0 0 16px;">Period: {{ $from }} to {{ $to }}</p>

<table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
    <tr>
        <td style="background:#f9fafb; border:1px solid #e5e7eb; padding:10px; text-align:center; border-radius:6px;">
            <div style="font-size:18px; font-weight:700; color:#111;">KES {{ number_format($summary['total_revenue'], 2) }}</div>
            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase;">Total Revenue</div>
        </td>
        <td style="width:10px;"></td>
        <td style="background:#f9fafb; border:1px solid #e5e7eb; padding:10px; text-align:center; border-radius:6px;">
            <div style="font-size:18px; font-weight:700; color:#dc2626;">KES {{ number_format($summary['total_vat'], 2) }}</div>
            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase;">VAT Collected</div>
        </td>
        <td style="width:10px;"></td>
        <td style="background:#f9fafb; border:1px solid #e5e7eb; padding:10px; text-align:center; border-radius:6px;">
            <div style="font-size:18px; font-weight:700; color:#111;">{{ $summary['transaction_count'] }}</div>
            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase;">Transactions</div>
        </td>
    </tr>
</table>

<h3 style="font-size:13px; color:#374151; border-bottom:2px solid #dc2626; padding-bottom:6px; margin-bottom:10px;">Daily Revenue Breakdown</h3>
<table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:16px;">
    <thead>
        <tr style="background:#dc2626; color:#fff;">
            <th style="padding:6px 8px; text-align:left;">Date</th>
            <th style="padding:6px 8px; text-align:center;">Transactions</th>
            <th style="padding:6px 8px; text-align:right;">Revenue (KES)</th>
            <th style="padding:6px 8px; text-align:right;">VAT (KES)</th>
            <th style="padding:6px 8px; text-align:right;">Discounts (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $i => $row)
        <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f9fafb' }};">
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb;">{{ $row->date }}</td>
            <td style="padding:5px 8px; text-align:center; border-bottom:1px solid #e5e7eb;">{{ $row->count }}</td>
            <td style="padding:5px 8px; text-align:right; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ number_format($row->revenue, 2) }}</td>
            <td style="padding:5px 8px; text-align:right; color:#dc2626; border-bottom:1px solid #e5e7eb;">{{ number_format($row->vat, 2) }}</td>
            <td style="padding:5px 8px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ number_format($row->discounts, 2) }}</td>
        </tr>
        @endforeach
        <tr style="background:#f9fafb; font-weight:700; font-size:12px;">
            <td colspan="2" style="padding:7px 8px;">TOTAL</td>
            <td style="padding:7px 8px; text-align:right;">{{ number_format($summary['total_revenue'], 2) }}</td>
            <td style="padding:7px 8px; text-align:right; color:#dc2626;">{{ number_format($summary['total_vat'], 2) }}</td>
            <td style="padding:7px 8px; text-align:right;">{{ number_format($summary['total_discounts'], 2) }}</td>
        </tr>
    </tbody>
</table>

<h3 style="font-size:13px; color:#374151; border-bottom:2px solid #dc2626; padding-bottom:6px; margin-bottom:10px;">Payment Method Breakdown</h3>
<table style="width:100%; border-collapse:collapse; font-size:11px;">
    <thead>
        <tr style="background:#374151; color:#fff;">
            <th style="padding:6px 8px; text-align:left;">Payment Method</th>
            <th style="padding:6px 8px; text-align:center;">Count</th>
            <th style="padding:6px 8px; text-align:right;">Total (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $pm)
        <tr>
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb;">{{ ucfirst(str_replace('_', ' ', $pm->method)) }}</td>
            <td style="padding:5px 8px; text-align:center; border-bottom:1px solid #e5e7eb;">{{ $pm->count }}</td>
            <td style="padding:5px 8px; text-align:right; font-weight:700; border-bottom:1px solid #e5e7eb;">{{ number_format($pm->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection