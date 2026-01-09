<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch; // <--- ZEILE 1: HIER EINFÜGEN

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
        // HIER STARTET DER CONFIG-BLOCK
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['de', 'en', 'hu', 'sk']) // Unsere Sprachen
                ->visible(fn (): bool => true);      // Immer sichtbar machen
        });
        // ENDE CONFIG-BLOCK
    }
}