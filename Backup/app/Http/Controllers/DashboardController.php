<?php

namespace App\Http\Controllers;

use App\Models\BonOut;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Receivable;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\WorkOrder;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('manager')) {
            return $this->managerDashboard();
        }

        if ($user->hasRole('purchasing')) {
            return $this->purchasingDashboard();
        }

        if ($user->hasRole('warehouse')) {
            return $this->warehouseDashboard();
        }

        if ($user->hasAnyRole(['finance', 'accounting'])) {
            return $this->financeDashboard();
        }

        if ($user->hasRole('audit')) {
            return $this->auditDashboard();
        }

        return $this->staffDashboard();
    }

    // -------------------------------------------------------------------------
    // Admin / Super Admin / Director
    // -------------------------------------------------------------------------
    private function adminDashboard()
    {
        $today = now();
        $lowStockBaseQuery = $this->lowStockBaseQuery();

        $summary = [
            'purchase_requests_total'  => PurchaseRequest::count(),
            'purchase_requests_pending' => PurchaseRequest::whereIn('status', ['on_progress', 'dept_head_approved', 'gm_approved'])->count(),
            'purchase_orders_total'    => PurchaseOrder::count(),
            'purchase_orders_open'     => PurchaseOrder::whereIn('status', ['on_progress', 'approved', 'partial'])->count(),
            'open_work_orders'         => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])->count(),
            'overdue_work_orders'      => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])
                ->whereNotNull('deadline')->whereDate('deadline', '<', $today)->count(),
            'outstanding_invoices'     => Invoice::whereIn('status', ['on_progress', 'sent', 'partial'])->count(),
            'low_stock_items'          => (clone $lowStockBaseQuery)->count(),
        ];

        $entityCounts = [
            'customers'   => Customer::count(),
            'suppliers'   => Supplier::count(),
            'items'       => Item::count(),
            'work_orders' => WorkOrder::count(),
            'bon_outs'    => BonOut::count(),
            'invoices'    => Invoice::count(),
        ];

        $statusSections = $this->buildAllStatusSections();

        $recentWorkOrders        = WorkOrder::with('customer')->orderBy('created_at', 'desc')->limit(5)->get();
        $recentPurchaseOrders    = PurchaseOrder::orderBy('created_at', 'desc')->limit(5)->get();
        $recentInvoices          = Invoice::with('customer')->orderBy('created_at', 'desc')->limit(5)->get();
        $recentStockTransactions = StockTransaction::with('item')->orderBy('created_at', 'desc')->limit(5)->get();

        $lowStockItems = (clone $lowStockBaseQuery)
            ->select('items.id', 'items.code', 'items.name', 'items.reorder_level')
            ->selectRaw('COALESCE(stock_totals.total_quantity, 0) as quantity')
            ->with('smallestUom')
            ->orderByRaw('COALESCE(stock_totals.total_quantity, 0) - COALESCE(items.reorder_level, 0) asc')
            ->take(5)->get();

        return view('dashboard', compact(
            'summary', 'entityCounts', 'statusSections',
            'recentWorkOrders', 'recentPurchaseOrders',
            'recentInvoices', 'recentStockTransactions', 'lowStockItems'
        ));
    }

    // -------------------------------------------------------------------------
    // Manager
    // -------------------------------------------------------------------------
    private function managerDashboard()
    {
        $today = now();

        $summary = [
            'pending_pr_dept_head'  => PurchaseRequest::where('status', 'on_progress')->count(),
            'pending_pr_gm'         => PurchaseRequest::where('status', 'dept_head_approved')->count(),
            'open_work_orders'      => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])->count(),
            'overdue_work_orders'   => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])
                ->whereNotNull('deadline')->whereDate('deadline', '<', $today)->count(),
            'outstanding_invoices'  => Invoice::whereIn('status', ['on_progress', 'sent', 'partial'])->count(),
            'purchase_orders_open'  => PurchaseOrder::whereIn('status', ['on_progress', 'approved', 'partial'])->count(),
        ];

        $prsPendingApproval = PurchaseRequest::with('requester')
            ->whereIn('status', ['on_progress', 'dept_head_approved'])
            ->orderBy('request_date', 'asc')
            ->limit(10)->get();

        $overdueWorkOrders = WorkOrder::with('customer')
            ->whereIn('status', ['on_progress', 'in_progress'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $today)
            ->orderBy('deadline', 'asc')
            ->limit(10)->get();

        $recentInvoices = Invoice::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)->get();

        $woStatusCounts      = WorkOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
        $invoiceStatusCounts = Invoice::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();

        $woStatusItems = $this->buildStatusItems($woStatusCounts, [
            'on_progress' => ['label' => 'On Progress', 'class' => 'secondary'],
            'in_progress' => ['label' => 'In Progress', 'class' => 'warning'],
            'completed'   => ['label' => 'Completed',   'class' => 'success'],
            'invoiced'    => ['label' => 'Invoiced',    'class' => 'info'],
            'cancelled'   => ['label' => 'Cancelled',   'class' => 'danger'],
        ]);

        $invoiceStatusItems = $this->buildStatusItems($invoiceStatusCounts, [
            'on_progress' => ['label' => 'On Progress', 'class' => 'secondary'],
            'sent'        => ['label' => 'Sent',        'class' => 'info'],
            'partial'     => ['label' => 'Partial',     'class' => 'warning'],
            'paid'        => ['label' => 'Paid',        'class' => 'success'],
            'cancelled'   => ['label' => 'Cancelled',   'class' => 'danger'],
        ]);

        return view('dashboards.manager', compact(
            'summary', 'prsPendingApproval', 'overdueWorkOrders',
            'recentInvoices', 'woStatusItems', 'invoiceStatusItems'
        ));
    }

    // -------------------------------------------------------------------------
    // Purchasing
    // -------------------------------------------------------------------------
    private function purchasingDashboard()
    {
        $summary = [
            'prs_ready_for_po'   => PurchaseRequest::where('status', 'gm_approved')->count(),
            'purchase_orders_open' => PurchaseOrder::whereIn('status', ['on_progress', 'approved', 'partial'])->count(),
            'suppliers'          => Supplier::count(),
            'items'              => Item::count(),
        ];

        $prsReadyForPo = PurchaseRequest::with('requester')
            ->where('status', 'gm_approved')
            ->orderBy('request_date', 'asc')
            ->limit(10)->get();

        $recentPurchaseOrders = PurchaseOrder::orderBy('created_at', 'desc')->limit(10)->get();

        $poStatusCounts = PurchaseOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
        $prStatusCounts = PurchaseRequest::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();

        $poStatusItems = $this->buildStatusItems($poStatusCounts, [
            'on_progress' => ['label' => 'On Progress',        'class' => 'secondary'],
            'approved'    => ['label' => 'Approved',           'class' => 'info'],
            'partial'     => ['label' => 'Partially Received', 'class' => 'warning'],
            'received'    => ['label' => 'Received',           'class' => 'success'],
            'completed'   => ['label' => 'Completed',          'class' => 'success'],
            'cancelled'   => ['label' => 'Cancelled',          'class' => 'danger'],
        ]);

        $prStatusItems = $this->buildStatusItems($prStatusCounts, [
            'on_progress'      => ['label' => 'On Progress',       'class' => 'secondary'],
            'dept_head_approved' => ['label' => 'Dept Head Approved', 'class' => 'info'],
            'gm_approved'      => ['label' => 'GM Approved',       'class' => 'primary'],
            'completed'        => ['label' => 'Completed',         'class' => 'success'],
            'rejected'         => ['label' => 'Rejected',          'class' => 'danger'],
            'cancelled'        => ['label' => 'Cancelled',         'class' => 'danger'],
        ]);

        return view('dashboards.purchasing', compact(
            'summary', 'prsReadyForPo', 'recentPurchaseOrders',
            'poStatusItems', 'prStatusItems'
        ));
    }

    // -------------------------------------------------------------------------
    // Warehouse
    // -------------------------------------------------------------------------
    private function warehouseDashboard()
    {
        $today = now();
        $lowStockBaseQuery = $this->lowStockBaseQuery();

        $summary = [
            'open_work_orders'    => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])->count(),
            'overdue_work_orders' => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])
                ->whereNotNull('deadline')->whereDate('deadline', '<', $today)->count(),
            'low_stock_items'     => (clone $lowStockBaseQuery)->count(),
            'pending_receivables' => Receivable::whereIn('status', ['on_progress', 'partial_received'])->count(),
        ];

        $lowStockItems = (clone $lowStockBaseQuery)
            ->select('items.id', 'items.code', 'items.name', 'items.reorder_level')
            ->selectRaw('COALESCE(stock_totals.total_quantity, 0) as quantity')
            ->with('smallestUom')
            ->orderByRaw('COALESCE(stock_totals.total_quantity, 0) - COALESCE(items.reorder_level, 0) asc')
            ->take(10)->get();

        $openWorkOrders = WorkOrder::with('customer')
            ->whereIn('status', ['on_progress', 'in_progress'])
            ->orderBy('deadline', 'asc')
            ->limit(10)->get();

        $recentStockTransactions = StockTransaction::with('item')
            ->orderBy('created_at', 'desc')
            ->limit(8)->get();

        $recentBonOuts = BonOut::orderBy('created_at', 'desc')->limit(5)->get();

        return view('dashboards.warehouse', compact(
            'summary', 'lowStockItems', 'openWorkOrders',
            'recentStockTransactions', 'recentBonOuts'
        ));
    }

    // -------------------------------------------------------------------------
    // Finance / Accounting
    // -------------------------------------------------------------------------
    private function financeDashboard()
    {
        $summary = [
            'outstanding_invoices'   => Invoice::whereIn('status', ['on_progress', 'sent', 'partial'])->count(),
            'outstanding_amount'     => Invoice::whereIn('status', ['on_progress', 'sent', 'partial'])->sum('grand_total'),
            'paid_invoices_month'    => Invoice::where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'revenue_this_month'     => Invoice::where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('grand_total'),
        ];

        $recentInvoices = Invoice::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(10)->get();

        $invoiceStatusCounts = Invoice::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();

        $invoiceStatusItems = $this->buildStatusItems($invoiceStatusCounts, [
            'on_progress' => ['label' => 'On Progress', 'class' => 'secondary'],
            'sent'        => ['label' => 'Sent',        'class' => 'info'],
            'partial'     => ['label' => 'Partial',     'class' => 'warning'],
            'paid'        => ['label' => 'Paid',        'class' => 'success'],
            'cancelled'   => ['label' => 'Cancelled',   'class' => 'danger'],
        ]);

        $recentWorkOrders = WorkOrder::with('customer')
            ->whereIn('status', ['invoiced', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)->get();

        return view('dashboards.finance', compact(
            'summary', 'recentInvoices', 'invoiceStatusItems', 'recentWorkOrders'
        ));
    }

    // -------------------------------------------------------------------------
    // Audit
    // -------------------------------------------------------------------------
    private function auditDashboard()
    {
        $today = now();
        $lowStockBaseQuery = $this->lowStockBaseQuery();

        $summary = [
            'purchase_requests_total'  => PurchaseRequest::count(),
            'purchase_requests_pending' => PurchaseRequest::whereIn('status', ['on_progress', 'dept_head_approved', 'gm_approved'])->count(),
            'purchase_orders_total'    => PurchaseOrder::count(),
            'purchase_orders_open'     => PurchaseOrder::whereIn('status', ['on_progress', 'approved', 'partial'])->count(),
            'open_work_orders'         => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])->count(),
            'overdue_work_orders'      => WorkOrder::whereIn('status', ['on_progress', 'in_progress'])
                ->whereNotNull('deadline')->whereDate('deadline', '<', $today)->count(),
            'outstanding_invoices'     => Invoice::whereIn('status', ['on_progress', 'sent', 'partial'])->count(),
            'low_stock_items'          => (clone $lowStockBaseQuery)->count(),
        ];

        $entityCounts = [
            'customers'   => Customer::count(),
            'suppliers'   => Supplier::count(),
            'items'       => Item::count(),
            'work_orders' => WorkOrder::count(),
            'bon_outs'    => BonOut::count(),
            'invoices'    => Invoice::count(),
        ];

        $statusSections = $this->buildAllStatusSections();

        return view('dashboards.audit', compact('summary', 'entityCounts', 'statusSections'));
    }

    // -------------------------------------------------------------------------
    // Staff
    // -------------------------------------------------------------------------
    private function staffDashboard()
    {
        $userId = auth()->id();

        $summary = [
            'my_prs_total'    => PurchaseRequest::where('requested_by', $userId)->count(),
            'my_prs_pending'  => PurchaseRequest::where('requested_by', $userId)
                ->whereIn('status', ['on_progress', 'dept_head_approved', 'gm_approved'])->count(),
            'my_prs_approved' => PurchaseRequest::where('requested_by', $userId)
                ->where('status', 'gm_approved')->count(),
            'my_prs_completed' => PurchaseRequest::where('requested_by', $userId)
                ->where('status', 'completed')->count(),
        ];

        $myPurchaseRequests = PurchaseRequest::where('requested_by', $userId)
            ->orderBy('request_date', 'desc')
            ->limit(10)->get();

        return view('dashboards.staff', compact('summary', 'myPurchaseRequests'));
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------
    private function lowStockBaseQuery()
    {
        $stockTotals = Stock::query()
            ->selectRaw('item_id, SUM(quantity) as total_quantity')
            ->where('location', 'default')
            ->groupBy('item_id');

        return Item::query()
            ->leftJoinSub($stockTotals, 'stock_totals', function ($join) {
                $join->on('items.id', '=', 'stock_totals.item_id');
            })
            ->where(function ($query) {
                $query->where('items.is_active', true)->orWhereNull('items.is_active');
            })
            ->where(function ($query) {
                $query->whereRaw('COALESCE(stock_totals.total_quantity, 0) <= 0')
                    ->orWhereRaw('COALESCE(stock_totals.total_quantity, 0) <= COALESCE(items.reorder_level, 0)');
            });
    }

    private function buildAllStatusSections(): array
    {
        $prStatusCounts      = PurchaseRequest::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
        $poStatusCounts      = PurchaseOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
        $woStatusCounts      = WorkOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
        $invoiceStatusCounts = Invoice::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();

        return [
            [
                'title' => 'Purchase Requests',
                'items' => $this->buildStatusItems($prStatusCounts, [
                    'on_progress'      => ['label' => 'On Progress',       'class' => 'secondary'],
                    'dept_head_approved' => ['label' => 'Dept Head Approved', 'class' => 'info'],
                    'gm_approved'      => ['label' => 'GM Approved',       'class' => 'primary'],
                    'completed'        => ['label' => 'Completed',         'class' => 'success'],
                    'rejected'         => ['label' => 'Rejected',          'class' => 'danger'],
                    'cancelled'        => ['label' => 'Cancelled',         'class' => 'danger'],
                ]),
            ],
            [
                'title' => 'Purchase & Service Orders',
                'items' => $this->buildStatusItems($poStatusCounts, [
                    'on_progress' => ['label' => 'On Progress',        'class' => 'secondary'],
                    'approved'    => ['label' => 'Approved',           'class' => 'info'],
                    'partial'     => ['label' => 'Partially Received', 'class' => 'warning'],
                    'received'    => ['label' => 'Received',           'class' => 'success'],
                    'completed'   => ['label' => 'Completed',          'class' => 'success'],
                    'cancelled'   => ['label' => 'Cancelled',          'class' => 'danger'],
                ]),
            ],
            [
                'title' => 'Work Orders',
                'items' => $this->buildStatusItems($woStatusCounts, [
                    'on_progress' => ['label' => 'On Progress', 'class' => 'secondary'],
                    'in_progress' => ['label' => 'In Progress', 'class' => 'warning'],
                    'completed'   => ['label' => 'Completed',   'class' => 'success'],
                    'invoiced'    => ['label' => 'Invoiced',    'class' => 'info'],
                    'cancelled'   => ['label' => 'Cancelled',   'class' => 'danger'],
                ]),
            ],
            [
                'title' => 'Invoices',
                'items' => $this->buildStatusItems($invoiceStatusCounts, [
                    'on_progress' => ['label' => 'On Progress', 'class' => 'secondary'],
                    'sent'        => ['label' => 'Sent',        'class' => 'info'],
                    'partial'     => ['label' => 'Partial',     'class' => 'warning'],
                    'paid'        => ['label' => 'Paid',        'class' => 'success'],
                    'cancelled'   => ['label' => 'Cancelled',   'class' => 'danger'],
                ]),
            ],
        ];
    }

    private function buildStatusItems(array $statusCounts, array $config): array
    {
        $items = [];
        $total = array_sum($statusCounts);

        foreach ($config as $status => $meta) {
            $count = (int) ($statusCounts[$status] ?? 0);
            $items[] = [
                'status'     => $status,
                'label'      => $meta['label'],
                'class'      => $meta['class'],
                'count'      => $count,
                'percentage' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        foreach ($statusCounts as $status => $count) {
            if (isset($config[$status])) {
                continue;
            }
            $items[] = [
                'status'     => $status,
                'label'      => ucwords(str_replace('_', ' ', $status)),
                'class'      => 'secondary',
                'count'      => (int) $count,
                'percentage' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $items;
    }
}
