<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi OR - {{ $receiptNumber }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            padding: 30px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header-logo img {
            height: 55px;
        }

        .header-company {
            text-align: right;
            vertical-align: top;
            font-size: 10px;
            line-height: 1.5;
        }

        .header-company strong {
            font-size: 12px;
        }

        .doc-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 16px 0 4px;
            letter-spacing: 2px;
        }

        .doc-number {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 150px;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 24px;
            text-align: center;
        }

        .amount-box {
            border: 1px solid #000;
            padding: 6px 12px;
            min-width: 300px;
            display: inline-block;
            font-style: italic;
            font-weight: bold;
        }

        .note-box {
            background-color: #ffeb3b;
            padding: 4px 8px;
            display: inline-block;
            font-weight: bold;
        }

        .footer-stamp {
            text-align: right;
            margin-top: 30px;
        }

        .stamp-box {
            border: 2px solid #000;
            display: inline-block;
            padding: 10px 20px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 60px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 180px;
            margin-left: auto;
            margin-top: 8px;
        }

        .no-print {
            margin-bottom: 12px;
        }

        @media print {
            body {
                padding: 10px 20px;
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

    <div class="no-print">
        <button onclick="window.print()" style="padding:6px 16px;font-size:12px;cursor:pointer;background:#007bff;color:#fff;border:none;border-radius:4px;">
            &#128438; Print
        </button>
        <a href="{{ route('invoices.show', $invoice) }}" style="margin-left:10px;font-size:12px;">&larr; Back</a>
    </div>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('HAS.png'))) }}" alt="HR Auto Studio">
            </td>
            <td class="header-company">
                <strong>PT Hartono Auto Studio</strong><br>
                Jl. Daan Mogot KM 1 No. 99<br>
                Jakarta Barat 11510<br>
                hrautostudio@hartonomotor.com
            </td>
        </tr>
    </table>

    <div class="doc-title">KWITANSI</div>
    <div class="doc-number">No : {{ $receiptNumber }}</div>

    @php
        $monthsId = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $idDate = function ($date) use ($monthsId) {
            return $date->day . ' ' . $monthsId[$date->month] . ' ' . $date->year;
        };
        $wo = $invoice->workOrder;
        $insuranceName = $wo->insurance?->name ?? '-';
        if ($insuranceName !== '-' && !\Illuminate\Support\Str::startsWith($insuranceName, 'PT ')) {
            $insuranceName = 'PT ' . $insuranceName;
        }
    @endphp

    <table class="info-table">
        <tr>
            <td>Telah Terima dari</td>
            <td>:</td>
            <td>{{ $invoice->workOrder->customer->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td>
                Pembayaran Own Risk (OR) repair kendaraan {{ $invoice->workOrder->vehicle_merk ?? '' }} {{ $invoice->workOrder->vehicle_type_year ?? '' }}.
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>
                Nopol {{ $wo->vehicle_plate ?? '-' }} (Asuransi {{ $insuranceName }}). WO : {{ $wo->wo_number }}.
            </td>
        </tr>
        <tr>
            <td>Sebesar</td>
            <td>:</td>
            <td>Rp. {{ number_format($invoice->or_amount, 0, ',', '.') }}.-</td>
        </tr>
        <tr>
            <td>Terbilang</td>
            <td>:</td>
            <td>
                <div class="amount-box">{{ \App\Helpers\NumberToIndonesian::convert($invoice->or_amount) }} rupiah.</div>
            </td>
        </tr>
        <tr>
            <td>Pembayaran Melalui</td>
            <td>:</td>
            <td>TRANSFER KE BCA 012-8585-080 A.N. PT. HARTONO AUTO STUDIO</td>
        </tr>
        <tr>
            <td>Note</td>
            <td>:</td>
            <td>
                <div class="note-box">Kwitansi dianggap sah apabila pembayaran sudah diterima oleh PT. Hartono Auto Studio.</div>
            </td>
        </tr>
    </table>

    <div class="footer-stamp">
        <div>Jakarta, {{ $idDate($invoice->invoice_date) }}</div>
        <div class="stamp-box">PT. HARTONO AUTO STUDIO</div>
        <div class="signature-line"></div>
        <div style="text-align:right; margin-top:4px; width:180px; margin-left:auto;">(_____________________)</div>
    </div>

</body>

</html>
