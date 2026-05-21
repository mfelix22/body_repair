<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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

        /* ===== TABLES ===== */
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

        /* ===== BOTTOM SECTION ===== */
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

        /* ===== KETERANGAN ===== */
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

        /* ===== SIGNATURE + FOOTER ===== */
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
        $wo = $invoice->workOrder;

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

        // Parse paket codes / names
        $paketCodes = $wo->paket_code ? explode(' + ', $wo->paket_code) : ['-'];
        $paketNames = $wo->paket_name ? explode(' + ', $wo->paket_name) : ['-'];
        $paketCount = max(count($paketCodes), count($paketNames));

        // Calculate service amounts
        $laborTotal = (float) ($wo->labor_total ?? 0);
        $subtotal = (float) $invoice->subtotal;
        $discountPercentage = (float) ($invoice->discount_percentage ?? 0);
        $discountAmount = (float) ($invoice->discount_amount ?? 0);
        $grandTotal = (float) $invoice->grand_total;

        // Service total (after discount) = subtotal - labor_total
        $serviceTotal = $subtotal - $laborTotal;
        // Pre-discount service price = service total + discount
        $preDiscountService = $serviceTotal + $discountAmount;

        // Per-item pricing (split among paket items)
        $pricePerItem = $paketCount > 0 ? $preDiscountService / $paketCount : $preDiscountService;
        $totalPerItem = $paketCount > 0 ? $serviceTotal / $paketCount : $serviceTotal;
        $discountPerItem = $paketCount > 0 ? $discountAmount / $paketCount : $discountAmount;

        // WO Size display
        $sizeDisplay = '';
        if ($wo->paket_size && $wo->paket_size !== 'All') {
            $sizeDisplay = str_replace('Size ', '', $wo->paket_size);
        }
    @endphp

    {{-- ===== PRINT BUTTON ===== --}}
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="window.print()"
            style="padding:6px 16px;font-size:12px;cursor:pointer;background:#007bff;color:#fff;border:none;border-radius:4px;">
            &#128438; Print
        </button>
        <a href="{{ route('invoices.show', $invoice) }}" style="margin-left:10px;font-size:12px;">
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

    <div class="doc-title">INVOICE</div>
    <div class="doc-number">No : {{ $invoice->invoice_number }}</div>

    <hr class="separator">

    {{-- ===== INFO SECTION ===== --}}
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
                        <td class="val">{{ $invoice->customer->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>:</td>
                        <td class="val">{{ $invoice->customer->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>:</td>
                        <td class="val">{{ $invoice->customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Invoice Date</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($invoice->invoice_date) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== ITEM TABLE (Service / Package) ===== --}}
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
            @for ($i = 0; $i < $paketCount; $i++)
                <tr>
                    <td>{{ $paketCodes[$i] ?? '-' }}</td>
                    <td>{{ $paketNames[$i] ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($pricePerItem, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $discountPercentage > 0 ? number_format($discountPercentage, 2) . '%' : '' }}</td>
                    <td class="text-center">1</td>
                    <td class="text-right">Rp {{ number_format($totalPerItem, 0, ',', '.') }}</td>
                </tr>
            @endfor
            {{-- Empty rows to fill minimum 3 --}}
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
        </tbody>
    </table>

    {{-- ===== LABOR TABLE ===== --}}
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
            <tr>
                <td>LAB</td>
                <td>Jasa Pengerjaan</td>
                <td class="text-right">Rp {{ number_format(75000, 0, ',', '.') }}</td>
                <td class="text-center"></td>
                <td class="text-center">1</td>
                <td class="text-right">Rp {{ number_format(75000, 0, ',', '.') }}</td>
            </tr>
            {{-- Empty rows to fill minimum 3 --}}
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
        </tbody>
    </table>

    {{-- ===== BOTTOM: PAYMENT + TOTALS ===== --}}
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
                    <tr class="grand-total">
                        <td><strong>Grand Total</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== KETERANGAN ===== --}}
    <div class="keterangan">
        <p><strong>Keterangan :</strong></p>
        <p>1. PT Hartono Auto Studio tidak bertanggung jawab atas kondisi kendaraan yang telah selesai dikerjakan akan
            tetapi tidak diambil dalam kurun waktu 1 minggu dari tanggal diinformasikannya customer.</p>
        <p>2. Perbaikan atas dasar kinerja Workshop yang tidak sesuai dapat dilakukan secara gratis maksimal H+3 setelah
            unit diserahkan ke customer.</p>
        <p>3. <span class="highlight">Perawatan kendaraan yang dikerjakan di PT Hartono Auto Studio disarankan setiap 6
                bulan sekali.</span></p>
        <p>4. Pengambilan kendaraan WAJIB menyertakan Lembar Pemeriksaan Kendaraan atau Tanda Terima yang diberikan oleh
            Service Advisor pada saat penyerahan unit sebelum pengerjaan.</p>
        <p>5. Barang yang sudah dibeli tidak dapat dikembalikan kecuali dengan perjanjian.</p>
        <p>6. Pembayaran secara transfer dianggap SAH apabila masuk ke rekening resmi yang tertera di Invoice atas nama
            Hartono Auto Studio.</p>
    </div>

    {{-- ===== SIGNATURE + COMPANY FOOTER (same row) ===== --}}
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
