@extends('pdf.layout')

@section('doc-title', 'Quotation ' . $quotation->quotation_number)

@section('content')

    {{-- ── Document title + client row ── --}}
    <table class="client-row">
        <tr>
            <td style="width:60%;">
                <span class="label">Client:</span>&nbsp;
                <span class="value">{{ strtoupper($quotation->client_name) }}</span>
            </td>
            <td style="text-align:right;">
                <span class="label">Quotation No:</span>&nbsp;
                <span class="value">{{ $quotation->quotation_number }}</span><br>
                <span class="label">Date:</span>&nbsp;
                <span class="value">{{ \Carbon\Carbon::parse($quotation->date)->format('d/m/Y') }}</span>
            </td>
        </tr>
        @if($quotation->client_address || $quotation->client_phone)
        <tr>
            <td colspan="2" style="padding-top:4px;font-size:10px;color:#555;">
                @if($quotation->client_address) {{ $quotation->client_address }} @endif
                @if($quotation->client_phone) &nbsp;|&nbsp; {{ $quotation->client_phone }} @endif
            </td>
        </tr>
        @endif
    </table>

    {{-- ── Items table ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:6%;">NO.</th>
                <th>DESCRIPTION</th>
                <th class="center" style="width:10%;">UNITS</th>
                <th class="right"  style="width:16%;">UNIT PRICE</th>
                <th class="right"  style="width:18%;">TOTAL PRICE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Totals ── --}}
    <table class="totals-table">
        <tr>
            <td class="total-label" style="width:60%;">&nbsp;</td>
            <td style="width:40%;">&nbsp;</td>
        </tr>
        @php
            $subtotal = $quotation->items->sum(fn($i) => $i->quantity * $i->unit_price);
        @endphp

        @if($quotation->include_vat ?? false)
        @php $vat = $subtotal * $vatRate; @endphp
        <tr>
            <td class="total-label">Subtotal</td>
            <td class="total-value">KES {{ number_format($subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="total-label">VAT ({{ $vatRate * 100 }}%)</td>
            <td class="total-value">KES {{ number_format($vat, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td class="total-label" style="color:#fff;">TOTAL (VAT INCLUSIVE)</td>
            <td class="total-value" style="color:#fff;">KES {{ number_format($subtotal + $vat, 2) }}</td>
        </tr>
        @else
        <tr class="grand-total">
            <td class="total-label" style="color:#fff;">TOTAL TAX EXCLUSIVE</td>
            <td class="total-value" style="color:#fff;">KES {{ number_format($subtotal, 2) }}</td>
        </tr>
        @endif
    </table>

    {{-- ── Notes / Validity ── --}}
    @if($quotation->notes)
    <div class="notes-block">
        <strong>Notes:</strong> {{ $quotation->notes }}
    </div>
    @endif

    <div class="notes-block" style="margin-top:10px;">
        <strong>Validity:</strong> This quotation is valid for 30 days from the date above.<br>
        <strong>Payment Terms:</strong> 50% deposit on acceptance, balance on completion.
    </div>

    {{-- ── Signature block ── --}}
    <table class="signature-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                Authorised By<br>
                <strong>{{ $company['name'] }}</strong>
            </td>
            <td>
                <div class="sig-line"></div>
                Client Acceptance<br>
                <strong>{{ $quotation->client_name }}</strong>
            </td>
        </tr>
    </table>

@endsection