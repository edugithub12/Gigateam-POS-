@extends('emails.layout', ['badgeText' => 'Overdue Alert'])

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
<tr>
    <td style="border-left:4px solid #DC2626;background:#fdf2f2;padding:14px 16px;border-radius:0 4px 4px 0;">
        <p style="margin:0;font-weight:700;font-size:15px;color:#1a1a1a;">&#128308; Overdue Invoice Alert &mdash; {{ now()->format('l, d M Y') }}</p>
        <p style="margin:4px 0 0;font-size:13px;color:#666;">
            {{ $invoices->count() }} invoice(s) are past their due date.
            Total outstanding: <strong>KES {{ number_format($invoices->sum('balance_due'), 2) }}</strong>
        </p>
    </td>
</tr>
</table>

<p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Overdue Invoices</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:4px;font-size:12px;">
    <thead>
        <tr style="background:#1a1a1a;">
            <th style="color:#fff;padding:8px 10px;text-align:left;font-size:10px;text-transform:uppercase;">Invoice #</th>
            <th style="color:#fff;padding:8px 10px;text-align:left;font-size:10px;text-transform:uppercase;">Client</th>
            <th style="color:#fff;padding:8px 10px;text-align:center;font-size:10px;text-transform:uppercase;">Due Date</th>
            <th style="color:#fff;padding:8px 10px;text-align:center;font-size:10px;text-transform:uppercase;">Days Overdue</th>
            <th style="color:#fff;padding:8px 10px;text-align:right;font-size:10px;text-transform:uppercase;">Balance (KES)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $i => $invoice)
        @php $daysOverdue = now()->diffInDays(\Carbon\Carbon::parse($invoice->due_date), false) * -1; @endphp
        <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#fafafa' }};border-bottom:1px solid #f0f0f0;">
            <td style="padding:8px 10px;color:#DC2626;font-weight:700;">{{ $invoice->invoice_number ?? $invoice->reference ?? '—' }}</td>
            <td style="padding:8px 10px;color:#333;">
                {{ $invoice->customer->name ?? '—' }}
                @if($invoice->customer->phone ?? false)
                <br><span style="font-size:10px;color:#aaa;">{{ $invoice->customer->phone }}</span>
                @endif
            </td>
            <td style="padding:8px 10px;text-align:center;color:#333;">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
            <td style="padding:8px 10px;text-align:center;">
                <strong style="color:{{ $daysOverdue > 30 ? '#DC2626' : '#e67e22' }};">{{ $daysOverdue }} day(s)</strong>
            </td>
            <td style="padding:8px 10px;text-align:right;font-weight:700;color:#1a1a1a;">{{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
        @endforeach
        <tr style="background:#1a1a1a;">
            <td colspan="4" style="padding:9px 10px;color:#fff;font-weight:700;">Total Outstanding</td>
            <td style="padding:9px 10px;text-align:right;color:#fff;font-weight:700;">KES {{ number_format($invoices->sum('balance_due'), 2) }}</td>
        </tr>
    </tbody>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
<tr><td style="border-top:1px solid #eeeeee;font-size:0;line-height:0;">&nbsp;</td></tr>
</table>

<p style="font-size:12px;color:#555;margin:0 0 16px;">
    Please follow up with these clients. You can send payment reminders directly from the Invoices page in the POS system.
</p>

<a href="{{ config('app.url') }}/admin/invoices"
   style="display:inline-block;background:#DC2626;color:#ffffff;text-decoration:none;padding:10px 22px;border-radius:4px;font-weight:700;font-size:13px;">
    View All Invoices &rarr;
</a>

@endsection