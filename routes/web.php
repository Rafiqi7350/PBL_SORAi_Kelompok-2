<?php

use App\Http\Controllers\Auth\SupplierRegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.index');
});

Route::get('/register/supplier', [SupplierRegisterController::class, 'create'])->name('register.supplier');
Route::post('/register/supplier', [SupplierRegisterController::class, 'store'])->name('register.supplier.store');
