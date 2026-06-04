<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect('/admin/erp/dashboard');
});

Route::get('/admin', function () {
    return redirect('/admin/erp/dashboard');
});
Route::prefix('admin')
    ->name('admin.')
    ->middleware('web')
    ->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', fn () => redirect()->route('erp.dashboard'))->name('dashboard');
        Route::get('/settings', fn () => redirect()->route('erp.dashboard'))->name('settings.index');
        Route::get('/users', fn () => redirect()->route('erp.dashboard'))->name('users.index');
        Route::post('/locale', function (\Illuminate\Http\Request $request) {
            $request->session()->put('locale', $request->input('locale', 'tr'));

            return back();
        })->name('locale.update');
    });
