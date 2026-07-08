<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\CreditNote;
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
        $canEdit = $user->hasAnyRole(['admin', 'super_admin']);

        $month = request('month');
        $year  = request('year');
        $query = Invoice::with(['customer', 'workOrder', 'creator']);
        if ($month) {
            $query->whereMonth('invoice_date', (int) $month);
        }
        if ($year) {
            $query->whereYear('invoice_date', (int) $year);
        }
        $invoices = $query->orderBy('invoice_date', 'desc')->get();
        // For filter dropdowns
        $allYears = Invoice::selectRaw('YEAR(invoice_date) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        return view('invoices.index', compact('invoices', 'canChangeStatus', 'canModify', 'canEdit', 'month', 'year', 'allYears'));
    }

    public function create(Request $request)
    {
        if (!PermissionHelper::canCreate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'create');
        }

        // Show WOs that either have no proforma (no-discount, Finance invoices directly)
        // OR have an approved/no_discount proforma
        $workOrders = WorkOrder::where('status', 'completed')
            ->whereDoesntHave('invoice', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
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
            'work_order_id' => ['required', 'exists:work_orders,id', \Illuminate\Validation\Rule::unique('invoices')->where(fn($q) => $q->where('status', '!=', 'cancelled'))],
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'qq' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        // Wrap in a transaction and lock the WO row to prevent simultaneous proforma/invoice creation
        try {
            $invoice = \DB::transaction(function () use ($validated, $request) {
                $workOrder = WorkOrder::with(['bonOut.items', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($validated['work_order_id']);

                // Re-check state after acquiring lock
                if ($workOrder->invoice()->where('status', '!=', 'cancelled')->exists()) {
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
        } catch (\Illuminate\Database\QueryException $e) {
            // Likely a DB-level failure (e.g. enum value not yet in schema on server)
            \Log::error('Invoice creation DB error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'A database error occurred while creating the invoice. Please ensure all server migrations have been run (php artisan migrate --force). Error: ' . $e->getPrevious()?->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully!');
    }

    private function buildAndSaveInvoice(WorkOrder $workOrder, $proforma, array $validated): Invoice
    {

        $subtotal = $workOrder->grand_total;
        // Discount comes from the proforma if it exists, otherwise 0
        // Include both line discounts AND voucher amount in the effective discount
        $voucherAmount      = $proforma ? (float) ($proforma->voucher_amount ?? 0) : 0;
        $discountAmount     = $proforma ? ((float) $proforma->discount_amount + $voucherAmount) : 0;
        $discountPercentage = $subtotal > 0 ? round($discountAmount / $subtotal * 100, 4) : 0;
        $grandTotal = $subtotal - $discountAmount;

        // Calculate COGM — use ALL BonOut actual quantities if available, else fallback to WO demand × avg_cost
        $cogmMaterial = 0.0;

        $bonOuts = $workOrder->bonOuts;
        if ($bonOuts->isNotEmpty()) {
            foreach ($bonOuts as $bonOut) {
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
        $cogmLabor = (float) ($workOrder->labor_total ?? 0);
        $cogm = $cogmMaterial + $cogmLabor;

        // Invoice number format: 4YYMM/HAS/SEQ for WO-based invoices (resets monthly)
        $now = now();
        $yyMm = $now->format('y') . str_pad($now->month, 2, '0', STR_PAD_LEFT);
        $prefix = '4'; // 4 = Work Order invoice, 3 = Sales Order invoice
        $monthStart = $now->clone()->startOfMonth();
        $monthEnd = $now->clone()->endOfMonth();
        $countInv = Invoice::where('invoice_number', 'like', $prefix . $yyMm . '/HAS/%')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
        $invoiceNumber = $prefix . $yyMm . '/HAS/' . str_pad($countInv + 1, 3, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'invoice_number'      => $invoiceNumber,
            'work_order_id'       => $validated['work_order_id'],
            'customer_id'         => $workOrder->billing_customer_id ?? $workOrder->customer_id,
            'invoice_date'        => $validated['invoice_date'],
            'due_date'            => $validated['due_date'],
            'subtotal'            => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount'     => $discountAmount,
            'grand_total'         => $grandTotal,
            'cogm_material'       => round($cogmMaterial, 2),
            'cogm_labor'          => round($cogmLabor, 2),
            'cogm'                => round($cogm, 2),
            'status'              => 'on_progress',
            'notes'               => $validated['notes'],
            'qq'                  => $validated['qq'] ?? null,
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

        $customers = \App\Models\Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('invoices.show', compact('invoice', 'customers'));
    }

    public function edit(Invoice $invoice)
    {
        if (!PermissionHelper::canUpdate('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'update');
        }

        if (!auth()->user()?->hasAnyRole(['admin', 'super_admin'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only admins can edit invoices.');
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
            'qq' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        $discountAmount = $validated['discount_amount'];
        $grandTotal = $invoice->subtotal - $discountAmount;

        $invoice->update([
            'invoice_date'        => $validated['invoice_date'],
            'due_date'            => $validated['due_date'],
            'discount_percentage' => $validated['discount_percentage'],
            'discount_amount'     => $discountAmount,
            'grand_total'         => $grandTotal,
            'qq'                  => $validated['qq'] ?? null,
            'notes'               => $validated['notes'],
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully!');
    }

    public function print(Invoice $invoice)
    {
        if (!PermissionHelper::canView('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        $invoice->load(['customer', 'workOrder.labors.labor', 'workOrder.proformaInvoice.discountLines', 'creator']);
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

    public function cancel(Request $request, Invoice $invoice)
    {
        if (!auth()->user()?->hasAnyRole(['admin', 'super_admin', 'finance'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'You do not have permission to cancel invoices.');
        }

        if ($invoice->status === 'cancelled') {
            return redirect()->route('invoices.show', $invoice)->with('info', 'Invoice is already cancelled.');
        }

        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'A paid invoice cannot be cancelled.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $invoice->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);
        $invoice->workOrder->update(['status' => 'completed']);

        // Auto-create Credit Note
        $invoicePrefix = substr($invoice->invoice_number, 0, 1);
        $cnPrefix = $invoicePrefix === '4' ? '5' : '6'; // 5=WO credit note, 6=SO credit note
        $now = now();
        $yyMm = $now->format('y') . str_pad($now->month, 2, '0', STR_PAD_LEFT);
        $monthStart = $now->clone()->startOfMonth();
        $monthEnd   = $now->clone()->endOfMonth();
        $cnCount = CreditNote::where('credit_note_number', 'like', $cnPrefix . $yyMm . '/HAS/%')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();
        $cnNumber = $cnPrefix . $yyMm . '/HAS/' . str_pad($cnCount + 1, 3, '0', STR_PAD_LEFT);

        CreditNote::create([
            'credit_note_number'  => $cnNumber,
            'invoice_id'          => $invoice->id,
            'work_order_id'       => $invoice->work_order_id,
            'customer_id'         => $invoice->customer_id,
            'qq'                  => $invoice->qq,
            'credit_note_date'    => $now->toDateString(),
            'subtotal'            => $invoice->subtotal,
            'discount_percentage' => $invoice->discount_percentage,
            'discount_amount'     => $invoice->discount_amount,
            'grand_total'         => $invoice->grand_total,
            'notes'               => $invoice->notes,
            'cancellation_reason' => $request->cancellation_reason,
            'created_by'          => auth()->id(),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice cancelled. Work order reverted to completed. Credit note generated.');
    }

    public function changeCustomer(Request $request, Invoice $invoice)
    {
        if (!auth()->user()?->hasAnyRole(['finance', 'admin', 'super_admin'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only Finance can change the customer on an invoice.');
        }

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot change the customer on a paid or cancelled invoice.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $invoice->update(['customer_id' => $request->customer_id]);
        $invoice->workOrder->update(['customer_id' => $request->customer_id]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Customer updated on invoice and work order.');
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
