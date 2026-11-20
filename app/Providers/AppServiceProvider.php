<?php

namespace App\Providers;

use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        Paginator::useTailwind();

        // Kunci pengaman: Blokir migrate:fresh, db:wipe, dan perintah destruktif lainnya jika terhubung ke Supabase
        if (str_contains((string) config('database.connections.pgsql.host', ''), 'supabase.com')) {
            DB::prohibitDestructiveCommands(! $this->app->environment('testing'));
        }

        // Cache Invalidation Otomatis untuk Data Master
        $clearCommodityCache = function () {
            Cache::forget('catalog_categories');
            Cache::forget('catalog_available_commodities');
        };
        Commodity::saved($clearCommodityCache);
        Commodity::deleted($clearCommodityCache);

        $clearCategoryCache = function () {
            Cache::forget('catalog_categories');
            Cache::forget('admin_db_categories');
        };
        CommodityCategory::saved($clearCategoryCache);
        CommodityCategory::deleted($clearCategoryCache);

        $clearUnitCache = function () {
            Cache::forget('catalog_available_units');
            Cache::forget('admin_db_units');
        };
        CommodityUnit::saved($clearUnitCache);
        CommodityUnit::deleted($clearUnitCache);
    }
}
