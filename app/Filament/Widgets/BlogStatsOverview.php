<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Content\Models\Post;

class BlogStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Die Basis-Abfrage vorbereiten
        $query = Post::query();

        // 2. Wenn KEIN Admin: Filter auf eigene User-ID setzen
        if (auth()->check() && !auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        // 3. Daten zählen (mit den Filtern von oben)
        // Wir klonen die Query, damit wir sie mehrmals nutzen können
        return [
            // Statistik 1: Alle (eigenen) Beiträge
            Stat::make(__('blog.widget_total'), (clone $query)->count())
                ->description(__('blog.widget_total_desc'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            // Statistik 2: Veröffentlicht (nur eigene)
            Stat::make(__('blog.widget_published'), (clone $query)->where('status', 'published')->count())
                ->description(__('blog.widget_published_desc'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            // Statistik 3: Entwürfe (nur eigene)
            Stat::make(__('blog.widget_draft'), (clone $query)->where('status', 'draft')->count())
                ->description(__('blog.widget_draft_desc'))
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),
        ];
    }
}