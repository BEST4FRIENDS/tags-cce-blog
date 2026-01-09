<?php

namespace App\Filament\Creator\Widgets;

use Filament\Widgets\Widget;

class AnalyticsOverview extends Widget
{
    // Verweist auf die HTML-Datei
    protected static string $view = 'filament.creator.widgets.analytics-overview';

    // Ganz unten anzeigen
    protected static ?int $sort = 3; 
    
    // Volle Breite nutzen
    protected int | string | array $columnSpan = 'full';
    
    // Titel (optional)
    protected static ?string $heading = 'Google Analytics Report';
}