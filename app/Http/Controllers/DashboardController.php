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
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'viewer'])) {
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

        // Monthly Revenue & Material Cost (current year, non-cancelled invoices)
        $currentYear = now()->year;

        // Revenue: sum grand_total per month from non-cancelled invoices
        $monthlyRevenueData = Invoice::selectRaw('MONTH(invoice_date) as month, SUM(grand_total) as revenue')
            ->where('status', '!=', 'cancelled')
            ->whereYear('invoice_date', $currentYear)
            ->groupByRaw('MONTH(invoice_date)')
            ->get()
            ->keyBy('month');

        // Material COGS: live from completed bon out items (actual_quantity * unit_cost)
        // Joined through invoices → bon_outs (completed) → bon_out_items
        $monthlyCogsByMonth = DB::table('invoices as i')
            ->join('bon_outs as bo', function ($join) {
                $join->on('bo.work_order_id', '=', 'i.work_order_id')
                     ->where('bo.status', '=', 'completed');
            })
            ->join('bon_out_items as boi', 'boi.bon_out_id', '=', 'bo.id')
            ->where('i.status', '!=', 'cancelled')
            ->whereYear('i.invoice_date', $currentYear)
            ->selectRaw('MONTH(i.invoice_date) as month, SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as material_cost')
            ->groupByRaw('MONTH(i.invoice_date)')
            ->get()
            ->keyBy('month');

        $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $monthlyRevenue     = [];
        $monthlyMaterialCost = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyRevenue[$m]      = isset($monthlyRevenueData[$m])  ? (float) $monthlyRevenueData[$m]->revenue       : 0;
            $monthlyMaterialCost[$m] = isset($monthlyCogsByMonth[$m])  ? (float) $monthlyCogsByMonth[$m]->material_cost : 0;
        }

        $currentMonth = now()->month;
        $revenueThisMonth      = $monthlyRevenue[$currentMonth] ?? 0;
        $materialCostThisMonth = $monthlyMaterialCost[$currentMonth] ?? 0;

        // Active WOs created this month, excluding invoiced & cancelled
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();
        $activeWorkOrdersThisMonth = WorkOrder::with('customer')
            ->whereNotIn('status', ['invoiced', 'cancelled'])
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact(
            'summary', 'entityCounts', 'statusSections',
            'recentWorkOrders', 'recentPurchaseOrders',
            'recentInvoices', 'recentStockTransactions', 'lowStockItems',
            'monthNames', 'monthlyRevenue', 'monthlyMaterialCost',
            'revenueThisMonth', 'materialCostThisMonth', 'currentYear',
            'activeWorkOrdersThisMonth'
        ));
    }

    // -------------------------------------------------------------------------
    // Dashboard: Active WOs JSON (AJAX filter)
    // -------------------------------------------------------------------------
    public function activeWorkOrdersJson(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'director', 'viewer'])) {
            abort(403);
        }

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);

        $month = max(1, min(12, $month));
        $year  = max(2020, min((int) now()->year + 1, $year));

        $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->clone()->endOfMonth();

        $wos = WorkOrder::with('customer')
            ->whereNotIn('status', ['invoiced', 'cancelled'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($wo) {
                return [
                    'id'               => $wo->id,
                    'wo_number'        => $wo->wo_number,
                    'wo_url'           => route('work_orders.show', $wo),
                    'customer_name'    => $wo->customer->name ?? '-',
                    'vehicle_plate'    => $wo->vehicle_plate ?? '-',
                    'vehicle_merk'     => $wo->vehicle_merk ?? '',
                    'vehicle_type_year'=> $wo->vehicle_type_year ?? '',
                    'paket_name'       => $wo->paket_name ?? '',
                    'paket_size'       => $wo->paket_size ?? '',
                    'description'      => $wo->description ?? '',
                    'grand_total'      => (float) $wo->grand_total,
                    'status'           => $wo->status,
                    'created_at'       => $wo->created_at->format('d M'),
                    'deadline'         => $wo->deadline ? $wo->deadline->format('d M Y') : null,
                    'deadline_past'    => $wo->deadline ? $wo->deadline->isPast() : false,
                ];
            });

        return response()->json([
            'month'      => $month,
            'year'       => $year,
            'label'      => $start->format('F Y'),
            'count'      => $wos->count(),
            'work_orders'=> $wos,
        ]);
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
