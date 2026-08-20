<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order {{ $workOrder->wo_number }}</title>
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

        .header-logo .hs-text {
            display: inline-block;
            vertical-align: middle;
            margin-left: 6px;
        }

        .header-logo .hs-text strong {
            font-size: 18px;
            letter-spacing: 1px;
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
            width: 35%;
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
            width: 100px;
        }

        .info-section table td.val {
            color: #000;
        }

        .info-divider {
            width: 20px;
        }

        .car-size-cell {
            width: 100px;
            text-align: center;
            vertical-align: middle;
        }

        .car-size-box {
            border: 2px solid #000;
            display: inline-block;
            width: 90px;
            height: 90px;
            text-align: center;
            vertical-align: middle;
            line-height: 90px;
            font-size: 40px;
            font-weight: bold;
        }

        .car-size-label {
            font-size: 10px;
            text-align: center;
            margin-top: 3px;
        }

        /* ===== TABLES ===== */
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

        .section-table .empty-row td {
            height: 20px;
            border: 1px solid #ccc;
            color: #000;
        }

        /* ===== FOOTER ===== */
        .received-row {
            width: 100%;
            margin: 8px 0 6px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .ketentuan {
            font-size: 9.5px;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .ketentuan p {
            margin-bottom: 2px;
        }

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

        .sig-underline {
            width: 80%;
            margin: 40px auto 4px;
            border-bottom: 1px solid #000;
        }

        .sig-name {
            font-size: 10px;
            text-align: center;
        }

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
        <a href="{{ route('work_orders.show', $workOrder) }}" style="margin-left:10px;font-size:12px;">
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

    <div class="doc-title">WORK ORDER</div>
    <div class="doc-number">No : {{ $workOrder->wo_number }}</div>

    <hr class="separator">

    {{-- ===== INFO SECTION ===== --}}
    @php
        $accountLabels = ['C' => 'CASH', 'INT_WS' => 'INTERNAL WS', 'INT_W3' => 'INTERNAL W3', 'ASURANSI' => 'ASURANSI'];
        $accountDisplay = $accountLabels[$workOrder->account_code ?? 'C'] ?? $workOrder->account_code;

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

    <table class="info-table">
        <tr>
            <td class="info-section">
                <table>
                    <tr>
                        <td>Licence Plate</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->vehicle_plate ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Merk</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->vehicle_merk ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Type / Year</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->vehicle_type_year ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Mileage KM</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->vehicle_km ? number_format($workOrder->vehicle_km) : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td>Chasis No</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->chasis_no ?? '-' }}</td>
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
                    @if ($workOrder->account_code === 'ASURANSI')
                        <tr>
                            <td>Insurance Name</td>
                            <td>:</td>
                            <td class="val">{{ $workOrder->insurance?->name ?? '-' }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Customer Name</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->customer->name }}</td>
                    </tr>
                    <tr>
                        <td>Ditujukan Kepada</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->billingCustomer->name ?? $workOrder->customer->name }}</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->customer->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td>:</td>
                        <td class="val">{{ $workOrder->customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($workOrder->work_date) }}</td>
                    </tr>
                    <tr>
                        <td>Deadline</td>
                        <td>:</td>
                        <td class="val">{{ $idDate($workOrder->deadline) }}</td>
                    </tr>
                    @if ($workOrder->started_at)
                        <tr>
                            <td>Start Work</td>
                            <td>:</td>
                            <td class="val">{{ $workOrder->started_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @endif
                    @if ($workOrder->completed_at)
                        <tr>
                            <td>Complete Work</td>
                            <td>:</td>
                            <td class="val">{{ $workOrder->completed_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td class="info-divider"></td>
            {{-- <td class="car-size-cell">
                @php
                    $tierLabels = [
                        '0_300'   => '0–300jt',
                        '300_500' => '300–500jt',
                        '500_800' => '500–800jt',
                        '800_2000'=> '800jt–2M',
                    ];
                    $tierDisplay = $tierLabels[$workOrder->vehicle_price_tier] ?? '-';
                @endphp
                <div class="car-size-box" style="font-size:11px;line-height:normal;padding:8px;">{{ $tierDisplay }}</div>
                <div class="car-size-label">Kisaran Harga</div>
            </td> --}}
        </tr>
    </table>

    {{-- ===== PANELS TABLE ===== --}}
    @php
        $basePanels  = $workOrder->panelLabors->where('is_extra', false);
        $baseLabors  = $workOrder->generalLabors->where('is_extra', false);
        $extraLabors = $workOrder->generalLabors->where('is_extra', true);
    @endphp
    {{-- <table class="section-table">
        <thead>
            <tr>
                <td style="width:15%">Panel Code</td>
                <td style="width:45%">Panel</td>
                <td style="width:10%;text-align:center;">Qty</td>
                <td style="width:15%;text-align:right;">Rate</td>
                <td style="width:15%;text-align:right;">Total</td>
            </tr>
        </thead>
        <tbody>
            @forelse ($basePanels as $panel)
                <tr>
                    <td>{{ $panel->panel?->panel_code ?? '-' }}</td>
                    <td>{{ $panel->description }}</td>
                    <td style="text-align:center;">{{ number_format($panel->qty ?? 1, 0) }}</td>
                    <td style="text-align:right;">{{ $panel->rate ? number_format($panel->rate, 0, ',', '.') : '-' }}</td>
                    <td style="text-align:right;">{{ $panel->total_price ? number_format($panel->total_price, 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="5">&nbsp;</td></tr>
            @endforelse
            @for ($i = $basePanels->count(); $i < 4; $i++)
                <tr class="empty-row"><td colspan="5">&nbsp;</td></tr>
            @endfor
        </tbody>
    </table> --}}

    {{-- ===== PANEL TABLE ===== --}}
    @if ($baseLabors->isNotEmpty())
    <table class="section-table">
        <thead>
            <tr>
                <td style="width:15%">Panel Code</td>
                <td style="width:45%">Panel</td>
                <td style="width:10%;text-align:center;">Qty</td>
                <td style="width:15%;text-align:right;">Rate</td>
                <td style="width:15%;text-align:right;">Total</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($baseLabors as $labor)
                <tr>
                    <td>{{ $labor->labor?->labor_code ?? '-' }}</td>
                    <td>{{ $labor->description }}</td>
                    <td style="text-align:center;">{{ number_format($labor->qty ?? 1, 0) }}</td>
                    <td style="text-align:right;">{{ $labor->rate ? number_format($labor->rate, 0, ',', '.') : '-' }}</td>
                    <td style="text-align:right;">{{ $labor->total_price ? number_format($labor->total_price, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===== EXTRA PANEL TABLE (only if any) ===== --}}
    @if ($extraLabors->isNotEmpty())
    <table class="section-table">
        <thead>
            <tr>
                <td style="width:15%">Panel Code</td>
                <td style="width:45%">Extra Panel</td>
                <td style="width:10%;text-align:center;">Qty</td>
                <td style="width:15%;text-align:right;">Rate</td>
                <td style="width:15%;text-align:right;">Total</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($extraLabors as $labor)
                <tr>
                    <td>{{ $labor->labor?->labor_code ?? '-' }}</td>
                    <td>{{ $labor->description }}</td>
                    <td style="text-align:center;">{{ number_format($labor->qty ?? 1, 0) }}</td>
                    <td style="text-align:right;">{{ $labor->rate ? number_format($labor->rate, 0, ',', '.') : '-' }}</td>
                    <td style="text-align:right;">{{ $labor->total_price ? number_format($labor->total_price, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===== SPAREPART / MATERIALS TABLE ===== --}}
    @if ($workOrder->items->isNotEmpty())
    <table class="section-table">
        <thead>
            <tr>
                <td style="width:15%">Item Code</td>
                <td style="width:45%">Sparepart / Material</td>
                <td style="width:10%;text-align:center;">Qty</td>
                <td style="width:15%;text-align:center;">UOM</td>
                <td style="width:15%;text-align:right;">Unit Price</td>
                <td style="width:15%;text-align:right;">Total</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($workOrder->items as $woItem)
                <tr>
                    <td>{{ $woItem->item?->code ?? '-' }}</td>
                    <td>{{ $woItem->item?->name ?? '-' }}</td>
                    <td style="text-align:center;">{{ number_format($woItem->actual_quantity ?? $woItem->demand_quantity, 2) }}</td>
                    <td style="text-align:center;">{{ optional($woItem->item?->smallestUom)->code ?? '-' }}</td>
                    <td style="text-align:right;">{{ $woItem->unit_price ? number_format($woItem->unit_price, 0, ',', '.') : '-' }}</td>
                    <td style="text-align:right;">{{ $woItem->total_price ? number_format($woItem->total_price, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ===== RECEIVED BY / SA ROW ===== --}}
    <table style="width:100%;margin:6px 0;">
        <tr>
            <td style="width:50%;font-size:11px;">
                Received by : {{ $workOrder->creator?->name ?? '-' }}
            </td>
            <td style="width:50%;font-size:11px;text-align:right;">
                SA/Sales : {{ $workOrder->sa_sales ?? '-' }}
            </td>
        </tr>
    </table>

    {{-- ===== TERMS ===== --}}
    <div class="ketentuan">
        <p><em>Ketentuan:</em></p>
        <p>1. Work Order (WO) yang sudah ditandatangani merupakan surat kuasa dari pelanggan ke PT Hartono Auto Studio
            untuk dilakukan pekerjaan sesuai yang tertera pada WO.</p>
        <p>2. PT Hartono Auto Studio tidak bertanggung jawab atas segala kerugian akibat musibah, bencana alam, ataupun
            kejadian lain yang tidak bisa diprediksi atau berada diluar tanggung jawab Workshop.</p>
        <p>3. Tidak diperkenankan menyimpan barang - barang berharga di dalam kendaraan yang akan dilakukan pengerjaan
            di PT Hartono Auto Studio. PT Hartono Auto Studio tidak bertanggung jawab apabila terjadi kehilangan atas
            barang tersebut.</p>
    </div>

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
                    model_type: 'WorkOrder',
                    model_id: {{ $workOrder->id }},
                    document_number: '{{ $workOrder->wo_number }}'
                })
            }).finally(function() {
                window.print();
            });
        }
    </script>

</body>

</html>
