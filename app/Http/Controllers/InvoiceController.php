<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * @return User|null
     */
    private function currentUser(): ?User
    {
        return Auth::user();
    }

    public function index()
    {
        if (!PermissionHelper::canView('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        $user = $this->currentUser();
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

        // Show WOs that are ready to invoice:
        // - ASURANSI WOs: no Estimasi pending approval (either no Estimasi, or its latest one is approved/rejected/no_discount)
        // - Other account codes: no proforma, or an approved/no_discount proforma
        $workOrders = WorkOrder::where('status', 'completed')
            ->whereDoesntHave('invoice', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->where('account_code', 'ASURANSI')
                    ->whereDoesntHave('estimasis', function ($eq) {
                        $eq->where('status', 'pending_approval');
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('account_code', '!=', 'ASURANSI')
                            ->where(function ($q3) {
                                $q3->whereDoesntHave('proformaInvoice')
                                    ->orWhereHas('approvedProforma');
                            });
                    });
            })
            ->with(['customer', 'approvedProforma', 'activeEstimasi'])
            ->get();

        // Get pre-selected work order ID from query parameter
        $selectedWorkOrderId = $request->query('work_order_id');

        $isFinance = $this->currentUser()->hasAnyRole(['finance', 'admin', 'super_admin']);

        return view('invoices.create', compact('workOrders', 'selectedWorkOrderId', 'isFinance'));
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
            'or_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Wrap in a transaction and lock the WO row to prevent simultaneous proforma/invoice creation
        try {
            $invoice = DB::transaction(function () use ($validated, $request) {
                $workOrder = WorkOrder::with(['bonOut.items', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($validated['work_order_id']);

                // Re-check state after acquiring lock
                if ($workOrder->invoice()->where('status', '!=', 'cancelled')->exists()) {
                    throw new \RuntimeException('An Invoice has already been created for this Work Order.');
                }

                $proforma = null;
                if ($workOrder->usesEstimasiDiscount()) {
                    // Re-check Estimasi state: block if its discount is not yet approved
                    if ($workOrder->estimasis()->where('status', 'pending_approval')->exists()) {
                        throw new \RuntimeException('This Work Order has an Estimasi with a discount that is not yet approved.');
                    }
                } else {
                    // Re-check proforma state: block if a discount proforma is not yet approved
                    $proforma = $workOrder->proformaInvoice;
                    if ($proforma && in_array($proforma->status, ['pending_approval', 'rejected'])) {
                        throw new \RuntimeException('This Work Order has a proforma with a discount that is not yet approved.');
                    }
                }

                $orAmount = (float) ($request->input('or_amount', 0) ?? 0);
                return $this->buildAndSaveInvoice($workOrder, $proforma, $validated, $orAmount);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['work_order_id' => $e->getMessage()]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Likely a DB-level failure (e.g. enum value not yet in schema on server)
            Log::error('Invoice creation DB error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'A database error occurred while creating the invoice. Please ensure all server migrations have been run (php artisan migrate --force). Error: ' . $e->getPrevious()?->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully!');
    }

    private function buildAndSaveInvoice(WorkOrder $workOrder, $proforma, array $validated, float $orAmount = 0): Invoice
    {

        $subtotal = $workOrder->grand_total;

        if ($workOrder->usesEstimasiDiscount()) {
            // ASURANSI Work Orders: discount comes from the approved Estimasi's
            // panel/sparepart percentages instead of the ProformaInvoice flow.
            $discountAmount = $workOrder->estimasiDiscountAmount();
        } else {
            // Discount comes from the proforma if it exists, otherwise 0.
            // Include both line discounts AND voucher amount in the effective discount.
            $voucherAmount  = $proforma ? (float) ($proforma->voucher_amount ?? 0) : 0;
            $discountAmount = $proforma ? ((float) $proforma->discount_amount + $voucherAmount) : 0;
        }
        $discountPercentage = $subtotal > 0 ? round($discountAmount / $subtotal * 100, 4) : 0;
        $baseTotal = $subtotal - $discountAmount - $orAmount;
        $materai   = $baseTotal > 5000000 ? 10000 : 0;
        $grandTotal = $baseTotal + $materai;

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

        $kwitansiNumber = null;
        if ($workOrder->account_code === 'ASURANSI' && $orAmount > 0) {
            $kwitansiNumber = $this->generateKwitansiOrNumber(\Carbon\Carbon::parse($validated['invoice_date']));
        }

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
            'or_amount'           => $workOrder->account_code === 'ASURANSI' ? $orAmount : 0,
            'materai'             => $materai,
            'kwitansi_or_number'  => $kwitansiNumber,
            'created_by'          => Auth::id(),
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
            'workOrder.activeEstimasi',
            'workOrder.proformaInvoice',
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

        if (!$this->currentUser()?->hasAnyRole(['admin', 'super_admin'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only admins can edit invoices.');
        }

        if ($invoice->status !== 'on_progress') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only on progress invoices can be edited.');
        }

        $isFinance = $this->currentUser()->hasAnyRole(['finance', 'admin', 'super_admin']);

        return view('invoices.edit', compact('invoice', 'isFinance'));
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
            'or_amount' => 'nullable|numeric|min:0',
            'qq' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        $discountAmount = $validated['discount_amount'];
        $orAmount = $invoice->workOrder->account_code === 'ASURANSI'
            ? ((float) ($validated['or_amount'] ?? $invoice->or_amount ?? 0))
            : 0;

        $kwitansiNumber = $invoice->kwitansi_or_number;
        if ($orAmount > 0 && !$kwitansiNumber) {
            $kwitansiNumber = $this->generateKwitansiOrNumber(\Carbon\Carbon::parse($validated['invoice_date']));
        }

        $baseTotal = $invoice->subtotal - $discountAmount - $orAmount;
        $materai   = $baseTotal > 5000000 ? 10000 : 0;
        $grandTotal = $baseTotal + $materai;

        $invoice->update([
            'invoice_date'        => $validated['invoice_date'],
            'due_date'            => $validated['due_date'],
            'discount_percentage' => $validated['discount_percentage'],
            'discount_amount'     => $discountAmount,
            'grand_total'         => $grandTotal,
            'materai'             => $materai,
            'or_amount'           => $invoice->workOrder->account_code === 'ASURANSI' ? ((float) ($validated['or_amount'] ?? 0)) : 0,
            'kwitansi_or_number'  => $kwitansiNumber,
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

    public function printKwitansiOr(Invoice $invoice)
    {
        if (!PermissionHelper::canView('invoices')) {
            return PermissionHelper::denyAccess('invoices', 'view');
        }

        if ((float) ($invoice->or_amount ?? 0) <= 0) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'This invoice does not have an OR amount.');
        }

        $invoice->load(['customer', 'workOrder.customer', 'workOrder.billingCustomer', 'workOrder.insurance']);

        $receiptNumber = $invoice->kwitansi_or_number;
        if (!$receiptNumber) {
            $receiptNumber = $this->generateKwitansiOrNumber($invoice->invoice_date);
            $invoice->update(['kwitansi_or_number' => $receiptNumber]);
        }

        return view('invoices.kwitansi_or_print', compact('invoice', 'receiptNumber'));
    }

    private function generateKwitansiOrNumber(\Carbon\Carbon $date): string
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $monthRoman = $romans[$date->month - 1];

        $existing = Invoice::where('kwitansi_or_number', 'like', '%HAS/FIN/' . $monthRoman . '/' . $date->year)
            ->get()
            ->map(fn($inv) => (int) explode('/', $inv->kwitansi_or_number)[0])
            ->max() ?? 0;

        $seq = str_pad($existing + 1, 3, '0', STR_PAD_LEFT);
        return $seq . '/HAS/FIN/' . $monthRoman . '/' . $date->year;
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
            'workOrder.activeEstimasi',
            'workOrder.proformaInvoice',
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
        if (!$this->currentUser()?->hasAnyRole(['admin', 'super_admin', 'finance'])) {
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
            'created_by'          => Auth::id(),
        ]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice cancelled. Work order reverted to completed. Credit note generated.');
    }

    public function changeCustomer(Request $request, Invoice $invoice)
    {
        if (!$this->currentUser()?->hasAnyRole(['finance', 'admin', 'super_admin'])) {
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
