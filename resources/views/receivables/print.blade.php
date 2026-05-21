<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bon In - {{ $receivable->receive_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            padding: 18px 20px;
        }

        .red {
            color: #000;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        /* ─── HEADER ─── */
        .header-outer {
            width: 100%;
            border-bottom: 2px dashed #999;
            padding-bottom: 8px;
            margin-bottom: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 2px 4px;
        }

        .logo-cell {
            width: 22%;
        }

        .logo-cell img {
            max-width: 100px;
            height: auto;
        }

        .company-cell {
            width: 78%;
            text-align: right;
            padding-right: 4px;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-tagline {
            font-size: 10px;
            color: #555;
        }

        /* ─── TITLE ─── */
        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 8px 0 2px 0;
            letter-spacing: 2px;
        }

        .bon-number {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 8px;
        }

        /* ─── INFO TABLE ─── */
        .info-table {
            width: 58%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .info-table td {
            padding: 3px 6px;
            vertical-align: top;
            font-size: 11px;
            border: none;
        }

        .info-table .info-label {
            width: 28%;
            font-weight: bold;
            white-space: nowrap;
        }

        .info-table .info-val {
            color: #000;
        }

        /* ─── ITEMS TABLE ─── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .items-table th {
            border: 1px solid #999;
            padding: 5px 8px;
            text-align: center;
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }

        .items-table td {
            border: 1px solid #999;
            padding: 5px 8px;
            font-size: 11px;
        }

        .col-sku {
            width: 10%;
            text-align: center;
        }

        .col-desc {
            width: 44%;
        }

        .col-qty {
            width: 8%;
            text-align: center;
        }

        .col-unit {
            width: 10%;
            text-align: center;
        }

        .col-rem {
            width: 28%;
        }

        .empty-row td {
            height: 22px;
        }

        /* ─── FOOTER ─── */
        .footer-outer {
            width: 100%;
            border-top: 2px dashed #999;
            margin-top: 14px;
            padding-top: 6px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: top;
            border: none;
            padding: 2px 4px;
            font-size: 11px;
        }

        .footer-company {
            width: 42%;
        }

        .footer-sign {
            width: 58%;
            text-align: right;
        }

        .sign-table {
            display: inline-table;
            border-collapse: collapse;
        }

        .sign-table td {
            text-align: center;
            width: 160px;
            border: 1px solid #999;
            padding: 4px 8px;
            vertical-align: top;
            font-size: 11px;
        }

        .sign-header {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .sign-body {
            height: 52px;
            vertical-align: bottom;
        }

        .sign-name {
            border-top: 1px solid #999;
        }

        @media print {
            body {
                padding: 10px 12px;
            }

            @page {
                margin: 8mm;
            }
        }
    </style>
</head>

<body>

    {{-- ─── HEADER ─── --}}
    <div class="header-outer">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('HAS.png'))) }}"
                        alt="Logo" style="max-width:90px; height:auto;">
                </td>
                <td class="company-cell">
                    <div class="company-name">PT Hartono Auto Studio</div>
                    <div class="company-tagline">Auto Care and Lifestyle Solutions</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ─── TITLE ─── --}}
    <div class="title">BON IN</div>
    <div class="bon-number">No : {{ $receivable->receive_number }}</div>

    {{-- ─── INFO ROWS ─── --}}
    <table class="info-table">
        <tr>
            <td class="info-label">Supplier Name</td>
            <td class="info-val">
                {{ $receivable->purchaseOrder->supplier->name ?? $receivable->purchaseOrder->supplier_name }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Date / Time</td>
            <td class="info-val">
                {{ $receivable->received_date->translatedFormat('d F Y') }} /
                {{ $receivable->received_date->format('h:i A') }}
            </td>
        </tr>
        <tr>
            <td class="info-label" style="vertical-align: top;">Note</td>
            <td class="info-val" style="white-space: pre-line;">{{ $receivable->notes ?? '' }}</td>
        </tr>
    </table>

    {{-- ─── ITEMS TABLE ─── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-sku">SKU</th>
                <th class="col-desc">Description</th>
                <th class="col-qty">Qty</th>
                <th class="col-unit">Unit</th>
                <th class="col-rem">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receivable->items as $receivableItem)
                <tr>
                    <td class="col-sku red center">{{ $receivableItem->item->code ?? '-' }}</td>
                    <td class="col-desc red center">{{ $receivableItem->item->name }}</td>
                    <td class="col-qty red center">
                        {{ rtrim(rtrim(number_format($receivableItem->quantity_received, 2, '.', ''), '0'), '.') }}
                    </td>
                    <td class="col-unit red center">{{ $receivableItem->uom->code ?? $receivableItem->uom->name }}</td>
                    <td class="col-rem"></td>
                </tr>
            @endforeach
            {{-- Pad to at least 6 rows --}}
            @for ($i = $receivable->items->count(); $i < 6; $i++)
                <tr class="empty-row">
                    <td class="col-sku"></td>
                    <td class="col-desc"></td>
                    <td class="col-qty"></td>
                    <td class="col-unit"></td>
                    <td class="col-rem"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- ─── FOOTER ─── --}}
    <div class="footer-outer">
        <table class="footer-table">
            <tr>
                <td class="footer-company">
                    <div class="bold">PT Hartono Auto Studio</div>
                    <div><strong>Jl. Demak No. 166 - 168, Gundih, Kec. Bubutan, Surabaya, Jawa Timur, 60172</strong>
                    </div>
                    <div><a href="mailto:hrautostudio@hartonomotor.com"
                            style="color:#000; text-decoration:none;">hrautostudio@hartonomotor.com</a></div>
                    <div>+62 877 2095 5959</div>
                </td>
                <td class="footer-sign">
                    <table class="sign-table">
                        <tr>
                            <td class="sign-header">Receiver</td>
                            <td class="sign-header">Acknowledge</td>
                        </tr>
                        <tr>
                            <td class="sign-body">&nbsp;</td>
                            <td class="sign-body">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="sign-name">&nbsp;(________________)&nbsp;</td>
                            <td class="sign-name">&nbsp;(________________)&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function() {
            fetch('/audit/log-print', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    model_type: 'Receivable',
                    model_id: {{ $receivable->id }},
                    document_number: '{{ $receivable->receive_number }}'
                })
            }).finally(function() {
                window.print();
            });
        };
    </script>

</body>

</html>
