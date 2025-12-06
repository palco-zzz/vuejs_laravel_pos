<?php

use Inertia\Inertia;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ManajemenMenuController;
use App\Http\Controllers\Admin\ManajemenCabangController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Reports Routes - Admin Only
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/reports/transactions', [App\Http\Controllers\ReportController::class, 'transactions'])->name('reports.transactions');
    Route::put('/reports/transactions/{order}', [App\Http\Controllers\ReportController::class, 'updateTransaction'])->name('reports.transactions.update');
    Route::get('/reports/menu-analysis', [App\Http\Controllers\ReportController::class, 'menuAnalysis'])->name('reports.menu-analysis');
});


// POS Routes (accessible by both admin and cashier)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pos', [AdminController::class, 'index'])->name('pos.index');
    Route::post('/pos/order', [AdminController::class, 'storeOrder'])->name('pos.order.store');
    Route::get('/pos/history', [AdminController::class, 'history'])->name('pos.history');
    
    // Admin-only: Edit order items
    Route::put('/pos/order/{order}/items', [AdminController::class, 'updateItems'])
        ->middleware('role:admin')
        ->name('pos.order.updateItems');
    
    // Admin-only: Void (soft delete) transaction
    Route::delete('/pos/order/{order}/void', [AdminController::class, 'voidTransaction'])
        ->middleware('role:admin')
        ->name('pos.order.void');
});

// Karyawan CRUD Routes (using modals) - Admin Only
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
    Route::put('/karyawan/{user}', [KaryawanController::class, 'update'])->name('karyawan.update');
    Route::delete('/karyawan/{user}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');

    // Branch Management - Admin Only
    Route::get('branch', [ManajemenCabangController::class, 'index'])->name('branch.index');
    Route::post('branch', [ManajemenCabangController::class, 'store'])->name('branch.store');
    Route::put('branch/{branch}', [ManajemenCabangController::class, 'update'])->name('branch.update');
    Route::delete('branch/{branch}', [ManajemenCabangController::class, 'destroy'])->name('branch.destroy');

    // Menu CRUD Routes - Admin Only
    Route::get('/menu', [ManajemenMenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [ManajemenMenuController::class, 'storeMenu'])->name('menu.store');
    Route::put('/menu/{menu}', [ManajemenMenuController::class, 'updateMenu'])->name('menu.update');
    Route::delete('/menu/{menu}', [ManajemenMenuController::class, 'destroyMenu'])->name('menu.destroy');

    // Category CRUD Routes - Admin Only
    Route::post('/category', [ManajemenMenuController::class, 'storeCategory'])->name('category.store');
    Route::put('/category/{category}', [ManajemenMenuController::class, 'updateCategory'])->name('category.update');
    Route::delete('/category/{category}', [ManajemenMenuController::class, 'destroyCategory'])->name('category.destroy');
});

require __DIR__ . '/settings.php';
