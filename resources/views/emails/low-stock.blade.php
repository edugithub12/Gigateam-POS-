@extends('emails.layout', ['badgeText' => 'Stock Alert'])

@section('content')

{{-- Alert box --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
<tr>
    <td style="border-left:4px solid #e67e22;background:#fef9f0;padding:14px 16px;border-radius:0 4px 4px 0;">
        <p style="margin:0;font-weight:700;font-size:15px;color:#1a1a1a;">&#9888; Low Stock Warning &mdash; {{ now()->format('l, d M Y') }}</p>
        <p style="margin:4px 0 0;font-size:13px;color:#666;">{{ $lowStockItems->count() }} product(s) are below their reorder level and need restocking.</p>
    </td>
</tr>
</table>

{{-- Section heading --}}
<p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Products Requiring Restock</p>

{{-- Items table --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;font-size:12px;">
    <thead>
        <tr style="background:#1a1a1a;">
            <th style="color:#fff;padding:8px 10px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">Product</th>
            <th style="color:#fff;padding:8px 10px;text-align:center;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">Current Stock</th>
            <th style="color:#fff;padding:8px 10px;text-align:center;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">Reorder Level</th>
            <th style="color:#fff;padding:8px 10px;text-align:center;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lowStockItems as $i => $item)
        <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#fafafa' }};border-bottom:1px solid #f0f0f0;">
            <td style="padding:9px 10px;color:#1a1a1a;vertical-align:top;">
                <strong style="display:block;">{{ $item->name }}</strong>
                @if($item->sku ?? false)
                <span style="font-size:10px;color:#aaa;">SKU: {{ $item->sku }}</span>
                @endif
            </td>
            <td style="padding:9px 10px;text-align:center;vertical-align:middle;">
                <strong style="color:{{ $item->stock_quantity == 0 ? '#DC2626' : '#e67e22' }};font-size:14px;">{{ $item->stock_quantity }}</strong>
            </td>
            <td style="padding:9px 10px;text-align:center;vertical-align:middle;color:#666;">
                {{ $item->reorder_level }}
            </td>
            <td style="padding:9px 10px;text-align:center;vertical-align:middle;">
                @if($item->stock_quantity == 0)
                <span style="display:inline-block;background:#fde8e8;color:#DC2626;border:1px solid #f5c6c6;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;text-transform:uppercase;">Out of Stock</span>
                @else
                <span style="display:inline-block;background:#fef3e2;color:#e67e22;border:1px solid #fad7a0;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:700;text-transform:uppercase;">Low Stock</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Divider --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
<tr><td style="border-top:1px solid #eeeeee;font-size:0;line-height:0;">&nbsp;</td></tr>
</table>

<p style="font-size:12px;color:#555;margin:0 0 16px;">
    Please contact your suppliers to restock these items. You can view full stock levels and supplier details in the POS system.
</p>

<a href="{{ config('app.url') }}/admin/products"
   style="display:inline-block;background:#DC2626;color:#ffffff;text-decoration:none;padding:10px 22px;border-radius:4px;font-weight:700;font-size:13px;">
    View Stock Levels &rarr;
</a>

@endsection