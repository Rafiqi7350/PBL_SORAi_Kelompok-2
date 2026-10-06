<?php

use App\Http\Controllers\Admin\CommodityCategoryController;
use App\Http\Controllers\Admin\CommodityController;
use App\Http\Controllers\Admin\CommodityUnitController;
use App\Http\Controllers\Auth\SupplierRegisterController;
use App\Http\Controllers\Supplier\KatalogSupplierController;
use App\Http\Controllers\Umkm\KatalogController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.index');
});

Route::get('/register/supplier', [SupplierRegisterController::class, 'create'])->name('register.supplier');
Route::post('/register/supplier', [SupplierRegisterController::class, 'store'])->name('register.supplier.store');

/* UMKM Routes */
Route::prefix('umkm')->name('umkm.')->group(function () {
    Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
    Route::post('/katalog/ajukan-kemitraan', [KatalogController::class, 'ajukanKemitraan'])->name('katalog.ajukan');
});

/* Supplier Routes */
Route::prefix('supplier')->name('supplier.')->group(function () {
    Route::get('/katalog', [KatalogSupplierController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/create', [KatalogSupplierController::class, 'create'])->name('katalog.create');
    Route::post('/katalog', [KatalogSupplierController::class, 'store'])->name('katalog.store');
    Route::get('/katalog/{id}/edit', [KatalogSupplierController::class, 'edit'])->name('katalog.edit');
    Route::put('/katalog/{id}', [KatalogSupplierController::class, 'update'])->name('katalog.update');
    Route::patch('/katalog/{id}/quick-stock', [KatalogSupplierController::class, 'updateStockStatus'])->name('katalog.quick-stock');
    Route::delete('/katalog/{id}', [KatalogSupplierController::class, 'destroy'])->name('katalog.destroy');
});

/* Admin Routes (Master Data Komoditas, Kategori, Satuan Baku) */
Route::prefix('admin')->name('admin.')->middleware(EnsureUserIsAdmin::class)->group(function () {
    Route::resource('commodities', CommodityController::class);
    Route::resource('categories', CommodityCategoryController::class)->except(['create', 'edit']);
    Route::resource('units', CommodityUnitController::class)->except(['create', 'edit']);
});
