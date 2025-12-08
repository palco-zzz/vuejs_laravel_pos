<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

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
        // Force PHP's internal timezone to Asia/Jakarta (GMT+7)
        date_default_timezone_set('Asia/Jakarta');
        
        // Set Carbon locale to Indonesian for localized date formatting
        Carbon::setLocale('id');
    }
}
