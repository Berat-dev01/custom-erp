<?php

use App\Erp\Http\Controllers\Admin\AccountsController;
use App\Erp\Http\Controllers\Admin\ApiTokensController;
use App\Erp\Http\Controllers\Admin\BomsController;
use App\Erp\Http\Controllers\Admin\CurrenciesController;
use App\Erp\Http\Controllers\Admin\WorkOrdersController;
use App\Erp\Http\Controllers\Admin\AssetsController;
use App\Erp\Http\Controllers\Admin\AttendanceController;
use App\Erp\Http\Controllers\Admin\BankAccountsController;
use App\Erp\Http\Controllers\Admin\LeaveRequestsController;
use App\Erp\Http\Controllers\Admin\ChecksController;
use App\Erp\Http\Controllers\Admin\JournalEntriesController;
use App\Erp\Http\Controllers\Admin\CustomersController;
use App\Erp\Http\Controllers\Admin\DashboardController;
use App\Erp\Http\Controllers\Admin\ReportsController;
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

                // İzin & Devam Modülü
                Route::resource('leave-requests', LeaveRequestsController::class)->only(['index', 'create', 'store']);
                Route::patch('leave-requests/{leave_request}/approve', [LeaveRequestsController::class, 'approve'])->name('leave-requests.approve');
                Route::patch('leave-requests/{leave_request}/reject',  [LeaveRequestsController::class, 'reject'])->name('leave-requests.reject');
                Route::patch('leave-requests/{leave_request}/cancel',  [LeaveRequestsController::class, 'cancel'])->name('leave-requests.cancel');
                Route::get('attendance',                                  [AttendanceController::class, 'index'])->name('attendance.index');
                Route::post('attendance',                                 [AttendanceController::class, 'store'])->name('attendance.store');
                Route::get('attendance/{employee}/monthly-report',        [AttendanceController::class, 'monthlyReport'])->name('attendance.monthly-report');

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
                Route::post('payroll-runs/{payroll_run}/approve',       [PayrollRunsController::class, 'approve'])->name('payroll-runs.approve');
                Route::get('payroll-runs/{payroll_run}/sgk-bildirgesi', [PayrollRunsController::class, 'exportSgkBildirgesi'])->name('payroll-runs.sgk-bildirgesi');
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

                // Assets Modülü
                Route::resource('assets', AssetsController::class);
                Route::post('assets/{asset}/depreciate', [AssetsController::class, 'depreciate'])->name('assets.depreciate');

                // Üretim Modülü
                Route::resource('boms', BomsController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
                Route::resource('work-orders', WorkOrdersController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
                Route::patch('work-orders/{work_order}/release',  [WorkOrdersController::class, 'release'])->name('work-orders.release');
                Route::patch('work-orders/{work_order}/complete', [WorkOrdersController::class, 'complete'])->name('work-orders.complete');
                Route::patch('work-orders/{work_order}/cancel',   [WorkOrdersController::class, 'cancel'])->name('work-orders.cancel');

                // Kasa & Banka Modülü
                Route::resource('bank-accounts', BankAccountsController::class)->only(['index', 'create', 'store', 'show']);
                Route::post('bank-accounts/{bankAccount}/transactions', [BankAccountsController::class, 'storeTransaction'])->name('bank-accounts.store-transaction');
                Route::post('bank-accounts/{bankAccount}/transfer',     [BankAccountsController::class, 'transfer'])->name('bank-accounts.transfer');
                Route::post('bank-accounts/{bankAccount}/reconcile',    [BankAccountsController::class, 'reconcile'])->name('bank-accounts.reconcile');
                Route::post('bank-accounts/{bankAccount}/import',       [BankAccountsController::class, 'importStatement'])->name('bank-accounts.import');
                Route::resource('checks', ChecksController::class)->only(['index', 'create', 'store', 'destroy']);
                Route::patch('checks/{check}/status', [ChecksController::class, 'updateStatus'])->name('checks.update-status');

                // Muhasebe Modülü
                Route::resource('accounts', AccountsController::class)->only(['index', 'show']);
                Route::resource('journal-entries', JournalEntriesController::class)->only(['index', 'create', 'store', 'show']);

                // Muhasebe Raporları
                Route::get('reports/trial-balance',    [ReportsController::class, 'trialBalance'])->name('reports.trial-balance');
                Route::get('reports/balance-sheet',    [ReportsController::class, 'balanceSheet'])->name('reports.balance-sheet');
                Route::get('reports/income-statement', [ReportsController::class, 'incomeStatement'])->name('reports.income-statement');
                Route::get('reports/tax-report',       [ReportsController::class, 'taxReport'])->name('reports.tax-report');

                // Para Birimi & Kur Yönetimi
                Route::get('currencies',           [CurrenciesController::class, 'index'])->name('currencies.index');
                Route::post('currencies',          [CurrenciesController::class, 'store'])->name('currencies.store');
                Route::post('currencies/rates',    [CurrenciesController::class, 'storeRate'])->name('currencies.store-rate');
                Route::post('currencies/fetch-tcmb',[CurrenciesController::class, 'fetchTcmb'])->name('currencies.fetch-tcmb');

                // API Token Yönetimi
                Route::get('api-tokens',              [ApiTokensController::class, 'index'])->name('api-tokens.index');
                Route::post('api-tokens',             [ApiTokensController::class, 'store'])->name('api-tokens.store');
                Route::delete('api-tokens/{apiToken}',[ApiTokensController::class, 'destroy'])->name('api-tokens.destroy');

                // Raporlar Modülü
                Route::get('reports',           [ReportsController::class, 'index'])->name('reports.index');
                Route::get('reports/revenue',   [ReportsController::class, 'revenueReport'])->name('reports.revenue');
                Route::get('reports/inventory', [ReportsController::class, 'inventoryReport'])->name('reports.inventory');
                Route::get('reports/hr',        [ReportsController::class, 'hrReport'])->name('reports.hr');
                Route::get('reports/aging',     [ReportsController::class, 'agingReport'])->name('reports.aging');
            });
    });
