<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UOMController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierImportController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\VendorComparisonController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\LaborController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\BonOutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\EstimasiController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\SparepartReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/active-work-orders', [DashboardController::class, 'activeWorkOrdersJson'])->name('dashboard.active_wo_json');

    // User Profile & Signature
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::post('/profile/signature', [UserController::class, 'updateSignature'])->name('users.updateSignature');
    Route::post('/profile/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');
    Route::post('/profile/change-email', [UserController::class, 'changeEmail'])->name('users.changeEmail');
    Route::get('/users/{user}/signature', [UserController::class, 'getSignature'])->name('users.signature');

    // User Management (Admin only)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // UOMs
    Route::resource('uoms', UOMController::class);
    Route::get('uoms-conversions', [UOMController::class, 'conversions'])->name('uoms.conversions');
    Route::post('uoms-conversions', [UOMController::class, 'storeConversion'])->name('uoms.conversions.store');
    Route::delete('uoms-conversions/{conversion}', [UOMController::class, 'destroyConversion'])->name('uoms.conversions.destroy');

    // Items
    Route::middleware('role.permission:items,view')->group(function () {
        Route::get('items/import', [ItemImportController::class, 'index'])->middleware('role.permission:items,create')->name('items.import');
        Route::post('items/import', [ItemImportController::class, 'import'])->middleware('role.permission:items,create')->name('items.import.process');
        Route::get('items/import/template', [ItemImportController::class, 'downloadTemplate'])->name('items.import.template');
        Route::get('items', [ItemController::class, 'index'])->name('items.index');
        Route::get('items/create', [ItemController::class, 'create'])->middleware('role.permission:items,create')->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->middleware('role.permission:items,create')->name('items.store');
        Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->middleware('role.permission:items,update')->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->middleware('role.permission:items,update')->name('items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->middleware('role.permission:items,delete')->name('items.destroy');
        Route::post('items/{item}/adjust-cost', [ItemController::class, 'adjustCost'])->name('items.adjustCost');
    });

    // Customers
    Route::middleware('role.permission:customers,view')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::get('customers/import', [CustomerController::class, 'showImportForm'])->middleware('role.permission:customers,create')->name('customers.import');
        Route::post('customers/import', [CustomerController::class, 'processImport'])->middleware('role.permission:customers,create')->name('customers.processImport');
        Route::get('customers/create', [CustomerController::class, 'create'])->middleware('role.permission:customers,create')->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->middleware('role.permission:customers,create')->name('customers.store');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('role.permission:customers,update')->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('role.permission:customers,update')->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('role.permission:customers,delete')->name('customers.destroy');
    });

    // Vehicles (master: linked to customers)
    Route::resource('vehicles', VehicleController::class);
    Route::get('customers/{customer}/vehicles', [VehicleController::class, 'byCustomer'])->name('customers.vehicles');

    // Suppliers
    Route::middleware('role.permission:suppliers,view')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/import', [SupplierImportController::class, 'index'])->middleware('role.permission:suppliers,create')->name('suppliers.import');
        Route::post('suppliers/import', [SupplierImportController::class, 'import'])->middleware('role.permission:suppliers,create')->name('suppliers.import.process');
        Route::get('suppliers/create', [SupplierController::class, 'create'])->middleware('role.permission:suppliers,create')->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->middleware('role.permission:suppliers,create')->name('suppliers.store');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->middleware('role.permission:suppliers,update')->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('role.permission:suppliers,update')->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('role.permission:suppliers,delete')->name('suppliers.destroy');
    });

    // Packages
    Route::middleware('role.permission:packages,view')->group(function () {
        Route::get('packages/bom-import', [PackageController::class, 'importBomIndex'])->middleware('role.permission:packages,create')->name('packages.bom_import');
        Route::post('packages/bom-import', [PackageController::class, 'importBom'])->middleware('role.permission:packages,create')->name('packages.bom_import.process');
        Route::get('packages/bom-import/template', [PackageController::class, 'downloadBomTemplate'])->name('packages.bom_import.template');
        Route::get('packages/item-names', [PackageController::class, 'showItemNames'])->name('packages.item_names');
        Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
        Route::get('packages/create', [PackageController::class, 'create'])->middleware('role.permission:packages,create')->name('packages.create');
        Route::post('packages', [PackageController::class, 'store'])->middleware('role.permission:packages,create')->name('packages.store');
        Route::get('packages/{package}', [PackageController::class, 'show'])->name('packages.show');
        Route::get('packages/{package}/edit', [PackageController::class, 'edit'])->middleware('role.permission:packages,update')->name('packages.edit');
        Route::put('packages/{package}', [PackageController::class, 'update'])->middleware('role.permission:packages,update')->name('packages.update');
        Route::delete('packages/{package}', [PackageController::class, 'destroy'])->middleware('role.permission:packages,delete')->name('packages.destroy');
    });

    // Purchase Requests
    Route::middleware('role.permission:purchase_requests,view')->group(function () {
        Route::resource('purchase-requests', PurchaseRequestController::class)->names('purchase_requests');
        Route::get('purchase-requests/{purchaseRequest}/json', [PurchaseRequestController::class, 'getJson'])->name('purchase_requests.json');
        Route::post('purchase-requests/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase_requests.approve');
        Route::post('purchase-requests/{purchaseRequest}/gm-approve', [PurchaseRequestController::class, 'gmApprove'])->name('purchase_requests.gm_approve');
        Route::post('purchase-requests/{purchaseRequest}/received', [PurchaseRequestController::class, 'receivedByPurchasing'])->name('purchase_requests.received');
        Route::post('purchase-requests/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('purchase_requests.reject');
        Route::post('purchase-requests/{purchaseRequest}/cancel', [PurchaseRequestController::class, 'cancel'])->name('purchase_requests.cancel');
        Route::post('purchase-requests/{purchaseRequest}/close', [PurchaseRequestController::class, 'close'])->name('purchase_requests.close');
        Route::post('purchase-requests/{purchaseRequest}/upload-berita-acara', [PurchaseRequestController::class, 'uploadBeritaAcara'])->name('purchase_requests.upload_berita_acara');
        Route::get('purchase-requests/{purchaseRequest}/print', [PurchaseRequestController::class, 'print'])->name('purchase_requests.print')->middleware('signed');
        Route::get('purchase-requests/{purchaseRequest}/print-preview', [PurchaseRequestController::class, 'printPreview'])->name('purchase_requests.print_preview')->middleware('signed');
        Route::get('purchase-requests/{purchaseRequest}/attachment', [PurchaseRequestController::class, 'attachment'])->name('purchase_requests.attachment');
    });

    // Purchase Orders
    Route::get('purchase-orders/export-excel', [PurchaseOrderController::class, 'exportExcel'])->name('purchase_orders.export_excel');
    Route::post('purchase-orders/preview', [PurchaseOrderController::class, 'preview'])->name('purchase_orders.preview');
    Route::resource('purchase-orders', PurchaseOrderController::class)->names('purchase_orders');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase_orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/revoke-approval', [PurchaseOrderController::class, 'revokeApproval'])->name('purchase_orders.revoke_approval');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase_orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/close-remaining', [PurchaseOrderController::class, 'closeRemaining'])->name('purchase_orders.close_remaining');
    Route::post('purchase-orders/{purchaseOrder}/record-invoice', [PurchaseOrderController::class, 'recordInvoice'])->name('purchase_orders.record_invoice');
    Route::post('purchase-orders/{purchaseOrder}/complete', [PurchaseOrderController::class, 'complete'])->name('purchase_orders.complete');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase_orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/close-so', [PurchaseOrderController::class, 'closeSO'])->name('purchase_orders.close_so');
    Route::get('purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase_orders.print')->middleware('signed');
    Route::get('purchase-orders/{purchaseOrder}/print-preview', [PurchaseOrderController::class, 'printPreview'])->name('purchase_orders.print_preview');

    // Vendor Comparisons (FK-PCH)
    Route::resource('vendor-comparisons', VendorComparisonController::class)->names('vendor_comparisons');
    Route::post('vendor-comparisons/{vendorComparison}/submit', [VendorComparisonController::class, 'submit'])->name('vendor_comparisons.submit');
    Route::post('vendor-comparisons/{vendorComparison}/select-vendor', [VendorComparisonController::class, 'selectVendor'])->name('vendor_comparisons.select_vendor');
    Route::get('vendor-comparisons/{vendorComparison}/print', [VendorComparisonController::class, 'print'])->name('vendor_comparisons.print');

    // Receivables (Goods Receipt)
    Route::get('receivables/create-standalone', [ReceivableController::class, 'createStandalone'])->name('receivables.create_standalone');
    Route::post('receivables/store-standalone', [ReceivableController::class, 'storeStandalone'])->name('receivables.store_standalone');
    Route::resource('receivables', ReceivableController::class);
    Route::get('receivables/{receivable}/print', [ReceivableController::class, 'print'])->name('receivables.print')->middleware('signed');
    Route::post('receivables/{receivable}/complete', [ReceivableController::class, 'complete'])->name('receivables.complete');
    Route::post('receivables/{receivable}/cancel', [ReceivableController::class, 'cancel'])->name('receivables.cancel');

    // Bon Out (Stock Issue — WO-linked & Standalone)
    Route::get('bon-outs/create-from-wo/{workOrder}', [BonOutController::class, 'createFromWO'])->name('bon_outs.createFromWO');
    Route::get('bon-outs/create-standalone', [BonOutController::class, 'createStandalone'])->name('bon_outs.createStandalone');
    Route::resource('bon-outs', BonOutController::class)->names('bon_outs')->except(['edit', 'update']);
    Route::get('bon-outs/{bonOut}/edit', [BonOutController::class, 'edit'])->name('bon_outs.edit');
    Route::put('bon-outs/{bonOut}', [BonOutController::class, 'update'])->name('bon_outs.update');
    Route::post('bon-outs/{bonOut}/complete', [BonOutController::class, 'complete'])->name('bon_outs.complete');
    Route::post('bon-outs/{bonOut}/cancel', [BonOutController::class, 'cancel'])->name('bon_outs.cancel');
    Route::get('bon-outs/{bonOut}/print', [BonOutController::class, 'print'])->name('bon_outs.print')->middleware('signed');

    // Work Orders
    Route::resource('work-orders', WorkOrderController::class)->names('work_orders');
    Route::post('work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work_orders.start');
    Route::post('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work_orders.complete');
    Route::post('work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work_orders.cancel');
    Route::get('work-orders/{workOrder}/print', [WorkOrderController::class, 'printView'])->name('work_orders.print')->middleware('signed');
    Route::post('work-orders/{workOrder}/add-labor', [WorkOrderController::class, 'addLabor'])->name('work_orders.add_labor');
    Route::delete('work-orders/{workOrder}/labors/{labor}', [WorkOrderController::class, 'removeLabor'])->name('work_orders.remove_labor');

    // Labor Master
    Route::get('labors/template', [LaborController::class, 'downloadTemplate'])->name('labors.template');
    Route::post('labors/import', [LaborController::class, 'import'])->name('labors.import');
    Route::resource('labors', LaborController::class)->except(['show']);

    // Panel Master
    Route::get('panels/template', [PanelController::class, 'downloadTemplate'])->name('panels.template');
    Route::post('panels/import', [PanelController::class, 'import'])->name('panels.import');
    Route::resource('panels', PanelController::class)->except(['show']);

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.markAsPaid');
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('invoices/{invoice}/change-customer', [InvoiceController::class, 'changeCustomer'])->name('invoices.changeCustomer');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print')->middleware('signed');
    Route::get('invoices/{invoice}/cogs-report', [InvoiceController::class, 'cogsReport'])->name('invoices.cogsReport');

    // Credit Notes
    Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit_notes.index');
    Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit_notes.show');
    Route::get('credit-notes/{creditNote}/print', [CreditNoteController::class, 'print'])->name('credit_notes.print')->middleware('signed');

    // Sparepart Report
    Route::get('reports/sparepart', [SparepartReportController::class, 'index'])->name('reports.sparepart');
    Route::get('reports/sparepart/export', [SparepartReportController::class, 'export'])->name('reports.sparepart.export');

    // Sales Orders
    Route::resource('sales-orders', SalesOrderController::class)->names('sales_orders');
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales_orders.confirm');
    Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales_orders.cancel');
    Route::get('sales-orders/{salesOrder}/print', [SalesOrderController::class, 'print'])->name('sales_orders.print')->middleware('signed');

    // Proforma Invoices
    Route::resource('proforma-invoices', ProformaInvoiceController::class)->names('proforma_invoices');
    Route::post('proforma-invoices/{proformaInvoice}/approve', [ProformaInvoiceController::class, 'approve'])->name('proforma_invoices.approve');
    Route::post('proforma-invoices/{proformaInvoice}/reject', [ProformaInvoiceController::class, 'reject'])->name('proforma_invoices.reject');
    Route::get('proforma-invoices/{proformaInvoice}/print', [ProformaInvoiceController::class, 'print'])->name('proforma_invoices.print')->middleware('signed');
    Route::post('proforma-invoices/{proformaInvoice}/lines/{line}/approve', [ProformaInvoiceController::class, 'approveLine'])->name('proforma_invoices.approve_line');
    Route::post('proforma-invoices/{proformaInvoice}/lines/{line}/reject', [ProformaInvoiceController::class, 'rejectLine'])->name('proforma_invoices.reject_line');

    // Estimasi
    Route::resource('estimasis', EstimasiController::class)->except(['edit', 'update', 'destroy']);
    Route::post('estimasis/{estimasi}/approve', [EstimasiController::class, 'approve'])->name('estimasis.approve');
    Route::post('estimasis/{estimasi}/reject', [EstimasiController::class, 'reject'])->name('estimasis.reject');
    Route::get('estimasis/{estimasi}/print', [EstimasiController::class, 'print'])->name('estimasis.print')->middleware('signed');


    // Stock Management (Purchasing role has no access)
    Route::middleware('role.permission:stocks,view')->group(function () {
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/export', [StockController::class, 'export'])->name('stocks.export');
        Route::get('stocks/export-prices', [StockController::class, 'exportWithPrices'])->name('stocks.export_prices');
        Route::get('stocks/transactions', [StockController::class, 'transactions'])->name('stocks.transactions');
        Route::post('stocks/adjust', [StockController::class, 'adjust'])->middleware('role.permission:stocks,adjust')->name('stocks.adjust');
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::get('notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');

    // Audit Logs (Admin/Super Admin only)
    Route::post('audit/log-print', [AuditLogController::class, 'recordPrint'])->name('audit.recordPrint');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Master Data Review (Accounting/Admin only) - For reviewing warehouse changes
    Route::get('audit-logs/review/master-data', [AuditLogController::class, 'review'])->name('audit-logs.review');
});
