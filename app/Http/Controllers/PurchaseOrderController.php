<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderMiscCost;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\UOM;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceLine;
use App\Models\Stock;
use App\Models\AuditLog;
use App\Models\StockTransaction;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['purchaseRequest', 'creator', 'details.item'])
            ->where('po_type', 'purchase_order')
            ->withCount('details')
            ->orderBy('order_date', 'desc')
            ->get();

        $serviceOrders = PurchaseOrder::with(['purchaseRequest', 'creator', 'details'])
            ->where('po_type', 'service_order')
            ->withCount('details')
            ->orderBy('order_date', 'desc')
            ->get();

        return view('purchase_orders.index', compact('purchaseOrders', 'serviceOrders'));
    }

    public function create(Request $request)
    {
        // Check if user has purchasing role
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Only purchasing staff can create Purchase Orders.');
        }

        $prs = PurchaseRequest::whereIn('status', ['completed', 'printed'])
            ->whereHas('details', function ($q) {
                // Only include PRs that still have at least one unfulfilled item
                $q->whereRaw('ordered_quantity < quantity');
            })
            ->with(['details.item.itemUoms.uom', 'details.uom'])
            ->get();
        $items = Item::where('is_active', true)->with(['itemUoms.uom', 'smallestUom'])->get();
        $suppliers = Supplier::orderBy('name')->get();

        $selectedPrId = $request->query('pr_id');
        $selectedPr = null;

        // Load the selected PR with full data if pr_id is provided
        if ($selectedPrId) {
            $selectedPr = PurchaseRequest::with(['details.item.itemUoms.uom', 'details.uom'])
                ->findOrFail($selectedPrId);
        }

        return view('purchase_orders.create', compact('prs', 'items', 'suppliers', 'selectedPrId', 'selectedPr'));
    }

    public function store(Request $request)
    {
        // Check if user has purchasing role
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Only purchasing staff can create Purchase Orders.');
        }
        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before creating a PO. Go to your profile.');
        }

        $poType = $request->input('po_type');
        $baseRules = [
            'po_type' => 'required|in:purchase_order,service_order',
            'purchase_request_id' => 'nullable|exists:purchase_requests,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'supplier_name' => 'required|string|max:200',
            'supplier_address' => 'nullable|string',
            'supplier_phone' => 'nullable|string|max:50',
            'supplier_contact_person' => 'nullable|string|max:100',
            'lokasi_pengerjaan' => 'nullable|string|max:255',
            'lokasi_pengiriman' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'include_ppn' => 'nullable|boolean',
            'pph_type' => 'nullable|in:none,pph_21,pph_23',
            'waktu_pengerjaan' => 'nullable|string|max:100',
            'payment_method' => 'required|in:credit,cbd,dp,cod',
            'pembayaran' => 'required|in:tunai,non_tunai',
            'bank_account' => 'nullable|string|max:255',
            'jatuh_tempo' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string',
            'misc_costs' => 'nullable|array',
            'misc_costs.*.description' => 'nullable|string|max:255',
            'misc_costs.*.amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.purchase_request_detail_id' => 'nullable|exists:purchase_request_details,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ];

        // Add type-specific rules
        if ($poType === 'service_order') {
            $baseRules['items.*.service_description'] = 'required|string|min:3';
            $baseRules['items.*.item_id'] = 'nullable|exists:items,id';
            $baseRules['items.*.uom_id'] = 'nullable|exists:uoms,id';
            $baseRules['items.*.conversion_to_smallest'] = 'nullable|numeric|min:0.000001';
        } else {
            // purchase_order
            $baseRules['items.*.item_id'] = 'required|exists:items,id';
            $baseRules['items.*.uom_id'] = 'required|exists:uoms,id';
            $baseRules['items.*.service_description'] = 'nullable|string';
            $baseRules['items.*.conversion_to_smallest'] = 'required|numeric|min:0.000001';
        }

        $validated = $request->validate($baseRules);

        // --- Pre-validate business rules BEFORE any database writes ---

        // 1. Check total amount is not zero
        $preTotal = array_sum(array_map(
            fn($i) => max(0, (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0) - (float)($i['discount'] ?? 0)),
            $validated['items']
        ));
        if ($preTotal <= 0) {
            return back()->withErrors([
                'items' => 'Total PO amount cannot be zero. Please enter unit prices for all items.'
            ])->withInput();
        }

        // 2. Check remaining PR quantities per item
        foreach ($validated['items'] as $index => $itemData) {
            if (!empty($itemData['purchase_request_detail_id'])) {
                $prDetail = PurchaseRequestDetail::find($itemData['purchase_request_detail_id']);
                if ($prDetail) {
                    $remainingQty = $prDetail->quantity - $prDetail->ordered_quantity;
                    if ((float)$itemData['quantity'] > $remainingQty) {
                        $itemName = $prDetail->item->name ?? 'N/A';
                        return back()->withErrors([
                            "items.{$index}.quantity" => "Item '{$itemName}' has only {$remainingQty} remaining quantity available. You cannot order {$itemData['quantity']}."
                        ])->withInput();
                    }
                }
            }
        }

        // --- All checks passed — now write to database inside a transaction ---
        return DB::transaction(function () use ($request, $validated, $poType, $preTotal) {
            $now = now();
            $year = $now->format('y');
            $month = $now->month;
            $companyCode = config('app.company_code', 'HAS.JKT');

            $romanNumerals = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $romanMonth = $romanNumerals[$month];

            $monthStart = $now->clone()->startOfMonth();
            $monthEnd   = $now->clone()->endOfMonth();

            // Find the highest existing PO sequence for this company/month/year
            $lastPo = PurchaseOrder::where('po_number', 'LIKE', "%/{$companyCode}/{$romanMonth}/{$year}")
                ->orderByRaw('CAST(SUBSTRING_INDEX(po_number, \'/\', 1) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $poSequence = 1;
            if ($lastPo) {
                $poSequence = ((int) explode('/', $lastPo->po_number)[0]) + 1;
            }
            $poNumber = str_pad($poSequence, 3, '0', STR_PAD_LEFT) . "/{$companyCode}/{$romanMonth}/{$year}";

            // PPB/PPJ number should match the linked PR number
            $ppbNumber = null;
            if (!empty($validated['purchase_request_id'])) {
                $linkedPr = PurchaseRequest::find($validated['purchase_request_id']);
                if ($linkedPr) {
                    $ppbNumber = $linkedPr->pr_number;
                }
            }

            // Fallback only when no PR is linked
            if (empty($ppbNumber)) {
                $typePrefix = $validated['po_type'] === 'service_order' ? 'J' : 'B';
                $yearPad    = $now->format('y');
                $monthPad   = str_pad($month, 2, '0', STR_PAD_LEFT);
                $countTypeThisMonth = PurchaseOrder::where('po_type', $validated['po_type'])
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->lockForUpdate()->count();
                $typeSequence = str_pad($countTypeThisMonth + 1, 3, '0', STR_PAD_LEFT);
                $ppbNumber    = "{$typePrefix}{$yearPad}{$monthPad}{$typeSequence}";
            }

            $po = PurchaseOrder::create([
                'po_number'              => $poNumber,
                'ppb_number'             => $ppbNumber,
                'po_type'                => $validated['po_type'],
                'purchase_request_id'    => $validated['purchase_request_id'] ?? null,
                'supplier_id'            => $validated['supplier_id'] ?? null,
                'order_date'             => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'supplier_name'          => $validated['supplier_name'],
                'supplier_address'       => $validated['supplier_address'] ?? null,
                'supplier_phone'         => $validated['supplier_phone'] ?? null,
                'supplier_contact_person' => $validated['supplier_contact_person'] ?? null,
                'lokasi_pengerjaan'      => $validated['lokasi_pengerjaan'] ?? null,
                'lokasi_pengiriman'      => $validated['lokasi_pengiriman'] ?? null,
                'notes'                  => $validated['notes'] ?? null,
                'include_ppn'            => $request->boolean('include_ppn'),
                'pph_type'               => $validated['pph_type'] ?? 'none',
                'waktu_pengerjaan'       => $validated['waktu_pengerjaan'] ?? null,
                'payment_method'         => $validated['payment_method'],
                'pembayaran'             => $validated['payment_method'] === 'cod' ? 'tunai' : $validated['pembayaran'],
                'bank_account'           => $validated['bank_account'] ?? null,
                'jatuh_tempo'            => $validated['jatuh_tempo'] ?? null,
                'payment_terms'          => $validated['payment_terms'] ?? null,
                'status'                 => 'on_progress',
                'created_by'             => Auth::id(),
            ]);

            $totalAmount = 0;
            $totalDiscount = 0;
            foreach ($validated['items'] as $itemData) {
                $grossPrice   = $itemData['quantity'] * $itemData['unit_price'];
                $itemDiscount = (float) ($itemData['discount'] ?? 0);
                $totalPrice   = max(0, $grossPrice - $itemDiscount);
                $totalAmount += $totalPrice;
                $totalDiscount += $itemDiscount;
                $prDetailId   = !empty($itemData['purchase_request_detail_id']) ? $itemData['purchase_request_detail_id'] : null;

                $detailData = [
                    'purchase_order_id'          => $po->id,
                    'quantity'                   => $itemData['quantity'],
                    'unit_price'                 => $itemData['unit_price'],
                    'total_price'                => $totalPrice,
                    'discount'                   => $itemDiscount,
                    'discount_percentage'        => (float) ($itemData['discount_percentage'] ?? 0),
                    'received_quantity'          => 0,
                    'purchase_request_detail_id' => $prDetailId,
                    'remarks'                    => $itemData['remarks'] ?? null,
                ];

                if ($poType === 'service_order') {
                    $detailData['service_description'] = $itemData['service_description'] ?? null;
                    $detailData['item_id'] = null;
                    $detailData['uom_id']  = null;
                } else {
                    $detailData['item_id']                 = $itemData['item_id'] ?? null;
                    $detailData['uom_id']                  = $itemData['uom_id'] ?? null;
                    $detailData['service_description']     = null;
                    $detailData['conversion_to_smallest']  = $itemData['conversion_to_smallest'] ?? null;
                }

                PurchaseOrderDetail::create($detailData);

                if ($prDetailId) {
                    PurchaseRequestDetail::where('id', $prDetailId)
                        ->increment('ordered_quantity', $itemData['quantity']);
                }
            }

            $po->update(['total_amount' => $totalAmount, 'discount' => $totalDiscount]);

            // Save misc costs
            if (!empty($validated['misc_costs'])) {
                foreach ($validated['misc_costs'] as $misc) {
                    if (!empty($misc['description']) && isset($misc['amount'])) {
                        PurchaseOrderMiscCost::create([
                            'purchase_order_id' => $po->id,
                            'description'       => $misc['description'],
                            'amount'            => $misc['amount'],
                        ]);
                    }
                }
            }

            $po->load('details.item');
            $isAllSparepart = $po->po_type === 'purchase_order'
                && $po->details->isNotEmpty()
                && $po->details->every(fn($detail) => $detail->item && $detail->item->item_type === 'SP');

            // All-Sparepart POs and dual-approval POs (>Rp 5,000,000, non-Sparepart) both
            // start with Manager (Sigit) as the first approver.
            $approverRoles = ($isAllSparepart || $totalAmount > 5000000)
                ? ['manager', 'super_admin']
                : ['manager', 'director', 'super_admin'];
            $poLabel = $po->po_type === 'service_order' ? 'SO' : 'PO';
            NotificationService::sendToRole(
                $approverRoles,
                'po_created',
                "New {$poLabel} Needs Approval",
                "{$poLabel} {$po->po_number} has been created by " . Auth::user()->name . " and needs your approval.",
                route('purchase_orders.show', $po),
                Auth::id()
            );

            return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order created successfully!');
        });
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'purchaseRequest',
            'supplier',
            'creator',
            'approver',
            'managerApprover',
            'revoker',
            'closer',
            'details.item',
            'details.uom',
            'details.purchaseOrderInvoiceLines',
            'details.purchaseOrderInvoiceLines.purchaseOrderInvoice',
            'miscCosts',
            'purchaseInvoices.supplier',
            'purchaseInvoices.recorder',
            'purchaseInvoices.lines.purchaseOrderDetail.item',
            'purchaseInvoices.lines.purchaseOrderDetail.uom',
        ]);
        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only purchasing staff can edit Purchase Orders.');
        }
        if (!in_array($purchaseOrder->status, ['on_progress', 'completed'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only On Progress or Completed POs can be edited.');
        }

        $purchaseOrder->load(['details.item.itemUoms.uom', 'details.uom', 'miscCosts', 'supplier', 'purchaseRequest']);
        $prs = PurchaseRequest::where('status', 'completed')
            ->where(function ($q) use ($purchaseOrder) {
                // Show PRs that still have remaining qty OR the PR already linked to this PO
                $q->whereHas('details', fn($d) => $d->whereRaw('ordered_quantity < quantity'))
                    ->orWhere('id', $purchaseOrder->purchase_request_id);
            })
            ->with(['details.item.itemUoms.uom', 'details.item.smallestUom', 'details.uom'])
            ->get();
        $items = Item::where('is_active', true)->with(['itemUoms.uom', 'smallestUom'])->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase_orders.edit', compact('purchaseOrder', 'prs', 'items', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only purchasing staff can edit Purchase Orders.');
        }
        if (!in_array($purchaseOrder->status, ['on_progress', 'completed'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only On Progress or Completed POs can be edited.');
        }

        $poType = $purchaseOrder->po_type; // PO type cannot be changed
        $baseRules = [
            'purchase_request_id' => 'nullable|exists:purchase_requests,id',
            'supplier_id'         => 'nullable|exists:suppliers,id',
            'order_date'          => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'supplier_name'       => 'required|string|max:200',
            'supplier_address'    => 'nullable|string',
            'supplier_phone'      => 'nullable|string|max:50',
            'supplier_contact_person' => 'nullable|string|max:100',
            'lokasi_pengerjaan'   => 'nullable|string|max:255',
            'lokasi_pengiriman'   => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
            'include_ppn'         => 'nullable|boolean',
            'pph_type'            => 'nullable|in:none,pph_21,pph_23',
            'waktu_pengerjaan'    => 'nullable|string|max:100',
            'payment_method'      => 'required|in:credit,cbd,dp,cod',
            'pembayaran'          => 'required|in:tunai,non_tunai',
            'discount'            => 'nullable|numeric|min:0',
            'bank_account'        => 'nullable|string|max:255',
            'jatuh_tempo'         => 'nullable|string|max:100',
            'payment_terms'       => 'nullable|string',
            'misc_costs'          => 'nullable|array',
            'misc_costs.*.description' => 'nullable|string|max:255',
            'misc_costs.*.amount'      => 'nullable|numeric|min:0',
        ];

        // Items can only be changed when PO is still on_progress
        if ($purchaseOrder->status === 'on_progress') {
            $baseRules['items']              = 'required|array|min:1';
            $baseRules['items.*.purchase_request_detail_id'] = 'nullable|exists:purchase_request_details,id';
            $baseRules['items.*.quantity']   = 'required|numeric|min:0.01';
            $baseRules['items.*.unit_price'] = 'required|numeric|min:0';
            $baseRules['items.*.remarks']    = 'nullable|string';
            if ($poType === 'service_order') {
                $baseRules['items.*.service_description'] = 'required|string|min:3';
                $baseRules['items.*.item_id'] = 'nullable|exists:items,id';
                $baseRules['items.*.uom_id']  = 'nullable|exists:uoms,id';
                $baseRules['items.*.conversion_to_smallest'] = 'nullable|numeric|min:0.000001';
            } else {
                $baseRules['items.*.item_id']             = 'required|exists:items,id';
                $baseRules['items.*.uom_id']              = 'required|exists:uoms,id';
                $baseRules['items.*.service_description'] = 'nullable|string';
                $baseRules['items.*.conversion_to_smallest'] = 'required|numeric|min:0.000001';
            }
        }

        $validated = $request->validate($baseRules);

        // --- Pre-validate business rules BEFORE any database writes ---
        if ($purchaseOrder->status === 'on_progress' && !empty($validated['items'])) {
            // 1. Check total amount is not zero
            $preTotal = array_sum(array_map(
                fn($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0),
                $validated['items']
            ));
            if ($preTotal <= 0) {
                return back()->withErrors([
                    'items' => 'Total PO amount cannot be zero. Please enter unit prices for all items.'
                ])->withInput();
            }

            // 2. Check remaining PR quantities — using current ordered_quantity minus what this PO
            //    currently holds (since it will be rolled back before new values are applied).
            $purchaseOrder->load('details');
            $currentPoQtyByPrDetail = [];
            foreach ($purchaseOrder->details as $d) {
                if ($d->purchase_request_detail_id) {
                    $currentPoQtyByPrDetail[$d->purchase_request_detail_id] =
                        ($currentPoQtyByPrDetail[$d->purchase_request_detail_id] ?? 0) + (float)$d->quantity;
                }
            }

            foreach ($validated['items'] as $index => $itemData) {
                if (!empty($itemData['purchase_request_detail_id'])) {
                    $prDetailId = $itemData['purchase_request_detail_id'];
                    $prDetail   = PurchaseRequestDetail::find($prDetailId);
                    if ($prDetail) {
                        // Available = quantity - ordered_quantity + what this PO currently holds
                        $availableQty = $prDetail->quantity - $prDetail->ordered_quantity
                            + ($currentPoQtyByPrDetail[$prDetailId] ?? 0);
                        if ((float)$itemData['quantity'] > $availableQty) {
                            $itemName = $prDetail->item->name ?? 'N/A';
                            return back()->withErrors([
                                "items.{$index}.quantity" => "Item '{$itemName}' has only {$availableQty} remaining quantity available. You cannot order {$itemData['quantity']}."
                            ])->withInput();
                        }
                    }
                }
            }
        }

        // --- All checks passed — write inside a transaction ---
        return DB::transaction(function () use ($request, $validated, $poType, $purchaseOrder) {
            // Update header fields
            $purchaseOrder->update([
                'purchase_request_id'    => $validated['purchase_request_id'] ?? null,
                'supplier_id'            => $validated['supplier_id'] ?? null,
                'order_date'             => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'supplier_name'          => $validated['supplier_name'],
                'supplier_address'       => $validated['supplier_address'] ?? null,
                'supplier_phone'         => $validated['supplier_phone'] ?? null,
                'supplier_contact_person' => $validated['supplier_contact_person'] ?? null,
                'lokasi_pengerjaan'      => $validated['lokasi_pengerjaan'] ?? null,
                'lokasi_pengiriman'      => $validated['lokasi_pengiriman'] ?? null,
                'notes'                  => $validated['notes'] ?? null,
                'include_ppn'            => $request->boolean('include_ppn'),
                'pph_type'               => $validated['pph_type'] ?? 'none',
                'waktu_pengerjaan'       => $validated['waktu_pengerjaan'] ?? null,
                'payment_method'         => $validated['payment_method'],
                'pembayaran'             => $validated['payment_method'] === 'cod' ? 'tunai' : $validated['pembayaran'],
                'bank_account'           => $validated['bank_account'] ?? null,
                'jatuh_tempo'            => $validated['jatuh_tempo'] ?? null,
                'payment_terms'          => $validated['payment_terms'] ?? null,
            ]);

            // Rebuild details and misc costs only when still on_progress
            if ($purchaseOrder->status === 'on_progress') {
                // Roll back ordered_quantity for old details before deleting them
                $purchaseOrder->load('details');
                foreach ($purchaseOrder->details as $oldDetail) {
                    if ($oldDetail->purchase_request_detail_id) {
                        PurchaseRequestDetail::where('id', $oldDetail->purchase_request_detail_id)
                            ->decrement('ordered_quantity', $oldDetail->quantity);
                    }
                }

                $purchaseOrder->details()->delete();
                $totalAmount = 0;
                $totalDiscount = 0;
                foreach ($validated['items'] as $itemData) {
                    $grossPrice   = $itemData['quantity'] * $itemData['unit_price'];
                    $itemDiscount = (float) ($itemData['discount'] ?? 0);
                    $totalPrice   = max(0, $grossPrice - $itemDiscount);
                    $totalAmount += $totalPrice;
                    $totalDiscount += $itemDiscount;
                    $prDetailId   = !empty($itemData['purchase_request_detail_id']) ? $itemData['purchase_request_detail_id'] : null;

                    $detailData = [
                        'purchase_order_id'          => $purchaseOrder->id,
                        'quantity'                   => $itemData['quantity'],
                        'unit_price'                 => $itemData['unit_price'],
                        'total_price'                => $totalPrice,
                        'discount'                   => $itemDiscount,
                        'discount_percentage'        => (float) ($itemData['discount_percentage'] ?? 0),
                        'received_quantity'          => 0,
                        'remarks'                    => $itemData['remarks'] ?? null,
                        'purchase_request_detail_id' => $prDetailId,
                    ];

                    if ($poType === 'service_order') {
                        $detailData['service_description'] = $itemData['service_description'] ?? null;
                        $detailData['item_id'] = null;
                        $detailData['uom_id']  = null;
                    } else {
                        $detailData['item_id']                = $itemData['item_id'] ?? null;
                        $detailData['uom_id']                 = $itemData['uom_id'] ?? null;
                        $detailData['service_description']    = null;
                        $detailData['conversion_to_smallest'] = $itemData['conversion_to_smallest'] ?? null;
                    }

                    PurchaseOrderDetail::create($detailData);

                    if ($prDetailId) {
                        PurchaseRequestDetail::where('id', $prDetailId)
                            ->increment('ordered_quantity', $itemData['quantity']);
                    }
                }
                $purchaseOrder->update(['total_amount' => $totalAmount, 'discount' => $totalDiscount]);

                // Rebuild misc costs
                $purchaseOrder->miscCosts()->delete();
                if (!empty($validated['misc_costs'])) {
                    foreach ($validated['misc_costs'] as $misc) {
                        if (!empty($misc['description']) && isset($misc['amount'])) {
                            PurchaseOrderMiscCost::create([
                                'purchase_order_id' => $purchaseOrder->id,
                                'description'       => $misc['description'],
                                'amount'            => $misc['amount'],
                            ]);
                        }
                    }
                }
            }

            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order updated successfully!');
        });
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'User not authenticated.');
        }

        $purchaseOrder->load(['details.item']);

        $isAllSparepart = $purchaseOrder->po_type === 'purchase_order'
            && $purchaseOrder->details->isNotEmpty()
            && $purchaseOrder->details->every(fn($detail) => $detail->item && $detail->item->item_type === 'SP');

        $isSigit = stripos($user->name, 'Sigit') !== false;

        // Determine approval authority based on PO amount
        $amountThreshold = 5000000; // 5,000,000 Rupiah
        // Non-Sparepart POs above the threshold need a two-step signature:
        // Manager (Sigit) first, then Director (Direksi) second.
        $requiresDualApproval = !$isAllSparepart && $purchaseOrder->total_amount > $amountThreshold;

        // Prevent self-approval at any step
        if ($purchaseOrder->created_by == $user->id) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'You cannot approve your own Purchase Order.');
        }

        if ($isAllSparepart) {
            // All-Sparepart POs: single-step approval, always by Sigit.
            if (!$isSigit) {
                return redirect()->route('purchase_orders.show', $purchaseOrder)
                    ->with('error', 'This PO contains only Sparepart items and must be approved by Sigit.');
            }
            if ($purchaseOrder->status !== 'on_progress') {
                return redirect()->route('purchase_orders.show', $purchaseOrder)
                    ->with('error', 'Only on progress POs can be approved.');
            }
        } elseif ($requiresDualApproval) {
            if ($purchaseOrder->status === 'on_progress') {
                // Step 1: Manager (Sigit) signs first.
                if (!$isSigit) {
                    return redirect()->route('purchase_orders.show', $purchaseOrder)
                        ->with('error', 'This PO exceeds Rp 5,000,000 and needs Manager (Sigit) approval first.');
                }
            } elseif ($purchaseOrder->status === 'pending_director_approval') {
                // Step 2: Director (Direksi) signs after Manager.
                if (!$user->hasAnyRole(['director', 'super_admin'])) {
                    return redirect()->route('purchase_orders.show', $purchaseOrder)
                        ->with('error', 'Only Director can give final approval for POs above Rp 5,000,000.');
                }
            } else {
                return redirect()->route('purchase_orders.show', $purchaseOrder)
                    ->with('error', 'Only on progress POs can be approved.');
            }
        } else {
            // Non-Sparepart, Rp 5,000,000 or less: single-step Manager approval (unchanged).
            if ($purchaseOrder->status !== 'on_progress') {
                return redirect()->route('purchase_orders.show', $purchaseOrder)
                    ->with('error', 'Only on progress POs can be approved.');
            }
            if (!$user->hasAnyRole(['manager', 'super_admin'])) {
                return redirect()->route('purchase_orders.show', $purchaseOrder)
                    ->with('error', 'Only Manager can approve POs with this amount.');
            }
        }

        // Check if user has signature
        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before approving a PO. Go to your profile.');
        }

        $poLabel = $purchaseOrder->po_type === 'service_order' ? 'SO' : 'PO';

        // Step 1 of a dual-approval PO: record Manager sign-off and hand off to Director.
        if ($requiresDualApproval && $purchaseOrder->status === 'on_progress') {
            $purchaseOrder->update([
                'status' => 'pending_director_approval',
                'manager_approved_by' => $user->id,
                'manager_approved_at' => now(),
            ]);

            NotificationService::sendToRole(
                ['director', 'super_admin'],
                'po_created',
                "{$poLabel} Needs Director Approval",
                "{$poLabel} {$purchaseOrder->po_number} has been approved by Manager ({$user->name}) and now needs Director approval.",
                route('purchase_orders.show', $purchaseOrder),
                $user->id
            );

            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('success', 'Manager approval recorded. Waiting for Director approval.');
        }

        // Final approval: either single-step approval, or step 2 (Director) of a dual-approval PO.
        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Notify the PO creator that their PO has been approved
        NotificationService::send(
            $purchaseOrder->created_by,
            'po_approved',
            "{$poLabel} Approved",
            "{$poLabel} {$purchaseOrder->po_number} has been approved by {$user->name}.",
            route('purchase_orders.show', $purchaseOrder)
        );

        // Notify warehouse to prepare for receiving
        NotificationService::sendToRole(
            ['warehouse', 'admin', 'super_admin'],
            'po_approved',
            "{$poLabel} Ready for Receiving",
            "{$poLabel} {$purchaseOrder->po_number} has been approved. Please prepare to receive goods.",
            route('purchase_orders.show', $purchaseOrder),
            $user->id
        );

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order approved successfully!');
    }

    public function revokeApproval(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();

        if (!in_array($purchaseOrder->status, ['approved', 'pending_director_approval'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only approved or pending-director-approval POs can have their approval revoked.');
        }

        // Only Purchasing or Admin can send back for revision
        if (!$user->hasAnyRole(['admin', 'super_admin', 'purchasing'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only Purchasing or Admin can send a PO back for revision.');
        }

        // Block if receiving or invoice has already started
        $hasDownstream = $purchaseOrder->receivables()->exists()
            || $purchaseOrder->purchaseInvoices()->exists();

        if ($hasDownstream) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Cannot revoke approval — receiving or invoice recording has already started for this PO.');
        }

        $request->validate([
            'revocation_reason' => 'required|string|min:5|max:500',
        ]);

        $purchaseOrder->update([
            'status'             => 'on_progress',
            'revoked_by'         => $user->id,
            'revoked_at'         => now(),
            'revocation_reason'  => $request->revocation_reason,
            // Clear approval fields so it must be re-approved from the start
            'approved_by'        => null,
            'approved_at'        => null,
            'manager_approved_by' => null,
            'manager_approved_at' => null,
        ]);

        $poLabel = $purchaseOrder->po_type === 'service_order' ? 'SO' : 'PO';

        // Notify the PO creator
        if ($purchaseOrder->created_by !== $user->id) {
            NotificationService::send(
                $purchaseOrder->created_by,
                'po_revoked',
                "{$poLabel} Sent Back for Revision",
                "{$poLabel} {$purchaseOrder->po_number} has been sent back for revision by {$user->name}. Reason: {$request->revocation_reason}",
                route('purchase_orders.show', $purchaseOrder)
            );
        }

        // Notify purchasing team
        NotificationService::sendToRole(
            ['purchasing', 'admin', 'super_admin'],
            'po_revoked',
            "{$poLabel} Requires Revision",
            "{$poLabel} {$purchaseOrder->po_number} was sent back for revision by {$user->name}. Please edit and re-submit for approval.",
            route('purchase_orders.show', $purchaseOrder),
            $user->id
        );

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Approval revoked. The PO has been sent back to purchasing for revision.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'User not authenticated.');
        }

        if (!in_array($purchaseOrder->status, ['on_progress', 'approved'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only on progress or approved POs can be cancelled.');
        }

        $hasDownstreamProcess =
            !empty($purchaseOrder->invoice_number) ||
            $purchaseOrder->receivables()->exists() ||
            $purchaseOrder->purchaseInvoices()->exists();
        if ($purchaseOrder->status === 'approved' && $hasDownstreamProcess) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'This approved PO cannot be cancelled because receiving or invoice recording has started.');
        }

        if (!$this->canCancelPurchaseOrder($user, $purchaseOrder)) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'You are not allowed to cancel this Purchase Order.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $purchaseOrder->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        // Decrement ordered_quantity back on all linked PR details so the PR becomes
        // available again for new POs covering those quantities.
        foreach ($purchaseOrder->details as $poDetail) {
            if ($poDetail->purchase_request_detail_id) {
                PurchaseRequestDetail::where('id', $poDetail->purchase_request_detail_id)
                    ->decrement('ordered_quantity', $poDetail->quantity);
            }
        }

        $poLabel = $purchaseOrder->po_type === 'service_order' ? 'SO' : 'PO';

        // Notify the PO creator if they are not the one cancelling
        if ($purchaseOrder->created_by !== Auth::id()) {
            NotificationService::send(
                $purchaseOrder->created_by,
                'po_cancelled',
                "{$poLabel} Cancelled",
                "{$poLabel} {$purchaseOrder->po_number} has been cancelled by " . Auth::user()->name . ". Reason: {$request->cancellation_reason}",
                route('purchase_orders.show', $purchaseOrder)
            );
        }

        // Notify purchasing team (excluding the actor)
        NotificationService::sendToRole(
            ['purchasing', 'manager', 'director'],
            'po_cancelled',
            "{$poLabel} Cancelled",
            "{$poLabel} {$purchaseOrder->po_number} has been cancelled by " . Auth::user()->name . ".",
            route('purchase_orders.show', $purchaseOrder),
            Auth::id()
        );

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order cancelled successfully.');
    }

    private function canCancelPurchaseOrder($user, PurchaseOrder $purchaseOrder): bool
    {
        if (!$user) {
            return false;
        }

        if (!in_array($purchaseOrder->status, ['on_progress', 'approved'])) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        if ($purchaseOrder->status === 'on_progress') {
            if ($user->hasAnyRole(['manager', 'director'])) {
                return true;
            }

            return $user->hasAnyRole(['purchasing']) && $purchaseOrder->created_by === $user->id;
        }

        if ($purchaseOrder->status === 'approved') {
            return $user->hasAnyRole(['manager', 'director']) || $purchaseOrder->approved_by === $user->id;
        }

        return false;
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Check if user has warehouse role
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only warehouse staff can receive Purchase Orders.');
        }

        if ($purchaseOrder->status === 'received' || $purchaseOrder->status === 'cancelled') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Cannot receive this PO.');
        }

        $validated = $request->validate([
            'details' => 'required|array',
            'details.*.pod_id' => 'required|exists:purchase_order_details,id',
            'details.*.received_qty' => 'required|numeric|min:0',
        ]);

        foreach ($validated['details'] as $detail) {
            $pod = PurchaseOrderDetail::find($detail['pod_id']);

            if ($detail['received_qty'] > 0) {
                $remainingOpenQty = $pod->getOpenQuantity();
                if ($detail['received_qty'] > $remainingOpenQty) {
                    return redirect()->route('purchase_orders.show', $purchaseOrder)
                        ->with('error', 'Received quantity cannot exceed remaining open quantity.');
                }

                // ADD to existing received quantity (support partial receiving)
                $pod->increment('received_quantity', $detail['received_qty']);

                // Get the ItemUOM to convert to smallest UOM
                $itemUom = ItemUOM::where('item_id', $pod->item_id)
                    ->where('uom_id', $pod->uom_id)
                    ->first();

                $quantityInSmallest = $detail['received_qty'] * $itemUom->conversion_to_smallest;

                // Add to stock
                $stock = Stock::where('item_id', $pod->item_id)
                    ->where('location', 'default')
                    ->first();

                $taxMultiplier = ($purchaseOrder->include_ppn && $purchaseOrder->po_type === 'purchase_order') ? 1.11 : 1.0;
                $netUnitPrice = (float) $pod->quantity > 0
                    ? (float) $pod->total_price / (float) $pod->quantity
                    : (float) $pod->unit_price;
                $receivedUnitCost = ($netUnitPrice * $taxMultiplier) / (float) $itemUom->conversion_to_smallest;
                $stock->addQuantity($quantityInSmallest, $receivedUnitCost);

                // Create transaction record
                StockTransaction::create([
                    'item_id' => $pod->item_id,
                    'transaction_type' => 'in',
                    'quantity' => $quantityInSmallest,
                    'unit_cost' => $receivedUnitCost,
                    'balance_after' => $stock->quantity,
                    'location' => 'default',
                    'reference_type' => 'PO',
                    'reference_id' => $purchaseOrder->id,
                    'notes' => "Received from PO {$purchaseOrder->po_number}",
                    'created_by' => Auth::id(),
                ]);
            }
        }

        $purchaseOrder->refresh()->load('details');
        $this->refreshPurchaseOrderStatus($purchaseOrder);

        $poLabel = $purchaseOrder->po_type === 'service_order' ? 'SO' : 'PO';
        $newStatus = $purchaseOrder->fresh()->status;

        if ($newStatus === 'received') {
            // Fully received — notify the PO creator and purchasing team
            NotificationService::send(
                $purchaseOrder->created_by,
                'po_completed',
                "{$poLabel} Fully Received",
                "{$poLabel} {$purchaseOrder->po_number} has been fully received by " . Auth::user()->name . ".",
                route('purchase_orders.show', $purchaseOrder)
            );
            NotificationService::sendToRole(
                ['purchasing', 'manager'],
                'po_completed',
                "{$poLabel} Fully Received",
                "{$poLabel} {$purchaseOrder->po_number} has been fully received into stock.",
                route('purchase_orders.show', $purchaseOrder),
                Auth::id()
            );
        } else {
            // Partially received — notify purchasing so they can follow up
            NotificationService::send(
                $purchaseOrder->created_by,
                'po_received',
                "{$poLabel} Partially Received",
                "{$poLabel} {$purchaseOrder->po_number} has been partially received. Some items are still outstanding.",
                route('purchase_orders.show', $purchaseOrder)
            );
        }

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Stock received successfully!');
    }

    public function closeRemaining(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only purchasing staff can close remaining PO quantities.');
        }

        if (!in_array($purchaseOrder->status, ['approved', 'partial'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Remaining quantities can only be closed for approved or partial POs.');
        }

        $validated = $request->validate([
            'lines' => 'required|array',
            'lines.*.purchase_order_detail_id' => 'required|exists:purchase_order_details,id',
            'lines.*.close_quantity' => 'required|numeric|min:0',
            'lines.*.reason' => 'nullable|string',
        ]);

        $details = $purchaseOrder->details()->get()->keyBy('id');
        $hasAnyClosure = false;

        DB::beginTransaction();
        try {
            foreach ($validated['lines'] as $index => $line) {
                $detail = $details->get((int) $line['purchase_order_detail_id']);
                if (!$detail) {
                    throw new \Exception('PO detail mismatch detected.');
                }

                $closeQuantity = (float) $line['close_quantity'];
                if ($closeQuantity <= 0) {
                    continue;
                }

                $remainingOpenQty = $detail->getOpenQuantity();
                if ($closeQuantity > $remainingOpenQty) {
                    return back()->withInput()->withErrors([
                        "lines.$index.close_quantity" => "Close quantity cannot exceed remaining open quantity ({$remainingOpenQty}).",
                    ]);
                }

                if (empty(trim((string) ($line['reason'] ?? '')))) {
                    return back()->withInput()->withErrors([
                        "lines.$index.reason" => 'Reason is required when closing remaining quantity.',
                    ]);
                }

                $detail->update([
                    'closed_shortage_quantity' => (float) $detail->closed_shortage_quantity + $closeQuantity,
                    'shortage_close_reason' => trim((string) $line['reason']),
                    'shortage_closed_by' => Auth::id(),
                    'shortage_closed_at' => now(),
                ]);

                // Release closed quantity back to the PR detail so the remaining
                // items can be ordered on a new PO
                if ($detail->purchase_request_detail_id) {
                    PurchaseRequestDetail::where('id', $detail->purchase_request_detail_id)
                        ->decrement('ordered_quantity', $closeQuantity);
                }

                $hasAnyClosure = true;
            }

            if (!$hasAnyClosure) {
                return back()->withInput()->withErrors([
                    'lines' => 'At least one line must have close quantity greater than 0.',
                ]);
            }

            $purchaseOrder->refresh()->load('details');
            $this->refreshPurchaseOrderStatus($purchaseOrder);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Failed to close remaining quantity: ' . $e->getMessage());
        }

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Remaining PO quantities closed successfully.');
    }

    public function recordInvoice(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Check if user has purchasing role
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only purchasing staff can record invoices.');
        }

        // Can only record invoice after receiving (partial or complete)
        if (!in_array($purchaseOrder->status, ['partial', 'received', 'closed_shortage'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Invoice can only be recorded after goods are received.');
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:255',
            'invoice_date' => 'required|date',
            'invoice_due_date' => 'nullable|date|after_or_equal:invoice_date',
            'invoice_notes' => 'nullable|string',
            'lines' => 'required|array',
            'lines.*.purchase_order_detail_id' => 'required|exists:purchase_order_details,id',
            'lines.*.qty_billed' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.notes' => 'nullable|string',
        ]);

        $purchaseOrder->load('details.purchaseOrderInvoiceLines');
        $details = $purchaseOrder->details->keyBy('id');
        $invoiceLines = [];
        $totalAmount = 0;

        foreach ($validated['lines'] as $index => $line) {
            $detail = $details->get((int) $line['purchase_order_detail_id']);
            if (!$detail) {
                return back()->withInput()->withErrors([
                    "lines.$index.purchase_order_detail_id" => 'Invoice line does not belong to this PO.',
                ]);
            }

            $qtyBilled = (float) $line['qty_billed'];
            if ($qtyBilled <= 0) {
                continue;
            }

            $remainingBillable = $detail->getRemainingBillableQuantity();
            if ($qtyBilled > $remainingBillable) {
                return back()->withInput()->withErrors([
                    "lines.$index.qty_billed" => "Billed quantity cannot exceed remaining billable quantity ({$remainingBillable}).",
                ]);
            }

            $lineTotal = $qtyBilled * (float) $line['unit_price'];
            $totalAmount += $lineTotal;

            $invoiceLines[] = [
                'purchase_order_detail_id' => $detail->id,
                'qty_billed' => $qtyBilled,
                'unit_price' => (float) $line['unit_price'],
                'line_total' => $lineTotal,
                'notes' => $line['notes'] ?? null,
            ];
        }

        if (empty($invoiceLines)) {
            return back()->withInput()->withErrors([
                'lines' => 'At least one line must have billed quantity greater than 0.',
            ]);
        }

        $resolvedSupplierId = $purchaseOrder->supplier_id;
        $resolvedSupplierName = $purchaseOrder->supplier_name;

        DB::beginTransaction();
        try {
            $invoice = PurchaseOrderInvoice::create([
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $resolvedSupplierId,
                'supplier_name' => $resolvedSupplierName,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['invoice_due_date'] ?? null,
                'total_amount' => $totalAmount,
                'status' => 'on_progress',
                'notes' => $validated['invoice_notes'] ?? null,
                'recorded_by' => Auth::id(),
                'recorded_at' => now(),
            ]);

            foreach ($invoiceLines as $lineData) {
                $invoice->lines()->create($lineData);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Failed to record invoice: ' . $e->getMessage());
        }

        // Auto-complete the PO when all lines are fully received/closed AND fully billed
        $purchaseOrder->load('details.purchaseOrderInvoiceLines');
        $allDone = $purchaseOrder->details->every(
            fn($d) => $d->getOpenQuantity() <= 0 && $d->getRemainingBillableQuantity() <= 0
        );
        if ($allDone) {
            $purchaseOrder->update(['status' => 'completed']);
            $this->autoClosePurchaseRequest($purchaseOrder);
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('success', 'Invoice recorded and Purchase Order completed successfully!');
        }

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Invoice recorded successfully!');
    }

    public function complete(PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only purchasing staff can complete POs.');
        }

        if (!in_array($purchaseOrder->status, ['partial', 'received', 'closed_shortage'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'PO can only be completed after goods are received or remaining quantities are closed.');
        }

        if (!$purchaseOrder->purchaseInvoices()->exists()) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Please record an invoice before completing the PO.');
        }

        $purchaseOrder->update(['status' => 'completed']);
        $this->autoClosePurchaseRequest($purchaseOrder);

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order has been completed successfully.');
    }

    public function closeSO(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin', 'manager', 'director'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'You do not have permission to close this Service Order.');
        }

        if ($purchaseOrder->po_type !== 'service_order') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'This action is only available for Service Orders (PPJ).');
        }

        if ($purchaseOrder->status !== 'approved') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only approved Service Orders can be closed.');
        }

        $linkedPR = $purchaseOrder->purchaseRequest;
        if (!$linkedPR || !$linkedPR->berita_acara_path) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Please upload the Berita Acara on the linked PPJ before closing this Service Order.');
        }

        $validated = $request->validate([
            'nomor_nota' => 'required|string|max:100',
        ]);

        $purchaseOrder->update([
            'status'     => 'completed',
            'closed_by'  => Auth::id(),
            'closed_at'  => now(),
            'nomor_nota' => $validated['nomor_nota'],
        ]);

        $this->autoClosePurchaseRequest($purchaseOrder);

        NotificationService::send(
            $purchaseOrder->created_by,
            'so_closed',
            'Service Order Closed',
            "SO {$purchaseOrder->po_number} has been closed by " . Auth::user()->name . ".",
            route('purchase_orders.show', $purchaseOrder)
        );

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Service Order has been closed successfully.');
    }

    /**
     * Auto-close the linked Purchase Request when the PO is fully completed.
     * Closes the PR from any non-terminal state (anything except already closed/cancelled/rejected).
     * Manual close is still available for partial-received cases where the user
     * decides they no longer need the remaining quantity.
     */
    private function autoClosePurchaseRequest(PurchaseOrder $purchaseOrder): void
    {
        if (!$purchaseOrder->purchase_request_id) {
            return;
        }

        $pr = PurchaseRequest::find($purchaseOrder->purchase_request_id);
        if (!$pr || in_array($pr->status, ['closed', 'cancelled', 'rejected'])) {
            return;
        }

        // Only auto-close if ALL PR detail items are fully covered by POs
        $allItemsOrdered = $pr->details()->where(function ($q) {
            $q->whereRaw('ordered_quantity < quantity');
        })->doesntExist();

        if (!$allItemsOrdered) {
            return;
        }

        $pr->update([
            'status' => 'closed',
            'cancellation_reason' => 'Auto-closed: all items fully ordered. Last completed PO: ' . $purchaseOrder->po_number . '.',
        ]);
    }

    public function preview(Request $request)
    {
        Log::info('Preview method called', ['method' => $request->method(), 'all' => $request->all()]);

        // Check if user has purchasing role
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return back()->with('error', 'Only purchasing staff can preview POs.');
        }

        // Get data without strict validation for preview
        $poType = $request->input('po_type', 'purchase_order');
        $orderDate = $request->input('order_date', now()->format('Y-m-d'));
        $supplierId = $request->input('supplier_id');
        $supplierName = $request->input('supplier_name');
        $supplierAddress = $request->input('supplier_address');
        $supplierPhone = $request->input('supplier_phone');
        $supplierContact = $request->input('supplier_contact_person');
        $bankAccount = $request->input('bank_account');
        $items = $request->input('items', []);

        // Fall back to supplier master data when the form hasn't been filled yet
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier) {
                $supplierName = $supplierName ?: $supplier->name;
                $supplierAddress = $supplierAddress ?: $supplier->address;
                $supplierPhone = $supplierPhone ?: $supplier->phone;
                $supplierContact = $supplierContact ?: $supplier->contact_person;
                if (! $bankAccount && ($supplier->bank_name || $supplier->bank_account_no)) {
                    $bankAccount = $supplier->bank_name
                        . ($supplier->bank_account_no ? ' - ' . $supplier->bank_account_no : '')
                        . ($supplier->bank_account_name ? ' a.n. ' . $supplier->bank_account_name : '');
                }
            }
        }

        if (empty($items)) {
            return back()->with('error', 'Please add at least one item to preview. Items received: ' . json_encode($items));
        }

        // Create a temporary PO object for preview
        $tempPo = new \stdClass();
        $tempPo->po_number = 'PREVIEW-' . strtoupper(substr(uniqid(), -6));
        $tempPo->po_type = $poType;
        $tempPo->order_date = \Carbon\Carbon::parse($orderDate);
        $tempPo->expected_delivery_date = $request->input('expected_delivery_date') ? \Carbon\Carbon::parse($request->input('expected_delivery_date')) : null;
        $tempPo->supplier_name = $supplierName ?: 'Supplier Name';
        $tempPo->supplier_address = $supplierAddress;
        $tempPo->supplier_phone = $supplierPhone;
        $tempPo->supplier_contact_person = $supplierContact;
        $tempPo->lokasi_pengerjaan = $request->input('lokasi_pengerjaan');
        $tempPo->lokasi_pengiriman = $request->input('lokasi_pengiriman');
        $tempPo->waktu_pengerjaan = $request->input('waktu_pengerjaan');
        $tempPo->payment_method = $request->input('payment_method');
        $tempPo->pembayaran = $request->input('payment_method') === 'cod' ? 'tunai' : $request->input('pembayaran');
        $tempPo->bank_account = $bankAccount;
        $tempPo->jatuh_tempo = $request->input('jatuh_tempo');
        $tempPo->payment_terms = $request->input('payment_terms');
        $tempPo->notes = $request->input('notes');
        $tempPo->include_ppn = $request->input('include_ppn', false);
        $tempPo->pph_type = $request->input('pph_type', 'none');
        $tempPo->discount = (float) $request->input('discount', 0);
        $tempPo->status = 'on_progress';
        $tempPo->total_amount = 0;

        // Load related data
        $tempPo->creator = $user;
        $tempPo->approver = null;
        $tempPo->purchaseRequest = null;
        $tempPo->supplier = null;

        // Build details collection
        $details = collect();
        $totalDiscount = 0;
        $tempPo->total_amount = 0;
        foreach ($items as $itemData) {
            $detail = new \stdClass();
            $detail->quantity = (float)$itemData['quantity'];
            $detail->unit_price = (float)$itemData['unit_price'];
            $gross = $detail->quantity * $detail->unit_price;
            $itemDiscount = (float)($itemData['discount'] ?? 0);
            $detail->total_price = max(0, $gross - $itemDiscount);
            $detail->discount = $itemDiscount;
            $detail->remarks = $itemData['remarks'] ?? null;
            $tempPo->total_amount += $detail->total_price;
            $totalDiscount += $itemDiscount;

            if ($poType === 'service_order') {
                $detail->service_description = $itemData['service_description'];
                $detail->item = null;
                $detail->uom = null;
            } else {
                $detail->item = Item::find($itemData['item_id']);
                $detail->uom = UOM::find($itemData['uom_id']);
                $detail->service_description = null;
            }

            $details->push($detail);
        }
        $tempPo->details = $details;
        $tempPo->discount = $totalDiscount;

        // Handle misc costs — use Eloquent-like objects with 'amount' key for ->sum('amount') to work
        $miscCosts = collect();
        if ($request->input('misc_costs')) {
            foreach ($request->input('misc_costs') as $misc) {
                if (!empty($misc['description']) && !empty($misc['amount'])) {
                    $miscCosts->push((object)[
                        'description' => $misc['description'],
                        'amount'      => (float)$misc['amount'],
                    ]);
                }
            }
        }
        $tempPo->miscCosts = $miscCosts;

        // Generate PDF and return directly
        $pdf = Pdf::loadView('purchase_orders.print', ['purchaseOrder' => $tempPo]);
        $filename = 'PREVIEW-' . $tempPo->po_number . '.pdf';

        return $pdf->stream($filename);
    }

    public function printPreview(PurchaseOrder $purchaseOrder)
    {
        if (!PermissionHelper::canPrint('purchase_orders')) {
            return PermissionHelper::denyAccess('purchase_orders', 'view');
        }

        if (!in_array($purchaseOrder->status, ['on_progress', 'approved', 'partial', 'received', 'completed', 'closed_shortage', 'printed'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only POs that can be printed can be previewed.');
        }

        $purchaseOrder->load(['purchaseRequest', 'creator', 'approver', 'supplier', 'details.item', 'details.uom', 'miscCosts']);

        $pdf = Pdf::loadView('purchase_orders.print', compact('purchaseOrder'));
        $safeFilename = str_replace('/', '-', $purchaseOrder->po_number);
        return $pdf->stream('PREVIEW-PO-' . $safeFilename . '.pdf');
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        if (!PermissionHelper::canPrint('purchase_orders')) {
            return PermissionHelper::denyAccess('purchase_orders', 'view');
        }

        if (!in_array($purchaseOrder->status, ['approved', 'partial', 'received', 'completed', 'closed_shortage', 'printed'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only approved POs can be printed.');
        }

        // Stamp printed_at but do NOT change the workflow status
        $purchaseOrder->update([
            'printed_at' => now(),
            'printed_by' => Auth::id(),
        ]);

        $purchaseOrder->load(['purchaseRequest', 'creator', 'approver', 'supplier', 'details.item', 'details.uom', 'miscCosts']);

        $pdf = Pdf::loadView('purchase_orders.print', compact('purchaseOrder'));
        // Sanitize filename by replacing "/" with "-"
        $safeFilename = str_replace('/', '-', $purchaseOrder->po_number);
        AuditLog::logPrint($purchaseOrder, $purchaseOrder->po_number);
        return $pdf->download('PO-' . $safeFilename . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $poType = $request->query('type', 'purchase_order');
        $month = $request->query('month');
        $status = $request->query('status');

        $query = PurchaseOrder::with(['details.item', 'details.uom', 'creator', 'approver'])
            ->where('po_type', $poType);

        if ($month) {
            $start = \Carbon\Carbon::parse($month)->startOfMonth();
            $end = \Carbon\Carbon::parse($month)->endOfMonth();
            $query->whereBetween('order_date', [$start, $end]);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($poType === 'service_order' ? 'Service Orders' : 'Purchase Orders');

        // Header row
        $headers = ['PO Number', 'Supplier', 'Order Date', 'Status', 'Total Amount', 'Item #', 'Description', 'Qty', 'Unit', 'Unit Price', 'Total Price'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4053']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(18);

        $poRowStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6EAF8']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AEB6BF']]],
        ];
        $detailRowStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D5D8DC']]],
        ];

        $statusLabels = [
            'on_progress'     => 'On Progress',
            'approved'        => 'Approved',
            'partial'         => 'Partial',
            'received'        => 'Received',
            'completed'       => 'Completed',
            'closed_shortage' => 'Closed w/ Shortage',
            'cancelled'       => 'Cancelled',
        ];

        $row = 2;
        foreach ($orders as $po) {
            $poRow = $row;
            $sheet->setCellValue("A{$row}", $po->po_number);
            $sheet->setCellValue("B{$row}", $po->supplier_name);
            $sheet->setCellValue("C{$row}", $po->order_date->format('d M Y'));
            $sheet->setCellValue("D{$row}", $statusLabels[$po->status] ?? ucfirst($po->status));
            $sheet->setCellValue("E{$row}", 'Rp ' . number_format($po->total_amount, 0, ',', '.'));
            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($poRowStyle);
            $row++;

            foreach ($po->details as $i => $detail) {
                $name = $poType === 'service_order'
                    ? ($detail->service_description ?? '-')
                    : ($detail->item->name ?? 'N/A');
                $uom = $poType === 'service_order' ? '-' : ($detail->uom->code ?? '-');

                $sheet->setCellValue("F{$row}", $i + 1);
                $sheet->setCellValue("G{$row}", $name);
                $sheet->setCellValue("H{$row}", number_format($detail->quantity, 0, ',', '.'));
                $sheet->setCellValue("I{$row}", $uom);
                $sheet->setCellValue("J{$row}", 'Rp ' . number_format($detail->unit_price, 0, ',', '.'));
                $sheet->setCellValue("K{$row}", 'Rp ' . number_format($detail->total_price, 0, ',', '.'));
                $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($detailRowStyle);
                $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
        }

        // Column widths
        $colWidths = [
            'A' => 22,
            'B' => 28,
            'C' => 14,
            'D' => 18,
            'E' => 18,
            'F' => 6,
            'G' => 38,
            'H' => 8,
            'I' => 8,
            'J' => 16,
            'K' => 16
        ];
        foreach ($colWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $typeLabel = $poType === 'service_order' ? 'Service-Orders' : 'Purchase-Orders';
        $filename  = $typeLabel . '-' . now()->format('Ymd') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $response = response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);

        return $response;
    }

    private function refreshPurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('details');

        $hasOpenLines = $purchaseOrder->details->contains(function ($detail) {
            return $detail->getOpenQuantity() > 0;
        });

        $hasReceivedQty = $purchaseOrder->details->contains(function ($detail) {
            return (float) $detail->received_quantity > 0;
        });

        $hasClosedShortage = $purchaseOrder->details->contains(function ($detail) {
            return (float) ($detail->closed_shortage_quantity ?? 0) > 0;
        });

        if ($hasOpenLines) {
            if ($hasReceivedQty || $hasClosedShortage) {
                $purchaseOrder->update(['status' => 'partial']);
            }

            return;
        }

        $purchaseOrder->update([
            'status' => $hasClosedShortage ? 'closed_shortage' : 'received',
        ]);
    }
}
