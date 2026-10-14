<?php

namespace App\Providers;

use App\Models\Partnership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Badge jumlah pengajuan kemitraan yang menunggu di sidebar Pemasok.
        View::composer('layouts.supplier', function ($view) {
            $supplierId = Auth::user()?->supplierProfile?->id;

            $view->with(
                'pendingPartnershipCount',
                $supplierId ? Partnership::where('supplier_id', $supplierId)->pending()->count() : 0
            );
        });
    }
}