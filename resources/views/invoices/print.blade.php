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

        html {
            background: #ccc;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            padding: 24px 32px;
            max-width: 800px;
            margin: 20px auto;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.15);
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

        .keterangan p:not(:first-child) {
            padding-left: 16px;
            text-indent: -16px;
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
            /* background-color: #ffff00; */
            padding: 4px 20px;
            /* border: 1px solid #aaa; */
            display: inline-block;
        }

        .sig-underline {
            width: 80%;
            margin: 90px auto 4px;
            border-bottom: 1px solid #000;
        }

        .sig-name {
            font-size: 10px;
        }

        @media print {
            html {
                background: #fff;
            }

            body {
                padding: 0;
                max-width: 100%;
                margin: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 12mm 15mm 12mm 15mm;
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

        // Calculate totals
        $subtotal = (float) $invoice->subtotal;
        $discountPercentage = (float) ($invoice->discount_percentage ?? 0);
        $discountAmount = (float) ($invoice->discount_amount ?? 0);
        $orAmount = (float) ($invoice->or_amount ?? 0);
        $materai = (float) ($invoice->materai ?? 0);
        $grandTotal = (float) $invoice->grand_total;

        // Panels & labors
        $basePanels  = $wo->panelLabors->where('is_extra', false);
        $baseLabors  = $wo->generalLabors->where('is_extra', false);
        $extraLabors = $wo->generalLabors->where('is_extra', true);
        $panelTotal = (float) $basePanels->sum('total_price');
        $baseLaborTotal = (float) $baseLabors->sum('total_price');
        $extraLaborTotal = (float) $extraLabors->sum('total_price');

        // Proforma discount lines (if any)
        $proforma = $wo->proformaInvoice;
        $hasLines = $proforma && $proforma->discountLines->isNotEmpty();
        $laborLines = $hasLines ? $proforma->discountLines->where('target_type', 'extra_labor') : collect();

        $voucherAmt = $proforma ? (float) ($proforma->voucher_amount ?? 0) : 0;
        $lineDiscAmt = $discountAmount - $voucherAmt;
    @endphp

    {{-- ===== PRINT BUTTON ===== --}}
    <div class="no-print" style="margin-bottom:12px;">
        <button onclick="doPrint()"
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
                        <td>WO Number</td>
                        <td>:</td>
                        <td class="val">{{ $wo->wo_number ?? '-' }}</td>
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
                        <td class="val">
                            {{ $invoice->customer->name ?? '-' }}
                            @if ($invoice->qq)
                                - {{ $invoice->qq }}
                            @endif
                        </td>
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
                        <td>NPWP</td>
                        <td>:</td>
                        <td class="val">{{ $invoice->customer->npwp ?? '-' }}</td>
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

    {{-- ===== PANEL TABLE ===== --}}
    @if ($baseLabors->isNotEmpty())
    <div class="section-label">Panel yang Dikerjakan</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:12%">Panel Code</th>
                <th style="width:38%">Panel</th>
                <th style="width:8%" class="text-center">Qty</th>
                <th style="width:18%">Rate</th>
                <th style="width:10%">Discount</th>
                <th style="width:14%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baseLabors as $labor)
                <tr>
                    <td>{{ $labor->labor?->labor_code ?? '-' }}</td>
                    <td>{{ $labor->description }}</td>
                    <td class="text-center">{{ number_format($labor->qty, 0) }}</td>
                    <td class="text-right">Rp {{ number_format($labor->rate ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">Rp {{ number_format($labor->total_price ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===== EXTRA LABOR TABLE ===== --}}
    @if ($extraLabors->isNotEmpty() || $laborLines->isNotEmpty())
    <div class="section-label">Extra Labor</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:12%">Labor Code</th>
                <th style="width:38%">Extra Labor</th>
                <th style="width:8%" class="text-center">Qty</th>
                <th style="width:18%">Rate</th>
                <th style="width:10%">Discount</th>
                <th style="width:14%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($extraLabors as $labor)
                <tr>
                    <td>{{ $labor->labor?->labor_code ?? 'LAB' }}</td>
                    <td>{{ $labor->description }}</td>
                    <td class="text-center">{{ number_format($labor->qty, 0) }}</td>
                    <td class="text-right">Rp {{ number_format($labor->rate ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">Rp {{ number_format($labor->total_price ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @if ($hasLines)
                @foreach ($laborLines as $line)
                    <tr>
                        <td>LAB</td>
                        <td>{{ $line->description }}</td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp {{ number_format($line->original_price, 0, ',', '.') }}</td>
                        <td class="text-center">
                            {{ $line->status === 'approved' && $line->discount_percentage > 0 ? number_format($line->discount_percentage, 2).'%' : '-' }}
                        </td>
                        <td class="text-right">Rp {{ number_format($line->final_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    @endif

    {{-- ===== SPAREPART TABLE ===== --}}
    @php
        $spareparts = $wo->items->whereNotNull('total_price');
    @endphp
    @if ($spareparts->isNotEmpty())
    <div class="section-label">Sparepart yang Digunakan</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:12%">Item Code</th>
                <th style="width:38%">Sparepart</th>
                <th style="width:8%" class="text-center">Qty</th>
                <th style="width:18%" class="text-center">UOM</th>
                <th style="width:10%" class="text-center">Discount</th>
                <th style="width:14%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($spareparts as $woItem)
                <tr>
                    <td>{{ $woItem->item?->code ?? '-' }}</td>
                    <td>{{ $woItem->item?->name ?? '-' }}</td>
                    <td class="text-center">{{ number_format($woItem->actual_quantity ?? $woItem->demand_quantity, 0) }}</td>
                    <td class="text-center">{{ $woItem->uom?->code ?? optional($woItem->item?->smallestUom)->code ?? '-' }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">Rp {{ number_format($woItem->total_price ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===== NOTES ===== --}}
    @if ($invoice->notes)
        <div style="margin-top:10px; font-size:11px; line-height:1.5;">
            <strong>Notes :</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif


    {{-- ===== BOTTOM: PAYMENT + TOTALS ===== --}}
    <table class="bottom-section">
        <tr>
            <td style="width:55%;">
                <div class="payment-info">
                    @if (($wo->account_code ?? 'C') === 'C')
                        <strong>Pembayaran Melalui</strong>
                    @else
                        <strong>Pembayaran Melalui</strong>
                    @endif
                    <br>
                    Rekening BCA : <strong>088 869 5080</strong><br>
                    a.n <strong>PT Hartono Auto Studio</strong>
                </div>
            </td>
            <td style="width:45%;">
                <table class="totals-table">
                    <tr>
                        <td style="width:45%"><strong>Total Panel</strong></td>
                        <td class="text-right">Rp {{ number_format($panelTotal + $baseLaborTotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($extraLaborTotal > 0)
                        <tr>
                            <td><strong>Total Extra Labor</strong></td>
                            <td class="text-right">Rp {{ number_format($extraLaborTotal, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Subtotal</strong></td>
                        <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($discountAmount > 0)
                        @if ($lineDiscAmt > 0)
                            <tr>
                                <td>
                                    <strong>Discount</strong>
                                    @if ($discountPercentage > 0)
                                        <small class="text-muted">({{ number_format($discountPercentage, 2) }}%)</small>
                                    @endif
                                </td>
                                <td class="text-right" style="color:#c00;">— Rp
                                    {{ number_format($lineDiscAmt, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($voucherAmt > 0)
                            <tr>
                                <td>
                                    <strong>Voucher</strong>
                                </td>
                                <td class="text-right" style="color:#c00;">— Rp
                                    {{ number_format($voucherAmt, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endif
                    @if ($orAmount > 0)
                        <tr>
                            <td><strong>OR (Own Risk)</strong></td>
                            <td class="text-right" style="color:#c00;">— Rp {{ number_format($orAmount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($materai > 0)
                        <tr>
                            <td><strong>Materai</strong></td>
                            <td class="text-right" style="color:#060;">+ Rp {{ number_format($materai, 0, ',', '.') }}</td>
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

    {{-- ===== KETENTUAN ===== --}}
    <div class="keterangan">
        <p><strong>Ketentuan :</strong></p>
        <p>1. PT Hartono Auto Studio tidak bertanggung jawab atas kondisi kendaraan yang telah selesai dikerjakan akan
            tetapi tidak diambil dalam kurun waktu 1 minggu dari tanggal diinformasikannya customer.</p>
        <p>2. Perbaikan atas dasar kinerja Workshop yang tidak sesuai dapat dilakukan secara gratis maksimal H+3 setelah
            unit diserahkan ke customer.</p>
        <p><b>3. Perawatan kendaraan yang dikerjakan di PT Hartono Auto Studio disarankan setiap 6
                bulan sekali.</b></p>
        <p>4. Pengambilan kendaraan WAJIB menyertakan Lembar Pemeriksaan Kendaraan atau Tanda Terima yang diberikan oleh
            Service Advisor pada saat penyerahan unit sebelum pengerjaan.</p>
        <p>5. Barang yang sudah dibeli tidak dapat dikembalikan.</p>
        <p>6. Pembayaran secara transfer dianggap SAH apabila masuk ke rekening resmi yang tertera di Invoice atas nama
            Hartono Auto Studio.</p>
    </div>

    {{-- ===== SIGNATURE + COMPANY FOOTER (same row) ===== --}}
    <table class="sig-footer-table">
        <tr>
            <td style="width:55%;">
                <div class="company-footer">
                    <strong>PT Hartono Auto Studio</strong><br>
                    Jl. Daan Mogot Km. 1 No. 99, RT.6/RW.5, Duri Kepa, Kecamatan Kebon Jeruk, Kota Jakarta Barat, DKI Jakarta 11510<br>
                    Jam Buka: Senin-Jumat 08:00-17:00 Sabtu 08:00-13:00<br>
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

    <script>
        function doPrint() {
            fetch('/audit/log-print', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    model_type: 'Invoice',
                    model_id: {{ $invoice->id }},
                    document_number: '{{ $invoice->invoice_number }}'
                })
            }).finally(function() {
                window.print();
            });
        }
    </script>

</body>

</html>
