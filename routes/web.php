<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CommodityCategoryController;
use App\Http\Controllers\Admin\CommodityController;
use App\Http\Controllers\Admin\CommodityUnitController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PendingRegistrationController;
use App\Http\Controllers\Auth\SupplierRegisterController;
use App\Http\Controllers\Auth\UmkmRegisterController;
use App\Http\Controllers\Supplier\KatalogSupplierController;
use App\Http\Controllers\Supplier\PengajuanMasukController;
use App\Http\Controllers\Supplier\PengirimanController as SupplierPengirimanController;
use App\Http\Controllers\Supplier\SupplyPlanningController as SupplierSupplyPlanningController;
use App\Http\Controllers\Umkm\CollaborativeSupplyPlanningController;
use App\Http\Controllers\Umkm\KatalogController;
use App\Http\Controllers\Umkm\PemesananController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSupplier;
use App\Http\Middleware\EnsureUserIsUmkm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.index');
});

/* Authentication Routes */
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::match(['get', 'post'], '/logout', [LoginController::class, 'destroy'])->name('logout');

Route::get('/register/supplier', [SupplierRegisterController::class, 'create'])->name('register.supplier');
Route::post('/register/supplier', [SupplierRegisterController::class, 'store'])->name('register.supplier.store');

Route::get('/register/umkm', [UmkmRegisterController::class, 'create'])->name('register.umkm');
Route::post('/register/umkm', [UmkmRegisterController::class, 'store'])->name('register.umkm.store');

Route::get('/register/pending', [PendingRegistrationController::class, 'index'])->name('register.pending');
Route::get('/api/check-status', [PendingRegistrationController::class, 'checkStatus'])->name('api.check-status');

/* Admin Guest Auth Routes */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');
    Route::match(['get', 'post'], '/logout', [AdminLoginController::class, 'destroy'])->name('logout');
});

/* UMKM Routes */
Route::prefix('umkm')->name('umkm.')->middleware(EnsureUserIsUmkm::class)->group(function () {
    Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
    Route::post('/katalog/ajukan-kemitraan', [KatalogController::class, 'ajukanKemitraan'])->name('katalog.ajukan');

    Route::post('/supply-planning', [CollaborativeSupplyPlanningController::class, 'store'])->name('supply-planning.store');
    Route::delete('/supply-planning/{id}', [CollaborativeSupplyPlanningController::class, 'destroy'])->name('supply-planning.destroy');

    Route::get('/pemesanan', [PemesananController::class, 'index'])->name('pemesanan.index');
    Route::post('/pemesanan/terbitkan', [PemesananController::class, 'terbitkan'])->name('pemesanan.terbitkan');
    Route::get('/pemesanan/{order}/konfirmasi', [PemesananController::class, 'showKonfirmasi'])->name('pemesanan.konfirmasi.show');
    Route::post('/pemesanan/{order}/konfirmasi', [PemesananController::class, 'konfirmasi'])->name('pemesanan.konfirmasi');
    Route::post('/pemesanan/{order}/konfirmasi-backorder', [PemesananController::class, 'konfirmasiBackorder'])->name('pemesanan.konfirmasi-backorder');
    Route::post('/pemesanan/{order}/terima-backorder', [PemesananController::class, 'terimaBackorder'])->name('pemesanan.terima-backorder');
    Route::post('/pemesanan/{order}/tolak-backorder', [PemesananController::class, 'tolakBackorder'])->name('pemesanan.tolak-backorder');
    Route::post('/pemesanan/pembayaran/{payment}/bayar', [PemesananController::class, 'bayarInvoice'])->name('pemesanan.bayar-invoice');
});

/* Supplier Routes */
Route::prefix('supplier')->name('supplier.')->middleware(EnsureUserIsSupplier::class)->group(function () {
    Route::get('/katalog', [KatalogSupplierController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/create', [KatalogSupplierController::class, 'create'])->name('katalog.create');
    Route::post('/katalog', [KatalogSupplierController::class, 'store'])->name('katalog.store');
    Route::get('/katalog/{id}/edit', [KatalogSupplierController::class, 'edit'])->name('katalog.edit');
    Route::put('/katalog/{id}', [KatalogSupplierController::class, 'update'])->name('katalog.update');
    Route::patch('/katalog/{id}/quick-stock', [KatalogSupplierController::class, 'updateStockStatus'])->name('katalog.quick-stock');
    Route::delete('/katalog/{id}', [KatalogSupplierController::class, 'destroy'])->name('katalog.destroy');

    Route::get('/pengajuan-masuk', [PengajuanMasukController::class, 'index'])->name('pengajuan-masuk.index');
    Route::patch('/pengajuan-masuk/{id}/terima', [PengajuanMasukController::class, 'terima'])->name('pengajuan-masuk.terima');
    Route::patch('/pengajuan-masuk/{id}/tolak', [PengajuanMasukController::class, 'tolak'])->name('pengajuan-masuk.tolak');

    Route::get('/supply-planning', [SupplierSupplyPlanningController::class, 'index'])->name('supply-planning.index');
    Route::patch('/supply-planning/{id}/konfirmasi', [SupplierSupplyPlanningController::class, 'konfirmasi'])->name('supply-planning.konfirmasi');

    Route::get('/pengiriman', [SupplierPengirimanController::class, 'index'])->name('pengiriman.index');
    Route::post('/pengiriman/{order}/kirim', [SupplierPengirimanController::class, 'prosesKirim'])->name('pengiriman.kirim');
    Route::post('/pengiriman/{order}/kirim-batch1', [SupplierPengirimanController::class, 'kirimBatch1'])->name('pengiriman.kirim-batch1');
    Route::post('/pengiriman/{order}/kirim-backorder', [SupplierPengirimanController::class, 'kirimBackorder'])->name('pengiriman.kirim-backorder');
});

/* Admin Protected Routes (Master Data Komoditas, Kategori, Satuan Baku, User Moderasi) */
Route::prefix('admin')->name('admin.')->middleware(EnsureUserIsAdmin::class)->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/users/{id}/status', [AdminDashboardController::class, 'updateUserStatus'])->name('users.update-status');
    Route::resource('commodities', CommodityController::class);
    Route::resource('categories', CommodityCategoryController::class)->except(['create', 'edit']);
    Route::resource('units', CommodityUnitController::class)->except(['create', 'edit']);
});
