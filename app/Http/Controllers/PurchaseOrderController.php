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
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceLine;
use App\Models\Stock;
use App\Models\AuditLog;
use App\Models\StockTransaction;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['purchaseRequest', 'creator', 'details.item'])
            ->where('po_type', 'purchase_order')
            ->withCount('details')
            ->orderBy('created_at', 'desc')
            ->get();

        $serviceOrders = PurchaseOrder::with(['purchaseRequest', 'creator', 'details'])
            ->where('po_type', 'service_order')
            ->withCount('details')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchase_orders.index', compact('purchaseOrders', 'serviceOrders'));
    }

    public function create(Request $request)
    {
        // Check if user has purchasing role
        $user = auth()->user();
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
        $user = auth()->user();
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
            'payment_method' => 'required|in:credit,cbd,dp',
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
            fn($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0),
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
            $companyCode = config('app.company_code', 'HAS.SBY');

            $romanNumerals = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $romanMonth = $romanNumerals[$month];

            $monthStart = $now->clone()->startOfMonth();
            $monthEnd   = $now->clone()->endOfMonth();

            // Lock rows to get accurate sequence inside the transaction
            $countAllThisMonth = PurchaseOrder::whereBetween('created_at', [$monthStart, $monthEnd])
                ->lockForUpdate()->count();
            $poSequence = str_pad($countAllThisMonth + 1, 3, '0', STR_PAD_LEFT);
            $poNumber   = "{$poSequence}/{$companyCode}/{$romanMonth}/{$year}";

            $typePrefix = $validated['po_type'] === 'service_order' ? 'J' : 'B';
            $yearPad    = $now->format('y');
            $monthPad   = str_pad($month, 2, '0', STR_PAD_LEFT);
            $countTypeThisMonth = PurchaseOrder::where('po_type', $validated['po_type'])
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->lockForUpdate()->count();
            $typeSequence = str_pad($countTypeThisMonth + 1, 3, '0', STR_PAD_LEFT);
            $ppbNumber    = "{$typePrefix}{$yearPad}{$monthPad}{$typeSequence}";

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
                'pembayaran'             => $validated['pembayaran'],
                'bank_account'           => $validated['bank_account'] ?? null,
                'jatuh_tempo'            => $validated['jatuh_tempo'] ?? null,
                'payment_terms'          => $validated['payment_terms'] ?? null,
                'status'                 => 'on_progress',
                'created_by'             => auth()->id(),
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $itemData) {
                $totalPrice   = $itemData['quantity'] * $itemData['unit_price'];
                $totalAmount += $totalPrice;
                $prDetailId   = !empty($itemData['purchase_request_detail_id']) ? $itemData['purchase_request_detail_id'] : null;

                $detailData = [
                    'purchase_order_id'          => $po->id,
                    'quantity'                   => $itemData['quantity'],
                    'unit_price'                 => $itemData['unit_price'],
                    'total_price'                => $totalPrice,
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

            $po->update(['total_amount' => $totalAmount]);

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

            $approverRoles = $totalAmount > 5000000
                ? ['director', 'super_admin']
                : ['manager', 'director', 'super_admin'];
            $poLabel = $po->po_type === 'service_order' ? 'SO' : 'PO';
            NotificationService::sendToRole(
                $approverRoles,
                'po_created',
                "New {$poLabel} Needs Approval",
                "{$poLabel} {$po->po_number} has been created by " . auth()->user()->name . " and needs your approval.",
                route('purchase_orders.show', $po),
                auth()->id()
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
            'revoker',
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
        $user = auth()->user();
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
            ->with(['details.item.itemUoms.uom', 'details.uom'])
            ->get();
        $items = Item::where('is_active', true)->with(['itemUoms.uom', 'smallestUom'])->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase_orders.edit', compact('purchaseOrder', 'prs', 'items', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = auth()->user();
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
            'payment_method'      => 'required|in:credit,cbd,dp',
            'pembayaran'          => 'required|in:tunai,non_tunai',
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
                'pembayaran'             => $validated['pembayaran'],
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
                foreach ($validated['items'] as $itemData) {
                    $totalPrice   = $itemData['quantity'] * $itemData['unit_price'];
                    $totalAmount += $totalPrice;
                    $prDetailId   = !empty($itemData['purchase_request_detail_id']) ? $itemData['purchase_request_detail_id'] : null;

                    $detailData = [
                        'purchase_order_id'          => $purchaseOrder->id,
                        'quantity'                   => $itemData['quantity'],
                        'unit_price'                 => $itemData['unit_price'],
                        'total_price'                => $totalPrice,
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
                $purchaseOrder->update(['total_amount' => $totalAmount]);

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
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'User not authenticated.');
        }

        // Determine approval authority based on PO amount
        $amountThreshold = 5000000; // 5,000,000 Rupiah
        $canApprove = false;

        if ($purchaseOrder->total_amount > $amountThreshold) {
            // Amount > 5,000,000: Only Director can approve
            if ($user->hasAnyRole(['director', 'super_admin'])) {
                $canApprove = true;
            }
        } else {
            // Amount <= 5,000,000: Only Manager (not Director) can approve
            if ($user->hasAnyRole(['manager', 'super_admin'])) {
                $canApprove = true;
            }
        }

        if (!$canApprove) {
            $requiredRole = $purchaseOrder->total_amount > $amountThreshold ? 'Director' : 'Manager';
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', "Only $requiredRole can approve POs with this amount.");
        }

        // Prevent self-approval
        if ($purchaseOrder->created_by == auth()->id()) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'You cannot approve your own Purchase Order.');
        }

        if ($purchaseOrder->status !== 'on_progress') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only on progress POs can be approved.');
        }

        // Check if user has signature
        $user = auth()->user();
        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before approving a PO. Go to your profile.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $poLabel = $purchaseOrder->po_type === 'service_order' ? 'SO' : 'PO';

        // Notify the PO creator that their PO has been approved
        NotificationService::send(
            $purchaseOrder->created_by,
            'po_approved',
            "{$poLabel} Approved",
            "{$poLabel} {$purchaseOrder->po_number} has been approved by " . auth()->user()->name . ".",
            route('purchase_orders.show', $purchaseOrder)
        );

        // Notify warehouse to prepare for receiving
        NotificationService::sendToRole(
            ['warehouse', 'admin', 'super_admin'],
            'po_approved',
            "{$poLabel} Ready for Receiving",
            "{$poLabel} {$purchaseOrder->po_number} has been approved. Please prepare to receive goods.",
            route('purchase_orders.show', $purchaseOrder),
            auth()->id()
        );

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order approved successfully!');
    }

    public function revokeApproval(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = auth()->user();

        if ($purchaseOrder->status !== 'approved') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Only approved POs can have their approval revoked.');
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
            // Clear approval fields so it must be re-approved
            'approved_by'        => null,
            'approved_at'        => null,
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
        $user = auth()->user();
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
        if ($purchaseOrder->created_by !== auth()->id()) {
            NotificationService::send(
                $purchaseOrder->created_by,
                'po_cancelled',
                "{$poLabel} Cancelled",
                "{$poLabel} {$purchaseOrder->po_number} has been cancelled by " . auth()->user()->name . ". Reason: {$request->cancellation_reason}",
                route('purchase_orders.show', $purchaseOrder)
            );
        }

        // Notify purchasing team (excluding the actor)
        NotificationService::sendToRole(
            ['purchasing', 'manager', 'director'],
            'po_cancelled',
            "{$poLabel} Cancelled",
            "{$poLabel} {$purchaseOrder->po_number} has been cancelled by " . auth()->user()->name . ".",
            route('purchase_orders.show', $purchaseOrder),
            auth()->id()
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
        $user = auth()->user();
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

                $oldQuantity = $stock->quantity;
                $stock->addQuantity($quantityInSmallest);

                // Create transaction record
                StockTransaction::create([
                    'item_id' => $pod->item_id,
                    'transaction_type' => 'in',
                    'quantity' => $quantityInSmallest,
                    'balance_after' => $stock->quantity,
                    'location' => 'default',
                    'reference_type' => 'PO',
                    'reference_id' => $purchaseOrder->id,
                    'notes' => "Received from PO {$purchaseOrder->po_number}",
                    'created_by' => auth()->id(),
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
                "{$poLabel} {$purchaseOrder->po_number} has been fully received by " . auth()->user()->name . ".",
                route('purchase_orders.show', $purchaseOrder)
            );
            NotificationService::sendToRole(
                ['purchasing', 'manager'],
                'po_completed',
                "{$poLabel} Fully Received",
                "{$poLabel} {$purchaseOrder->po_number} has been fully received into stock.",
                route('purchase_orders.show', $purchaseOrder),
                auth()->id()
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
        $user = auth()->user();
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
                    'shortage_closed_by' => auth()->id(),
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
        $user = auth()->user();
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
                'recorded_by' => auth()->id(),
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
        $user = auth()->user();
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
            'cancellation_reason' => 'Auto-closed: all items fully ordered. Last completed PO: '.$purchaseOrder->po_number.'.',
        ]);
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
            'printed_by' => auth()->id(),
        ]);

        $purchaseOrder->load(['purchaseRequest', 'creator', 'approver', 'supplier', 'details.item', 'details.uom', 'miscCosts']);

        $pdf = Pdf::loadView('purchase_orders.print', compact('purchaseOrder'));
        // Sanitize filename by replacing "/" with "-"
        $safeFilename = str_replace('/', '-', $purchaseOrder->po_number);
        AuditLog::logPrint($purchaseOrder, $purchaseOrder->po_number);
        return $pdf->download('PO-' . $safeFilename . '.pdf');
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
