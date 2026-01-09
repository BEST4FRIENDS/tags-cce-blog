<?php

namespace App\Filament\Creator\Resources\PostResource\Pages;

use App\Filament\Creator\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    // WICHTIG: Aktiviert die Sprachlogik beim Erstellen
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Der Sprachumschalter oben rechts
            Actions\LocaleSwitcher::make(),
        ];
    }
}