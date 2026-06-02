<?php

use App\Erp\Http\Controllers\Admin\DashboardController;
use App\Erp\Http\Controllers\Admin\DepartmentsController;
use App\Erp\Http\Controllers\Admin\EmployeesController;
use App\Erp\Http\Controllers\Admin\PositionsController;
use App\Erp\Http\Controllers\Admin\ProductsController;
use App\Erp\Http\Controllers\Admin\PurchaseOrdersController;
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
                Route::resource('products',       ProductsController::class);
                Route::resource('warehouses',     WarehousesController::class);
                Route::resource('stock-movements', StockMovementsController::class)->only(['index', 'create', 'store']);

                // Procurement Modülü
                Route::resource('suppliers',       SuppliersController::class);
                Route::resource('purchase-orders', PurchaseOrdersController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
                Route::post('purchase-orders/{purchase_order}/approve',        [PurchaseOrdersController::class, 'approve'])->name('purchase-orders.approve');
                Route::get('purchase-orders/{purchase_order}/receive',         [PurchaseOrdersController::class, 'receive'])->name('purchase-orders.receive');
                Route::post('purchase-orders/{purchase_order}/store-receiving', [PurchaseOrdersController::class, 'storeReceiving'])->name('purchase-orders.store-receiving');
            });
    });
