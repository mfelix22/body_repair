<?php

namespace App\Http\Controllers;

use App\Models\Estimasi;
use App\Models\User;
use App\Models\WorkOrder;
use App\Helpers\PermissionHelper;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimasiController extends Controller
{
    /** Returns the single Manager and single Director users used for auto-assignment. */
    private function getApprovers(): array
    {
        $manager  = User::whereRaw("FIND_IN_SET('manager', REPLACE(role,'|',','))")->orderBy('name')->first();
        $director = User::whereRaw("FIND_IN_SET('director', REPLACE(role,'|',','))")->orderBy('name')->first();
        return compact('manager', 'director');
    }

    private function validateApproversExist(float $pct): array
    {
        $errors = [];
        if ($pct <= 0) {
            return $errors;
        }
        $approvers = $this->getApprovers();
        if (!$approvers['manager']) {
            $errors['discount_percentage'] = 'No Manager user found. Please configure a user with the Manager role.';
        }
        if ($pct > 20 && !$approvers['director']) {
            $errors['discount_percentage'] = ($errors['discount_percentage'] ?? '') . ' No Director user found. Please configure a user with the Director role.';
        }
        return $errors;
    }

    private function notifyApproverOnSubmit(Estimasi $estimasi): void
    {
        if (!$estimasi->approver1_id) {
            return;
        }
        NotificationService::send(
            $estimasi->approver1_id,
            'est_needs_approval',
            'Estimasi Approval Needed',
            "Estimasi {$estimasi->estimasi_number} needs your approval.",
            route('estimasis.show', $estimasi)
        );
    }

    public function index()
    {
        $user = auth()->user();

        $estimasis = Estimasi::with(['workOrder.customer', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($estimasis as $est) {
            $est->pendingMyApproval = $est->isPendingMyApproval($user->id);
        }

        return view('estimasis.index', compact('estimasis'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['service_advisor', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard')->with('error', 'Only Service Advisors can create an Estimasi.');
        }

        $workOrderId = $request->query('work_order_id');
        $workOrder = WorkOrder::with('customer', 'items.item')->whereIn('status', ['on_progress', 'in_progress'])->find($workOrderId);

        if (!$workOrder) {
            return redirect()->route('dashboard')->with('error', 'Work Order not found or is not Pending/Working.');
        }

        $sparepartTotal = $workOrder->sparepartTotal();
        $estimasiSubtotal = (float) $workOrder->grand_total + $sparepartTotal;

        return view('estimasis.create', compact('workOrder', 'sparepartTotal', 'estimasiSubtotal'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['service_advisor', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard')->with('error', 'Only Service Advisors can create an Estimasi.');
        }

        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',
            'discount_percentage_panel' => 'required|numeric|min:0|max:100',
            'discount_percentage_sparepart' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $panelPct     = (float) $validated['discount_percentage_panel'];
        $sparepartPct = (float) $validated['discount_percentage_sparepart'];

        try {
            $estimasi = DB::transaction(function () use ($validated, $user, $panelPct, $sparepartPct) {
                $wo = WorkOrder::lockForUpdate()->findOrFail($validated['work_order_id']);

                if (!in_array($wo->status, ['on_progress', 'in_progress'])) {
                    throw new \RuntimeException('Estimasi can only be created while the Work Order is Pending or Working.');
                }

                $seq = $wo->estimasis()->count() + 1;
                $estimasiNumber = $wo->wo_number . '/EST-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

                $panelSubtotal     = (float) $wo->grand_total;
                $sparepartSubtotal = $wo->sparepartTotal();
                $subtotal          = $panelSubtotal + $sparepartSubtotal;

                $panelDiscountAmt     = round($panelSubtotal * $panelPct / 100, 2);
                $sparepartDiscountAmt = round($sparepartSubtotal * $sparepartPct / 100, 2);
                $discountAmt          = $panelDiscountAmt + $sparepartDiscountAmt;
                $total                = $subtotal - $discountAmt;

                // Blended percentage of the overall subtotal — used only to decide
                // the approval tier (single approval flow, 1 or 2 sequential approvers).
                $blendedPct = $subtotal > 0 ? round($discountAmt / $subtotal * 100, 2) : 0;

                $approverErrors = $this->validateApproversExist($blendedPct);
                if (!empty($approverErrors)) {
                    throw new \RuntimeException(reset($approverErrors));
                }

                $baseAttributes = [
                    'estimasi_number'              => $estimasiNumber,
                    'work_order_id'                => $wo->id,
                    'created_by'                   => $user->id,
                    'subtotal'                     => $subtotal,
                    'panel_subtotal'                => $panelSubtotal,
                    'panel_discount_percentage'     => $panelPct,
                    'panel_discount_amount'         => $panelDiscountAmt,
                    'sparepart_subtotal'            => $sparepartSubtotal,
                    'sparepart_discount_percentage' => $sparepartPct,
                    'sparepart_discount_amount'     => $sparepartDiscountAmt,
                    'notes'                         => $validated['notes'] ?? null,
                ];

                if ($blendedPct <= 0) {
                    return Estimasi::create($baseAttributes + [
                        'discount_percentage' => 0,
                        'discount_amount'     => 0,
                        'total'               => $subtotal,
                        'status'              => 'no_discount',
                        'approvals_required'  => 0,
                    ]);
                }

                $approvers = $this->getApprovers();

                return Estimasi::create($baseAttributes + [
                    'discount_percentage' => $blendedPct,
                    'discount_amount'     => $discountAmt,
                    'total'               => $total,
                    'status'              => 'pending_approval',
                    'approvals_required'  => $blendedPct <= 20 ? 1 : 2,
                    'approver1_id'        => $approvers['manager']->id,
                    'approver2_id'        => $blendedPct > 20 ? $approvers['director']->id : null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['work_order_id' => $e->getMessage()]);
        }

        if ($estimasi->status === 'pending_approval') {
            $this->notifyApproverOnSubmit($estimasi);
        }

        $msg = $estimasi->status === 'no_discount'
            ? 'Estimasi ' . $estimasi->estimasi_number . ' created (no discount).'
            : 'Estimasi ' . $estimasi->estimasi_number . ' created and sent for approval.';

        return redirect()->route('estimasis.show', $estimasi)->with('success', $msg);
    }

    public function show(Estimasi $estimasi)
    {
        $estimasi->load(['workOrder.customer', 'workOrder.items.item', 'creator', 'approver1', 'approver2']);

        $user = auth()->user();
        $pendingMyApproval = $estimasi->isPendingMyApproval($user->id);

        return view('estimasis.show', compact('estimasi', 'pendingMyApproval'));
    }

    public function print(Estimasi $estimasi)
    {
        if (!PermissionHelper::canPrint('estimasis')) {
            return PermissionHelper::denyAccess('estimasis', 'view');
        }

        $estimasi->load([
            'workOrder.customer',
            'workOrder.labors.labor',
            'workOrder.panelLabors.panel',
            'workOrder.items.item',
            'creator',
        ]);

        return view('estimasis.print', compact('estimasi'));
    }

    public function approve(Request $request, Estimasi $estimasi)
    {
        $user = auth()->user();

        if ($estimasi->status !== 'pending_approval') {
            return redirect()->route('estimasis.show', $estimasi)->with('error', 'This Estimasi is not pending approval.');
        }

        if (!$estimasi->isPendingMyApproval($user->id)) {
            return redirect()->route('estimasis.show', $estimasi)->with('error', 'You are not authorised to approve this Estimasi at this stage.');
        }

        $now = now();

        if ($estimasi->approvals_required === 1) {
            $estimasi->approver1_approved_at = $now;
            $estimasi->status = 'approved';
            $estimasi->save();

            NotificationService::send(
                $estimasi->created_by,
                'est_approved',
                'Estimasi Approved',
                "Estimasi {$estimasi->estimasi_number} has been approved.",
                route('estimasis.show', $estimasi)
            );

            return redirect()->route('estimasis.show', $estimasi)->with('success', 'Estimasi approved.');
        }

        if ($estimasi->approvals_required === 2) {
            $stage1Done = !is_null($estimasi->approver1_approved_at);

            if (!$stage1Done && $estimasi->approver1_id == $user->id) {
                $estimasi->approver1_approved_at = $now;
                $estimasi->save();

                NotificationService::send(
                    $estimasi->created_by,
                    'est_approved',
                    'Estimasi — Stage 1 Approved',
                    "{$user->name} approved Estimasi {$estimasi->estimasi_number} (awaiting Director).",
                    route('estimasis.show', $estimasi)
                );
                NotificationService::send(
                    $estimasi->approver2_id,
                    'est_needs_approval',
                    'Estimasi Director Approval Needed',
                    "Stage 1 approved. Estimasi {$estimasi->estimasi_number} now needs your Director approval.",
                    route('estimasis.show', $estimasi)
                );

                return redirect()->route('estimasis.show', $estimasi)->with('success', 'Approved (1/2). Waiting for Director approval.');
            }

            if ($stage1Done && $estimasi->approver2_id == $user->id && is_null($estimasi->approver2_approved_at)) {
                $estimasi->approver2_approved_at = $now;
                $estimasi->status = 'approved';
                $estimasi->save();

                NotificationService::send(
                    $estimasi->created_by,
                    'est_approved',
                    'Estimasi Fully Approved',
                    "{$user->name} (Director) approved Estimasi {$estimasi->estimasi_number}.",
                    route('estimasis.show', $estimasi)
                );

                return redirect()->route('estimasis.show', $estimasi)->with('success', 'Estimasi fully approved.');
            }
        }

        return redirect()->route('estimasis.show', $estimasi)->with('error', 'You are not authorised to approve this Estimasi at this stage.');
    }

    public function reject(Request $request, Estimasi $estimasi)
    {
        $user = auth()->user();

        if ($estimasi->status !== 'pending_approval') {
            return redirect()->route('estimasis.show', $estimasi)->with('error', 'This Estimasi is not pending approval.');
        }

        if (!$estimasi->isPendingMyApproval($user->id)) {
            return redirect()->route('estimasis.show', $estimasi)->with('error', 'You are not authorised to reject this Estimasi at this stage.');
        }

        $now = now();

        if ($estimasi->approvals_required === 1) {
            $estimasi->approver1_rejected_at = $now;
        } elseif ($estimasi->approvals_required === 2) {
            $stage1Done = !is_null($estimasi->approver1_approved_at);
            if (!$stage1Done) {
                $estimasi->approver1_rejected_at = $now;
            } else {
                $estimasi->approver2_rejected_at = $now;
            }
        }

        $estimasi->status = 'rejected';
        $estimasi->save();

        NotificationService::send(
            $estimasi->created_by,
            'est_rejected',
            'Estimasi Rejected',
            "{$user->name} rejected Estimasi {$estimasi->estimasi_number}.",
            route('estimasis.show', $estimasi)
        );

        return redirect()->route('estimasis.show', $estimasi)->with('info', 'Estimasi rejected.');
    }
}
