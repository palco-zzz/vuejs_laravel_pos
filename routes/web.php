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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// POS Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pos', [AdminController::class, 'index'])->name('pos.index');
    Route::post('/pos/order', [AdminController::class, 'storeOrder'])->name('pos.order.store');
});

// Karyawan CRUD Routes (using modals)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
    Route::put('/karyawan/{user}', [KaryawanController::class, 'update'])->name('karyawan.update');
    Route::delete('/karyawan/{user}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');

    Route::get('branch', [ManajemenCabangController::class, 'index'])->name('branch.index');
    Route::post('branch', [ManajemenCabangController::class, 'store'])->name('branch.store');
    Route::put('branch/{branch}', [ManajemenCabangController::class, 'update'])->name('branch.update');
    Route::delete('branch/{branch}', [ManajemenCabangController::class, 'destroy'])->name('branch.destroy');

    // Menu CRUD Routes
    Route::get('/menu', [ManajemenMenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [ManajemenMenuController::class, 'storeMenu'])->name('menu.store');
    Route::put('/menu/{menu}', [ManajemenMenuController::class, 'updateMenu'])->name('menu.update');
    Route::delete('/menu/{menu}', [ManajemenMenuController::class, 'destroyMenu'])->name('menu.destroy');

    // Category CRUD Routes
    Route::post('/category', [ManajemenMenuController::class, 'storeCategory'])->name('category.store');
    Route::put('/category/{category}', [ManajemenMenuController::class, 'updateCategory'])->name('category.update');
    Route::delete('/category/{category}', [ManajemenMenuController::class, 'destroyCategory'])->name('category.destroy');
});

require __DIR__ . '/settings.php';
