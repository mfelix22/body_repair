<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Month-End Stock Cost {{ $monthLabel }}</title>
    @php
        $rp = fn($v) => number_format((float) $v, 2, '.', ',');
        $qty = fn($v) => number_format((float) $v, 2, '.', ',');
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; padding: 18px 20px; }

        .head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .head td { vertical-align: top; }
        .company { font-weight: bold; text-transform: uppercase; }
        .title { text-align: center; font-size: 14px; font-weight: bold; letter-spacing: 3px; }
        .period { text-align: center; font-size: 12px; margin-top: 2px; }
        .printed { text-align: right; font-size: 10px; }

        table.listing { width: 100%; border-collapse: collapse; }
        .listing th { text-align: left; font-weight: bold; padding: 4px; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .listing td { padding: 2px 4px; }
        .listing .right { text-align: right; }
        .listing .type td { padding-top: 22px; font-weight: bold; }
        .listing .line td.num { border-bottom: 1px solid #000; }
        .listing .subtotal td { padding-top: 4px; padding-bottom: 6px; font-weight: bold; }
        .listing .subtotal td.num { border-bottom: 3px double #000; }
        .listing .grand td { font-weight: bold; padding-top: 14px; }
        .listing .grand td.num { border-top: 1px solid #000; border-bottom: 3px double #000; }

        .footnote { margin-top: 12px; font-size: 9px; color: #333; }

        @media print {
            body { padding: 0; }
            @page { size: A4 landscape; margin: 8mm; }
            .listing tr { page-break-inside: avoid; }
            .listing thead { display: table-header-group; }
        }
    </style>
</head>

<body>
    <table class="head">
        <tr>
            <td style="width:30%">
                <div class="company">PT Hartono Auto Studio</div>
                <div>JAKARTA</div>
            </td>
            <td style="width:40%">
                <div class="title">*** MONTH-END STOCK COST ***</div>
                <div class="period">{{ $monthLabel }}</div>
            </td>
            <td style="width:30%" class="printed">{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="listing">
        <thead>
            <tr>
                <th>Code</th>
                <th>Item Name</th>
                <th>UOM</th>
                <th class="right">Open Qty</th>
                <th class="right">Open Value</th>
                <th class="right">In Qty</th>
                <th class="right">In Value</th>
                <th class="right">Out Qty</th>
                <th class="right">Out Value</th>
                <th class="right">Adj Qty</th>
                <th class="right">Adj/Reval</th>
                <th class="right">Close Qty</th>
                <th class="right">Avg Cost</th>
                <th class="right">Close Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $type => $group)
                <tr class="type">
                    <td colspan="14">Type : {{ $type }} &nbsp;- {{ $types[$type] ?? $type }}</td>
                </tr>
                @foreach ($group['rows'] as $r)
                    <tr class="{{ $loop->last ? 'line' : '' }}">
                        <td>{{ $r['code'] }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td>{{ $r['uom'] }}</td>
                        <td class="right num">{{ $qty($r['open_qty']) }}</td>
                        <td class="right num">{{ $rp($r['open_val']) }}</td>
                        <td class="right num">{{ $qty($r['in_qty']) }}</td>
                        <td class="right num">{{ $rp($r['in_val']) }}</td>
                        <td class="right num">{{ $qty(-$r['out_qty']) }}</td>
                        <td class="right num">{{ $rp(-$r['out_val']) }}</td>
                        <td class="right num">{{ $qty($r['adj_qty']) }}</td>
                        <td class="right num">{{ $rp($r['adj_val'] + $r['reval']) }}</td>
                        <td class="right num">{{ $qty($r['close_qty']) }}</td>
                        <td class="right num">{{ $rp($r['close_avg']) }}</td>
                        <td class="right num">{{ $rp($r['close_val']) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td></td>
                    <td colspan="3">Total {{ $type }} &nbsp;- {{ $types[$type] ?? $type }} ({{ $group['totals']['count'] }})</td>
                    <td class="right num">{{ $rp($group['totals']['open_val']) }}</td>
                    <td></td>
                    <td class="right num">{{ $rp($group['totals']['in_val']) }}</td>
                    <td></td>
                    <td class="right num">{{ $rp(-$group['totals']['out_val']) }}</td>
                    <td></td>
                    <td class="right num">{{ $rp($group['totals']['adj_val'] + $group['totals']['reval']) }}</td>
                    <td></td>
                    <td></td>
                    <td class="right num">{{ $rp($group['totals']['close_val']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align:center; padding:20px;">No stock data for {{ $monthLabel }}.</td>
                </tr>
            @endforelse
            @if ($groups)
                <tr class="grand">
                    <td colspan="4">GRAND TOTAL ({{ $totals['count'] }} items)</td>
                    <td class="right num">{{ $rp($totals['open_val']) }}</td>
                    <td></td>
                    <td class="right num">{{ $rp($totals['in_val']) }}</td>
                    <td></td>
                    <td class="right num">{{ $rp(-$totals['out_val']) }}</td>
                    <td></td>
                    <td class="right num">{{ $rp($totals['adj_val'] + $totals['reval']) }}</td>
                    <td></td>
                    <td></td>
                    <td class="right num">{{ $rp($totals['close_val']) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footnote">
        Valuation: weighted average cost, reconstructed from the stock transaction ledger
        (incl. manual avg-cost adjustments). Open + In &minus; Out &plusmn; Adj/Reval = Close.
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>

</html>
