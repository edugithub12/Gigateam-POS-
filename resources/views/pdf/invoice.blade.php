@extends('pdf.layout')

@section('doc-title', 'Invoice ' . $invoice->invoice_number)

@section('content')

    {{-- ── Document title + meta ── --}}
    <table class="client-row">
        <tr>
            <td style="width:60%;">
                <span class="label">Bill To:</span><br>
                <span class="value" style="font-size:13px;">{{ strtoupper($invoice->client_name) }}</span><br>
                @if($invoice->client_address)
                <span style="font-size:10px;color:#555;">{{ $invoice->client_address }}</span><br>
                @endif
                @if($invoice->client_phone)
                <span style="font-size:10px;color:#555;">{{ $invoice->client_phone }}</span>
                @endif
            </td>
            <td style="text-align:right;vertical-align:top;">
                <div style="display:inline-block;border:2px solid #cc0000;padding:4px 14px;margin-bottom:8px;">
                    <span style="font-size:16px;font-weight:bold;color:#cc0000;letter-spacing:2px;">INVOICE</span>
                </div><br>
                <span class="label">Invoice No:</span>&nbsp;
                <span class="value">{{ $invoice->invoice_number }}</span><br>
                <span class="label">Date:</span>&nbsp;
                <span class="value">{{ $invoice->created_at->format('d/m/Y') }}</span><br>
                @if($invoice->due_date)
                <span class="label">Due Date:</span>&nbsp;
                <span class="value" style="color:#cc0000;">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span><br>
                @endif
                @if($invoice->order_number)
                <span class="label">Order Ref:</span>&nbsp;
                <span class="value">{{ $invoice->order_number }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Items table ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:6%;">NO.</th>
                <th>DESCRIPTION</th>
                <th class="center" style="width:10%;">QTY</th>
                <th class="right" style="width:16%;">UNIT PRICE</th>
                <th class="right" style="width:18%;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="center">{{ $item->quantity }} {{ $item->unit }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Totals ── --}}
    @php
        $vatRate  = $invoice->include_vat ? 0.16 : 0;
        $subtotal = $invoice->items->sum(fn($i) => $i->quantity * $i->unit_price);
        $discount = $invoice->discount_amount ?? 0;
        $taxable  = $subtotal - $discount;
        $vat      = round($taxable * $vatRate, 2);
        $grand    = $taxable + $vat;
    @endphp

    <table class="totals-table">
        <tr>
            <td style="width:55%;">&nbsp;</td>
            <td class="total-label" style="width:25%;">Sub Total</td>
            <td class="total-value" style="width:20%;">KES {{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($discount > 0)
        <tr>
            <td>&nbsp;</td>
            <td class="total-label">Discount</td>
            <td class="total-value" style="color:#cc0000;">- KES {{ number_format($discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td>&nbsp;</td>
            <td class="total-label">VAT ({{ $vatRate * 100 }}%)</td>
            <td class="total-value">KES {{ number_format($vat, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td style="color:#fff;">&nbsp;</td>
            <td style="color:#fff;font-weight:bold;">TOTAL DUE</td>
            <td style="text-align:right;color:#fff;font-weight:bold;">KES {{ number_format($grand, 2) }}</td>
        </tr>
    </table>

    {{-- ── Payment info ── --}}
    <div class="notes-block" style="margin-top:14px;">
        <strong>Payment Details:</strong><br>
        Bank: &nbsp;&nbsp;&nbsp; [Your Bank Name]<br>
        A/C Name: {{ $company['name'] }}<br>
        A/C No: &nbsp; [Your Account Number]<br>
        Branch: &nbsp;[Branch Name]<br>
        <br>
        <strong>KRA PIN:</strong> {{ $company['kra_pin'] }}
    </div>

    @if($invoice->notes)
    <div class="notes-block" style="margin-top:10px;">
        <strong>Notes:</strong> {{ $invoice->notes }}
    </div>
    @endif

    @if($invoice->footer_text)
    <div class="notes-block" style="margin-top:10px;font-style:italic;color:#888;">
        {{ $invoice->footer_text }}
    </div>
    @endif

    {{-- ── Signature ── --}}
    <table class="signature-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                Authorised By<br>
                <strong>{{ $company['name'] }}</strong>
            </td>
            <td>
                <div class="sig-line"></div>
                Received By<br>
                <strong>{{ $invoice->client_name }}</strong>
            </td>
        </tr>
    </table>

@endsection