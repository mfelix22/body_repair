<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bon In Listing {{ $period }}</title>
    @php
        $rp = fn($v) => number_format((float) $v, 2, '.', ',');
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; padding: 18px 20px; }

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
        .listing .vendor td { padding-top: 22px; }
        .listing .line td.num { border-bottom: 1px solid #000; }
        .listing .subtotal td { padding-top: 4px; padding-bottom: 6px; }
        .listing .subtotal td.num { border-bottom: 3px double #000; }
        .listing .grand td { font-weight: bold; padding-top: 14px; }
        .listing .grand td.num { border-top: 1px solid #000; border-bottom: 3px double #000; }

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
                <div class="title">*** BON IN LISTING ***</div>
                <div class="period">{{ $period }}</div>
            </td>
            <td style="width:30%" class="printed">{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="listing">
        <thead>
            <tr>
                <th>Type</th>
                <th>Bon In</th>
                <th>Date</th>
                <th>Order</th>
                <th class="right">Value</th>
                <th class="right">Tax</th>
                <th class="right">Total</th>
                <th class="right">Cost Value</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                <tr class="vendor">
                    <td colspan="9">Vendor : {{ $group['code'] }} &nbsp;- {{ $group['name'] }}</td>
                </tr>
                @foreach ($group['rows'] as $r)
                    <tr class="{{ $loop->last ? 'line' : '' }}">
                        <td>{{ $r['type'] }}</td>
                        <td>{{ $r['bon_in'] }}</td>
                        <td>{{ $r['date'] }}</td>
                        <td>{{ $r['order'] }}</td>
                        <td class="right num">{{ $rp($r['value']) }}</td>
                        <td class="right num">{{ $rp($r['tax']) }}</td>
                        <td class="right num">{{ $rp($r['total']) }}</td>
                        <td class="right num">{{ $rp($r['cost_value']) }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r['note'], 30) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td></td>
                    <td colspan="3">Total {{ $group['code'] }} &nbsp;- {{ $group['name'] }}</td>
                    <td class="right num">{{ $rp($group['totals']['value']) }}</td>
                    <td class="right num">{{ $rp($group['totals']['tax']) }}</td>
                    <td class="right num">{{ $rp($group['totals']['total']) }}</td>
                    <td class="right num">{{ $rp($group['totals']['cost_value']) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:20px;">Tidak ada data.</td>
                </tr>
            @endforelse
            @if ($groups)
                <tr class="grand">
                    <td colspan="4">GRAND TOTAL ({{ $totals['count'] }} Bon In)</td>
                    <td class="right num">{{ $rp($totals['value']) }}</td>
                    <td class="right num">{{ $rp($totals['tax']) }}</td>
                    <td class="right num">{{ $rp($totals['total']) }}</td>
                    <td class="right num">{{ $rp($totals['cost_value']) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>

</html>
