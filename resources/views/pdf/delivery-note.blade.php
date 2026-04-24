<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Delivery Note {{ $dn->delivery_number }}</title>
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
    .title-bar { padding: 10px 20px; border-bottom: 1px solid #e0e0e0; display: table; width: 100%; }
    .title-bar-left { display: table-cell; vertical-align: middle; }
    .title-bar-right { display: table-cell; vertical-align: middle; text-align: right; }
    .doc-type-box { border: 2px solid #c0392b; display: inline-block; padding: 3px 14px; }
    .doc-type-text { color: #c0392b; font-size: 14px; font-weight: bold; letter-spacing: 2px; }
    .section { padding: 12px 20px; border-bottom: 1px solid #e8e8e8; }
    .section-title { font-size: 10px; font-weight: bold; color: #c0392b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; border-left: 3px solid #c0392b; padding-left: 6px; }
    .field-label { font-size: 9.5px; color: #888; text-transform: uppercase; }
    .field-value { font-size: 11px; color: #1a1a1a; margin-top: 2px; }
    .two-col { display: table; width: 100%; }
    .col-left { display: table-cell; width: 50%; padding-right: 10px; }
    .col-right { display: table-cell; width: 50%; padding-left: 10px; }
    .dn-type-badge { display: inline-block; padding: 2px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .type-customer { background: #e3f2fd; color: #1565c0; }
    .type-technician { background: #fff3e0; color: #e65100; }
    table.items { width: 100%; border-collapse: collapse; }
    table.items thead tr { background-color: #c0392b; }
    table.items thead th { color: #fff; padding: 7px 8px; font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
    table.items tbody tr { border-bottom: 0.5px solid #e8e8e8; }
    table.items tbody tr:nth-child(even) { background-color: #fafafa; }
    table.items tbody td { padding: 6px 8px; font-size: 10.5px; color: #1a1a1a; vertical-align: top; }
    .sign-section { padding: 16px 20px; display: table; width: 100%; }
    .sign-cell { display: table-cell; width: 33%; text-align: center; padding: 0 10px; }
    .sign-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 4px; font-size: 9px; color: #555; }
    .footer { margin-top: 10px; padding: 8px 20px; border-top: 1px solid #e0e0e0; text-align: center; }
    .footer-text { font-size: 10px; color: #888; font-style: italic; }
    .footer-brand { font-size: 9px; color: #aaa; letter-spacing: 1px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
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
    <div class="title-bar-left">
        <div class="doc-type-box">
            <span class="doc-type-text">DELIVERY NOTE</span>
        </div>
        &nbsp;&nbsp;
        <span class="dn-type-badge type-{{ $dn->type }}">
            {{ $dn->type === 'technician' ? 'Site Delivery' : 'Customer Delivery' }}
        </span>
    </div>
    <div class="title-bar-right">
        <p style="font-size:10px; color:#555;">No. <strong>{{ $dn->delivery_number }}</strong></p>
        <p style="font-size:10px; color:#555;">Date: {{ $dn->created_at->format('d/m/Y') }}</p>
    </div>
</div>

{{-- Recipient info --}}
<div class="section">
    <div class="two-col">
        <div class="col-left">
            <div class="section-title">Recipient</div>
            <div class="field-label">Name</div>
            <div class="field-value" style="font-weight:bold; font-size:13px;">{{ strtoupper($dn->recipient_name) }}</div>
            @if($dn->recipient_phone)
            <div style="margin-top:5px;">
                <div class="field-label">Phone</div>
                <div class="field-value">{{ $dn->recipient_phone }}</div>
            </div>
            @endif
            @if($dn->delivery_address)
            <div style="margin-top:5px;">
                <div class="field-label">Delivery Address</div>
                <div class="field-value">{{ $dn->delivery_address }}</div>
            </div>
            @endif
        </div>
        <div class="col-right">
            <div class="section-title">Delivery Details</div>
            @if($dn->site_location)
            <div class="field-label">Site / Location</div>
            <div class="field-value">{{ $dn->site_location }}</div>
            @endif
            @if($dn->delivery_date)
            <div style="margin-top:5px;">
                <div class="field-label">Delivery Date</div>
                <div class="field-value">{{ $dn->delivery_date->format('d M Y') }}</div>
            </div>
            @endif
            <div style="margin-top:5px;">
                <div class="field-label">Status</div>
                <div class="field-value">{{ strtoupper($dn->status) }}</div>
            </div>
            <div style="margin-top:5px;">
                <div class="field-label">Prepared By</div>
                <div class="field-value">{{ $dn->createdBy->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Items --}}
<div class="section">
    <div class="section-title">Items Delivered</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%; text-align:center;">No.</th>
                <th>Description</th>
                <th style="width:12%; text-align:center;">Unit</th>
                <th style="width:12%; text-align:center;">Qty</th>
                <th style="width:30%;">Notes / Serial No.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dn->items as $index => $item)
            <tr>
                <td class="text-center" style="font-size:10px; color:#888;">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center" style="font-size:10px; color:#888;">{{ $item->unit }}</td>
                <td class="text-center" style="font-weight:bold;">{{ $item->quantity }}</td>
                <td style="font-size:10px; color:#555;">{{ $item->notes ?? '' }}</td>
            </tr>
            @endforeach
            {{-- Empty rows --}}
            @for($i = 0; $i < max(0, 4 - count($dn->items)); $i++)
            <tr><td colspan="5" style="padding:10px;">&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>
</div>

@if($dn->notes)
<div style="padding: 8px 20px;">
    <p style="font-size:10px; color:#555;">Note: {{ $dn->notes }}</p>
</div>
@endif

{{-- Signatures --}}
<div class="sign-section">
    <div class="sign-cell">
        <div class="sign-line">Issued By (Gigateam)</div>
    </div>
    <div class="sign-cell">
        <div class="sign-line">Received By (Name)</div>
    </div>
    <div class="sign-cell">
        <div class="sign-line">Received By (Signature &amp; Date)</div>
    </div>
</div>

<div class="footer">
    <div class="footer-text">{{ $dn->footer_text ?? 'Accounts are due on demand.' }}</div>
    <div class="footer-brand">{{ $company['footer'] }}</div>
</div>

</body>
</html>