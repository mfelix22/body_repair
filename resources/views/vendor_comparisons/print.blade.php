<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Perbandingan Vendor - {{ $vendorComparison->comparison_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            padding: 15mm 15mm 10mm 15mm;
        }

        /* ---- Header ---- */
        .page-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .page-header td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 120px;
            /* border: 1px solid #000; */
            padding: 6px 8px;
            font-size: 11px;
            font-weight: bold;
        }

        .title-cell {
            text-align: center;
            padding: 4px 10px;
        }

        .title-cell .doc-title {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .title-cell .doc-subtitle {
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .doc-info-table {
            border-collapse: collapse;
            width: 180px;
            font-size: 9px;
        }

        .doc-info-table td {
            border: 1px solid #000;
            padding: 2px 6px;
        }

        .doc-info-table .label-col {
            font-weight: bold;
            white-space: nowrap;
        }

        /* ---- Body ---- */
        .intro-text {
            font-size: 10px;
            margin-bottom: 6px;
            line-height: 1.5;
        }

        .intro-text em {
            font-style: italic;
            text-decoration: underline;
        }

        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .field-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .field-label {
            font-size: 10px;
            white-space: nowrap;
            width: 150px;
        }

        .field-colon {
            width: 12px;
        }

        .field-line {
            border-bottom: 1px dotted #000;
            min-width: 200px;
        }

        /* ---- Vendor Section ---- */
        .vendor-section {
            border: 1px solid #000;
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .vendor-section-header {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
            padding: 3px 8px;
            border-bottom: 1px solid #000;
        }

        .vendor-section-header.selected-header {
            background: #c8e6c9;
        }

        .vendor-body {
            padding: 5px 8px;
        }

        .vendor-field-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .vf-label {
            display: table-cell;
            width: 130px;
            font-size: 9.5px;
            vertical-align: top;
            padding-top: 1px;
        }

        .vf-colon {
            display: table-cell;
            width: 10px;
        }

        .vf-value {
            display: table-cell;
            border-bottom: 1px dotted #555;
            font-size: 9.5px;
            vertical-align: bottom;
            padding-bottom: 1px;
        }

        .vf-inline {
            display: table;
            width: 100%;
        }

        .vf-inline-left {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
        }

        .vf-inline-right {
            display: table-cell;
            width: 50%;
        }

        .selected-badge {
            display: inline-block;
            background: #2e7d32;
            color: #fff;
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 8px;
        }

        /* ---- Note Section ---- */
        .note-section {
            margin-bottom: 8px;
        }

        /* ---- Signature ---- */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .sig-cell {
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: top;
        }

        .sig-label {
            font-size: 10px;
            margin-bottom: 50px;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 4px;
            padding-top: 2px;
            font-size: 9px;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            margin: 1px 0 3px 0;
            height: 14px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    {{-- ============================================================ --}}
    {{-- PAGE HEADER                                                   --}}
    {{-- ============================================================ --}}
    <table class="page-header">
        <tr>
            <td class="logo-cell"><img
                    src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('HAS.png'))) }}"
                    alt="Logo" style="max-width:90px; height:auto;"></td>
            <td class="title-cell">
                <div class="doc-title">FORMULIR</div>
                <div class="doc-subtitle">PERBANDINGAN VENDOR</div>
            </td>
            <td style="width:180px; vertical-align:top;">
                <table class="doc-info-table">
                    <tr>
                        <td class="label-col">Hal.</td>
                        <td>1 dari 1</td>
                    </tr>
                    <tr>
                        <td class="label-col">No. Doc</td>
                        <td>{{ $vendorComparison->comparison_number }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Rev.</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td class="label-col">Tgl Terbit</td>
                        <td>{{ $vendorComparison->tanggal->format('d F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ============================================================ --}}
    {{-- INTRO & GENERAL INFO                                          --}}
    {{-- ============================================================ --}}
    <p class="intro-text">
        Departemen Purchasing dengan ini mengajukan calon <em>supplier</em> untuk permintaan dengan detail:
    </p>

    <table class="field-table">
        <tr>
            <td class="field-label">Nomor Permintaan</td>
            <td class="field-colon">:</td>
            <td class="field-line">
                {{ $vendorComparison->nomor_permintaan ?? ($vendorComparison->purchaseRequest->pr_number ?? '') }}</td>
            <td style="width:20px;">&nbsp;</td>
            <td class="field-label">Tanggal</td>
            <td class="field-colon">:</td>
            <td class="field-line">{{ $vendorComparison->tanggal->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="field-label">Detail Barang / Jasa</td>
            <td class="field-colon">:</td>
            <td colspan="5" class="field-line">{{ $vendorComparison->detail_barang_jasa }}</td>
        </tr>
    </table>

    <br>

    {{-- ============================================================ --}}
    {{-- VENDOR SECTIONS (repeat per vendor)                           --}}
    {{-- ============================================================ --}}
    @foreach ($vendorComparison->vendors as $vendor)
        @php $isSelected = $vendorComparison->selected_vendor_id === $vendor->id; @endphp
        <div class="vendor-section">
            <div class="vendor-section-header {{ $isSelected ? 'selected-header' : '' }}">
                Vendor {{ $vendor->vendor_order }}
                @if ($isSelected)
                    <span class="selected-badge">VENDOR TERPILIH</span>
                @endif
            </div>
            <div class="vendor-body">

                <div class="vendor-field-row">
                    <div class="vf-label">Nama Calon Vendor</div>
                    <div class="vf-colon">:</div>
                    <div class="vf-value">{{ $vendor->nama_calon_vendor }}</div>
                </div>

                <div class="vendor-field-row">
                    <div class="vf-label">Alamat</div>
                    <div class="vf-colon">:</div>
                    <div class="vf-value">{{ $vendor->alamat ?: '...........................................' }}</div>
                </div>

                <div class="vf-inline">
                    <div class="vf-inline-left">
                        <div class="vendor-field-row">
                            <div class="vf-label">Telepon / Fax</div>
                            <div class="vf-colon">:</div>
                            <div class="vf-value">{{ $vendor->telepon_fax ?: '.........................' }}</div>
                        </div>
                    </div>
                    <div class="vf-inline-right">
                        <div class="vendor-field-row">
                            <div class="vf-label">Email</div>
                            <div class="vf-colon">:</div>
                            <div class="vf-value">{{ $vendor->email ?: '.........................' }}</div>
                        </div>
                    </div>
                </div>

                <div class="vendor-field-row">
                    <div class="vf-label">PIC / Contact Person</div>
                    <div class="vf-colon">:</div>
                    <div class="vf-value">
                        {{ $vendor->pic_contact_person ?: '...........................................' }}</div>
                </div>

                <div class="vf-inline">
                    <div class="vf-inline-left">
                        <div class="vendor-field-row">
                            <div class="vf-label">Metode Pembayaran</div>
                            <div class="vf-colon">:</div>
                            <div class="vf-value">{{ $vendor->metode_pembayaran ?: 'Tunai / Kredit' }}
                            </div>
                        </div>
                    </div>
                    <div class="vf-inline-right">
                        <div class="vendor-field-row">
                            <div class="vf-label">Rekening Bank</div>
                            <div class="vf-colon">:</div>
                            <div class="vf-value">{{ $vendor->rekening_bank ?: '.........................' }}</div>
                        </div>
                    </div>
                </div>

                <div class="vf-inline">
                    <div class="vf-inline-left">
                        <div class="vendor-field-row">
                            <div class="vf-label">Term of Payment</div>
                            <div class="vf-colon">:</div>
                            <div class="vf-value">{{ $vendor->term_of_payment ?: '............' }} hari</div>
                        </div>
                    </div>
                    <div class="vf-inline-right">
                        <div class="vendor-field-row">
                            <div class="vf-label">Harga Barang / Jasa</div>
                            <div class="vf-colon">:</div>
                            <div class="vf-value">
                                {{ $vendor->harga_barang_jasa ? 'Rp ' . number_format($vendor->harga_barang_jasa, 0, ',', '.') : '.....................................' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vendor-field-row">
                    <div class="vf-label">Include PPn 11%</div>
                    <div class="vf-colon">:</div>
                    <div class="vf-value">{{ $vendor->include_ppn ? 'Ya' : 'Tidak' }}</div>
                </div>

                <div class="vendor-field-row">
                    <div class="vf-label" style="white-space: normal; padding-top: 2px;">Ketentuan lain-lain dari calon
                        <em>supplier</em>:
                    </div>
                </div>
                <div class="dotted-line">{{ $vendor->ketentuan_lain }}</div>
                <div class="dotted-line">&nbsp;</div>
                <div class="dotted-line">&nbsp;</div>

            </div>
        </div>
    @endforeach

    {{-- ============================================================ --}}
    {{-- NOTE                                                          --}}
    {{-- NOTE                                                          --}}
    {{-- ============================================================ --}}
    <div class="note-section">
        <div style="font-size:10px; margin-bottom:3px;"><strong>Note:</strong></div>
        <div class="dotted-line">{{ $vendorComparison->notes }}</div>
        <div class="dotted-line">&nbsp;</div>
        <div class="dotted-line">&nbsp;</div>
    </div>

    {{-- ============================================================ --}}
    {{-- SIGNATURES                                                    --}}
    {{-- ============================================================ --}}
    <table class="signature-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-label">Prepared By:</div>
                @if ($vendorComparison->creator && $vendorComparison->creator->signature_path)
                    <img src="{{ storage_path('app/public/' . $vendorComparison->creator->signature_path) }}"
                        style="height:40px; max-width:120px; object-fit:contain; margin-bottom:4px;">
                @else
                    <div style="height:44px;"></div>
                @endif
                <div class="sig-line">
                    ........................................<br>
                    <strong>Purchasing</strong>
                    @if ($vendorComparison->creator)
                        <br><small>{{ $vendorComparison->creator->name }}</small>
                    @endif
                </div>
            </td>
            <td class="sig-cell">
                <div class="sig-label">Approved By:</div>
                @if ($vendorComparison->approver && $vendorComparison->approver->signature_path)
                    <img src="{{ storage_path('app/public/' . $vendorComparison->approver->signature_path) }}"
                        style="height:40px; max-width:120px; object-fit:contain; margin-bottom:4px;">
                @else
                    <div style="height:44px;"></div>
                @endif
                <div class="sig-line">
                    ........................................<br>
                    <strong>General Manager / Direksi</strong>
                    @if ($vendorComparison->approver)
                        <br><small>{{ $vendorComparison->approver->name }}</small>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
