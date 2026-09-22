<?php

namespace App\Http\Controllers;

use App\Helpers\ExcelExporter;
use App\Helpers\PermissionHelper;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\AuditLog;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    private const STATUSES = [
        'draft'     => 'Draft',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    ];

    public function index(Request $request)
    {
        if (!PermissionHelper::canView('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'view');
        }

        $month  = $request->input('month');
        $year   = $request->input('year');
        $status = $request->input('status');

        $query = SalesOrder::with(['customer', 'creator']);

        if ($month) {
            $query->whereMonth('order_date', (int) $month);
        }
        if ($year) {
            $query->whereYear('order_date', (int) $year);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $salesOrders = $query->orderBy('created_at', 'desc')->get();

        $allYears = SalesOrder::selectRaw('YEAR(order_date) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        $statuses = self::STATUSES;

        return view('sales_orders.index', compact('salesOrders', 'month', 'year', 'status', 'allYears', 'statuses'));
    }

    public function exportExcel(Request $request)
    {
        if (!PermissionHelper::canView('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'view');
        }

        $month  = $request->input('month');
        $year   = $request->input('year');
        $status = $request->input('status');

        $query = SalesOrder::with(['customer', 'creator']);

        if ($month) {
            $query->whereMonth('order_date', (int) $month);
        }
        if ($year) {
            $query->whereYear('order_date', (int) $year);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $salesOrders = $query->orderBy('order_date', 'desc')->get();

        $rows = [];
        foreach ($salesOrders as $so) {
            $rows[] = [
                $so->so_number,
                $so->customer->name ?? '-',
                $so->order_date->format('Y-m-d'),
                $so->material_total,
                self::STATUSES[$so->status] ?? ucwords(str_replace('_', ' ', $so->status)),
                $so->creator->name ?? '-',
            ];
        }

        return ExcelExporter::download(
            'Sales Orders',
            ['SO Number', 'Customer', 'Order Date', 'Total (Rp)', 'Status', 'Created By'],
            $rows,
            ['A' => 18, 'B' => 30, 'C' => 12, 'D' => 16, 'E' => 12, 'F' => 18],
            'Sales-Orders-' . now()->format('Ymd') . '.xlsx'
        );
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'create');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::where('is_active', true)
            ->with(['smallestUom', 'stocks'])
            ->orderBy('name')
            ->get();

        return view('sales_orders.create', compact('customers', 'items'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'create');
        }

        $validated = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'order_date'         => 'required|date',
            'description'        => 'nullable|string|max:255',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:items,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Auto-generate SO number: SO-YYMM/SEQ (monthly reset)
        $prefix = 'SO-' . date('ym') . '/';
        $last = SalesOrder::where('so_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        $next = $last ? intval(substr($last->so_number, -3)) + 1 : 1;
        $soNumber = $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);

        $materialTotal = 0;
        foreach ($validated['items'] as $row) {
            $materialTotal += (float) $row['quantity'] * (float) $row['unit_price'];
        }

        $so = SalesOrder::create([
            'so_number'      => $soNumber,
            'customer_id'    => $validated['customer_id'],
            'order_date'     => $validated['order_date'],
            'description'    => $validated['description'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'material_total' => $materialTotal,
            'status'         => 'draft',
            'created_by'     => auth()->id(),
        ]);

        foreach ($validated['items'] as $row) {
            $total = round((float) $row['quantity'] * (float) $row['unit_price'], 2);
            SalesOrderItem::create([
                'sales_order_id' => $so->id,
                'item_id'        => $row['item_id'],
                'quantity'       => $row['quantity'],
                'unit_price'     => $row['unit_price'],
                'total_price'    => $total,
            ]);
        }

        return redirect()->route('sales_orders.show', $so)
            ->with('success', "Sales Order {$soNumber} created successfully.");
    }

    public function show(SalesOrder $salesOrder)
    {
        if (!PermissionHelper::canView('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'view');
        }

        $salesOrder->load(['customer', 'creator', 'items.item.smallestUom']);

        return view('sales_orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        if (!PermissionHelper::canUpdate('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'update');
        }

        if ($salesOrder->status !== 'draft') {
            return redirect()->route('sales_orders.show', $salesOrder)
                ->with('error', 'Only draft Sales Orders can be edited.');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::where('is_active', true)
            ->with(['smallestUom', 'stocks'])
            ->orderBy('name')
            ->get();

        $salesOrder->load(['items.item.smallestUom']);

        return view('sales_orders.edit', compact('salesOrder', 'customers', 'items'));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if (!PermissionHelper::canUpdate('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'update');
        }

        if ($salesOrder->status !== 'draft') {
            return redirect()->route('sales_orders.show', $salesOrder)
                ->with('error', 'Only draft Sales Orders can be edited.');
        }

        $validated = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'order_date'         => 'required|date',
            'description'        => 'nullable|string|max:255',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:items,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $materialTotal = 0;
        foreach ($validated['items'] as $row) {
            $materialTotal += (float) $row['quantity'] * (float) $row['unit_price'];
        }

        $salesOrder->update([
            'customer_id'    => $validated['customer_id'],
            'order_date'     => $validated['order_date'],
            'description'    => $validated['description'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'material_total' => $materialTotal,
        ]);

        $salesOrder->items()->delete();
        foreach ($validated['items'] as $row) {
            $total = round((float) $row['quantity'] * (float) $row['unit_price'], 2);
            SalesOrderItem::create([
                'sales_order_id' => $salesOrder->id,
                'item_id'        => $row['item_id'],
                'quantity'       => $row['quantity'],
                'unit_price'     => $row['unit_price'],
                'total_price'    => $total,
            ]);
        }

        return redirect()->route('sales_orders.show', $salesOrder)
            ->with('success', 'Sales Order updated successfully.');
    }

    public function confirm(SalesOrder $salesOrder)
    {
        if (!PermissionHelper::canUpdate('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'update');
        }

        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'Only draft Sales Orders can be confirmed.');
        }

        $salesOrder->update(['status' => 'confirmed']);

        return redirect()->route('sales_orders.show', $salesOrder)
            ->with('success', 'Sales Order confirmed.');
    }

    public function cancel(SalesOrder $salesOrder)
    {
        if (!PermissionHelper::canUpdate('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'update');
        }

        if ($salesOrder->status === 'cancelled') {
            return back()->with('error', 'Already cancelled.');
        }

        $salesOrder->update(['status' => 'cancelled']);

        return redirect()->route('sales_orders.show', $salesOrder)
            ->with('success', 'Sales Order cancelled.');
    }

    public function print(SalesOrder $salesOrder)
    {
        if (!PermissionHelper::canView('sales_orders')) {
            return PermissionHelper::denyAccess('sales_orders', 'view');
        }

        $salesOrder->load(['customer', 'creator', 'items.item.smallestUom']);
        return view('sales_orders.print', compact('salesOrder'));
    }
}
