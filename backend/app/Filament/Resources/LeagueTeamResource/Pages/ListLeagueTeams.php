<?php

namespace App\Filament\Resources\LeagueTeamResource\Pages;

use App\Filament\Resources\LeagueTeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeagueTeams extends ListRecords
{
    protected static string $resource = LeagueTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
