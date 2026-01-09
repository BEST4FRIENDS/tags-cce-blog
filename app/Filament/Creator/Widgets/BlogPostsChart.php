<?php

namespace App\Filament\Creator\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Content\Models\Post; // WICHTIG: Dein Modul-Model nutzen
use Illuminate\Support\Facades\Auth;

class BlogPostsChart extends ChartWidget
{
    protected static ?string $heading = 'Meine Top Beiträge (Aufrufe)';
    
    // Sortierung: Soll unter den Info-Boxen erscheinen
    protected static ?int $sort = 2; 

    protected function getData(): array
    {
        // Hole die Top 5 Artikel des eingeloggten Users (Peter)
        $data = Post::where('user_id', Auth::id())
            ->orderByDesc('views')
            ->take(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Anzahl Aufrufe',
                    'data' => $data->pluck('views')->toArray(),
                    'backgroundColor' => '#22c55e', // Grün (passend zum Creator Theme)
                    'borderColor' => '#16a34a',
                ],
            ],
            'labels' => $data->pluck('title')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Balkendiagramm
    }
}