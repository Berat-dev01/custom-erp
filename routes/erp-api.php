<?php

use Illuminate\Support\Facades\Route;

Route::prefix('erp')
    ->name('erp.api.')
    ->group(function () {
        Route::middleware(['erp.api.auth', 'throttle:60,1'])->group(function () {
            // API endpoint'leri her fazda buraya eklenecek
        });
    });
