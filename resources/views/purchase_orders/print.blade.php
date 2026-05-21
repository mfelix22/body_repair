<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            padding: 15px;
        }

        .w100 {
            width: 100%;
        }

        .red {
            color: #000;
        }

        .bold {
            font-weight: bold;
        }

        .italic {
            font-style: italic;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .underline {
            text-decoration: underline;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .header-table td {
            padding: 2px 4px;
            vertical-align: top;
            border: none;
        }

        .company-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .company-details {
            font-size: 10px;
            line-height: 1.4;
        }

        .header-border-top {
            border-top: 2px solid #000;
            margin: 8px 0 0 0;
        }

        .header-border-bottom {
            border-bottom: 2px solid #000;
            margin: 0 0 8px 0;
            padding-bottom: 8px;
        }

        /* Title */
        .title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding: 6px 0 2px 0;
        }

        .po-number {
            text-align: center;
            font-size: 11px;
            margin-bottom: 8px;
        }

        /* Supplier section */
        .supplier-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .supplier-table td {
            padding: 4px 6px;
            border: none;
            vertical-align: top;
            font-size: 11px;
        }

        .supplier-table .label-col {
            width: 12%;
            font-weight: bold;
        }

        .supplier-table .val-col {
            width: 28%;
        }

        .supplier-table .meta-label {
            width: 12%;
            font-weight: bold;
        }

        .supplier-table .meta-val {
            width: 28%;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .items-table th {
            border: 1px solid #999;
            padding: 5px 6px;
            text-align: center;
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }

        .items-table td {
            border: 1px solid #999;
            padding: 5px 6px;
            font-size: 11px;
            vertical-align: middle;
        }

        .items-table .num-col {
            width: 4%;
            text-align: center;
        }

        .items-table .desc-col {
            width: 38%;
        }

        .items-table .qty-col {
            width: 8%;
            text-align: center;
        }

        .items-table .unit-col {
            width: 7%;
            text-align: center;
        }

        .items-table .price-col {
            width: 15%;
            text-align: right;
        }

        .items-table .total-col {
            width: 17%;
            text-align: right;
        }

        .items-table .rem-col {
            width: 11%;
            text-align: center;
        }

        /* Totals section */
        .totals-outer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .totals-outer td {
            vertical-align: top;
            padding: 4px 6px;
            border: none;
        }

        .terbilang-cell {
            width: 50%;
        }

        .totals-cell {
            width: 50%;
        }

        .totals-rows {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-rows td {
            padding: 3px 6px;
            font-size: 11px;
            border: none;
        }

        .totals-rows .t-label {
            text-align: left;
            font-weight: bold;
            width: 40%;
        }

        .totals-rows .t-colon {
            width: 5%;
            text-align: center;
        }

        .totals-rows .t-value {
            text-align: right;
            font-weight: bold;
            color: #000;
            width: 55%;
        }

        .totals-rows .t-misc {
            font-size: 10px;
            color: #555;
        }

        .grand-total-row {
            border-top: 1px solid #000;
        }

        /* Dashed separators */
        .dashed {
            border-top: 1px dashed #999;
            margin: 6px 0;
        }

        /* Payment section */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table td {
            padding: 3px 6px;
            vertical-align: top;
            font-size: 11px;
            border: none;
        }

        .payment-left {
            width: 50%;
        }

        .payment-right {
            width: 50%;
            text-align: right;
        }

        /* Signature section */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        .sig-table td {
            padding: 4px 6px;
            vertical-align: bottom;
            font-size: 11px;
            border: none;
        }

        .sig-left {
            width: 35%;
            text-align: left;
            border-top: 1px solid #000;
            padding-top: 4px;
        }

        .sig-mid {
            width: 30%;
        }

        .sig-right {
            width: 35%;
            text-align: right;
            border-top: 1px solid #000;
            padding-top: 4px;
        }

        .signature-image {
            height: 45px;
            width: auto;
            object-fit: contain;
            display: inline-block;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .footer-table td {
            padding: 2px 6px;
            font-size: 11px;
            border: none;
        }
    </style>
</head>

<body>
    @php
        use App\Helpers\NumberToIndonesian;
    @endphp

    <!-- HEADER: Logo left, Company info right -->
    <div class="header-border-bottom">
        <table class="header-table">
            <tr>
                <td style="width:20%; vertical-align:middle;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('HAS.png'))) }}"
                        alt="Logo" style="max-width:90px; height:auto;">
                </td>
                <td style="width:80%; vertical-align:middle; text-align:right; padding-right:4px;">
                    <div class="company-name">PT Hartono Auto Studio</div>
                    <div class="company-details">
                        Jl. Demak No. 166 - 168, Gundih, Kec. Bubutan<br>
                        Surabaya, Jawa Timur, 60172<br>
                        <span class="red">hrautostudio@hartonomotor.com</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- TITLE -->
    <div class="title">PURCHASE ORDER</div>
    <div class="po-number">No : {{ $purchaseOrder->po_number }}</div>

    <!-- SUPPLIER SECTION -->
    <table class="supplier-table">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div style="font-weight:bold; margin-bottom:2px;">Kepada Yth.</div>
                <div style="color:#000; font-weight:bold; margin-bottom:2px;">{{ $purchaseOrder->supplier_name }}
                </div>
                <div style="color:#000; margin-bottom:2px;">{{ $purchaseOrder->supplier_address ?? '' }}</div>
                <div style="color:#000;">{{ $purchaseOrder->supplier_phone ?? '' }}</div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div style="margin-bottom:3px;"><strong>
                        Permintaan No
                    </strong> <span class="red bold">:
                        {{ $purchaseOrder->purchaseRequest->pr_number ?? '-' }}</span></div>
                <div><strong>Date of Order</strong> <span class="red bold">:
                        {{ $purchaseOrder->order_date->format('d F Y') }}</span></div>
            </td>
        </tr>
    </table>

    <p style="font-size:11px; margin:4px 0 6px 0;">Bersama ini kami melakukan pemesanan barang sebagai berikut:</p>

    <!-- ITEMS TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="num-col">&nbsp;</th>
                <th class="desc-col">Description</th>
                <th class="qty-col">Qty</th>
                <th class="unit-col">Unit</th>
                <th class="price-col">Price/Unit</th>
                <th class="total-col">Total Price</th>
                <th class="rem-col">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->details as $index => $detail)
                <tr>
                    <td class="num-col red bold">{{ $index + 1 }}.</td>
                    <td class="desc-col red">
                        @if ($purchaseOrder->po_type === 'service_order')
                            {{ $detail->service_description }}
                        @else
                            {{ $detail->item->name ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="qty-col red">{{ number_format($detail->quantity, 0) }}</td>
                    <td class="unit-col red">
                        @if ($purchaseOrder->po_type === 'service_order')
                            -
                        @else
                            {{ $detail->uom->code ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="price-col red">Rp &nbsp;&nbsp;{{ number_format($detail->unit_price, 2, ',', '.') }}</td>
                    <td class="total-col red">Rp &nbsp;{{ number_format($detail->total_price, 2, ',', '.') }}</td>
                    <td class="rem-col">{{ $detail->remarks ?? '' }}</td>
                </tr>
            @endforeach
            @if ($purchaseOrder->details->count() < 4)
                @for ($i = $purchaseOrder->details->count(); $i < 4; $i++)
                    <tr>
                        <td class="num-col">&nbsp;</td>
                        <td class="desc-col">&nbsp;</td>
                        <td class="qty-col">&nbsp;</td>
                        <td class="unit-col">&nbsp;</td>
                        <td class="price-col">&nbsp;</td>
                        <td class="total-col">&nbsp;</td>
                        <td class="rem-col">&nbsp;</td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    @php
        $subTotal = $purchaseOrder->total_amount ?? 0;
        $miscCost = $purchaseOrder->miscCosts->sum('amount');
        $ppn =
            $purchaseOrder->include_ppn && $purchaseOrder->po_type === 'purchase_order'
                ? ($subTotal + $miscCost) * 0.11
                : 0;
        $pph = 0;
        if ($purchaseOrder->po_type === 'service_order') {
            if ($purchaseOrder->pph_type === 'pph_21') {
                $pph = $subTotal * 0.025;
            } elseif ($purchaseOrder->pph_type === 'pph_23') {
                $pph = $subTotal * 0.02;
            }
        }
        $grandTotal = $subTotal + $miscCost + $ppn - $pph;
        $terbilang = NumberToIndonesian::convert((int) $grandTotal);
    @endphp

    <div class="dashed"></div>

    <!-- TERBILANG + TOTALS -->
    <table class="totals-outer">
        <tr>
            <td class="terbilang-cell" style="vertical-align:top; padding-top:4px;">
                <span class="red bold italic">Terbilang:</span><br>
                <span class="red italic">{{ ucfirst($terbilang) }} Rupiah</span>
            </td>
            <td class="totals-cell">
                <table class="totals-rows">
                    <tr>
                        <td class="t-label">Total</td>
                        <td class="t-colon"></td>
                        <td class="t-value">Rp. {{ number_format($subTotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($purchaseOrder->po_type === 'purchase_order' && $purchaseOrder->include_ppn)
                        <tr>
                            <td class="t-label">PPN (11%)</td>
                            <td class="t-colon"></td>
                            <td class="t-value">: Rp. {{ number_format($ppn, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($miscCost > 0)
                        <tr>
                            <td class="t-label">Lain - lain</td>
                            <td class="t-colon"></td>
                            <td class="t-value">: Rp. {{ number_format($miscCost, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    @if ($purchaseOrder->po_type === 'service_order' && $pph > 0)
                        <tr>
                            <td class="t-label">
                                {{ $purchaseOrder->pph_type === 'pph_21' ? 'PPh 21 (2.5%)' : 'PPh 23 (2%)' }}</td>
                            <td class="t-colon"></td>
                            <td class="t-value">: Rp. {{ number_format($pph, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td class="t-label">Grand Total</td>
                        <td class="t-colon"></td>
                        <td class="t-value">: Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="dashed"></div>

    <!-- PAYMENT SECTION -->
    <table class="payment-table">
        <tr>
            <td class="payment-left">
                <strong>Pembayaran :</strong>
                @php
                    $methodLabels = ['credit' => 'Credit', 'cbd' => 'CBD', 'dp' => 'DP'];
                    $pemLabels = ['tunai' => 'Tunai', 'non_tunai' => 'Non-Tunai'];
                    $m = $purchaseOrder->payment_method;
                    $p = $purchaseOrder->pembayaran;
                @endphp
                {{ $methodLabels[$m] ?? strtoupper($m ?? '-') }}@if ($p && $m !== 'credit')
                    / {{ $pemLabels[$p] ?? ucfirst($p) }}
                @endif
            </td>
            <td class="payment-right">
                @if ($purchaseOrder->po_type === 'service_order')
                    <strong>Lokasi Pengerjaan :</strong> {{ $purchaseOrder->lokasi_pengerjaan ?? '-' }}
                @else
                    <strong>Lokasi Pengiriman :</strong> {{ $purchaseOrder->lokasi_pengiriman ?? '-' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="payment-left">
                <strong class="red">Bank :</strong>
                <span class="red">{{ $purchaseOrder->bank_account ?? '' }}</span>
            </td>
            <td class="payment-right">
                @if ($purchaseOrder->po_type === 'service_order')
                    <strong>Waktu Pengerjaan :</strong>
                @else
                    <strong>Waktu Pengiriman :</strong>
                @endif
                {{ $purchaseOrder->waktu_pengerjaan ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="payment-left">
                <strong>Jatuh Tempo :</strong>
                @if ($purchaseOrder->jatuh_tempo)
                    {{ $purchaseOrder->jatuh_tempo }}
                @endif
            </td>
            <td class="payment-right">&nbsp;</td>
        </tr>
        @if ($purchaseOrder->payment_terms)
            <tr>
                <td colspan="2" style="padding:6px 8px;">
                    <strong>Syarat Pembayaran :</strong><br>
                    <ul style="margin:4px 0 0 0; padding-left:18px; line-height:1.6;">
                        @foreach (explode("\n", trim($purchaseOrder->payment_terms)) as $term)
                            @if (trim($term))
                                <li>{{ trim($term) }}</li>
                            @endif
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endif
        <tr>
            <td colspan="2" style="padding:6px;">&nbsp;</td>
        </tr>
    </table>

    <!-- SIGNATURES -->
    <table class="sig-table">
        <tr>
            <td class="sig-left">
                <strong>Prepared by</strong>
            </td>
            <td class="sig-mid">&nbsp;</td>
            <td class="sig-right">
                <strong>Approved by</strong>
            </td>
        </tr>
        <tr>
            <td style="height:55px; width:35%; border:none;">
                @if ($purchaseOrder->creator && $purchaseOrder->creator->signature_path)
                    @php $sigPath = Storage::disk('public')->path($purchaseOrder->creator->signature_path); @endphp
                    @if (file_exists($sigPath))
                        <img src="data:{{ mime_content_type($sigPath) }};base64,{{ base64_encode(file_get_contents($sigPath)) }}"
                            class="signature-image">
                    @endif
                @endif
            </td>
            <td style="width:30%; border:none;">&nbsp;</td>
            <td style="height:55px; width:35%; border:none; text-align:right;">
                @if ($purchaseOrder->approver && $purchaseOrder->approver->signature_path)
                    @php $sigPath = Storage::disk('public')->path($purchaseOrder->approver->signature_path); @endphp
                    @if (file_exists($sigPath))
                        <img src="data:{{ mime_content_type($sigPath) }};base64,{{ base64_encode(file_get_contents($sigPath)) }}"
                            class="signature-image">
                    @endif
                @endif
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <table class="footer-table">
        <tr>
            <td style="width:35%; text-align:left;">
                <span class="underline bold">{{ $purchaseOrder->creator->name ?? 'Purchasing' }}</span>
            </td>
            <td style="width:30%;">&nbsp;</td>
            <td style="width:35%; text-align:right;">
                <span class="underline bold">{{ $purchaseOrder->approver->name ?? 'Director' }}</span>
            </td>
        </tr>
    </table>

</body>

</html>
