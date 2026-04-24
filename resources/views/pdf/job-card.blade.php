<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Job Card {{ $job->job_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; }
    .header { background-color: #c0392b; padding: 14px 20px; }
    .header-inner { display: table; width: 100%; }
    .header-left { display: table-cell; vertical-align: middle; width: 60%; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; }
    .logo { height: 55px; width: auto; }
    .company-tagline { color: rgba(255,255,255,0.85); font-size: 11px; font-style: italic; margin-top: 2px; }
    .header-right p { color: #ffffff; font-size: 9.5px; line-height: 1.5; }
    .title-bar { padding: 10px 20px; border-bottom: 2px solid #c0392b; display: table; width: 100%; background: #fff3f3; }
    .doc-type-box { border: 2px solid #c0392b; display: inline-block; padding: 3px 14px; }
    .doc-type-text { color: #c0392b; font-size: 14px; font-weight: bold; letter-spacing: 2px; }
    .section { padding: 12px 20px; border-bottom: 1px solid #e8e8e8; }
    .section-title { font-size: 10px; font-weight: bold; color: #c0392b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; border-left: 3px solid #c0392b; padding-left: 6px; }
    .field-row { display: table; width: 100%; margin-bottom: 5px; }
    .field-cell { display: table-cell; vertical-align: top; }
    .field-label { font-size: 9.5px; color: #888; text-transform: uppercase; }
    .field-value { font-size: 11px; color: #1a1a1a; margin-top: 2px; font-weight: bold; }
    .field-value.normal { font-weight: normal; }
    .status-badge { display: inline-block; padding: 2px 10px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .status-pending { background: #f0f0f0; color: #666; }
    .status-scheduled { background: #e3f2fd; color: #1565c0; }
    .status-in_progress { background: #fff3e0; color: #e65100; }
    .status-completed { background: #e8f5e9; color: #2e7d32; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.items thead tr { background-color: #c0392b; }
    table.items thead th { color: #fff; padding: 6px 8px; font-size: 10px; font-weight: bold; }
    table.items tbody tr { border-bottom: 0.5px solid #e8e8e8; }
    table.items tbody tr:nth-child(even) { background-color: #fafafa; }
    table.items tbody td { padding: 5px 8px; font-size: 10px; }
    .work-box { border: 1px solid #e0e0e0; padding: 8px; min-height: 50px; font-size: 10.5px; color: #1a1a1a; border-radius: 3px; }
    .write-box { border: 1px solid #e0e0e0; min-height: 60px; border-radius: 3px; }
    .sign-section { padding: 16px 20px; display: table; width: 100%; }
    .sign-cell { display: table-cell; width: 33%; text-align: center; padding: 0 10px; }
    .sign-line { border-top: 1px solid #333; margin-top: 35px; padding-top: 4px; font-size: 9px; color: #555; }
    .footer { margin-top: 10px; padding: 8px 20px; border-top: 1px solid #e0e0e0; text-align: center; }
    .footer-text { font-size: 10px; color: #888; font-style: italic; }
    .footer-brand { font-size: 9px; color: #aaa; letter-spacing: 1px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .bold { font-weight: bold; }
    .red { color: #c0392b; }
    .two-col { display: table; width: 100%; }
    .col-left { display: table-cell; width: 50%; padding-right: 10px; }
    .col-right { display: table-cell; width: 50%; padding-left: 10px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-inner">
        <div class="header-left">
            @if($logo)
                <img src="{{ $logo }}" class="logo" alt="Gigateam Logo">
            @endif
            <div class="company-tagline">{{ $company['tagline'] }}</div>
        </div>
        <div class="header-right">
            <p>{{ $company['address1'] }}</p>
            <p>{{ $company['po_box'] }}</p>
            <p>{{ $company['phone1'] }} / {{ $company['phone2'] }}</p>
            <p>{{ $company['email1'] }}</p>
            <p>{{ $company['website'] }}</p>
        </div>
    </div>
</div>

<div class="title-bar">
    <div style="display:table; width:100%;">
        <div style="display:table-cell; vertical-align:middle;">
            <div class="doc-type-box">
                <span class="doc-type-text">JOB CARD</span>
            </div>
        </div>
        <div style="display:table-cell; vertical-align:middle; text-align:right;">
            <span style="font-size:13px; font-weight:bold; color:#c0392b;">{{ $job->job_number }}</span>
            &nbsp;&nbsp;
            <span class="status-badge status-{{ $job->status }}">{{ strtoupper(str_replace('_', ' ', $job->status)) }}</span>
        </div>
    </div>
</div>

{{-- Job Info --}}
<div class="section">
    <div class="two-col">
        <div class="col-left">
            <div class="section-title">Client Information</div>
            <div class="field-label">Client Name</div>
            <div class="field-value">{{ $job->client_name }}</div>
            <div style="margin-top:6px;">
                <div class="field-label">Phone</div>
                <div class="field-value normal">{{ $job->client_phone ?? '—' }}</div>
            </div>
            <div style="margin-top:6px;">
                <div class="field-label">Site Address</div>
                <div class="field-value normal">{{ $job->site_address }}</div>
                @if($job->site_area)
                <div class="field-value normal" style="font-size:10px; color:#555;">{{ $job->site_area }}</div>
                @endif
            </div>
        </div>
        <div class="col-right">
            <div class="section-title">Job Information</div>
            <div class="two-col">
                <div class="col-left">
                    <div class="field-label">Job Type</div>
                    <div class="field-value">{{ \App\Models\JobCard::$jobTypes[$job->job_type] ?? $job->job_type }}</div>
                </div>
                <div class="col-right">
                    <div class="field-label">Category</div>
                    <div class="field-value">{{ $job->category }}</div>
                </div>
            </div>
            <div style="margin-top:6px;" class="two-col">
                <div class="col-left">
                    <div class="field-label">Scheduled Date</div>
                    <div class="field-value normal">{{ $job->scheduled_date ? $job->scheduled_date->format('d M Y') : '—' }}</div>
                </div>
                <div class="col-right">
                    <div class="field-label">Scheduled Time</div>
                    <div class="field-value normal">{{ $job->scheduled_time ?? '—' }}</div>
                </div>
            </div>
            <div style="margin-top:6px;">
                <div class="field-label">Assigned Technician</div>
                <div class="field-value">{{ $job->technician->name ?? 'Unassigned' }}</div>
            </div>
            <div style="margin-top:6px;">
                <div class="field-label">Created By</div>
                <div class="field-value normal">{{ $job->createdBy->name ?? '—' }} &nbsp;|&nbsp; {{ $job->created_at->format('d M Y') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Work Description --}}
<div class="section">
    <div class="section-title">Work to be Done</div>
    <div class="work-box">{{ $job->work_description }}</div>
</div>

{{-- Work Done --}}
<div class="section">
    <div class="section-title">Work Done (Technician to Complete)</div>
    @if($job->work_done)
        <div class="work-box">{{ $job->work_done }}</div>
    @else
        <div class="write-box"></div>
    @endif
</div>

{{-- Materials --}}
@if($job->items->count() > 0)
<div class="section">
    <div class="section-title">Materials Used</div>
    <table class="items">
        <thead>
            <tr>
                <th>Item Description</th>
                <th style="width:10%; text-align:center;">Unit</th>
                <th style="width:10%; text-align:center;">Qty</th>
                <th style="width:18%; text-align:right;">Unit Price</th>
                <th style="width:18%; text-align:right;">Total</th>
                <th style="width:12%; text-align:center;">Source</th>
            </tr>
        </thead>
        <tbody>
            @foreach($job->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->unit }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ $item->unit_price > 0 ? number_format($item->unit_price, 2) : '—' }}</td>
                <td class="text-right">{{ $item->total > 0 ? number_format($item->total, 2) : '—' }}</td>
                <td class="text-center" style="font-size:9px;">{{ ucfirst($item->source) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #c0392b;">
                <td colspan="3" style="padding:6px 8px; font-weight:bold; font-size:10.5px;">Labour: KES {{ number_format($job->labour_cost, 2) }} &nbsp;|&nbsp; Transport: KES {{ number_format($job->transport_cost, 2) }}</td>
                <td colspan="2" style="padding:6px 8px; text-align:right; font-weight:bold; font-size:12px; color:#c0392b;">TOTAL: KES {{ number_format($job->grandTotal(), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- Technician Notes --}}
<div class="section">
    <div class="two-col">
        <div class="col-left">
            <div class="section-title">Technician Notes</div>
            @if($job->technician_notes)
                <div class="work-box" style="min-height:35px;">{{ $job->technician_notes }}</div>
            @else
                <div class="write-box" style="min-height:35px;"></div>
            @endif
        </div>
        <div class="col-right">
            <div class="section-title">Client Feedback</div>
            <div style="margin-top:4px;">
                <span style="font-size:10px;">Satisfied: &nbsp;</span>
                <span style="font-size:10px; border:1px solid #ccc; padding:1px 6px; margin-right:6px;">Yes</span>
                <span style="font-size:10px; border:1px solid #ccc; padding:1px 6px;">No</span>
            </div>
        </div>
    </div>
</div>

{{-- Signatures --}}
<div class="sign-section">
    <div class="sign-cell">
        <div class="sign-line">Technician Signature</div>
    </div>
    <div class="sign-cell">
        <div class="sign-line">Client Signature</div>
    </div>
    <div class="sign-cell">
        <div class="sign-line">Authorised by (Gigateam)</div>
    </div>
</div>

<div class="footer">
    <div class="footer-brand">{{ $company['footer'] }}</div>
</div>

</body>
</html>