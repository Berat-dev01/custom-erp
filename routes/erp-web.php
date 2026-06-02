<?php

use App\Erp\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('erp.routes.middleware', ['web']))
    ->group(function () {
        Route::prefix(config('erp.routes.admin_prefix', 'admin/erp'))
            ->name('erp.')
            ->middleware(['erp.access', 'throttle:240,1'])
            ->group(function () {
                Route::get('/', fn () => redirect()->route('erp.dashboard'))->name('home');
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

                // Modül rotaları her fazda buraya eklenecek
            });
    });
