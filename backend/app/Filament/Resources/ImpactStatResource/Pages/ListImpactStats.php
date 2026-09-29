<?php

namespace App\Filament\Resources\ImpactStatResource\Pages;

use App\Filament\Resources\ImpactStatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImpactStats extends ListRecords
{
    protected static string $resource = ImpactStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
