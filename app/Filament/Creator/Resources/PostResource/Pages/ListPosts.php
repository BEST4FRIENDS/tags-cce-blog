<?php

namespace App\Filament\Creator\Resources\PostResource\Pages;

use App\Filament\Creator\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    // WICHTIG: Aktiviert die Sprachlogik für die Liste
    use ListRecords\Concerns\Translatable;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // Der Sprachumschalter für die Liste
            Actions\LocaleSwitcher::make(),
        ];
    }
}