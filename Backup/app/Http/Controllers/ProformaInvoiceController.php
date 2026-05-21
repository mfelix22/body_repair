<?php

namespace App\Http\Controllers;

use App\Models\ProformaDiscountLine;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceController extends Controller
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function approverCandidates(): array
    {
        $all = User::whereRaw(
            "FIND_IN_SET('accounting', REPLACE(role,'|',','))
              OR FIND_IN_SET('manager', REPLACE(role,'|',','))
              OR FIND_IN_SET('director', REPLACE(role,'|',','))"
        )->orderBy('name')->get();

        $mgrAcc = User::whereRaw(
            "FIND_IN_SET('accounting', REPLACE(role,'|',','))
              OR FIND_IN_SET('manager', REPLACE(role,'|',','))"
        )->orderBy('name')->get();

        $directors = User::whereRaw(
            "FIND_IN_SET('director', REPLACE(role,'|',','))"
        )->orderBy('name')->get();

        return compact('all', 'mgrAcc', 'directors');
    }

    /**
     * Build a keyed array of discountable content for each WO.
     * Passed to the view as JSON so JS can render per-line cards.
     */
    private function buildWoDetails($workOrders): array
    {
        $details = [];
        foreach ($workOrders as $wo) {
            $package = null;
            if ($wo->paket_name && (float) $wo->paket_grand_total > 0) {
                $package = [
                    'description'    => $wo->paket_name,
                    'original_price' => (float) $wo->paket_grand_total,
                ];
            }

            $extraItems = [];
            foreach ($wo->items->where('unit_price', '>', 0) as $i) {
                $itemCode = optional($i->item)->code ?? '';
                $itemName = optional($i->item)->name ?? '';
                $uom      = optional(optional($i->item)->smallestUom)->abbreviation ?? '';
                $label    = trim($itemCode . ' — ' . $itemName . ($uom ? ' (' . $uom . ')' : ''));
                $extraItems[] = [
                    'target_id'      => $i->id,
                    'description'    => $label,
                    'original_price' => (float) $i->total_price,
                ];
            }

            $extraLabors = [];
            foreach ($wo->labors->where('total_price', '>', 0)->whereNotNull('labor_id') as $l) {
                $extraLabors[] = [
                    'target_id'      => $l->id,
                    'description'    => $l->description ?? 'Labor',
                    'original_price' => (float) $l->total_price,
                ];
            }

            $details[$wo->id] = [
                'id'           => $wo->id,
                'subtotal'     => (float) $wo->grand_total,
                'customer'     => optional($wo->customer)->name ?? '',
                'package'      => $package,
                'extra_items'  => array_values($extraItems),
                'extra_labors' => array_values($extraLabors),
            ];
        }
        return $details;
    }

    /**
     * Shared per-line approver role validation.
     * Returns an errors array (field => message) or empty array if valid.
     */
    private function validateLineApprovers(array $lines): array
    {
        $errors = [];
        foreach ($lines as $idx => $line) {
            $pct = (float) ($line['discount_percentage'] ?? 0);
            $a1  = User::find($line['approver1_id'] ?? null);
            $a2  = User::find($line['approver2_id'] ?? null);

            if ($pct < 20) {
                // 3 approvers from Mgr/Acc/Director pool, all distinct
                $a3Id = $line['approver3_id'] ?? null;
                if (empty($a3Id)) {
                    $errors["lines.{$idx}.approver3_id"] = 'Approver 3 is required for discounts under 20%.';
                    continue;
                }
                $a3 = User::find($a3Id);
                foreach ([[$a1, "lines.{$idx}.approver1_id"], [$a2, "lines.{$idx}.approver2_id"], [$a3, "lines.{$idx}.approver3_id"]] as [$u, $field]) {
                    if (!$u || !$u->hasAnyRole(['accounting', 'manager', 'director'])) {
                        $errors[$field] = 'Approver must be Accounting, Manager, or Director.';
                    }
                }
                $ids = [(int) $line['approver1_id'], (int) $line['approver2_id'], (int) $a3Id];
                if (count($ids) !== count(array_unique($ids))) {
                    $errors["lines.{$idx}.approver2_id"] = 'All 3 approvers must be different people.';
                }
            } else {
                // 2 approvers: approver1 = Mgr/Acc, approver2 = Director (sequential)
                if (!$a1 || !$a1->hasAnyRole(['accounting', 'manager'])) {
                    $errors["lines.{$idx}.approver1_id"] = 'Approver 1 must be Accounting or Manager for discounts ≥ 20%.';
                }
                if (!$a2 || !$a2->hasAnyRole(['director'])) {
                    $errors["lines.{$idx}.approver2_id"] = 'Approver 2 must be a Director for discounts ≥ 20%.';
                }
            }
        }
        return $errors;
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Notify all first-stage approvers on a newly submitted/re-submitted proforma.
     * For < 20% lines (approvals_required=1): notify all 3 approvers (any can act).
     * For >= 20% lines (approvals_required=2): notify only approver1 (Mgr/Acc) now;
     *   approver2 (Director) is notified once stage 1 is done.
     */
    private function notifyApproversOnSubmit(ProformaInvoice $proforma): void
    {
        $url      = route('proforma_invoices.show', $proforma);
        $title    = 'Proforma Approval Needed';
        $message  = "Proforma {$proforma->proforma_number} needs your approval.";
        $notified = [];

        foreach ($proforma->discountLines as $dl) {
            $candidates = $dl->approvals_required === 1
                ? array_filter([$dl->approver1_id, $dl->approver2_id, $dl->approver3_id])
                : [$dl->approver1_id]; // sequential: only stage-1 approver notified now

            foreach ($candidates as $uid) {
                if ($uid && !in_array($uid, $notified)) {
                    NotificationService::send($uid, 'pf_needs_approval', $title, $message, $url);
                    $notified[] = $uid;
                }
            }
        }
    }

    public function index()
    {
        $user = auth()->user();

        $proformas = ProformaInvoice::with(['workOrder.customer', 'creator', 'discountLines'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($proformas as $pf) {
            $pf->pendingMyApproval = $pf->isPendingMyApproval($user->id);
        }

        return view('proforma_invoices.index', compact('proformas'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['service_advisor', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard')->with('error', 'Only Service Advisors can create Proforma Invoices.');
        }

        $workOrders = WorkOrder::where('status', 'completed')
            ->whereDoesntHave('invoice')
            ->whereDoesntHave('proformaInvoice')
            ->with([
                'customer',
                'items'  => fn($q) => $q->where('unit_price', '>', 0)->with('item.smallestUom'),
                'labors' => fn($q) => $q->where('total_price', '>', 0)->whereNotNull('labor_id'),
            ])
            ->get();

        $woDetails           = $this->buildWoDetails($workOrders);
        $selectedWorkOrderId = $request->query('work_order_id');
        $candidates          = $this->approverCandidates();

        return view('proforma_invoices.create', [
            'workOrders'                  => $workOrders,
            'woDetails'                   => $woDetails,
            'selectedWorkOrderId'         => $selectedWorkOrderId,
            'approverCandidates'          => $candidates['all'],
            'managerAccountingCandidates' => $candidates['mgrAcc'],
            'directorCandidates'          => $candidates['directors'],
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['service_advisor', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard')->with('error', 'Only Service Advisors can create Proforma Invoices.');
        }

        $validated = $request->validate([
            'work_order_id'               => 'required|exists:work_orders,id|unique:proforma_invoices',
            'notes'                       => 'nullable|string|max:1000',
            'voucher_code'                => 'nullable|string|max:100',
            'voucher_amount'              => 'nullable|numeric|min:0.01',
            'lines'                       => 'nullable|array',
            'lines.*.target_type'         => 'required_with:lines|in:package,extra_item,extra_labor',
            'lines.*.target_id'           => 'nullable|integer',
            'lines.*.description'         => 'required_with:lines|string|max:300',
            'lines.*.original_price'      => 'required_with:lines|numeric|min:0',
            'lines.*.discount_percentage' => 'required_with:lines|numeric|min:0.01|max:100',
            'lines.*.approver1_id'        => 'required_with:lines|exists:users,id',
            'lines.*.approver2_id'        => 'required_with:lines|exists:users,id',
            'lines.*.approver3_id'        => 'nullable|exists:users,id',
        ]);

        $hasLines   = !empty($validated['lines']);
        $hasVoucher = !empty(trim($validated['voucher_code'] ?? '')) && !empty($validated['voucher_amount']);

        if (!$hasLines && !$hasVoucher) {
            return back()->withInput()->withErrors(['lines' => 'You must submit at least one discount line or a voucher.']);
        }
        if ($hasLines && $hasVoucher) {
            return back()->withInput()->withErrors(['voucher_code' => 'Choose either % discount lines or a voucher — not both.']);
        }

        if ($hasLines) {
            $lineErrors = $this->validateLineApprovers($validated['lines']);
            if (!empty($lineErrors)) {
                return back()->withInput()->withErrors($lineErrors);
            }
        }

        // Check voucher code not already used on another proforma
        if ($hasVoucher) {
            $voucherCode = strtoupper(trim($validated['voucher_code']));
            if (ProformaInvoice::where('voucher_code', $voucherCode)->exists()) {
                return back()->withInput()->withErrors(['voucher_code' => 'Voucher code "' . $voucherCode . '" has already been used on another Proforma Invoice.']);
            }
        }

        try {
            $proforma = DB::transaction(function () use ($validated, $user, $hasVoucher, $hasLines) {
                $wo = WorkOrder::lockForUpdate()->findOrFail($validated['work_order_id']);

                if ($wo->proformaInvoice()->exists()) {
                    throw new \RuntimeException('A Proforma Invoice already exists for this Work Order.');
                }
                if ($wo->invoice()->exists()) {
                    throw new \RuntimeException('An Invoice already exists for this Work Order — no Proforma needed.');
                }

                $now   = now();
                $yyMm  = $now->format('y') . str_pad($now->month, 2, '0', STR_PAD_LEFT);
                $count = ProformaInvoice::whereBetween('created_at', [
                    $now->clone()->startOfMonth(),
                    $now->clone()->endOfMonth(),
                ])->count();
                $proformaNumber = 'PF-' . $yyMm . '/' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

                // --- Voucher path ---
                if ($hasVoucher) {
                    $voucherCode = strtoupper(trim($validated['voucher_code']));
                    $voucherAmt  = (float) $validated['voucher_amount'];

                    $proforma = ProformaInvoice::create([
                        'proforma_number'     => $proformaNumber,
                        'work_order_id'       => $validated['work_order_id'],
                        'created_by'          => $user->id,
                        'subtotal'            => $wo->grand_total,
                        'discount_percentage' => 0,
                        'discount_amount'     => 0,
                        'total'               => $wo->grand_total - $voucherAmt,
                        'status'              => 'approved',
                        'approvals_required'  => 0,
                        'notes'               => $validated['notes'] ?? null,
                        'voucher_code'        => $voucherCode,
                        'voucher_amount'      => $voucherAmt,
                    ]);

                    return $proforma;
                }

                // --- Lines path ---
                $proforma = ProformaInvoice::create([
                    'proforma_number'     => $proformaNumber,
                    'work_order_id'       => $validated['work_order_id'],
                    'created_by'          => $user->id,
                    'subtotal'            => $wo->grand_total,
                    'discount_percentage' => 0,
                    'discount_amount'     => 0,
                    'total'               => $wo->grand_total,
                    'status'              => 'pending_approval',
                    'approvals_required'  => 0,
                    'notes'               => $validated['notes'] ?? null,
                ]);

                foreach ($validated['lines'] as $line) {
                    $pct         = (float) $line['discount_percentage'];
                    $origPrice   = (float) $line['original_price'];
                    $discountAmt = round($origPrice * $pct / 100, 2);

                    ProformaDiscountLine::create([
                        'proforma_invoice_id' => $proforma->id,
                        'target_type'         => $line['target_type'],
                        'target_id'           => $line['target_id'] ?? null,
                        'description'         => $line['description'],
                        'original_price'      => $origPrice,
                        'discount_percentage' => $pct,
                        'discount_amount'     => $discountAmt,
                        'final_price'         => $origPrice - $discountAmt,
                        'status'              => 'pending_approval',
                        'approvals_required'  => $pct < 20 ? 1 : 2,
                        'approver1_id'        => $line['approver1_id'],
                        'approver2_id'        => $line['approver2_id'],
                        'approver3_id'        => $pct < 20 ? ($line['approver3_id'] ?? null) : null,
                    ]);
                }

                $proforma->load('discountLines');
                $proforma->recomputeFromLines();

                return $proforma;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['work_order_id' => $e->getMessage()]);
        }

        $msg = $hasVoucher
            ? 'Proforma Invoice ' . $proforma->proforma_number . ' created with voucher — approved.'
            : 'Proforma Invoice ' . $proforma->proforma_number . ' created and sent for approval.';

        // Notify approvers (lines path only — voucher path is auto-approved, no approvers)
        if (!$hasVoucher) {
            $proforma->load('discountLines');
            $this->notifyApproversOnSubmit($proforma);
        }

        return redirect()->route('proforma_invoices.show', $proforma)->with('success', $msg);
    }

    public function edit(ProformaInvoice $proformaInvoice)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['service_advisor', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard')->with('error', 'Only Service Advisors can edit Proforma Invoices.');
        }

        if ($proformaInvoice->workOrder->invoice) {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'Cannot edit: an Invoice has already been created for this Work Order.');
        }

        if ($proformaInvoice->discountLines()->exists()) {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'Cannot edit a proforma that already has discount lines submitted for approval.');
        }

        $proformaInvoice->load([
            'workOrder.customer',
            'workOrder.items'  => fn($q) => $q->where('unit_price', '>', 0)->with('item.smallestUom'),
            'workOrder.labors' => fn($q) => $q->where('total_price', '>', 0)->whereNotNull('labor_id'),
        ]);

        $woDetails  = $this->buildWoDetails(collect([$proformaInvoice->workOrder]));
        $candidates = $this->approverCandidates();

        return view('proforma_invoices.edit', [
            'proformaInvoice'             => $proformaInvoice,
            'woDetails'                   => $woDetails,
            'approverCandidates'          => $candidates['all'],
            'managerAccountingCandidates' => $candidates['mgrAcc'],
            'directorCandidates'          => $candidates['directors'],
        ]);
    }

    public function update(Request $request, ProformaInvoice $proformaInvoice)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['service_advisor', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard')->with('error', 'Only Service Advisors can edit Proforma Invoices.');
        }

        if ($proformaInvoice->workOrder->invoice) {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'Cannot edit: an Invoice has already been created for this Work Order.');
        }

        if ($proformaInvoice->discountLines()->exists()) {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'Cannot edit a proforma that already has discount lines submitted for approval.');
        }

        $validated = $request->validate([
            'notes'                       => 'nullable|string|max:1000',
            'voucher_code'                => 'nullable|string|max:100',
            'voucher_amount'              => 'nullable|numeric|min:0.01',
            'lines'                       => 'nullable|array',
            'lines.*.target_type'         => 'required_with:lines|in:package,extra_item,extra_labor',
            'lines.*.target_id'           => 'nullable|integer',
            'lines.*.description'         => 'required_with:lines|string|max:300',
            'lines.*.original_price'      => 'required_with:lines|numeric|min:0',
            'lines.*.discount_percentage' => 'required_with:lines|numeric|min:0.01|max:100',
            'lines.*.approver1_id'        => 'required_with:lines|exists:users,id',
            'lines.*.approver2_id'        => 'required_with:lines|exists:users,id',
            'lines.*.approver3_id'        => 'nullable|exists:users,id',
        ]);

        $hasLines   = !empty($validated['lines']);
        $hasVoucher = !empty(trim($validated['voucher_code'] ?? '')) && !empty($validated['voucher_amount']);

        if (!$hasLines && !$hasVoucher) {
            return back()->withInput()->withErrors(['lines' => 'You must submit at least one discount line or a voucher.']);
        }
        if ($hasLines && $hasVoucher) {
            return back()->withInput()->withErrors(['voucher_code' => 'Choose either % discount lines or a voucher — not both.']);
        }

        if ($hasLines) {
            $lineErrors = $this->validateLineApprovers($validated['lines']);
            if (!empty($lineErrors)) {
                return back()->withInput()->withErrors($lineErrors);
            }
        }

        // Check voucher code not already used on a DIFFERENT proforma
        if ($hasVoucher) {
            $voucherCode = strtoupper(trim($validated['voucher_code']));
            if (ProformaInvoice::where('voucher_code', $voucherCode)->where('id', '!=', $proformaInvoice->id)->exists()) {
                return back()->withInput()->withErrors(['voucher_code' => 'Voucher code "' . $voucherCode . '" has already been used on another Proforma Invoice.']);
            }
        }

        DB::transaction(function () use ($validated, $proformaInvoice, $hasVoucher) {
            if ($hasVoucher) {
                $voucherCode = strtoupper(trim($validated['voucher_code']));
                $voucherAmt  = (float) $validated['voucher_amount'];

                $proformaInvoice->update([
                    'notes'               => $validated['notes'] ?? null,
                    'discount_percentage' => 0,
                    'discount_amount'     => 0,
                    'total'               => $proformaInvoice->subtotal - $voucherAmt,
                    'status'              => 'approved',
                    'voucher_code'        => $voucherCode,
                    'voucher_amount'      => $voucherAmt,
                ]);
                return;
            }

            // Lines path
            $proformaInvoice->update([
                'notes'               => $validated['notes'] ?? null,
                'discount_percentage' => 0,
                'discount_amount'     => 0,
                'total'               => $proformaInvoice->subtotal,
                'status'              => 'pending_approval',
                'voucher_code'        => null,
                'voucher_amount'      => 0,
            ]);

            foreach ($validated['lines'] as $line) {
                $pct         = (float) $line['discount_percentage'];
                $origPrice   = (float) $line['original_price'];
                $discountAmt = round($origPrice * $pct / 100, 2);

                ProformaDiscountLine::create([
                    'proforma_invoice_id' => $proformaInvoice->id,
                    'target_type'         => $line['target_type'],
                    'target_id'           => $line['target_id'] ?? null,
                    'description'         => $line['description'],
                    'original_price'      => $origPrice,
                    'discount_percentage' => $pct,
                    'discount_amount'     => $discountAmt,
                    'final_price'         => $origPrice - $discountAmt,
                    'status'              => 'pending_approval',
                    'approvals_required'  => $pct < 20 ? 1 : 2,
                    'approver1_id'        => $line['approver1_id'],
                    'approver2_id'        => $line['approver2_id'],
                    'approver3_id'        => $pct < 20 ? ($line['approver3_id'] ?? null) : null,
                ]);
            }

            $proformaInvoice->load('discountLines');
            $proformaInvoice->recomputeFromLines();
        });

        $msg = $hasVoucher
            ? 'Proforma Invoice updated with voucher — approved.'
            : 'Proforma Invoice updated and sent for approval.';

        // Notify approvers (lines path only)
        if (!$hasVoucher) {
            $proformaInvoice->load('discountLines');
            $this->notifyApproversOnSubmit($proformaInvoice);
        }

        return redirect()->route('proforma_invoices.show', $proformaInvoice)->with('success', $msg);
    }

    public function show(ProformaInvoice $proformaInvoice)
    {
        $proformaInvoice->load([
            'workOrder.customer',
            'creator',
            'discountLines.approver1',
            'discountLines.approver2',
            'discountLines.approver3',
        ]);

        $user = auth()->user();
        $pendingMyApproval = $proformaInvoice->isPendingMyApproval($user->id);

        return view('proforma_invoices.show', compact('proformaInvoice', 'pendingMyApproval'));
    }

    public function print(ProformaInvoice $proformaInvoice)
    {
        $proformaInvoice->load([
            'workOrder.customer',
            'workOrder.labors',
            'creator',
            'discountLines',
        ]);
        AuditLog::logPrint($proformaInvoice, $proformaInvoice->proforma_number);
        return view('proforma_invoices.print', compact('proformaInvoice'));
    }

    // -------------------------------------------------------------------------
    // Per-line approval / rejection
    // -------------------------------------------------------------------------

    public function approveLine(Request $request, ProformaInvoice $proformaInvoice, ProformaDiscountLine $line)
    {
        $user = auth()->user();

        if ((int) $line->proforma_invoice_id !== (int) $proformaInvoice->id) {
            abort(404);
        }

        if ($line->status !== 'pending_approval') {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'This discount line is not pending approval.');
        }

        if (!$line->isPendingMyApproval($user->id)) {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'You are not authorised to approve this line at this stage.');
        }

        $now = now();

        if ($line->approvals_required === 1) {
            // Any one of the 3 approvers — first to act wins, line immediately approved
            if ($line->approver1_id == $user->id) {
                $line->approver1_approved_at = $now;
            } elseif ($line->approver2_id == $user->id) {
                $line->approver2_approved_at = $now;
            } elseif ($line->approver3_id == $user->id) {
                $line->approver3_approved_at = $now;
            }
            $line->status = 'approved';
            $line->save();

            $proformaInvoice->load('discountLines');
            $proformaInvoice->recomputeFromLines();

            // Notify creator; if all lines now approved, send the fully-approved notification
            $proformaInvoice->refresh();
            if ($proformaInvoice->status === 'approved') {
                NotificationService::send(
                    $proformaInvoice->created_by,
                    'pf_approved',
                    'Proforma Fully Approved',
                    "Proforma {$proformaInvoice->proforma_number} is now fully approved by all approvers.",
                    route('proforma_invoices.show', $proformaInvoice)
                );
            } else {
                NotificationService::send(
                    $proformaInvoice->created_by,
                    'pf_line_approved',
                    'Proforma Line Approved',
                    "{$user->name} approved a discount line on {$proformaInvoice->proforma_number}.",
                    route('proforma_invoices.show', $proformaInvoice)
                );
            }

            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('success', 'Discount line approved.');
        }

        if ($line->approvals_required === 2) {
            // Sequential: approver1 (Mgr/Acc) must approve first, then approver2 (Director)
            $stage1Done = !is_null($line->approver1_approved_at);

            if (!$stage1Done && $line->approver1_id == $user->id) {
                $line->approver1_approved_at = $now;
                $line->save();

                $proformaInvoice->load('discountLines');
                $proformaInvoice->recomputeFromLines();

                // Notify creator that stage 1 passed
                NotificationService::send(
                    $proformaInvoice->created_by,
                    'pf_line_approved',
                    'Proforma Line — Stage 1 Approved',
                    "{$user->name} approved a discount line on {$proformaInvoice->proforma_number} (awaiting Director).",
                    route('proforma_invoices.show', $proformaInvoice)
                );
                // Notify Director (approver2) that their turn has come
                NotificationService::send(
                    $line->approver2_id,
                    'pf_needs_approval',
                    'Proforma Director Approval Needed',
                    "Stage 1 approved. Proforma {$proformaInvoice->proforma_number} now needs your Director approval.",
                    route('proforma_invoices.show', $proformaInvoice)
                );

                return redirect()->route('proforma_invoices.show', $proformaInvoice)
                    ->with('success', 'Approved (1/2). Waiting for Director approval.');
            }

            if ($stage1Done && $line->approver2_id == $user->id && is_null($line->approver2_approved_at)) {
                $line->approver2_approved_at = $now;
                $line->status = 'approved';
                $line->save();

                $proformaInvoice->load('discountLines');
                $proformaInvoice->recomputeFromLines();

                // Notify creator; send fully-approved if all lines done
                $proformaInvoice->refresh();
                if ($proformaInvoice->status === 'approved') {
                    NotificationService::send(
                        $proformaInvoice->created_by,
                        'pf_approved',
                        'Proforma Fully Approved',
                        "Proforma {$proformaInvoice->proforma_number} is now fully approved by all approvers.",
                        route('proforma_invoices.show', $proformaInvoice)
                    );
                } else {
                    NotificationService::send(
                        $proformaInvoice->created_by,
                        'pf_line_approved',
                        'Proforma Line Fully Approved',
                        "{$user->name} (Director) approved a discount line on {$proformaInvoice->proforma_number}.",
                        route('proforma_invoices.show', $proformaInvoice)
                    );
                }

                return redirect()->route('proforma_invoices.show', $proformaInvoice)
                    ->with('success', 'Discount line fully approved.');
            }
        }

        return redirect()->route('proforma_invoices.show', $proformaInvoice)
            ->with('error', 'You are not authorised to approve this line at this stage.');
    }

    public function rejectLine(Request $request, ProformaInvoice $proformaInvoice, ProformaDiscountLine $line)
    {
        $user = auth()->user();

        if ((int) $line->proforma_invoice_id !== (int) $proformaInvoice->id) {
            abort(404);
        }

        if ($line->status !== 'pending_approval') {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'This discount line is not pending approval.');
        }

        if (!$line->isPendingMyApproval($user->id)) {
            return redirect()->route('proforma_invoices.show', $proformaInvoice)
                ->with('error', 'You are not authorised to reject this line at this stage.');
        }

        $now = now();

        if ($line->approvals_required === 1) {
            if ($line->approver1_id == $user->id) {
                $line->approver1_rejected_at = $now;
            } elseif ($line->approver2_id == $user->id) {
                $line->approver2_rejected_at = $now;
            } elseif ($line->approver3_id == $user->id) {
                $line->approver3_rejected_at = $now;
            }
        } elseif ($line->approvals_required === 2) {
            $stage1Done = !is_null($line->approver1_approved_at);
            if (!$stage1Done && $line->approver1_id == $user->id) {
                $line->approver1_rejected_at = $now;
            } elseif ($stage1Done && $line->approver2_id == $user->id) {
                $line->approver2_rejected_at = $now;
            }
        }

        // Rejected line = no discount applied; reset to full price
        $line->status         = 'rejected';
        $line->discount_amount = 0;
        $line->final_price    = $line->original_price;
        $line->save();

        $proformaInvoice->load('discountLines');
        $proformaInvoice->recomputeFromLines();

        // Notify creator
        NotificationService::send(
            $proformaInvoice->created_by,
            'pf_line_rejected',
            'Proforma Line Rejected',
            "{$user->name} rejected a discount line on {$proformaInvoice->proforma_number}. Full price applies for that item.",
            route('proforma_invoices.show', $proformaInvoice)
        );

        return redirect()->route('proforma_invoices.show', $proformaInvoice)
            ->with('info', 'Discount line rejected. Full price will apply for this item.');
    }
}
