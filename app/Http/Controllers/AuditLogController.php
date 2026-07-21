<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    /**
     * Display audit logs
     */
    public function index(Request $request)
    {
        if (!PermissionHelper::canView('audit_logs')) {
            return PermissionHelper::denyAccess('audit_logs', 'view');
        }

        $query = AuditLog::with('user');

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by model_id
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Get unique model types for filter
        $modelTypes = AuditLog::select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type)
                ];
            });

        // Get users who have made changes
        $users = \App\Models\User::whereIn('id', AuditLog::select('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('audit_logs.index', compact('auditLogs', 'modelTypes', 'users'));
    }

    /**
     * Display audit review dashboard for Accounting/Finance/Admin
     * Shows only master data changes: Items, Customers, Suppliers
     */
    public function review(Request $request)
    {
        if (!PermissionHelper::canView('audit_logs')) {
            return PermissionHelper::denyAccess('audit_logs', 'view');
        }

        $query = AuditLog::with('user')
            ->whereIn('model_type', [
                'App\Models\Item',
                'App\Models\Customer',
                'App\Models\Supplier',
            ]);

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        // Get unique model types for filter (only master data)
        $modelTypes = AuditLog::whereIn('model_type', [
            'App\Models\Item',
            'App\Models\Customer',
            'App\Models\Supplier',
        ])
            ->select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type)
                ];
            });

        // Get users who have made changes to master data
        $users = \App\Models\User::whereIn(
            'id',
            AuditLog::whereIn('model_type', [
                'App\Models\Item',
                'App\Models\Customer',
                'App\Models\Supplier',
            ])->select('user_id')->distinct()->pluck('user_id')
        )
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('audit_logs.review', compact('auditLogs', 'modelTypes', 'users'));
    }

    /**
     * Record a print audit log entry (called via AJAX from print pages).
     */
    public function recordPrint(Request $request)
    {
        $allowed = [
            'Invoice',
            'WorkOrder',
            'ProformaInvoice',
            'Estimasi',
            'BonOut',
            'SalesOrder',
            'Receivable',
            'PurchaseRequest',
        ];

        $request->validate([
            'model_type'      => ['required', 'string', 'in:' . implode(',', $allowed)],
            'model_id'        => ['required', 'integer'],
            'document_number' => ['required', 'string', 'max:100'],
        ]);

        $modelClass = 'App\\Models\\' . $request->model_type;
        $model = $modelClass::findOrFail($request->model_id);

        AuditLog::logPrint($model, $request->document_number);

        return response()->json(['ok' => true]);
    }
}
