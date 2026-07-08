<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COGS Report – {{ $invoice->invoice_number }}</title>
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

        .doc-subtitle {
            text-align: center;
            font-size: 11px;
            color: #555;
            margin-bottom: 8px;
        }

        .confidential {
            text-align: center;
            font-size: 10px;
            color: #000;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 6px;
        }

        /* ===== INFO SECTION ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
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
            color: #333;
        }

        .info-divider {
            width: 4%;
        }

        /* ===== TABLES ===== */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0 4px;
            padding: 3px 6px;
            background-color: #f0f0f0;
            border-left: 3px solid #000;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
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

        .items-table tfoot td {
            padding: 5px 6px;
            border: 1px solid #aaa;
            font-size: 11px;
            font-weight: bold;
            background-color: #e8e8e8;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ===== SUMMARY ===== */
        .summary-table {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .summary-table td {
            padding: 5px 8px;
            font-size: 11px;
            border: 1px solid #ccc;
        }

        .summary-table .label-col {
            font-weight: bold;
            width: 55%;
            background-color: #f8f8f8;
        }

        .summary-table .total-row td {
            font-weight: bold;
            font-size: 12px;
            background-color: #fff3cd;
            border-top: 2px solid #aaa;
        }

        .summary-table .profit-row td {
            font-weight: bold;
            font-size: 12px;
        }

        .profit-positive {
            background-color: #d4edda;
        }

        .profit-negative {
            background-color: #f8d7da;
        }

        /* ===== FOOTER ===== */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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
            padding: 4px 14px;
            border: 1px solid #aaa;
            display: inline-block;
        }

        .sig-underline {
            width: 80%;
            margin: 50px auto 4px;
            border-bottom: 1px solid #000;
        }

        .sig-name {
            font-size: 10px;
        }

        .company-footer {
            font-size: 10px;
            margin-top: 10px;
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
    @php
        $wo = $invoice->workOrder;
        // Get all completed Bon Outs
        $completedBonOuts = $wo->bonOuts->where('status', 'completed');
        $bonOutNumbers = $completedBonOuts->pluck('bon_out_number')->implode(', ');

        $accountLabels = ['C' => 'CASH', 'INT_WS' => 'Internal WS', 'INT_W3' => 'Internal W3'];
        $accountDisplay = $accountLabels[$wo->account_code ?? 'C'] ?? ($wo->account_code ?? '-');

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

        $cogmMaterial = (float) ($invoice->cogm_material ?? 0);
        $cogmLabor = (float) ($invoice->cogm_labor ?? 0);
        $totalCogs = $cogmMaterial + $cogmLabor;
        $revenue = (float) ($invoice->grand_total ?? 0);
        $grossProfit = $revenue - $totalCogs;
        $margin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
    @endphp

    {{-- ===== PRINT BUTTON ===== --}}
    <div class="no-print" style="margin-bottom:12px;">
        @if (\App\Helpers\PermissionHelper::canPrint('invoices'))
            <button onclick="window.print()"
                style="padding:6px 16px;font-size:12px;cursor:pointer;background:#007bff;color:#fff;border:none;border-radius:4px;">
                &#128438; Print
            </button>
            <a href="{{ route('invoices.show', $invoice) }}" style="margin-left:10px;font-size:12px;">
                &larr; Back to Invoice
            </a>
        @endif
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

    <div class="doc-title">COGS REPORT</div>
    <div class="doc-subtitle">Cost of Goods Sold – Invoice {{ $invoice->invoice_number }}</div>
    <div class="confidential">CONFIDENTIAL – FOR INTERNAL USE ONLY</div>

    <hr class="separator">

    {{-- ===== INFO SECTION ===== --}}
    <table class="info-table">
        <tr>
            <td class="info-section">
                <table>
                    <tr>
                        <td>Invoice #</td>
                        <td>:</td>
                        <td class="val">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td>Invoice Date</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($invoice->invoice_date) }}</td>
                    </tr>
                    <tr>
                        <td>WO Number</td>
                        <td>:</td>
                        <td class="val">{{ $wo->wo_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Vehicle</td>
                        <td>:</td>
                        <td class="val">{{ $wo->vehicle_plate ?? '-' }} – {{ $wo->vehicle_merk ?? '' }}
                            {{ $wo->vehicle_type_year ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td class="info-divider"></td>
            <td class="info-section">
                <table>
                    <tr>
                        <td>Customer</td>
                        <td>:</td>
                        <td class="val">{{ $invoice->customer->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Account Code</td>
                        <td>:</td>
                        <td class="val">{{ $accountDisplay }}</td>
                    </tr>
                    <tr>
                        <td>Bon Out(s)</td>
                        <td>:</td>
                        <td class="val">{{ $bonOutNumbers ?: '-' }} <small>({{ $completedBonOuts->count() }}
                                completed)</small></td>
                    </tr>
                    <tr>
                        <td>Revenue</td>
                        <td>:</td>
                        <td class="val">Rp {{ number_format($revenue, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== MATERIAL COGS ===== --}}
    <div class="section-title">A. Direct Materials (COGS Material) – Aggregated from {{ $completedBonOuts->count() }}
        Bon Out(s)</div>

    @php
        // Aggregate materials from ALL completed Bon Outs
        $aggregatedItems = [];

        foreach ($completedBonOuts as $bonOut) {
            foreach ($bonOut->items as $item) {
                $itemId = $item->item_id;

                if (!isset($aggregatedItems[$itemId])) {
                    $aggregatedItems[$itemId] = [
                        'item' => $item->item,
                        'total_qty' => 0,
                        'unit_cost' => (float) $item->unit_cost,
                        'total_cost' => 0,
                    ];
                }

                $qty = (float) $item->actual_quantity;
                $cost = (float) $item->unit_cost;

                $aggregatedItems[$itemId]['total_qty'] += $qty;
                $aggregatedItems[$itemId]['total_cost'] += $qty * $cost;
            }
        }

        // Check which Work Order items were NOT used in any Bon Out
        $woItemIds = $wo->items->pluck('item_id')->toArray();
        $bonOutItemIds = array_keys($aggregatedItems);
        $unusedItemIds = array_diff($woItemIds, $bonOutItemIds);
        $unusedItems = $wo->items->whereIn('item_id', $unusedItemIds);
    @endphp

    @if ($unusedItems->count() > 0)
        <div
            style="padding: 8px; margin-bottom: 8px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 3px;">
            <strong>⚠ Note:</strong> {{ $unusedItems->count() }} item(s) from Work Order BOM were NOT used in any Bon
            Out:
            <ul style="margin: 4px 0 0 20px; padding: 0;">
                @foreach ($unusedItems as $unused)
                    <li style="font-size: 10px;">
                        <strong>[{{ $unused->item->code }}]</strong> {{ $unused->item->name }}
                        <span style="color: #666;">(Planned: {{ number_format($unused->demand_quantity, 2) }}
                            {{ $unused->item->smallestUom->code ?? '' }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (count($aggregatedItems) > 0)
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:14%">SKU</th>
                    <th style="width:28%">Item Description</th>
                    <th style="width:8%">Unit</th>
                    <th style="width:12%">Qty Used</th>
                    <th style="width:15%">Unit Cost (Rp)</th>
                    <th style="width:18%">Total Cost (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumMaterial = 0;
                    $rowNum = 1;
                @endphp
                @foreach ($aggregatedItems as $itemData)
                    @php
                        $qty = $itemData['total_qty'];
                        $cost = $itemData['unit_cost'];
                        $lineCost = $itemData['total_cost'];
                        $sumMaterial += $lineCost;
                        $uomCode = $itemData['item']->smallestUom->code ?? '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $rowNum++ }}</td>
                        <td>{{ $itemData['item']->code ?? '-' }}</td>
                        <td>{{ $itemData['item']->name ?? '-' }}</td>
                        <td class="text-center">{{ $uomCode }}</td>
                        <td class="text-right">{{ number_format($qty, 2) }}</td>
                        <td class="text-right">{{ number_format($cost, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($lineCost, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right">Total Material COGS :</td>
                    <td class="text-right">Rp {{ number_format($sumMaterial, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="padding: 6px; color: #888; font-style: italic;">No completed Bon Out items available.</p>
    @endif

    {{-- ===== SUMMARY ===== --}}
    <div class="section-title">B. Summary</div>

    <table class="summary-table">
        <tr>
            <td class="label-col">Direct Materials</td>
            <td class="text-right">Rp {{ number_format($cogmMaterial, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label-col">Labor Cost</td>
            <td class="text-right">Rp {{ number_format($cogmLabor, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td class="label-col">Total COGS</td>
            <td class="text-right">Rp {{ number_format($totalCogs, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label-col">Revenue (Invoice Grand Total)</td>
            <td class="text-right">Rp {{ number_format($revenue, 0, ',', '.') }}</td>
        </tr>
        <tr class="profit-row">
            <td class="label-col {{ $grossProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                Gross Profit
            </td>
            <td class="text-right {{ $grossProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                Rp {{ number_format($grossProfit, 0, ',', '.') }}
                <span style="font-size:10px;color:#555;">({{ number_format($margin, 1) }}%)</span>
            </td>
        </tr>
    </table>

    {{-- ===== SIGNATURES ===== --}}
    {{-- <table class="sig-table">
        <tr>
            <td style="width:33%;">
                <div class="sig-label">Prepared By</div>
                <div class="sig-underline"></div>
                <div class="sig-name">( ___________________ )</div>
            </td>
            <td style="width:33%;">
                <div class="sig-label">Reviewed By</div>
                <div class="sig-underline"></div>
                <div class="sig-name">( ___________________ )</div>
            </td>
            <td style="width:33%;">
                <div class="sig-label">Approved By</div>
                <div class="sig-underline"></div>
                <div class="sig-name">( ___________________ )</div>
            </td>
        </tr>
    </table> --}}

    {{-- ===== COMPANY FOOTER ===== --}}
    <div class="company-footer">
        <strong>PT Hartono Auto Studio</strong><br>
        Jl. Demak No. 166 - 168, Gundih, Kec. Bubutan, Surabaya, Jawa Timur, 60172<br>
        <a href="mailto:hrautostudio@hartonomotor.com">hrautostudio@hartonomotor.com</a> | +62 877 2095 5959
    </div>

</body>

</html>
