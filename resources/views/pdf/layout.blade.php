<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('doc-title', 'Document')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #222222;
            background: #ffffff;
        }

        /* ═══════════════════════════════════════════
           HEADER
           - White background throughout
           - Logo + name on the left half
           - Contact block in the center-right (on white, NOT on wedge)
           - Decorative wedge (black + red triangles) at top-right corner only
        ═══════════════════════════════════════════ */

        .header-wrapper {
            width: 100%;
            position: relative;
            height: 130px;
            background: #ffffff;
            overflow: hidden;
        }

        /* ── Decorative wedge: black top-right triangle ── */
        .wedge-black {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            /* wide at right edge, tapers left — black fills top-right corner */
            border-width: 0 130px 70px 0;
            border-color: transparent #2d2d2d transparent transparent;
        }

        /* ── Decorative wedge: red triangle below black ── */
        .wedge-red {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            /* wider and taller than black so red peeks out below */
            border-width: 0 160px 130px 0;
            border-color: transparent #cc0000 transparent transparent;
            z-index: 0;
        }

        /* ── Main header table (3 columns: logo | name+tagline | contact) ── */
        .header-table {
            width: 100%;
            height: 130px;
            border-collapse: collapse;
            position: relative;
            z-index: 5; /* sits above wedge decorations */
        }

        /* Column 1 — Logo */
        .col-logo {
            width: 105px;
            vertical-align: middle;
            padding: 10px 8px 10px 8px;
        }

        .col-logo img {
            width: 95px;
            height: auto;
        }

        /* Column 2 — Company name + tagline */
        .col-name {
            vertical-align: middle;
            padding: 10px 0;
        }

        .co-name {
            font-size: 22px;
            font-weight: bold;
            color: #2d2d2d;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .co-tagline {
            font-size: 13px;
            font-style: italic;
            font-weight: bold;
            color: #cc0000;
            margin-top: 4px;
        }

        /* Column 3 — Contact details (on white background, left of wedge) */
        .col-contact {
            width: 220px;
            vertical-align: middle;
            padding: 10px 170px 10px 0;
            /* right padding pushes text away from the wedge */
            text-align: left;
        }

        .contact-line {
            font-size: 8.5px;
            line-height: 1.75;
            color: #333333;
        }

        .contact-line .red {
            color: #cc0000;
        }

        /* ═══════════════════════════════════════════
           SEPARATOR LINES
        ═══════════════════════════════════════════ */
        .sep-thick {
            height: 4px;
            background-color: #cc0000;
            width: 100%;
        }

        .sep-thin {
            height: 2px;
            background-color: #555555;
            width: 100%;
            margin-top: 2px;
        }

        /* ═══════════════════════════════════════════
           DOCUMENT BODY
        ═══════════════════════════════════════════ */
        .doc-body {
            padding: 16px 4px 50px 4px;
        }

        .client-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .client-row td { vertical-align: top; font-size: 11px; }

        .client-row .label {
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }

        .client-row .value {
            font-weight: bold;
            color: #2d2d2d;
            font-size: 11px;
        }

        /* ═══════════════════════════════════════════
           ITEMS TABLE
        ═══════════════════════════════════════════ */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        .items-table thead tr { background-color: #cc0000; }

        .items-table thead th {
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 8px;
            text-align: left;
            border: none;
        }

        .items-table thead th.right  { text-align: right; }
        .items-table thead th.center { text-align: center; }

        .items-table tbody tr { background-color: #ffffff; }

        .items-table tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
            color: #222;
        }

        .items-table tbody td.right  { text-align: right; }
        .items-table tbody td.center { text-align: center; }

        /* ═══════════════════════════════════════════
           TOTALS
        ═══════════════════════════════════════════ */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 8px;
            font-size: 11px;
            border-bottom: 1px solid #e8e8e8;
        }

        .totals-table .total-label { text-align: left;  font-weight: bold; color: #444; }
        .totals-table .total-value { text-align: right; font-weight: bold; color: #222; }

        .totals-table .grand-total td {
            background-color: #cc0000;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            padding: 8px;
            border: none;
        }

        /* ═══════════════════════════════════════════
           NOTES
        ═══════════════════════════════════════════ */
        .notes-block {
            margin-top: 16px;
            font-size: 10px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        .notes-block strong { color: #2d2d2d; }

        /* ═══════════════════════════════════════════
           SIGNATURE
        ═══════════════════════════════════════════ */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .signature-table td {
            width: 50%;
            padding: 0 10px;
            vertical-align: bottom;
            font-size: 10px;
            color: #444;
        }

        .sig-line {
            border-top: 1px solid #aaa;
            margin-bottom: 4px;
            width: 80%;
        }

        /* ═══════════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════════ */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #888;
            letter-spacing: 3px;
            text-transform: uppercase;
            padding: 4px 0 6px 0;
            border-top: 1px solid #e0e0e0;
            background: #fff;
        }
    </style>
</head>
<body>

    {{-- ══════════════════════════════════════════════
         LETTERHEAD
    ══════════════════════════════════════════════ --}}
    <div class="header-wrapper">

        {{-- Decorative wedge: red behind black (z-index 0) --}}
        <div class="wedge-red"></div>
        {{-- Decorative wedge: black top-right corner --}}
        <div class="wedge-black"></div>

        {{-- 3-column header table sits above wedges --}}
        <table class="header-table">
            <tr>
                {{-- Col 1: Logo --}}
                <td class="col-logo">
                    @if(!empty($logo))
                        <img src="{{ $logo }}" alt="{{ $company['name'] }}">
                    @else
                        <span style="font-size:30px;font-weight:bold;color:#cc0000;">G</span>
                    @endif
                </td>

                {{-- Col 2: Company name + tagline --}}
                <td class="col-name">
                    <div class="co-name">{{ $company['name'] }}</div>
                    <div class="co-tagline">{{ $company['tagline'] }}</div>
                </td>

                {{-- Col 3: Contact (on white, left of wedge) --}}
                <td class="col-contact">
                    <div class="contact-line">
                        {{ $company['address1'] ?? ($company['address'] ?? '') }}<br>
                        {{ $company['po_box'] }}<br>
                        {{ $company['phone1'] }} / {{ $company['phone2'] }}<br>
                        <span class="red">Email: {{ $company['email1'] }}</span><br>
                        <span class="red">{{ $company['email2'] }}</span><br>
                        <span class="red">{{ $company['website'] }}</span>
                    </div>
                </td>
            </tr>
        </table>

    </div>

    {{-- Separator lines --}}
    <div class="sep-thick"></div>
    <div class="sep-thin"></div>

    {{-- ══════════════════════════════════════════════
         DOCUMENT CONTENT
    ══════════════════════════════════════════════ --}}
    <div class="doc-body">
        @yield('content')
    </div>

    {{-- Footer --}}
    <div class="page-footer">
        {{ $company['footer'] ?? $company['tagline'] }}
    </div>

</body>
</html>