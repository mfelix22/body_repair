<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order {{ $salesOrder->so_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            padding: 20px;
        }

        /* ===== HEADER ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo {
            width: 180px;
            vertical-align: middle;
        }

        .header-logo img {
            height: 65px;
        }

        .header-company {
            text-align: right;
            vertical-align: middle;
        }

        .header-company .company-name {
            font-size: 16px;
            font-weight: bold;
        }

        .header-company .company-tagline {
            font-size: 10px;
            color: #555;
        }

        .separator {
            border: none;
            border-top: 1.5px dashed #000;
            margin: 6px 0;
        }

        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 4px 0;
        }

        .doc-number {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* ===== INFO SECTION ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-section {
            vertical-align: top;
        }

        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-section table td {
            padding: 2px 4px;
            font-size: 11px;
        }

        .info-section table td:first-child {
            font-weight: bold;
            width: 110px;
        }

        .info-section table td.val {
            color: #000;
        }

        /* ===== RECEIVED ROW ===== */
        .received-row {
            width: 100%;
            margin: 8px 0 6px;
        }

        .received-row td {
            font-size: 11px;
            padding: 2px 0;
        }

        /* ===== ITEMS TABLE ===== */
        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .section-table thead tr {
            background-color: #d0d0d0;
        }

        .section-table thead td,
        .section-table thead th {
            padding: 4px 6px;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #aaa;
        }

        .section-table tbody td {
            padding: 3px 6px;
            border: 1px solid #ccc;
            font-size: 11px;
            color: #000;
        }

        .section-table tbody tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .section-table tfoot td {
            padding: 4px 6px;
            border: 1px solid #aaa;
            font-size: 11px;
        }

        /* ===== SIGNATURE ===== */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .sig-table td {
            text-align: center;
            padding: 4px;
            border: none;
        }

        .sig-label {
            font-weight: bold;
            font-size: 11px;
            background-color: #ffff00;
            padding: 4px 20px;
            border: 1px solid #fff;
        }

        .sig-name {
            font-size: 10px;
            text-align: center;
        }

        .section-table .empty-row td {
            height: 20px;
            border: 1px solid #ccc;
            color: #000;
        }

        /* ===== FOOTER ===== */
        .company-footer {
            font-size: 10px;
            margin-top: 6px;
        }

        .company-footer a {
            color: #0000cc;
        }

        @media print {
            body {
                padding: 10px;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 10mm;
            }
        }
    </style>
</head>

<body>

    {{-- ===== PRINT BUTTON ===== --}}
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="doPrint()"
            style="padding:6px 16px;font-size:12px;cursor:pointer;background:#007bff;color:#fff;border:none;border-radius:4px;">
            &#128438; Print
        </button>
        <a href="{{ route('sales_orders.show', $salesOrder) }}" style="margin-left:10px;font-size:12px;">
            &larr; Back
        </a>
    </div>

    {{-- ===== HEADER ===== --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('HAS.png'))) }}"
                    alt="HR Auto Studio">
            </td>
            <td></td>
            <td class="header-company">
                <div class="company-name">PT Hartono Auto Studio</div>
                <div class="company-tagline">Auto Care and Lifestyle Solutions</div>
            </td>
        </tr>
    </table>

    <hr class="separator">

    <div class="doc-title">SALES ORDER</div>
    <div class="doc-number">No : {{ $salesOrder->so_number }}</div>

    <hr class="separator">

    {{-- ===== INFO SECTION ===== --}}
    @php
        $monthsId = [
            '',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        $idDate = fn($date) => $date ? $date->day . ' ' . $monthsId[$date->month] . ' ' . $date->year : '-';
    @endphp

    <table class="info-table">
        <tr>
            <td class="info-section">
                <table>
                    <tr>
                        <td>Customer Name</td>
                        <td>:</td>
                        <td class="val">{{ $salesOrder->customer->name }}</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>:</td>
                        <td class="val">{{ $salesOrder->customer->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>:</td>
                        <td class="val">{{ $salesOrder->customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($salesOrder->order_date) }}</td>
                    </tr>
                    @if ($salesOrder->description)
                        <tr>
                            <td>Description</td>
                            <td>:</td>
                            <td class="val">{{ $salesOrder->description }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE ===== --}}
    <table class="section-table">
        <thead>
            <tr>
                <th style="width:14%">Item Code</th>
                <th style="width:36%">Description</th>
                <th style="width:8%;text-align:center;">UOM</th>
                <th style="width:8%;text-align:right;">Qty</th>
                <th style="width:17%;text-align:right;">Unit Price (Rp)</th>
                <th style="width:17%;text-align:right;">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salesOrder->items as $i => $line)
                <tr>
                    <td>{{ $line->item->code }}</td>
                    <td>{{ $line->item->name }}</td>
                    <td style="text-align:center;">{{ $line->item->smallestUom->code ?? '-' }}</td>
                    <td style="text-align:right;">{{ number_format($line->quantity, 2) }}</td>
                    <td style="text-align:right;">{{ number_format($line->unit_price, 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($line->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @for ($i = $salesOrder->items->count(); $i < 5; $i++)
                <tr class="empty-row">
                    <td colspan="6">&nbsp;</td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;font-weight:bold;background:#d0d0d0;">Total</td>
                <td style="text-align:right;font-weight:bold;background:#d0d0d0;">
                    {{ number_format($salesOrder->material_total, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== NOTES ===== --}}
    @if ($salesOrder->notes)
        <div style="font-size:11px;margin-bottom:8px;">
            <strong>Notes:</strong> {{ $salesOrder->notes }}
        </div>
    @endif

    {{-- ===== RECEIVED BY / SA ROW ===== --}}
    <table class="received-row">
        <tr>
            <td style="width:50%">Received by : {{ $salesOrder->creator?->name ?? '-' }}</td>
            <td style="width:50%;text-align:right;">SA/Sales : {{ $salesOrder->creator?->name ?? '-' }}</td>
        </tr>
    </table>

    {{-- ===== SIGNATURE BOXES ===== --}}
    <table class="sig-table">
        <tr>
            <td style="width:33%">
                <div class="sig-label">Customer</div>
                <div style="height:80px;"></div>
                <div class="sig-name">(_____________________)</div>
            </td>
            <td style="width:33%">
                <div class="sig-label">Service Advisor</div>
                <div style="height:80px;"></div>
                <div class="sig-name">(_____________________)</div>
            </td>
            <td style="width:33%">
                <div class="sig-label">Foreman/Staff</div>
                <div style="height:80px;"></div>
                <div class="sig-name">(_____________________)</div>
            </td>
        </tr>
    </table>

    {{-- ===== COMPANY FOOTER ===== --}}
    <div class="company-footer">
        <strong>PT Hartono Auto Studio</strong><br>
        Jl. Daan Mogot Km. 1 No. 99, RT.6/RW.5, Duri Kepa, Kecamatan Kebon Jeruk, Kota Jakarta Barat, DKI Jakarta 11510<br>
        <a href="mailto:hrautostudio@hartonomotor.com">hrautostudio@hartonomotor.com</a><br>
        +62 877 2095 5959
    </div>

    <script>
        function doPrint() {
            fetch('/audit/log-print', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    model_type: 'SalesOrder',
                    model_id: {{ $salesOrder->id }},
                    document_number: '{{ $salesOrder->so_number }}'
                })
            }).finally(function() {
                window.print();
            });
        }
    </script>

</body>

</html>
