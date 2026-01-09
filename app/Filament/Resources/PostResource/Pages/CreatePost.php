<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\LocaleSwitcher; // <--- Wichtig: Importieren

class CreatePost extends CreateRecord
{
    use CreateRecord\Concerns\Translatable; // <--- Wichtig: Trait für die Seite

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(), // <--- Hier zwingen wir den Umschalter rein
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}