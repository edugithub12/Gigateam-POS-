@extends('emails.layout', ['badgeText' => 'Job Assigned'])

@section('content')

{{-- Alert box --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
<tr>
    <td style="border-left:4px solid #16a34a;background:#f0faf4;padding:14px 16px;border-radius:0 4px 4px 0;">
        <p style="margin:0;font-weight:700;font-size:15px;color:#1a1a1a;">&#128295; New Job Card Assigned to You</p>
        <p style="margin:4px 0 0;font-size:13px;color:#666;">
            Hi <strong>{{ $jobCard->technician->name ?? 'Technician' }}</strong>, you have been assigned a new job. Please review the details below.
        </p>
    </td>
</tr>
</table>

{{-- Job details --}}
<p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Job Details</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;font-size:12px;">
    <tr style="border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;width:38%;">Job Card #</td>
        <td style="padding:8px 10px;color:#DC2626;font-weight:700;">{{ $jobCard->job_number ?? '—' }}</td>
    </tr>
    <tr style="background:#fafafa;border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;">Job Type</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:600;">{{ $jobCard->job_type ?? '—' }}</td>
    </tr>
    <tr style="border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;">Category</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:600;">{{ $jobCard->category ?? '—' }}</td>
    </tr>
    <tr style="background:#fafafa;border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;">Scheduled Date</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:700;">
            {{ $jobCard->scheduled_date ? \Carbon\Carbon::parse($jobCard->scheduled_date)->format('l, d M Y') : 'Not yet scheduled' }}
        </td>
    </tr>
    <tr style="border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;">Scheduled Time</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:600;">{{ $jobCard->scheduled_time ?? '—' }}</td>
    </tr>
    <tr style="background:#fafafa;">
        <td style="padding:8px 10px;color:#888;font-size:11px;">Status</td>
        <td style="padding:8px 10px;">
            <span style="display:inline-block;background:#fef3e2;color:#e67e22;border:1px solid #fad7a0;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;text-transform:uppercase;">
                {{ ucfirst(str_replace('_', ' ', $jobCard->status ?? 'Scheduled')) }}
            </span>
        </td>
    </tr>
</table>

{{-- Client info --}}
<p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Client Information</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;font-size:12px;">
    <tr style="border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;width:38%;">Client Name</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:700;">{{ $jobCard->customer->name ?? $jobCard->client_name ?? '—' }}</td>
    </tr>
    <tr style="background:#fafafa;border-bottom:1px solid #f0f0f0;">
        <td style="padding:8px 10px;color:#888;font-size:11px;">Phone</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:600;">{{ $jobCard->customer->phone ?? $jobCard->client_phone ?? '—' }}</td>
    </tr>
    <tr>
        <td style="padding:8px 10px;color:#888;font-size:11px;">Site Address</td>
        <td style="padding:8px 10px;color:#1a1a1a;font-weight:600;">{{ $jobCard->site_address ?? $jobCard->customer->address ?? '—' }}</td>
    </tr>
</table>

{{-- Work description --}}
<p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Work Description</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
<tr>
    <td style="background:#f8f8f8;border:1px solid #eee;border-left:3px solid #DC2626;padding:12px 14px;border-radius:0 4px 4px 0;font-size:13px;color:#333;">
        {{ $jobCard->work_description ?? 'No description provided.' }}
    </td>
</tr>
</table>

{{-- Materials if any --}}
@if(($jobCard->items ?? collect())->count() > 0)
<p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Materials to Collect from Shop</p>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;font-size:12px;">
    <thead>
        <tr style="background:#1a1a1a;">
            <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;text-transform:uppercase;">Item</th>
            <th style="color:#fff;padding:7px 10px;text-align:center;font-size:10px;text-transform:uppercase;">Qty</th>
            <th style="color:#fff;padding:7px 10px;text-align:left;font-size:10px;text-transform:uppercase;">Unit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($jobCard->items as $mat)
        <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:8px 10px;color:#333;">{{ $mat->product->name ?? $mat->description ?? '—' }}</td>
            <td style="padding:8px 10px;text-align:center;color:#333;">{{ $mat->quantity }}</td>
            <td style="padding:8px 10px;color:#333;">{{ $mat->unit ?? 'pcs' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Divider --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
<tr><td style="border-top:1px solid #eeeeee;font-size:0;line-height:0;">&nbsp;</td></tr>
</table>

<p style="font-size:12px;color:#555;margin:0 0 16px;">
    Please confirm receipt of this job and update the job card status when you begin work. Contact the office if you have any questions.
</p>

<a href="{{ config('app.url') }}/admin/job-cards"
   style="display:inline-block;background:#DC2626;color:#ffffff;text-decoration:none;padding:10px 22px;border-radius:4px;font-weight:700;font-size:13px;">
    View Job Card &rarr;
</a>

@endsection