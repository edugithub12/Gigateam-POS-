{{-- VAT REPORT PDF: resources/views/pdf/reports/vat.blade.php --}}
@extends('pdf.layout')
@section('doc-title', 'VAT Report — ' . $summary['period'])
@section('content')
<h2 style="color:#dc2626; font-size:18px; margin:0 0 4px;">VAT Report</h2>
<p style="color:#666; font-size:12px; margin:0 0 4px;">Period: {{ $summary['period'] }}</p>
<p style="color:#666; font-size:12px; margin:0 0 16px;">KRA PIN: {{ $summary['pin'] }} | Rate: 16%</p>

<table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
    <tr>
        <td style="background:#1f2937; color:#fff; padding:12px; border-radius:6px; text-align:center;">
            <div style="font-size:20px; font-weight:700;">KES {{ number_format($summary['total_taxable'], 2) }}</div>
            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase;">Total Taxable Turnover</div>
        </td>
        <td style="width:12px;"></td>
        <td style="background:#dc2626; color:#fff; padding:12px; border-radius:6px; text-align:center;">
            <div style="font-size:20px; font-weight:700;">KES {{ number_format($summary['total_vat'], 2) }}</div>
            <div style="font-size:10px; color:#fca5a5; text-transform:uppercase;">Total VAT Due</div>
        </td>
    </tr>
</table>

<h3 style="font-size:13px; border-bottom:2px solid #dc2626; padding-bottom:6px; margin-bottom:10px;">VAT from POS Sales</h3>
<table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:16px;">
    <thead>
        <tr style="background:#dc2626; color:#fff;">
            <th style="padding:6px 8px; text-align:left;">Date</th>
            <th style="padding:6px 8px; text-align:center;">Transactions</th>
            <th style="padding:6px 8px; text-align:right;">Taxable (KES)</th>
            <th style="padding:6px 8px; text-align:right;">VAT 16% (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salesVat as $i => $row)
        <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f9fafb' }};">
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb;">{{ $row->date }}</td>
            <td style="padding:5px 8px; text-align:center; border-bottom:1px solid #e5e7eb;">{{ $row->transactions }}</td>
            <td style="padding:5px 8px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ number_format($row->subtotal, 2) }}</td>
            <td style="padding:5px 8px; text-align:right; font-weight:700; color:#dc2626; border-bottom:1px solid #e5e7eb;">{{ number_format($row->vat_collected, 2) }}</td>
        </tr>
        @endforeach
        @if($salesVat->isEmpty())
        <tr><td colspan="4" style="padding:10px; text-align:center; color:#9ca3af;">No VAT-inclusive POS sales this period.</td></tr>
        @endif
        <tr style="background:#f9fafb; font-weight:700;">
            <td colspan="3" style="padding:7px 8px;">SUBTOTAL</td>
            <td style="padding:7px 8px; text-align:right; color:#dc2626;">{{ number_format($summary['sales_vat'], 2) }}</td>
        </tr>
    </tbody>
</table>

<h3 style="font-size:13px; border-bottom:2px solid #dc2626; padding-bottom:6px; margin-bottom:10px;">VAT from Invoices</h3>
<table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:16px;">
    <thead>
        <tr style="background:#374151; color:#fff;">
            <th style="padding:6px 8px; text-align:left;">Invoice #</th>
            <th style="padding:6px 8px; text-align:left;">Client</th>
            <th style="padding:6px 8px; text-align:right;">Taxable (KES)</th>
            <th style="padding:6px 8px; text-align:right;">VAT 16% (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoiceVat as $i => $inv)
        <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f9fafb' }};">
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb; color:#dc2626; font-weight:600;">{{ $inv->invoice_number }}</td>
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb;">{{ $inv->client_name }}</td>
            <td style="padding:5px 8px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ number_format($inv->subtotal, 2) }}</td>
            <td style="padding:5px 8px; text-align:right; font-weight:700; color:#dc2626; border-bottom:1px solid #e5e7eb;">{{ number_format($inv->vat_amount, 2) }}</td>
        </tr>
        @endforeach
        @if($invoiceVat->isEmpty())
        <tr><td colspan="4" style="padding:10px; text-align:center; color:#9ca3af;">No VAT-inclusive invoices this period.</td></tr>
        @endif
        <tr style="background:#f9fafb; font-weight:700;">
            <td colspan="3" style="padding:7px 8px;">SUBTOTAL</td>
            <td style="padding:7px 8px; text-align:right; color:#dc2626;">{{ number_format($summary['invoice_vat'], 2) }}</td>
        </tr>
    </tbody>
</table>

<div style="background:#111827; color:#fff; border-radius:8px; padding:16px; text-align:center;">
    <div style="font-size:13px; color:#9ca3af; margin-bottom:4px;">TOTAL VAT DUE — {{ $summary['period'] }}</div>
    <div style="font-size:28px; font-weight:700; color:#f87171;">KES {{ number_format($summary['total_vat'], 2) }}</div>
    <div style="font-size:11px; color:#6b7280; margin-top:4px;">Gigateam Solutions Limited | PIN: {{ $summary['pin'] }}</div>
</div>
@endsection