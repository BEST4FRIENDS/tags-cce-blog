<?php

namespace App\Filament\Creator\Resources\PostResource\Pages;

use App\Filament\Creator\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    // WICHTIG: Aktiviert die Sprachlogik beim Bearbeiten
    use EditRecord\Concerns\Translatable;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            // Der Sprachumschalter
            Actions\LocaleSwitcher::make(),
        ];
    }
}