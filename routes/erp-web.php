<?php

use App\Erp\Http\Controllers\Admin\CustomersController;
use App\Erp\Http\Controllers\Admin\DashboardController;
use App\Erp\Http\Controllers\Admin\DepartmentsController;
use App\Erp\Http\Controllers\Admin\EmployeesController;
use App\Erp\Http\Controllers\Admin\ExpensesController;
use App\Erp\Http\Controllers\Admin\InvoicesController;
use App\Erp\Http\Controllers\Admin\PositionsController;
use App\Erp\Http\Controllers\Admin\ProductsController;
use App\Erp\Http\Controllers\Admin\PurchaseOrdersController;
use App\Erp\Http\Controllers\Admin\PayrollRunsController;
use App\Erp\Http\Controllers\Admin\PayslipsController;
use App\Erp\Http\Controllers\Admin\ProjectsController;
use App\Erp\Http\Controllers\Admin\ProjectTasksController;
use App\Erp\Http\Controllers\Admin\SalesOrdersController;
use App\Erp\Http\Controllers\Admin\StockMovementsController;
use App\Erp\Http\Controllers\Admin\SuppliersController;
use App\Erp\Http\Controllers\Admin\WarehousesController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('erp.routes.middleware', ['web']))
    ->group(function () {
        Route::prefix(config('erp.routes.admin_prefix', 'admin/erp'))
            ->name('erp.')
            ->middleware(['erp.access', 'throttle:240,1'])
            ->group(function () {
                Route::get('/', fn () => redirect()->route('erp.dashboard'))->name('home');
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

                // HR Modülü
                Route::resource('employees',   EmployeesController::class);
                Route::resource('departments', DepartmentsController::class);
                Route::resource('positions',   PositionsController::class);

                // Inventory Modülü
                Route::resource('products',        ProductsController::class);
                Route::resource('warehouses',      WarehousesController::class);
                Route::resource('stock-movements', StockMovementsController::class)->only(['index', 'create', 'store']);

                // Procurement Modülü
                Route::resource('suppliers',       SuppliersController::class);
                Route::resource('purchase-orders', PurchaseOrdersController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
                Route::post('purchase-orders/{purchase_order}/approve',         [PurchaseOrdersController::class, 'approve'])->name('purchase-orders.approve');
                Route::get('purchase-orders/{purchase_order}/receive',          [PurchaseOrdersController::class, 'receive'])->name('purchase-orders.receive');
                Route::post('purchase-orders/{purchase_order}/store-receiving', [PurchaseOrdersController::class, 'storeReceiving'])->name('purchase-orders.store-receiving');

                // Finance Modülü
                Route::resource('invoices', InvoicesController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
                Route::post('invoices/{invoice}/send',         [InvoicesController::class, 'send'])->name('invoices.send');
                Route::post('invoices/{invoice}/payments',     [InvoicesController::class, 'storePayment'])->name('invoices.payments.store');
                Route::get('invoices/{invoice}/pdf',           [InvoicesController::class, 'downloadPdf'])->name('invoices.pdf');
                Route::resource('expenses', ExpensesController::class)->except(['show']);

                // Sales Modülü
                Route::resource('customers',   CustomersController::class);
                Route::resource('sales-orders', SalesOrdersController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
                Route::post('sales-orders/{sales_order}/confirm',        [SalesOrdersController::class, 'confirm'])->name('sales-orders.confirm');
                Route::post('sales-orders/{sales_order}/deliver',        [SalesOrdersController::class, 'deliver'])->name('sales-orders.deliver');
                Route::post('sales-orders/{sales_order}/cancel',         [SalesOrdersController::class, 'cancel'])->name('sales-orders.cancel');
                Route::post('sales-orders/{sales_order}/create-invoice', [SalesOrdersController::class, 'createInvoice'])->name('sales-orders.create-invoice');

                // Payroll Modülü
                Route::resource('payroll-runs', PayrollRunsController::class)->only(['index', 'create', 'store', 'show']);
                Route::post('payroll-runs/{payroll_run}/approve', [PayrollRunsController::class, 'approve'])->name('payroll-runs.approve');
                Route::get('employees/{employee}/salary/create', [PayrollRunsController::class, 'salaryCreate'])->name('employees.salary.create');
                Route::post('employees/{employee}/salary',        [PayrollRunsController::class, 'salaryStore'])->name('employees.salary.store');
                Route::get('payslips/{payslip}',     [PayslipsController::class, 'show'])->name('payslips.show');
                Route::get('payslips/{payslip}/pdf', [PayslipsController::class, 'pdf'])->name('payslips.pdf');

                // Projects Modülü
                Route::resource('projects', ProjectsController::class);
                Route::post('projects/{project}/time-entries', [ProjectsController::class, 'storeTimeEntry'])->name('projects.time-entries.store');
                Route::post('projects/{project}/tasks',                          [ProjectTasksController::class, 'store'])->name('projects.tasks.store');
                Route::put('projects/{project}/tasks/{task}',                    [ProjectTasksController::class, 'update'])->name('projects.tasks.update');
                Route::patch('projects/{project}/tasks/{task}/status',           [ProjectTasksController::class, 'updateStatus'])->name('projects.tasks.update-status');
                Route::delete('projects/{project}/tasks/{task}',                 [ProjectTasksController::class, 'destroy'])->name('projects.tasks.destroy');
            });
    });
