<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoiced Workshop Detail {{ $period }}</title>
    @php
        $rp = fn($v) => number_format((float) $v, 0, '.', ',');
        $blank = fn($v) => $v ? $rp($v) : '';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; padding: 18px 20px; }

        .head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .head td { vertical-align: top; }
        .company { font-weight: bold; text-transform: uppercase; }
        .title { text-align: center; font-size: 13px; font-weight: bold; }
        .period { text-align: center; font-size: 11px; margin-top: 2px; }
        .printed { text-align: right; font-size: 10px; }
        .jobtype { font-style: italic; font-weight: bold; margin-top: 4px; }

        table.listing { width: 100%; border-collapse: collapse; }
        .listing th { text-align: left; font-weight: bold; padding: 4px 3px; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .listing td { padding: 1px 3px; white-space: nowrap; }
        .listing .right { text-align: right; }
        .listing .account td { padding-top: 10px; font-weight: bold; }
        .listing .line td.num { border-bottom: 1px solid #000; }
        .listing .subtotal td { padding-top: 4px; padding-bottom: 6px; font-weight: bold; }
        .listing .grand td { font-weight: bold; padding-top: 12px; }
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
                <div class="jobtype">{{ $jobLabel }}</div>
            </td>
            <td style="width:40%">
                <div class="title">*** INVOICED WORKSHOP DETAIL ***</div>
                <div class="period">From {{ $period }}</div>
            </td>
            <td style="width:30%" class="printed">{{ now()->format('d/m/Y - H:i:s') }}</td>
        </tr>
    </table>

    <table class="listing">
        <thead>
            <tr>
                <th>NO.</th>
                <th>DATE</th>
                <th>INV-NO</th>
                <th>WIP-NO</th>
                <th>CUSTOMER</th>
                <th class="right">LABOUR</th>
                <th class="right">SPAREPART</th>
                <th class="right">DISCOUNT</th>
                <th class="right">AMOUNT</th>
                <th class="right">TAX</th>
                <th class="right">STAMP</th>
                <th class="right">TOTAL</th>
                <th>JOB-NO</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                <tr class="account">
                    <td colspan="13">ACCOUNT : {{ $group['label'] }}</td>
                </tr>
                @foreach ($group['rows'] as $r)
                    <tr class="{{ $loop->last ? 'line' : '' }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $r['date'] }}</td>
                        <td>{{ $r['inv_no'] }}</td>
                        <td>{{ $r['wip_no'] }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r['customer'], 32) }}</td>
                        <td class="right num">{{ $blank($r['labour']) }}</td>
                        <td class="right num">{{ $blank($r['sparepart']) }}</td>
                        <td class="right num">{{ $blank($r['discount']) }}</td>
                        <td class="right num">{{ $rp($r['amount']) }}</td>
                        <td class="right num">{{ $blank($r['tax']) }}</td>
                        <td class="right num">{{ $blank($r['stamp']) }}</td>
                        <td class="right num">{{ $rp($r['total']) }}</td>
                        <td>{{ $r['job_no'] }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="4"></td>
                    <td>SUB TOTAL &nbsp;&nbsp; {{ $group['label'] }}</td>
                    <td class="right">{{ $blank($group['totals']['labour']) }}</td>
                    <td class="right">{{ $blank($group['totals']['sparepart']) }}</td>
                    <td class="right">{{ $blank($group['totals']['discount']) }}</td>
                    <td class="right">{{ $rp($group['totals']['amount']) }}</td>
                    <td class="right">{{ $blank($group['totals']['tax']) }}</td>
                    <td class="right">{{ $blank($group['totals']['stamp']) }}</td>
                    <td class="right">{{ $rp($group['totals']['total']) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center; padding:20px;">Tidak ada data.</td>
                </tr>
            @endforelse
            @if ($groups)
                <tr class="grand">
                    <td colspan="5">GRAND TOTAL ({{ $totals['count'] }} dokumen)</td>
                    <td class="right num">{{ $rp($totals['labour']) }}</td>
                    <td class="right num">{{ $rp($totals['sparepart']) }}</td>
                    <td class="right num">{{ $rp($totals['discount']) }}</td>
                    <td class="right num">{{ $rp($totals['amount']) }}</td>
                    <td class="right num">{{ $rp($totals['tax']) }}</td>
                    <td class="right num">{{ $rp($totals['stamp']) }}</td>
                    <td class="right num">{{ $rp($totals['total']) }}</td>
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
