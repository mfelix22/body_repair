<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        $user = auth()->user();
        $canChangeStatus = PermissionHelper::canUpdate('invoices');
        $canModify = PermissionHelper::canUpdate('invoices');

        $invoices = Invoice::with(['customer', 'workOrder', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('invoices.index', compact('invoices', 'canChangeStatus', 'canModify'));
    }

    public function create(Request $request)
    {
        if (!PermissionHelper::canCreate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'create');
        }

        // Show WOs that either have no proforma (no-discount, Finance invoices directly)
        // OR have an approved/no_discount proforma
        $workOrders = WorkOrder::where('status', 'completed')
            ->whereDoesntHave('invoice')
            ->where(function ($q) {
                $q->whereDoesntHave('proformaInvoice')
                  ->orWhereHas('approvedProforma');
            })
            ->with(['customer', 'approvedProforma'])
            ->get();

        // Get pre-selected work order ID from query parameter
        $selectedWorkOrderId = $request->query('work_order_id');

        return view('invoices.create', compact('workOrders', 'selectedWorkOrderId'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'create');
        }

        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id|unique:invoices',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'notes' => 'nullable|string',
        ]);

        // Wrap in a transaction and lock the WO row to prevent simultaneous proforma/invoice creation
        try {
            $invoice = \DB::transaction(function () use ($validated, $request) {
                $workOrder = WorkOrder::with(['bonOut.items', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($validated['work_order_id']);

                // Re-check state after acquiring lock
                if ($workOrder->invoice()->exists()) {
                    throw new \RuntimeException('An Invoice has already been created for this Work Order.');
                }

                // Re-check proforma state: block if a discount proforma is not yet approved
                $proforma = $workOrder->proformaInvoice;
                if ($proforma && in_array($proforma->status, ['pending_approval', 'rejected'])) {
                    throw new \RuntimeException('This Work Order has a proforma with a discount that is not yet approved.');
                }

                return $this->buildAndSaveInvoice($workOrder, $proforma, $validated);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['work_order_id' => $e->getMessage()]);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully!');
    }

    private function buildAndSaveInvoice(WorkOrder $workOrder, $proforma, array $validated): Invoice
    {

        $subtotal = $workOrder->grand_total;
        // Discount comes from the proforma if it exists, otherwise 0
        $discountPercentage = $proforma ? $proforma->discount_percentage : 0;
        $discountAmount     = $proforma ? $proforma->discount_amount : 0;
        $grandTotal = $subtotal - $discountAmount;

        // Calculate COGM — use BonOut actual quantities if available, else fallback to WO demand × avg_cost
        $cogmMaterial = 0.0;

        $bonOut = $workOrder->bonOut;
        if ($bonOut && $bonOut->items->isNotEmpty()) {
            foreach ($bonOut->items as $bonOutItem) {
                $unitCost = (float) ($bonOutItem->unit_cost ?? 0);

                if ($unitCost <= 0) {
                    $stock = \App\Models\Stock::where('item_id', $bonOutItem->item_id)
                        ->where('location', 'default')
                        ->first();

                    if (!$stock || (float) ($stock->avg_cost ?? 0) <= 0) {
                        $stock = \App\Models\Stock::where('item_id', $bonOutItem->item_id)
                            ->where('avg_cost', '>', 0)
                            ->orderByDesc('id')
                            ->first();
                    }

                    $unitCost = (float) ($stock?->avg_cost ?? 0);
                }

                $cogmMaterial += (float) $bonOutItem->actual_quantity * $unitCost;
            }
        } else {
            $workOrder->load('items.item');
            foreach ($workOrder->items as $woItem) {
                $stock = \App\Models\Stock::where('item_id', $woItem->item_id)
                    ->where('location', 'default')
                    ->first();

                if (!$stock || (float) ($stock->avg_cost ?? 0) <= 0) {
                    $stock = \App\Models\Stock::where('item_id', $woItem->item_id)
                        ->where('avg_cost', '>', 0)
                        ->orderByDesc('id')
                        ->first();
                }

                $cogmMaterial += (float) $woItem->demand_quantity * (float) ($stock?->avg_cost ?? 0);
            }
        }
        $cogm = $cogmMaterial;

        // Invoice number format: YYMM/HAS/SEQ (resets monthly)
        $now = now();
        $yyMm = $now->format('y') . str_pad($now->month, 2, '0', STR_PAD_LEFT);
        $monthStart = $now->clone()->startOfMonth();
        $monthEnd = $now->clone()->endOfMonth();
        $countInv = Invoice::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $invoiceNumber = $yyMm . '/HAS/' . str_pad($countInv + 1, 3, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'invoice_number'      => $invoiceNumber,
            'work_order_id'       => $validated['work_order_id'],
            'customer_id'         => $workOrder->customer_id,
            'invoice_date'        => $validated['invoice_date'],
            'due_date'            => $validated['due_date'],
            'subtotal'            => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount'     => $discountAmount,
            'grand_total'         => $grandTotal,
            'cogm_material'       => round($cogmMaterial, 2),
            'cogm_labor'          => 0,
            'cogm'                => round($cogm, 2),
            'status'              => 'on_progress',
            'notes'               => $validated['notes'],
            'created_by'          => auth()->id(),
        ]);

        $workOrder->update(['status' => 'invoiced']);

        return $invoice;
    }

    public function show(Invoice $invoice)
    {
        if (!PermissionHelper::canView('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        $invoice->load([
            'customer',
            'workOrder.items.item.smallestUom',
            'workOrder.labors',
            'workOrder.bonOuts.items.item.smallestUom',
            'creator',
        ]);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if (!PermissionHelper::canUpdate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'update');
        }

        if ($invoice->status !== 'on_progress') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only on progress invoices can be edited.');
        }

        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (!PermissionHelper::canUpdate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'update');
        }

        if ($invoice->status !== 'on_progress') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only on progress invoices can be edited.');
        }

        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'discount_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $discountAmount = $validated['discount_amount']; // Already calculated from percentage in frontend
        $grandTotal = $invoice->subtotal - $discountAmount;

        $invoice->update([
            'invoice_date'        => $validated['invoice_date'],
            'due_date'            => $validated['due_date'],
            'discount_percentage' => $validated['discount_percentage'],
            'discount_amount'     => $discountAmount,
            'grand_total'         => $grandTotal,
            'notes'               => $validated['notes'],
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully!');
    }

    public function print(Invoice $invoice)
    {
        if (!PermissionHelper::canView('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        $invoice->load(['customer', 'workOrder.labors', 'creator']);
        AuditLog::logPrint($invoice, $invoice->invoice_number);
        return view('invoices.print', compact('invoice'));
    }

    public function cogsReport(Invoice $invoice)
    {
        if (!PermissionHelper::canViewCOGS()) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        $invoice->load([
            'customer',
            'workOrder.items.item.smallestUom',
            'workOrder.labors',
            'workOrder.bonOuts.items.item.smallestUom',
            'creator',
        ]);
        return view('invoices.cogs_report', compact('invoice'));
    }

    public function markAsPaid(Invoice $invoice)
    {
        if (!PermissionHelper::canUpdate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'update');
        }

        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice)->with('info', 'Invoice is already paid.');
        }

        if (in_array($invoice->status, ['on_progress', 'sent', 'partial'])) {
            $invoice->update(['status' => 'paid']);
            return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice marked as paid.');
        }

        return redirect()->route('invoices.show', $invoice)->with('error', 'Invalid status transition.');
    }

    public function destroy(Invoice $invoice)
    {
        if (!PermissionHelper::canDelete('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'delete');
        }

        if ($invoice->status !== 'on_progress') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only on progress invoices can be deleted.');
        }

        $workOrder = $invoice->workOrder;
        $workOrder->update(['status' => 'completed']);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }
}
