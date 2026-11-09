<?php

namespace App\Filament\Portal\Resources\PlayerResource\Pages;

use App\Filament\Portal\Resources\PlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlayer extends EditRecord
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
