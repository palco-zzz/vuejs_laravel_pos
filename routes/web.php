<?php

use Inertia\Inertia;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ManajemenCabangController;
use App\Http\Controllers\KaryawanController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/pos', [AdminController::class, 'index'])->name('index');

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
});

require __DIR__ . '/settings.php';
