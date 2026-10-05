<?php

use App\Http\Controllers\Auth\SupplierRegisterController;
use App\Http\Controllers\Umkm\KatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.index');
});

Route::get('/register/supplier', [SupplierRegisterController::class, 'create'])->name('register.supplier');
Route::post('/register/supplier', [SupplierRegisterController::class, 'store'])->name('register.supplier.store');

Route::prefix('umkm')->name('umkm.')->group(function () {
    Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
    Route::post('/katalog/ajukan-kemitraan', [KatalogController::class, 'ajukanKemitraan'])->name('katalog.ajukan');
});
