<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma Invoice {{ $proformaInvoice->proforma_number }}</title>
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

        .section-label {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 4px;
        }

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

        .bottom-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .bottom-section td {
            vertical-align: top;
        }

        .payment-info {
            font-size: 11px;
            line-height: 1.6;
        }

        .payment-info strong {
            font-size: 11px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 4px 6px;
            font-size: 11px;
            border: 1px solid #ccc;
        }

        .totals-table .grand-total td {
            font-weight: bold;
            font-size: 12px;
            background-color: #f0f0f0;
            border-top: 2px solid #aaa;
        }

        .keterangan {
            font-size: 9.5px;
            margin-top: 14px;
            line-height: 1.5;
        }

        .keterangan p {
            margin-bottom: 2px;
        }

        .keterangan .highlight {
            font-weight: bold;
            background-color: #ffff00;
            padding: 1px 3px;
        }

        .sig-footer-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .sig-footer-table td {
            vertical-align: top;
            padding: 0;
        }

        .company-footer {
            font-size: 10px;
            text-align: left;
        }

        .company-footer a {
            color: #0000cc;
        }

        .sig-box {
            text-align: center;
            width: 250px;
            float: right;
        }

        .sig-label {
            font-weight: bold;
            font-size: 11px;
            background-color: #ffff00;
            padding: 4px 20px;
            border: 1px solid #aaa;
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
        $pf = $proformaInvoice;
        $wo = $pf->workOrder;

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
            if (is_string($date)) {
                $date = \Carbon\Carbon::parse($date);
            }
            return $date->day . ' ' . $monthsId[$date->month] . ' ' . $date->year;
        };

        $paketCodes = $wo->paket_code ? explode(' + ', $wo->paket_code) : ['-'];
        $paketNames = $wo->paket_name ? explode(' + ', $wo->paket_name) : ['-'];
        $paketCount = max(count($paketCodes), count($paketNames));

        $laborTotal = (float) ($wo->labor_total ?? 0);
        $subtotal = (float) $pf->subtotal;
        $discountPct = (float) ($pf->discount_percentage ?? 0);
        $discountAmt = (float) ($pf->discount_amount ?? 0);
        $grandTotal = (float) $pf->total;

        $serviceTotal = $subtotal - $laborTotal;

        $sizeDisplay = '';
        if ($wo->paket_size && $wo->paket_size !== 'All') {
            $sizeDisplay = str_replace('Size ', '', $wo->paket_size);
        }

        // Per-line discount data
        $hasLines = $pf->discountLines->isNotEmpty();
        $pkgLines = $pf->discountLines->where('target_type', 'package');
        $itemLines = $pf->discountLines->where('target_type', 'extra_item');
        $laborLines = $pf->discountLines->where('target_type', 'extra_labor');
    @endphp

    {{-- PRINT BUTTON --}}
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="window.print()"
            style="padding:6px 16px;font-size:12px;cursor:pointer;background:#007bff;color:#fff;border:none;border-radius:4px;">
            &#128438; Print
        </button>
        <a href="{{ route('proforma_invoices.show', $proformaInvoice) }}" style="margin-left:10px;font-size:12px;">
            &larr; Back
        </a>
    </div>

    {{-- HEADER --}}
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

    <div class="doc-title">PROFORMA INVOICE</div>
    <div class="doc-number">No : {{ $pf->proforma_number }}</div>

    <hr class="separator">

    {{-- INFO SECTION --}}
    <table class="info-table">
        <tr>
            <td class="info-section">
                <table>
                    <tr>
                        <td>WO No / Size</td>
                        <td>:</td>
                        <td class="val">{{ $wo->wo_number ?? '-' }}{{ $sizeDisplay ? ' / ' . $sizeDisplay : '' }}
                        </td>
                    </tr>
                    <tr>
                        <td>Licence Plate</td>
                        <td>:</td>
                        <td class="val">{{ $wo->vehicle_plate ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Merk</td>
                        <td>:</td>
                        <td class="val">{{ $wo->vehicle_merk ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Type / Year</td>
                        <td>:</td>
                        <td class="val">{{ $wo->vehicle_type_year ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Mileage</td>
                        <td>:</td>
                        <td class="val">{{ $wo->vehicle_km ? number_format($wo->vehicle_km) : '-' }}</td>
                    </tr>
                    <tr>
                        <td>Chasis No</td>
                        <td>:</td>
                        <td class="val">{{ $wo->chasis_no ?? '-' }}</td>
                    </tr>
                </table>
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
                        <td class="val">{{ $wo->customer->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>:</td>
                        <td class="val">{{ $wo->customer->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>:</td>
                        <td class="val">{{ $wo->customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Proforma Date</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($pf->created_at) }}</td>
                    </tr>
                    <tr>
                        <td>Created By</td>
                        <td>:</td>
                        <td class="val">{{ $pf->creator->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ITEM TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:12%">Item Code</th>
                <th style="width:33%">Description</th>
                <th style="width:18%">Price</th>
                <th style="width:10%">Discount</th>
                <th style="width:8%">Qty</th>
                <th style="width:19%">Total</th>
            </tr>
        </thead>
        <tbody>
            @if ($hasLines)
                {{-- Package lines from discount system --}}
                @foreach ($pkgLines as $line)
                    <tr>
                        <td>{{ implode('+', $paketCodes) }}</td>
                        <td>{{ $line->description }}</td>
                        <td class="text-right">Rp {{ number_format($line->original_price, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if ($line->status === 'approved')
                                {{ number_format($line->discount_percentage, 2) }}%
                            @elseif ($line->status === 'rejected')
                                <em>No disc.</em>
                            @else
                                Pending
                            @endif
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp {{ number_format($line->final_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                {{-- If no package line in discount system but WO has a package, show full price --}}
                @if ($pkgLines->isEmpty() && $wo->paket_name)
                    @for ($i = 0; $i < $paketCount; $i++)
                        <tr>
                            <td>{{ $paketCodes[$i] ?? '-' }}</td>
                            <td>{{ $paketNames[$i] ?? '-' }}</td>
                            <td class="text-right">Rp
                                {{ number_format($wo->paket_grand_total / $paketCount, 0, ',', '.') }}</td>
                            <td></td>
                            <td class="text-center">1</td>
                            <td class="text-right">Rp
                                {{ number_format($wo->paket_grand_total / $paketCount, 0, ',', '.') }}</td>
                        </tr>
                    @endfor
                @endif
                {{-- Extra item lines --}}
                @foreach ($itemLines as $line)
                    <tr>
                        <td>—</td>
                        <td>{{ $line->description }}</td>
                        <td class="text-right">Rp {{ number_format($line->original_price, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if ($line->status === 'approved')
                                {{ number_format($line->discount_percentage, 2) }}%
                            @elseif ($line->status === 'rejected')
                                <em>No disc.</em>
                            @else
                                Pending
                            @endif
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp {{ number_format($line->final_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                {{-- Filler rows --}}
                @php $itemRowCount = $pkgLines->count() + ($pkgLines->isEmpty() && $wo->paket_name ? $paketCount : 0) + $itemLines->count(); @endphp
                @for ($i = $itemRowCount; $i < 3; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            @else
                {{-- Legacy / no-lines rendering --}}
                @for ($i = 0; $i < $paketCount; $i++)
                    <tr>
                        <td>{{ $paketCodes[$i] ?? '-' }}</td>
                        <td>{{ $paketNames[$i] ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format($serviceTotal / $paketCount, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $discountPct > 0 ? number_format($discountPct, 2) . '%' : '' }}</td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp {{ number_format($serviceTotal / $paketCount, 0, ',', '.') }}</td>
                    </tr>
                @endfor
                @for ($i = $paketCount; $i < 3; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    {{-- LABOR TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:12%">Labor Code</th>
                <th style="width:33%">Description</th>
                <th style="width:18%">Price</th>
                <th style="width:10%">Discount</th>
                <th style="width:8%">Qty</th>
                <th style="width:19%">Total</th>
            </tr>
        </thead>
        <tbody>
            {{-- Standard labor row (always shown) --}}
            <tr>
                <td>LAB</td>
                <td>Jasa Pengerjaan</td>
                <td class="text-right">Rp {{ number_format(75000, 0, ',', '.') }}</td>
                <td></td>
                <td class="text-center">1</td>
                <td class="text-right">Rp {{ number_format(75000, 0, ',', '.') }}</td>
            </tr>
            @if ($hasLines)
                {{-- Extra labor lines from discount system --}}
                @foreach ($laborLines as $line)
                    <tr>
                        <td>LAB</td>
                        <td>{{ $line->description }}</td>
                        <td class="text-right">Rp {{ number_format($line->original_price, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if ($line->status === 'approved')
                                {{ number_format($line->discount_percentage, 2) }}%
                            @elseif ($line->status === 'rejected')
                                <em>No disc.</em>
                            @else
                                Pending
                            @endif
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp {{ number_format($line->final_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                @for ($i = 1 + $laborLines->count(); $i < 3; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            @else
                @for ($i = 1; $i < 3; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    {{-- BOTTOM: PAYMENT + TOTALS --}}
    <table class="bottom-section">
        <tr>
            <td style="width:55%;">
                <div class="payment-info">
                    @if (($wo->account_code ?? 'C') === 'C')
                        <strong>Pembayaran Tunai</strong>
                    @else
                        <strong>Pembayaran Non Tunai :</strong>
                    @endif
                    <br>
                    Rekening BCA : <strong>088 880 5080</strong><br>
                    a.n <strong>Hartono Auto Studio</strong>
                </div>
            </td>
            <td style="width:45%;">
                <table class="totals-table">
                    <tr>
                        <td style="width:45%"><strong>Total Item</strong></td>
                        <td class="text-right">Rp {{ number_format($serviceTotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Labor</strong></td>
                        <td class="text-right">Rp {{ number_format(75000, 0, ',', '.') }}</td>
                    </tr>
                    @if ($discountAmt > 0)
                        <tr>
                            <td><strong>Discount ({{ number_format($discountPct, 2) }}%)</strong></td>
                            <td class="text-right">- Rp {{ number_format($discountAmt, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ((float) $pf->voucher_amount > 0)
                        <tr>
                            <td><strong>Voucher
                                    @if ($pf->voucher_code)
                                        ({{ $pf->voucher_code }})
                                    @endif
                                </strong></td>
                            <td class="text-right">- Rp {{ number_format($pf->voucher_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td><strong>Grand Total</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- KETERANGAN --}}
    <div class="keterangan">
        <p><strong>Keterangan :</strong></p>
        <p>1. Dokumen ini adalah Proforma Invoice dan bukan merupakan tagihan resmi. Invoice resmi akan diterbitkan
            setelah konfirmasi dari pihak terkait.</p>
        <p>2. Harga yang tercantum bersifat estimasi dan dapat berubah sesuai kondisi aktual kendaraan.</p>
        <p>3. <span class="highlight">Perawatan kendaraan yang dikerjakan di PT Hartono Auto Studio disarankan setiap 6
                bulan sekali.</span></p>
        <p>4. PT Hartono Auto Studio tidak bertanggung jawab atas kondisi kendaraan yang telah selesai dikerjakan akan
            tetapi tidak diambil dalam kurun waktu 1 minggu dari tanggal diinformasikannya customer.</p>
        <p>5. Barang yang sudah dibeli tidak dapat dikembalikan kecuali dengan perjanjian.</p>
    </div>

    {{-- SIGNATURE + COMPANY FOOTER --}}
    <table class="sig-footer-table">
        <tr>
            <td style="width:55%;">
                <div class="company-footer">
                    <strong>PT Hartono Auto Studio</strong><br>
                    Jl. Demak No. 166 - 168, Gundih, Kec. Bubutan, Surabaya, Jawa Timur, 60172<br>
                    <a href="mailto:hrautostudio@hartonomotor.com">hrautostudio@hartonomotor.com</a><br>
                    +62 877 2095 5959
                </div>
            </td>
            <td style="width:45%; text-align:right;">
                <div class="sig-box">
                    <div class="sig-label">Signature &amp; Company Stamp</div>
                    <div class="sig-underline"></div>
                    <div class="sig-name">( ___________________ )</div>
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
