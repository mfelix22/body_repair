<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon Out {{ $bonOut->bon_out_number }}</title>
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
            border-top: 2px solid #000;
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
            width: 48%;
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

        .info-divider {
            width: 4%;
        }

        .note-box {
            border: 1px solid #fff;
            padding: 6px 8px;
            font-size: 11px;
            line-height: 1.5;
            min-height: 80px;
        }

        .note-box .note-label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .note-box .note-value {
            color: #000;
        }

        /* ===== ITEMS TABLE ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table thead tr {
            background-color: #d0d0d0;
        }

        .items-table thead th {
            padding: 5px 6px;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #aaa;
            text-align: center;
        }

        .items-table tbody td {
            padding: 4px 6px;
            border: 1px solid #ccc;
            font-size: 11px;
        }

        .items-table tbody tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .items-table .empty-row td {
            height: 20px;
            border: 1px solid #ccc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ===== SIGNATURE ===== */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sig-table td {
            text-align: center;
            padding: 4px;
            border: none;
            vertical-align: top;
        }

        .sig-label {
            font-weight: bold;
            font-size: 11px;
            /* background-color: #ffff00; */
            padding: 4px 20px;
            border: 1px solid #fff;
            display: inline-block;
        }

        .sig-underline {
            width: 80%;
            margin: 60px auto 4px;
            border-bottom: 1px solid #000;
        }

        .sig-name {
            font-size: 10px;
        }

        /* ===== FOOTER ===== */
        .company-footer {
            font-size: 10px;
            margin-top: 12px;
        }

        .company-footer a {
            color: #0000cc;
        }

        .rq-note {
            font-size: 9px;
            color: #555;
            font-style: italic;
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
    @php
        $wo = $bonOut->workOrder;

        $accountLabels = ['C' => 'CASH', 'INT_WS' => 'Internal WS', 'INT_W3' => 'Internal W3'];
        $accountDisplay = $wo ? $accountLabels[$wo->account_code ?? 'C'] ?? ($wo->account_code ?? '-') : '-';

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

        $idDate = function ($date) use ($monthsId) {
            if (!$date) {
                return '-';
            }
            return $date->day . ' ' . $monthsId[$date->month] . ' ' . $date->year;
        };
    @endphp

    {{-- ===== PRINT BUTTON ===== --}}
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="doPrint()"
            style="padding:6px 16px;font-size:12px;cursor:pointer;background:#007bff;color:#fff;border:none;border-radius:4px;">
            &#128438; Print
        </button>
        <a href="{{ route('bon_outs.show', $bonOut) }}" style="margin-left:10px;font-size:12px;">
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

    <div class="doc-title">BON OUT</div>
    <div class="doc-number">No : {{ $bonOut->bon_out_number }}</div>

    <hr class="separator">

    {{-- ===== INFO SECTION ===== --}}
    <table class="info-table">
        <tr>
            <td class="info-section">
                <div class="note-box">
                    <div class="note-label">Note :</div>
                    {{-- @if ($wo)
                        <div class="note-value">
                            {{ $wo->wo_number ?? '-' }}{{ $sizeDisplay ? ' / ' . $sizeDisplay : '' }}</div>
                        <div class="note-value">{{ $wo->vehicle_plate ?? '-' }}</div>
                        <div class="note-value">{{ $wo->vehicle_merk ?? '-' }}</div>
                        <div class="note-value">{{ $wo->vehicle_type_year ?? '-' }}</div>
                    {{-- @else --}}
                    <div class="note-value">{{ $bonOut->notes ?? '-' }}</div>
                    {{-- @endif --}}
                </div>
            </td>
            <td class="info-divider"></td>
            <td class="info-section">
                <table>
                    <tr>
                        <td>Account Code</td>
                        <td>:</td>
                        <td class="val">{{ $accountDisplay }}</td>
                    </tr>
                    <tr>
                        <td>Customer Name</td>
                        <td>:</td>
                        <td class="val">{{ $wo->customer->name ?? ($bonOut->issued_to ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td>WO Number</td>
                        <td>:</td>
                        <td class="val">{{ $wo->wo_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Plat Nomor</td>
                        <td>:</td>
                        <td class="val">{{ $wo->vehicle_plate ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($bonOut->issued_date) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE (grouped by section) ===== --}}
    @php
        $sectionLabels = [
            'A' => 'DEMPUL',
            'B' => 'CAT',
            'C' => 'VERNIS',
            'D' => 'POLES dan KEBERSIHAN AKHIR',
            'E' => 'SPAREPART',
        ];
        $sectionOrder  = ['A', 'B', 'C', 'D', 'E'];
        $grouped       = $bonOut->items->groupBy(fn($i) => $i->bon_out_section ?? 'Unsorted');
        $grandQtyTotal = 0;
        $rowsPrinted   = 0;
    @endphp

    @foreach ($sectionOrder as $sKey)
        @if (!isset($grouped[$sKey])) @continue @endif
        @php
            $sItems    = $grouped[$sKey];
            $sQtyTotal = 0;
        @endphp

        {{-- Section header row --}}
        <table class="items-table" style="margin-bottom:0;">
            <tbody>
                <tr>
                    <td colspan="6" style="background:#d8d8d8;font-weight:bold;padding:4px 6px;border:1px solid #aaa;font-size:11px;">
                        {{ $sKey }}.&nbsp;&nbsp;&nbsp;{{ $sectionLabels[$sKey] }}
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="items-table" style="margin-bottom:0;border-top:none;">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:14%">SKU</th>
                    <th style="width:33%">Description</th>
                    <th style="width:10%">Class</th>
                    <th style="width:10%">Qty</th>
                    <th style="width:8%">Unit</th>
                    <th style="width:20%">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sItems as $bi)
                    @php
                        $qty = (float) $bi->actual_quantity;
                        $sQtyTotal    += $qty;
                        $grandQtyTotal += $qty;
                        $rowsPrinted++;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $bi->item->code ?? '-' }}</td>
                        <td>{{ $bi->item->name ?? '-' }}</td>
                        <td class="text-center">{{ $bi->item->item_type ?? '' }}</td>
                        <td class="text-center">{{ number_format($qty, 2) }}</td>
                        <td class="text-center">{{ $bi->item->smallestUom->code ?? '-' }}</td>
                        <td>{{ $bi->remark ?? ($bi->workOrderItem->remark ?? '') }}</td>
                    </tr>
                @endforeach
                {{-- Subtotal row --}}
                <tr style="background:#f0f0f0;font-weight:bold;">
                    <td colspan="4" class="text-right" style="border:1px solid #ccc;padding:4px 6px;">
                        Subtotal Section {{ $sKey }}
                    </td>
                    <td class="text-center" style="border:1px solid #ccc;padding:4px 6px;">
                        {{ number_format($sQtyTotal, 2) }}
                    </td>
                    <td colspan="2" style="border:1px solid #ccc;padding:4px 6px;"></td>
                </tr>
            </tbody>
        </table>
        <div style="margin-bottom:6px;"></div>
    @endforeach

    @if (isset($grouped['Unsorted']))
        @php $uItems = $grouped['Unsorted']; $uQtyTotal = 0; @endphp
        <table class="items-table" style="margin-bottom:0;">
            <tbody>
                <tr>
                    <td colspan="7" style="background:#e8e8e8;font-weight:bold;padding:4px 6px;border:1px solid #aaa;font-size:11px;">
                        Unsorted
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="items-table" style="margin-bottom:0;border-top:none;">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:14%">SKU</th>
                    <th style="width:33%">Description</th>
                    <th style="width:10%">Class</th>
                    <th style="width:10%">Qty</th>
                    <th style="width:8%">Unit</th>
                    <th style="width:20%">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($uItems as $bi)
                    @php
                        $qty = (float) $bi->actual_quantity;
                        $uQtyTotal     += $qty;
                        $grandQtyTotal += $qty;
                        $rowsPrinted++;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $bi->item->code ?? '-' }}</td>
                        <td>{{ $bi->item->name ?? '-' }}</td>
                        <td class="text-center">{{ $bi->item->item_type ?? '' }}</td>
                        <td class="text-center">{{ number_format($qty, 2) }}</td>
                        <td class="text-center">{{ $bi->item->smallestUom->code ?? '-' }}</td>
                        <td>{{ $bi->remark ?? ($bi->workOrderItem->remark ?? '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-bottom:6px;"></div>
    @endif

    {{-- Grand total --}}
    <table class="items-table" style="margin-bottom:4px;">
        <tbody>
            <tr style="background:#c8c8c8;font-weight:bold;">
                <td colspan="4" class="text-right" style="border:1px solid #aaa;padding:4px 6px;width:62%;">
                    Grand Total
                </td>
                <td class="text-center" style="border:1px solid #aaa;padding:4px 6px;width:10%;">
                    {{ number_format($grandQtyTotal, 2) }}
                </td>
                <td colspan="2" style="border:1px solid #aaa;padding:4px 6px;width:28%;"></td>
            </tr>
        </tbody>
    </table>

    <p class="rq-note">*apabila permintaan dari RQ maka cantumkan nomor RQ pada kolom Remarks</p>

    {{-- ===== SIGNATURE SECTION ===== --}}
    <table class="sig-table">
        <tr>
            <td style="width:33%;text-align:left;padding-left:10px;">
                <div class="sig-label">PT Hartono Auto Studio</div>
                <div style="font-size:9px;margin-top:4px;line-height:1.4;">
                    Jl. Demak No. 166 - 168, Gundih, Kec. Bubutan, Surabaya, Jawa Timur, 60172<br>
                    <a href="mailto:hrautostudio@hartonomotor.com"
                        style="color:#000;text-decoration:none;">hrautostudio@hartonomotor.com</a><br>
                    +62 877 2095 5959
                </div>
            </td>
            <td style="width:33%;">
                <div class="sig-label">Dispatcher</div>
                <div class="sig-underline"></div>
                <div class="sig-name">( ___________________ )</div>
            </td>
            <td style="width:33%;">
                <div class="sig-label">Receiver</div>
                <div class="sig-underline"></div>
                <div class="sig-name">( ___________________ )</div>
            </td>
        </tr>
    </table>

    <script>
        function doPrint() {
            fetch('/audit/log-print', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    model_type: 'BonOut',
                    model_id: {{ $bonOut->id }},
                    document_number: '{{ $bonOut->bon_out_number }}'
                })
            }).finally(function() {
                window.print();
            });
        }
    </script>

</body>

</html>
