<?php

namespace App\Filament\Resources\LeagueResultResource\Pages;

use App\Filament\Resources\LeagueResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeagueResults extends ListRecords
{
    protected static string $resource = LeagueResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
