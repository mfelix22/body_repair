<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>
        {{ $purchaseRequest->type === 'Jasa' ? 'Permintaan Pembelian Jasa' : 'Permintaan Pembelian Barang' }}
        - {{ $purchaseRequest->pr_number }}
    </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            font-size: 12px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.info-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }

        table.info-table th {
            background-color: #f5f5f5;
            padding: 5px;
            border: 1px solid #ddd;
            font-weight: bold;
            text-align: left;
        }

        table.items-table th {
            background-color: #f5f5f5;
            padding: 8px;
            border: 1px solid #000;
            font-weight: bold;
            text-align: left;
            font-size: 12px;
        }

        table.items-table td {
            padding: 8px;
            border: 1px solid #000;
            font-size: 11px;
        }

        .signature-section {
            margin-top: 40px;
        }

        .signature-block {
            display: inline-block;
            width: 23%;
            margin-right: 2%;
            text-align: center;
            vertical-align: top;
        }

        .signature-block:last-child {
            margin-right: 0;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 10px;
            min-height: 60px;
            position: relative;
            margin-bottom: 35px;
        }

        .signature-image {
            max-width: 100px;
            max-height: 50px;
            margin-bottom: 5px;
        }

        .signature-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }

        .signature-date {
            font-size: 9px;
            color: #999;
            margin-top: 2px;
        }

        .empty-row {
            min-height: 60px;
        }

        .department-row {
            margin-bottom: 15px;
        }

        .row-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            gap: 20px;
        }

        .info-group {
            flex: 1;
        }

        .info-group label {
            font-weight: bold;
            font-size: 12px;
            display: block;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 12px;
            display: block;
            padding: 5px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>
            {{ $purchaseRequest->type === 'Jasa' ? 'PERMINTAAN PEMBELIAN JASA' : 'PERMINTAAN PEMBELIAN BARANG' }}
        </h1>
        <p>{{ $purchaseRequest->pr_number }}</p>
    </div>

    <div class="section">
        <div class="row-container">
            <div class="info-group">
                <label>{{ $purchaseRequest->type === 'Jasa' ? 'PPJ Number:' : 'PPB Number:' }}</label>
                <div class="info-value">{{ $purchaseRequest->pr_number }}</div>
            </div>
            <div class="info-group">
                <label>Request Date:</label>
                <div class="info-value">{{ $purchaseRequest->request_date->format('M d, Y') }}</div>
            </div>
            <div class="info-group">
                <label>Department:</label>
                <div class="info-value">-</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Items Requested</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 35%;">Item Description</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 15%;">Unit</th>
                    <th style="width: 30%;">Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseRequest->details as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @if ($purchaseRequest->type === 'Jasa')
                                {{ $detail->service_description ?? '-' }}
                            @elseif ($detail->is_custom_item)
                                {{ $detail->custom_item_name ?? '-' }} <em>(New Item)</em>
                            @else
                                {{ $detail->item->name ?? '-' }}
                            @endif
                        </td>
                        <td style="text-align: right;">{{ number_format($detail->quantity, 2) }}</td>
                        <td>{{ $purchaseRequest->type === 'Jasa' ? '-' : $detail->uom->code ?? '-' }}</td>
                        <td>{{ $detail->notes ?? '-' }}</td>
                    </tr>
                @endforeach
                @if ($purchaseRequest->details->count() < 5)
                    @for ($i = $purchaseRequest->details->count(); $i < 5; $i++)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>
    </div>

    @if ($purchaseRequest->notes)
        <div class="section">
            <div class="section-title">Notes</div>
            <p style="font-size: 12px; line-height: 1.6;">{{ $purchaseRequest->notes }}</p>
        </div>
    @endif

    <div class="signature-section">
        <div class="section-title">Approvals & Signatures</div>

        <!-- Signature Blocks -->
        <table style="width: 100%; border: none;">
            <tr>
                <!-- Created By -->
                <td style="border: none; width: 23%; text-align: center; vertical-align: top;">
                    <div class="signature-label">Created By</div>
                    <div class="signature-line">
                        @if ($purchaseRequest->requestor->signature_path)
                            @php $sigPath = Storage::disk('public')->path($purchaseRequest->requestor->signature_path); @endphp
                            @if (file_exists($sigPath))
                                <img src="data:{{ mime_content_type($sigPath) }};base64,{{ base64_encode(file_get_contents($sigPath)) }}"
                                    class="signature-image">
                            @else
                                <div class="empty-row"></div>
                            @endif
                        @else
                            <div class="empty-row"></div>
                        @endif
                    </div>
                    <div class="signature-name">{{ $purchaseRequest->requestor->name }}</div>
                </td>

                <!-- Approved By -->
                <td style="border: none; width: 23%; text-align: center; vertical-align: top;">
                    <div class="signature-label">Approved By</div>
                    <div class="signature-line">
                        @if ($purchaseRequest->deptHeadApprover && $purchaseRequest->deptHeadApprover->signature_path)
                            @php $sigPath = Storage::disk('public')->path($purchaseRequest->deptHeadApprover->signature_path); @endphp
                            @if (file_exists($sigPath))
                                <img src="data:{{ mime_content_type($sigPath) }};base64,{{ base64_encode(file_get_contents($sigPath)) }}"
                                    class="signature-image">
                            @else
                                <div class="empty-row"></div>
                            @endif
                        @else
                            <div class="empty-row"></div>
                        @endif
                    </div>
                    <div class="signature-name">
                        @if ($purchaseRequest->deptHeadApprover)
                            {{ $purchaseRequest->deptHeadApprover->name }}
                            <div class="signature-date">{{ $purchaseRequest->dept_head_at->format('M d, Y') }}</div>
                        @endif
                    </div>
                </td>

                <!-- Acknowledged By -->
                <td style="border: none; width: 23%; text-align: center; vertical-align: top;">
                    <div class="signature-label">Acknowledged By</div>
                    <div class="signature-line">
                        @if ($purchaseRequest->gmApprover && $purchaseRequest->gmApprover->signature_path)
                            @php $sigPath = Storage::disk('public')->path($purchaseRequest->gmApprover->signature_path); @endphp
                            @if (file_exists($sigPath))
                                <img src="data:{{ mime_content_type($sigPath) }};base64,{{ base64_encode(file_get_contents($sigPath)) }}"
                                    class="signature-image">
                            @else
                                <div class="empty-row"></div>
                            @endif
                        @else
                            <div class="empty-row"></div>
                        @endif
                    </div>
                    <div class="signature-name">
                        @if ($purchaseRequest->gmApprover)
                            {{ $purchaseRequest->gmApprover->name }}
                            <div class="signature-date">{{ $purchaseRequest->gm_at->format('M d, Y') }}</div>
                        @endif
                    </div>
                </td>

                <!-- Purchasing Received -->
                <td style="border: none; width: 23%; text-align: center; vertical-align: top;">
                    <div class="signature-label">Purchasing Received</div>
                    <div class="signature-line">
                        @if ($purchaseRequest->purchasingReceiver && $purchaseRequest->purchasingReceiver->signature_path)
                            @php $sigPath = Storage::disk('public')->path($purchaseRequest->purchasingReceiver->signature_path); @endphp
                            @if (file_exists($sigPath))
                                <img src="data:{{ mime_content_type($sigPath) }};base64,{{ base64_encode(file_get_contents($sigPath)) }}"
                                    class="signature-image">
                            @else
                                <div class="empty-row"></div>
                            @endif
                        @else
                            <div class="empty-row"></div>
                        @endif
                    </div>
                    <div class="signature-name">
                        @if ($purchaseRequest->purchasingReceiver)
                            {{ $purchaseRequest->purchasingReceiver->name }}
                            <div class="signature-date">
                                {{ $purchaseRequest->purchasing_received_at->format('M d, Y') }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #999;">
        <p>Printed on {{ now()->format('M d, Y H:i') }}</p>
    </div>
</body>

</html>
