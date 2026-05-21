<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\Item;
use App\Models\UOM;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $query = PurchaseRequest::with(['requestor', 'deptHeadApprover', 'gmApprover'])
            ->withCount('details');

        // Filter by type if requested
        if (request('type')) {
            $query->where('type', request('type'));
        }

        $prs = $query->orderBy('created_at', 'desc')->get();
        return view('purchase_requests.index', compact('prs'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['staff', 'warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Only staff/warehouse can create PPB/PPJ.');
        }
        $items = Item::where('is_active', true)->with(['itemUoms.uom'])->get();
        $uoms = UOM::orderBy('name')->get();
        return view('purchase_requests.create', compact('items', 'uoms'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['staff', 'warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_requests.index')
                ->with('error', 'Only staff/warehouse can create PPB/PPJ.');
        }
        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before creating a PPB/PPJ. Go to your profile.');
        }

        // Conditional validation based on type
        $type = $request->input('type');
        $baseRules = [
            'request_date' => 'required|date',
            'type' => 'required|in:Jasa,Barang',
            'notes' => 'nullable|string',
            'require_acknowledgement' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ];

        // Add type-specific rules
        if ($type === 'Jasa') {
            $baseRules['items.*.service_description'] = 'required|string|min:3';
            $baseRules['items.*.item_id'] = 'nullable|exists:items,id';
            $baseRules['items.*.uom_id'] = 'nullable|exists:uoms,id';
        } else {
            // Barang (Items)
            $baseRules['items.*.is_custom_item'] = 'nullable|boolean';
            $baseRules['items.*.item_id'] = 'nullable|exists:items,id';
            $baseRules['items.*.uom_id'] = 'required|exists:uoms,id';
            $baseRules['items.*.custom_item_name'] = 'nullable|string|min:2';
            $baseRules['items.*.custom_item_type'] = 'nullable|in:A,B,C,E,T,TE';
            $baseRules['items.*.service_description'] = 'nullable|string';
        }

        $validated = $request->validate($baseRules);

        // Manual validation for custom/existing item logic (Barang)
        if ($type === 'Barang') {
            foreach ($validated['items'] as $i => $itemData) {
                $isCustom = !empty($itemData['is_custom_item']);
                if (!$isCustom && empty($itemData['item_id'])) {
                    return back()->withErrors(["items.$i.item_id" => 'Please select an existing item.'])->withInput();
                }
                if ($isCustom && empty($itemData['custom_item_name'])) {
                    return back()->withErrors(["items.$i.custom_item_name" => 'Item name is required for new items.'])->withInput();
                }
            }
        }

        // Auto-generate PPB/PPJ number with format: [TYPE][YYMM][SEQUENCE]
        // Example: B2602001 (PPB Feb 2026) or J2602001 (PPJ Feb 2026)
        // Sequence resets to 001 each month, counted per type
        $now = now();
        $typePrefix = $validated['type'] === 'Jasa' ? 'J' : 'B';
        $yearPad = $now->format('y');
        $monthPad = str_pad($now->month, 2, '0', STR_PAD_LEFT);

        $monthStart = $now->clone()->startOfMonth();
        $monthEnd = $now->clone()->endOfMonth();
        $countThisMonth = PurchaseRequest::where('type', $validated['type'])
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
        $prNumber = $typePrefix . $yearPad . $monthPad . str_pad($countThisMonth + 1, 3, '0', STR_PAD_LEFT);

        $pr = PurchaseRequest::create([
            'pr_number' => $prNumber,
            'request_date' => $validated['request_date'],
            'requested_by' => auth()->id(),
            'notes' => $validated['notes'],
            'type' => $validated['type'],
            'require_acknowledgement' => $request->boolean('require_acknowledgement', true),
            'status' => 'on_progress',
        ]);

        foreach ($validated['items'] as $itemData) {
            $detailData = [
                'purchase_request_id' => $pr->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => null,
                'total_price' => null,
                'notes' => $itemData['notes'] ?? null,
            ];

            if ($type === 'Jasa') {
                $detailData['service_description'] = $itemData['service_description'] ?? null;
                $detailData['item_id'] = null;
                $detailData['uom_id'] = null;
                $detailData['is_custom_item'] = false;
                $detailData['custom_item_name'] = null;
            } else {
                $isCustom = !empty($itemData['is_custom_item']);

                if ($isCustom) {
                    // Create placeholder item for non-existing items (Odoo-style workflow)
                    $customName = $itemData['custom_item_name'] ?? 'Unnamed Item';
                    $customType = $itemData['custom_item_type'] ?? 'C'; // Use provided type

                    // Generate proper sequential code matching ItemController logic
                    $lastItem = Item::where('item_type', $customType)->orderBy('id', 'desc')->first();
                    if ($lastItem && preg_match('/' . $customType . '-(\d+)/', $lastItem->code, $matches)) {
                        $nextNumber = intval($matches[1]) + 1;
                    } else {
                        $nextNumber = Item::where('item_type', $customType)->count() + 1;
                    }
                    $itemCode = $customType . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                    // Create incomplete item that needs to be completed before Bon In
                    $placeholderItem = Item::create([
                        'code' => $itemCode,
                        'name' => $customName,
                        'item_type' => $customType,
                        'smallest_uom_id' => 1, // Placeholder - must be updated
                        'is_active' => true,
                        'is_complete' => false, // Mark as incomplete
                        'description' => 'Created from PPB - Complete item data before Bon In',
                    ]);

                    $detailData['is_custom_item'] = false; // Now it's a real item
                    $detailData['custom_item_name'] = null;
                    $detailData['item_id'] = $placeholderItem->id; // Link to placeholder
                    $detailData['uom_id'] = $itemData['uom_id'] ?? null;
                } else {
                    $detailData['is_custom_item'] = false;
                    $detailData['custom_item_name'] = null;
                    $detailData['item_id'] = $itemData['item_id'] ?? null;
                    $detailData['uom_id'] = $itemData['uom_id'] ?? null;
                }

                $detailData['service_description'] = null;
            }

            PurchaseRequestDetail::create($detailData);
        }

        NotificationService::sendToRole(
            ['manager', 'director', 'admin', 'super_admin'],
            'pr_submitted',
            'New PR Submitted',
            "PR {$pr->pr_number} submitted by " . auth()->user()->name . ".",
            route('purchase_requests.show', $pr),
            auth()->id()
        );

        return redirect()->route('purchase_requests.index')->with('success', 'PPB/PPJ created successfully!');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'requestor',
            'deptHeadApprover',
            'gmApprover',
            'details.item',
            'details.uom',
            'purchaseOrders'
        ]);
        return view('purchase_requests.show', compact('purchaseRequest'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['staff', 'warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only staff/warehouse can edit PPB/PPJ.');
        }
        if ($purchaseRequest->status !== 'on_progress') {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only on progress PPB/PPJ can be edited.');
        }

        $purchaseRequest->load('details');
        $items = Item::where('is_active', true)->with(['itemUoms.uom'])->get();
        $uoms = UOM::orderBy('name')->get();
        return view('purchase_requests.edit', compact('purchaseRequest', 'items', 'uoms'));
    }

    public function approve(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['manager'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only department head can approve PPB/PPJ.');
        }

        // Prevent self-approval
        if ($purchaseRequest->requested_by == auth()->id()) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'You cannot approve your own PPB/PPJ.');
        }

        if (!in_array($purchaseRequest->status, ['on_progress'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only on progress PPB/PPJ can be approved.');
        }

        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before approving a PPB/PPJ. Go to your profile.');
        }

        $purchaseRequest->update([
            'status' => 'dept_head_approved',
            'dept_head_by' => auth()->id(),
            'dept_head_at' => now(),
        ]);

        // Notify the requester
        NotificationService::send(
            $purchaseRequest->requested_by,
            'pr_dept_approved',
            'PR Approved by Dept Head',
            "Your PR {$purchaseRequest->pr_number} has been approved by the department head.",
            route('purchase_requests.show', $purchaseRequest)
        );

        // Notify next approver or purchasing
        if ($purchaseRequest->require_acknowledgement) {
            NotificationService::sendToRole(
                ['director', 'admin', 'super_admin'],
                'pr_needs_gm',
                'PR Needs GM Approval',
                "PR {$purchaseRequest->pr_number} is waiting for your GM/Direksi approval.",
                route('purchase_requests.show', $purchaseRequest),
                auth()->id()
            );
        } else {
            NotificationService::sendToRole(
                ['purchasing'],
                'pr_ready_for_po',
                'PR Ready for Purchase Order',
                "PR {$purchaseRequest->pr_number} is approved and ready for PO creation.",
                route('purchase_requests.show', $purchaseRequest)
            );
        }

        return redirect()->route('purchase_requests.show', $purchaseRequest)
            ->with('success', 'PPB/PPJ approved by department head.');
    }

    public function gmApprove(PurchaseRequest $purchaseRequest)
    {
        if (!$purchaseRequest->require_acknowledgement) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'GM/Direksi approval is not required for this PPB/PPJ.');
        }

        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['director'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only GM/Direksi can approve PPB/PPJ at this step.');
        }

        if ($purchaseRequest->status !== 'dept_head_approved') {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only department head approved PPB/PPJ can be approved by GM/Direksi.');
        }

        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before approving a PPB/PPJ. Go to your profile.');
        }

        $purchaseRequest->update([
            'status' => 'gm_approved',
            'gm_by' => auth()->id(),
            'gm_at' => now(),
        ]);

        // Notify requester
        NotificationService::send(
            $purchaseRequest->requested_by,
            'pr_gm_approved',
            'PR Approved by GM/Direksi',
            "Your PR {$purchaseRequest->pr_number} has been fully approved by GM/Direksi.",
            route('purchase_requests.show', $purchaseRequest)
        );

        // Notify purchasing
        NotificationService::sendToRole(
            ['purchasing'],
            'pr_ready_for_po',
            'PR Ready for Purchase Order',
            "PR {$purchaseRequest->pr_number} is fully approved and ready for PO creation.",
            route('purchase_requests.show', $purchaseRequest)
        );

        return redirect()->route('purchase_requests.show', $purchaseRequest)
            ->with('success', 'PPB/PPJ approved by GM/Direksi.');
    }

    public function receivedByPurchasing(PurchaseRequest $purchaseRequest)
    {
        // Check if user has purchasing or admin role
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['purchasing'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only purchasing staff can mark as received.');
        }

        // Check if PPB/PPJ is ready (either acknowledged if required, or approved if not required)
        if ($purchaseRequest->require_acknowledgement) {
            if ($purchaseRequest->status !== 'gm_approved') {
                return redirect()->route('purchase_requests.show', $purchaseRequest)
                    ->with('error', 'PPB/PPJ must be approved by GM/Direksi before marking as received.');
            }
        } else {
            if ($purchaseRequest->status !== 'dept_head_approved') {
                return redirect()->route('purchase_requests.show', $purchaseRequest)
                    ->with('error', 'PPB/PPJ must be approved by department head before marking as received.');
            }
        }

        // Check if user has signature
        $user = auth()->user();
        if (!$user->signature_path) {
            return redirect()->route('users.profile')
                ->with('error', 'Please upload your signature before proceeding. Go to your profile.');
        }

        $purchaseRequest->update([
            'purchasing_received_by' => auth()->id(),
            'purchasing_received_at' => now(),
            'status' => 'completed',
        ]);

        // Notify requester that their PR is now complete
        NotificationService::send(
            $purchaseRequest->requested_by,
            'pr_completed',
            'PR Completed',
            "Your PR {$purchaseRequest->pr_number} has been received by purchasing and is now complete.",
            route('purchase_requests.show', $purchaseRequest)
        );

        return redirect()->route('purchase_requests.show', $purchaseRequest)
            ->with('success', 'PPB/PPJ marked as received successfully!');
    }

    public function reject(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['manager', 'director'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only department head or GM/Direksi can reject PPB/PPJ.');
        }

        if ($purchaseRequest->status === 'on_progress' && !$user->hasAnyRole(['manager'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only department head can reject on progress PPB/PPJ.');
        }

        if ($purchaseRequest->status === 'dept_head_approved' && !$user->hasAnyRole(['director'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only GM/Direksi can reject at this step.');
        }

        if (!in_array($purchaseRequest->status, ['on_progress', 'dept_head_approved'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only on progress or department head approved PPB/PPJ can be rejected.');
        }

        $purchaseRequest->update(['status' => 'rejected']);

        // Notify the requester
        NotificationService::send(
            $purchaseRequest->requested_by,
            'pr_rejected',
            'PR Rejected',
            "Your PR {$purchaseRequest->pr_number} has been rejected by " . auth()->user()->name . ".",
            route('purchase_requests.show', $purchaseRequest)
        );

        return redirect()->route('purchase_requests.show', $purchaseRequest)
            ->with('success', 'PPB/PPJ rejected.');
    }

    public function cancel(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'User not authenticated.');
        }

        if (!in_array($purchaseRequest->status, ['on_progress', 'dept_head_approved', 'gm_approved'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only on progress, department head approved, or GM approved PPB/PPJ can be cancelled.');
        }

        if (!$this->canCancelPurchaseRequest($user, $purchaseRequest)) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'You are not allowed to cancel this PPB/PPJ.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $purchaseRequest->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return redirect()->route('purchase_requests.show', $purchaseRequest)
            ->with('success', 'PPB/PPJ cancelled successfully.');
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['staff', 'warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only staff/warehouse can edit PPB/PPJ.');
        }
        if ($purchaseRequest->status !== 'on_progress') {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only on progress PPB/PPJ can be edited.');
        }

        // Conditional validation based on type
        $type = $request->input('type');
        $baseRules = [
            'request_date' => 'required|date',
            'type' => 'required|in:Jasa,Barang',
            'notes' => 'nullable|string',
            'require_acknowledgement' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ];

        // Add type-specific rules
        if ($type === 'Jasa') {
            $baseRules['items.*.service_description'] = 'required|string|min:3';
            $baseRules['items.*.item_id'] = 'nullable|exists:items,id';
            $baseRules['items.*.uom_id'] = 'nullable|exists:uoms,id';
        } else {
            // Barang (Items)
            $baseRules['items.*.is_custom_item'] = 'nullable|boolean';
            $baseRules['items.*.item_id'] = 'nullable|exists:items,id';
            $baseRules['items.*.uom_id'] = 'required|exists:uoms,id';
            $baseRules['items.*.custom_item_name'] = 'nullable|string|min:2';
            $baseRules['items.*.custom_item_type'] = 'nullable|in:A,B,C,E,T,TE';
            $baseRules['items.*.service_description'] = 'nullable|string';
        }

        $validated = $request->validate($baseRules);

        // Manual validation for custom/existing item logic (Barang)
        if ($type === 'Barang') {
            foreach ($validated['items'] as $i => $itemData) {
                $isCustom = !empty($itemData['is_custom_item']);
                if (!$isCustom && empty($itemData['item_id'])) {
                    return back()->withErrors(["items.$i.item_id" => 'Please select an existing item.'])->withInput();
                }
                if ($isCustom && empty($itemData['custom_item_name'])) {
                    return back()->withErrors(["items.$i.custom_item_name" => 'Item name is required for new items.'])->withInput();
                }
            }
        }

        $purchaseRequest->update([
            'request_date' => $validated['request_date'],
            'notes' => $validated['notes'],
            'type' => $validated['type'],
            'require_acknowledgement' => $request->boolean('require_acknowledgement', true),
        ]);

        // Delete old details and create new
        $purchaseRequest->details()->delete();

        foreach ($validated['items'] as $itemData) {
            $detailData = [
                'purchase_request_id' => $purchaseRequest->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => null,
                'total_price' => null,
                'notes' => $itemData['notes'] ?? null,
            ];

            if ($type === 'Jasa') {
                $detailData['service_description'] = $itemData['service_description'] ?? null;
                $detailData['item_id'] = null;
                $detailData['uom_id'] = null;
                $detailData['is_custom_item'] = false;
                $detailData['custom_item_name'] = null;
            } else {
                $isCustom = !empty($itemData['is_custom_item']);

                if ($isCustom) {
                    // Create placeholder item for non-existing items (Odoo-style workflow)
                    $customName = $itemData['custom_item_name'] ?? 'Unnamed Item';
                    $customType = $itemData['custom_item_type'] ?? 'C'; // Use provided type

                    // Generate proper sequential code matching ItemController logic
                    $lastItem = Item::where('item_type', $customType)->orderBy('id', 'desc')->first();
                    if ($lastItem && preg_match('/' . $customType . '-(\d+)/', $lastItem->code, $matches)) {
                        $nextNumber = intval($matches[1]) + 1;
                    } else {
                        $nextNumber = Item::where('item_type', $customType)->count() + 1;
                    }
                    $itemCode = $customType . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                    // Create incomplete item that needs to be completed before Bon In
                    $placeholderItem = Item::create([
                        'code' => $itemCode,
                        'name' => $customName,
                        'item_type' => $customType,
                        'smallest_uom_id' => 1, // Placeholder - must be updated
                        'is_active' => true,
                        'is_complete' => false, // Mark as incomplete
                        'description' => 'Created from PPB - Complete item data before Bon In',
                    ]);

                    $detailData['is_custom_item'] = false; // Now it's a real item
                    $detailData['custom_item_name'] = null;
                    $detailData['item_id'] = $placeholderItem->id; // Link to placeholder
                    $detailData['uom_id'] = $itemData['uom_id'] ?? null;
                } else {
                    $detailData['is_custom_item'] = false;
                    $detailData['custom_item_name'] = null;
                    $detailData['item_id'] = $itemData['item_id'] ?? null;
                    $detailData['uom_id'] = $itemData['uom_id'] ?? null;
                }

                $detailData['service_description'] = null;
            }

            PurchaseRequestDetail::create($detailData);
        }

        return redirect()->route('purchase_requests.show', $purchaseRequest)
            ->with('success', 'PPB/PPJ updated successfully!');
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['manager', 'director'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only department head or GM/Direksi can reject PPB/PPJ.');
        }

        if ($purchaseRequest->status === 'on_progress' && !$user->hasAnyRole(['manager'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only department head can reject on progress PPB/PPJ.');
        }

        if ($purchaseRequest->status === 'dept_head_approved' && !$user->hasAnyRole(['director'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only GM/Direksi can reject at this step.');
        }

        if (!in_array($purchaseRequest->status, ['on_progress', 'dept_head_approved'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only on progress or department head approved PPB/PPJ can be rejected.');
        }
        $purchaseRequest->delete();
        return redirect()->route('purchase_requests.index')->with('success', 'PPB/PPJ deleted.');
    }

    public function print(PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['completed', 'printed'])) {
            return redirect()->route('purchase_requests.show', $purchaseRequest)
                ->with('error', 'Only completed PPB/PPJ can be printed.');
        }

        if ($purchaseRequest->status === 'completed') {
            $purchaseRequest->update(['status' => 'printed']);
        }

        $purchaseRequest->load(['requestor', 'deptHeadApprover', 'gmApprover', 'purchasingReceiver', 'details.item', 'details.uom']);

        $pdf = Pdf::loadView('purchase_requests.print', compact('purchaseRequest'));
        $prefix = $purchaseRequest->type === 'Jasa' ? 'PPJ' : 'PPB';
        AuditLog::logPrint($purchaseRequest, $purchaseRequest->pr_number);
        return $pdf->download($prefix . '-' . $purchaseRequest->pr_number . '.pdf');
    }

    private function canCancelPurchaseRequest(?User $user, PurchaseRequest $purchaseRequest): bool
    {
        if (!$user) {
            return false;
        }

        if (!in_array($purchaseRequest->status, ['on_progress', 'dept_head_approved', 'gm_approved'])) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        if ($purchaseRequest->status === 'on_progress') {
            return $purchaseRequest->requested_by === $user->id || $user->hasAnyRole(['manager', 'director']);
        }

        if ($purchaseRequest->status === 'dept_head_approved') {
            return $user->hasAnyRole(['manager', 'director']);
        }

        if ($purchaseRequest->status === 'gm_approved') {
            return $user->hasAnyRole(['director']);
        }

        return false;
    }
}
