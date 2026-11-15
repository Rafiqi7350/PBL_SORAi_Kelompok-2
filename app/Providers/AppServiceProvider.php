<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
