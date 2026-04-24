@extends('pdf.layout')
@section('doc-title', 'Statement — ' . $customer->name)
@section('content')
<h2 style="color:#dc2626; font-size:18px; margin:0 0 4px;">Account Statement</h2>
<p style="color:#666; font-size:12px; margin:0 0 16px;">Period: {{ $from }} to {{ $to }}</p>

{{-- Customer Info --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
    <tr>
        <td style="background:#f9fafb; border:1px solid #e5e7eb; padding:10px; border-radius:6px; width:50%;">
            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase; margin-bottom:2px;">Customer</div>
            <div style="font-size:15px; font-weight:700; color:#111;">{{ $customer->name }}</div>
            @if($customer->phone) <div style="font-size:12px; color:#374151;">{{ $customer->phone }}</div> @endif
            @if($customer->email) <div style="font-size:12px; color:#374151;">{{ $customer->email }}</div> @endif
        </td>
        <td style="width:12px;"></td>
        <td style="background:#1f2937; color:#fff; padding:10px; border-radius:6px; text-align:center; width:50%;">
            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase; margin-bottom:4px;">Balance Due</div>
            <div style="font-size:22px; font-weight:700; color:{{ $summary['balance_due'] > 0 ? '#f87171' : '#34d399' }};">
                KES {{ number_format($summary['balance_due'], 2) }}
            </div>
        </td>
    </tr>
</table>

{{-- Transactions --}}
<table style="width:100%; border-collapse:collapse; font-size:10px; margin-bottom:16px;">
    <thead>
        <tr style="background:#dc2626; color:#fff;">
            <th style="padding:6px 8px; text-align:left;">Date</th>
            <th style="padding:6px 8px; text-align:left;">Type</th>
            <th style="padding:6px 8px; text-align:left;">Reference</th>
            <th style="padding:6px 8px; text-align:left;">Description</th>
            <th style="padding:6px 8px; text-align:right;">Debit (KES)</th>
            <th style="padding:6px 8px; text-align:right;">Credit (KES)</th>
            <th style="padding:6px 8px; text-align:right;">Balance (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lines as $i => $line)
        <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f9fafb' }};">
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb; white-space:nowrap;">{{ $line['date'] }}</td>
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb;">{{ $line['type'] }}</td>
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb; color:#dc2626; font-weight:600;">{{ $line['reference'] }}</td>
            <td style="padding:5px 8px; border-bottom:1px solid #e5e7eb;">{{ $line['description'] }}</td>
            <td style="padding:5px 8px; text-align:right; border-bottom:1px solid #e5e7eb;">
                {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}
            </td>
            <td style="padding:5px 8px; text-align:right; color:#059669; border-bottom:1px solid #e5e7eb;">
                {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}
            </td>
            <td style="padding:5px 8px; text-align:right; font-weight:700; color:{{ $line['balance'] > 0 ? '#dc2626' : '#059669' }}; border-bottom:1px solid #e5e7eb;">
                {{ number_format($line['balance'], 2) }}
            </td>
        </tr>
        @endforeach
        <tr style="background:#f9fafb; font-weight:700; font-size:11px;">
            <td colspan="4" style="padding:7px 8px;">TOTALS</td>
            <td style="padding:7px 8px; text-align:right;">{{ number_format($summary['total_invoiced'], 2) }}</td>
            <td style="padding:7px 8px; text-align:right; color:#059669;">{{ number_format($summary['total_paid'], 2) }}</td>
            <td style="padding:7px 8px; text-align:right; color:{{ $summary['balance_due'] > 0 ? '#dc2626' : '#059669' }};">
                {{ number_format($summary['balance_due'], 2) }}
            </td>
        </tr>
    </tbody>
</table>

<p style="font-size:10px; color:#9ca3af; text-align:center; font-style:italic; margin-top:8px;">
    Accounts are due on demand. For queries contact: sales@gigateamltd.com | +254 111292948
</p>
@endsection