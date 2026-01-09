<?php

namespace App\Filament\Creator\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Content\Models\Post; // WICHTIG: Das Modul-Model
use Illuminate\Support\Facades\Auth;

class CreatorStatsOverview extends BaseWidget
{
    // Aktualisiert sich live (optional: Polling deaktivieren um Last zu sparen)
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Basis-Query: Nur meine eigenen Posts!
        $myPosts = Post::where('user_id', Auth::id());

        return [
            // Karte 1: Alle Beiträge
            Stat::make(__('blog.widget_total'), $myPosts->count())
                ->description(__('blog.widget_total_desc'))
                ->icon('heroicon-m-document-text')
                ->color('primary'), // Grün im Creator Panel

            // Karte 2: Veröffentlicht (Online)
            Stat::make(__('blog.widget_published'), (clone $myPosts)->where('status', 'published')->count())
                ->description(__('blog.widget_published_desc'))
                ->icon('heroicon-m-check-circle')
                ->color('success'),

            // Karte 3: Entwürfe & Prüfung
            Stat::make(__('blog.widget_draft'), (clone $myPosts)->whereIn('status', ['draft', 'review'])->count())
                ->description(__('blog.widget_draft_desc'))
                ->icon('heroicon-m-pencil-square')
                ->color('warning'),
        ];
    }
}