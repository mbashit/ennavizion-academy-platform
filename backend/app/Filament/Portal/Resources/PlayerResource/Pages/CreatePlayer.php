<?php

namespace App\Filament\Portal\Resources\PlayerResource\Pages;

use App\Filament\Portal\Resources\PlayerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePlayer extends CreateRecord
{
    protected static string $resource = PlayerResource::class;

    // Always attached to the signed-in parent — the owner never comes from form input.
    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->players()->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
