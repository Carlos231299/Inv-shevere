<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Pagination\Paginator;

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
        Paginator::useBootstrapFive();

        // Share digital settings status and bases globally
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check()) {
                $today = \Carbon\Carbon::today();
                $resetNequiAt_Raw = \App\Models\Setting::getResetTimestamp('nequi');
                $resetBancolombiaAt_Raw = \App\Models\Setting::getResetTimestamp('bancolombia');

                $needsDigitalSettings = (
                    $resetNequiAt_Raw === '1970-01-01 00:00:00' || 
                    $resetBancolombiaAt_Raw === '1970-01-01 00:00:00' ||
                    !\Carbon\Carbon::parse($resetNequiAt_Raw)->isToday() || 
                    !\Carbon\Carbon::parse($resetBancolombiaAt_Raw)->isToday()
                );

                $view->with([
                    'needsDigitalSettings' => $needsDigitalSettings,
                    'globalBaseNequi' => \App\Models\Setting::getInitialNequi(),
                    'globalBaseBancolombia' => \App\Models\Setting::getInitialBancolombia(),
                    'globalBaseCash' => \App\Models\Setting::getInitialCash(),
                ]);
            } else {
                $view->with(['needsDigitalSettings' => false]);
            }
        });
    }
}
