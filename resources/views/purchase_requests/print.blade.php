<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Permintaan Barang / Jasa - {{ $purchaseRequest->pr_number }}</title>
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
            margin: 18mm 14mm 14mm 14mm;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo-cell {
            width: 160px;
        }

        .logo-cell img {
            width: 150px;
        }

        .company-info {
            text-align: right;
            font-size: 11px;
            line-height: 1.5;
        }

        .company-info .company-name {
            font-weight: bold;
            font-size: 13px;
        }

        .company-info a,
        .company-info .email {
            color: #1155cc;
            text-decoration: none;
        }

        /* ── Title Block ── */
        .title-block {
            text-align: center;
            margin: 8px 0 6px;
        }

        .title-block h1 {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .title-block .doc-no {
            font-size: 12px;
            color: #c0392b;
            font-weight: bold;
            margin-top: 2px;
        }

        /* ── Info row ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .info-table td {
            padding: 3px 4px;
            font-size: 11px;
        }

        .info-value {
            color: #c0392b;
            font-weight: bold;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .items-table th {
            border: 1.5px solid #000;
            padding: 5px 6px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            background: #fff;
        }

        .items-table td {
            border: 1.5px solid #000;
            padding: 5px 6px;
            font-size: 11px;
            height: 22px;
        }

        .items-table tbody tr:nth-child(odd) {
            background: #e8f5e9;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fff;
        }

        .items-table td.no {
            text-align: center;
        }

        .items-table td.qty {
            text-align: right;
        }

        /* ── Signature section ── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .sig-table td {
            border: 1.5px solid #000;
            width: 25%;
            padding: 5px 8px 4px;
            vertical-align: top;
            font-size: 11px;
            font-weight: bold;
        }

        .sig-space {
            height: 55px;
        }

        .sig-name-line {
            font-weight: normal;
            margin-top: 4px;
        }

        @media print {
            body {
                margin: 10mm 12mm 10mm 12mm;
            }
        }
    </style>
</head>

<body>

    {{-- ── HEADER ── --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @php $logoPath = public_path('HAS.png'); @endphp
                @if (file_exists($logoPath))
                    <img src="data:{{ mime_content_type($logoPath) }};base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                        alt="HR Auto Studio">
                @endif
            </td>
            <td class="company-info">
                <div class="company-name">PT Hartono Auto Studio</div>
                <div>Jl. Demak No. 166 - 168, Gundih, Kec. Bubutan</div>
                <div>Surabaya, Jawa Timur, 60172</div>
                <div class="email">hrautostudio@hartonomotor.com</div>
            </td>
        </tr>
    </table>

    {{-- ── TITLE ── --}}
    <div class="title-block">
        <h1>PERMINTAAN BARANG / JASA</h1>
        <div class="doc-no">No : {{ $purchaseRequest->pr_number }}</div>
    </div>

    {{-- ── DEPARTMENT / DATE ── --}}
    <table class="info-table">
        <tr>
            <td width="100px"><strong>Department</strong></td>
            <td width="8px">:</td>
            <td><span class="info-value">{{ $purchaseRequest->requestor->role ?? '-' }}</span></td>
            <td width="60px" style="text-align:right;"><strong>Date</strong></td>
            <td width="8px">:</td>
            @php
                $bulan = [
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
                $tgl = $purchaseRequest->request_date;
                $tglStr = $tgl->format('d') . ' ' . $bulan[(int) $tgl->format('n')] . ' ' . $tgl->format('Y');
            @endphp
            <td width="160px"><span class="info-value">{{ $tglStr }}</span></td>
        </tr>
    </table>

    {{-- ── ITEMS TABLE ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:45%;">Description</th>
                <th style="width:10%;">Qty</th>
                <th style="width:10%;">Unit</th>
                <th style="width:30%;">Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseRequest->details as $index => $detail)
                <tr>
                    <td class="no">{{ $index + 1 }}</td>
                    <td>
                        @if ($purchaseRequest->type === 'Jasa')
                            {{ $detail->service_description ?? '' }}
                        @elseif ($detail->is_custom_item)
                            {{ $detail->custom_item_name ?? '' }}
                        @else
                            {{ $detail->item->name ?? '' }}
                        @endif
                    </td>
                    <td class="qty">
                        {{ $detail->quantity % 1 == 0 ? (int) $detail->quantity : number_format($detail->quantity, 2) }}
                    </td>
                    <td>{{ $purchaseRequest->type === 'Jasa' ? '' : $detail->uom->code ?? '' }}</td>
                    <td>{{ $detail->notes ?? '' }}</td>
                </tr>
            @endforeach
            @php
                $minRows = 7;
                $filled = $purchaseRequest->details->count();
            @endphp
            @for ($i = $filled; $i < $minRows; $i++)
                <tr>
                    <td class="no">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- ── SIGNATURES ── --}}
    <table class="sig-table">
        <tr>
            <td>Created by:</td>
            <td>Approve by:</td>
            <td>Acknowledged by:</td>
            <td>Purchasing Received:</td>
        </tr>
        <tr>
            <td>
                <div class="sig-space">
                    @if ($purchaseRequest->requestor && $purchaseRequest->requestor->signature_path)
                        @php $sp = Storage::disk('public')->path($purchaseRequest->requestor->signature_path); @endphp
                        @if (file_exists($sp))
                            <img src="data:{{ mime_content_type($sp) }};base64,{{ base64_encode(file_get_contents($sp)) }}"
                                style="max-height:50px;max-width:100px;">
                        @endif
                    @endif
                </div>
                <div class="sig-name-line">Name: {{ $purchaseRequest->requestor->name ?? '' }}</div>
            </td>
            <td>
                <div class="sig-space">
                    @if ($purchaseRequest->deptHeadApprover && $purchaseRequest->deptHeadApprover->signature_path)
                        @php $sp = Storage::disk('public')->path($purchaseRequest->deptHeadApprover->signature_path); @endphp
                        @if (file_exists($sp))
                            <img src="data:{{ mime_content_type($sp) }};base64,{{ base64_encode(file_get_contents($sp)) }}"
                                style="max-height:50px;max-width:100px;">
                        @endif
                    @endif
                </div>
                <div class="sig-name-line">Name: {{ $purchaseRequest->deptHeadApprover->name ?? '' }}</div>
            </td>
            <td>
                <div class="sig-space">
                    @if ($purchaseRequest->gmApprover && $purchaseRequest->gmApprover->signature_path)
                        @php $sp = Storage::disk('public')->path($purchaseRequest->gmApprover->signature_path); @endphp
                        @if (file_exists($sp))
                            <img src="data:{{ mime_content_type($sp) }};base64,{{ base64_encode(file_get_contents($sp)) }}"
                                style="max-height:50px;max-width:100px;">
                        @endif
                    @endif
                </div>
                <div class="sig-name-line">Name: {{ $purchaseRequest->gmApprover->name ?? '' }}</div>
            </td>
            <td>
                <div class="sig-space">
                    @if ($purchaseRequest->purchasingReceiver && $purchaseRequest->purchasingReceiver->signature_path)
                        @php $sp = Storage::disk('public')->path($purchaseRequest->purchasingReceiver->signature_path); @endphp
                        @if (file_exists($sp))
                            <img src="data:{{ mime_content_type($sp) }};base64,{{ base64_encode(file_get_contents($sp)) }}"
                                style="max-height:50px;max-width:100px;">
                        @endif
                    @endif
                </div>
                <div class="sig-name-line">Name: {{ $purchaseRequest->purchasingReceiver->name ?? '' }}</div>
            </td>
        </tr>
    </table>

</body>

</html>
