<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
// WICHTIG: Plugin Import
use Filament\SpatieLaravelTranslatablePlugin;

class CreatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('creator')
            ->path('creator') // Erreichbar unter localhost/creator
            ->login()        
            ->registration() // Blogger können sich selbst registrieren
            
            // --- 1. BRANDING & FARBE ---
            ->brandName('TAGS.BLOG Creator Studio')
            ->colors([
                'primary' => Color::Green, // Grüner Look für Blogger
            ])
            
            // --- 2. MEHRSPRACHIGKEIT AKTIVIEREN ---
            ->plugins([
                SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['de', 'en', 'hu', 'sk']),
            ])

            // --- 3. VERZEICHNISSE (Separiert vom Admin-Panel) ---
            ->discoverResources(in: app_path('Filament/Creator/Resources'), for: 'App\\Filament\\Creator\\Resources')
            ->discoverPages(in: app_path('Filament/Creator/Pages'), for: 'App\\Filament\\Creator\\Pages')
            ->discoverWidgets(in: app_path('Filament/Creator/Widgets'), for: 'App\\Filament\\Creator\\Widgets')

            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                // FilamentInfo (Github Werbung) bleibt hier weg
            ])

            // --- 4. MIDDLEWARE ---
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}