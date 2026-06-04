<?php

use App\Erp\Http\Controllers\Api\EmployeeApiController;
use App\Erp\Http\Controllers\Api\InvoiceApiController;
use App\Erp\Http\Controllers\Api\ProductApiController;
use App\Erp\Http\Controllers\Api\PurchaseOrderApiController;
use App\Erp\Http\Controllers\Api\SalesOrderApiController;
use App\Erp\Http\Controllers\Api\StockMovementApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('erp')
    ->name('erp.api.')
    ->group(function () {
        Route::middleware(['erp.api.auth', 'throttle:60,1'])->group(function () {
            // Employees
            Route::get('employees',       [EmployeeApiController::class, 'index'])->name('employees.index');
            Route::get('employees/{employee}', [EmployeeApiController::class, 'show'])->name('employees.show');

            // Products & Stock
            Route::get('products',                     [ProductApiController::class, 'index'])->name('products.index');
            Route::get('products/{product}',           [ProductApiController::class, 'show'])->name('products.show');
            Route::get('products/{product}/stock',     [ProductApiController::class, 'stock'])->name('products.stock');
            Route::post('stock-movements',             [StockMovementApiController::class, 'store'])->name('stock-movements.store');

            // Invoices & Payments
            Route::get('invoices',                     [InvoiceApiController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}',           [InvoiceApiController::class, 'show'])->name('invoices.show');
            Route::post('invoices/{invoice}/payments', [InvoiceApiController::class, 'storePayment'])->name('invoices.payments.store');

            // Sales Orders
            Route::get('sales-orders',  [SalesOrderApiController::class, 'index'])->name('sales-orders.index');
            Route::post('sales-orders', [SalesOrderApiController::class, 'store'])->name('sales-orders.store');

            // Purchase Orders
            Route::get('purchase-orders', [PurchaseOrderApiController::class, 'index'])->name('purchase-orders.index');
        });
    });
