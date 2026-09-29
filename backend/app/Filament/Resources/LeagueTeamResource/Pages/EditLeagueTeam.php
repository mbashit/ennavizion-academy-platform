<?php

namespace App\Filament\Resources\LeagueTeamResource\Pages;

use App\Filament\Resources\LeagueTeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeagueTeam extends EditRecord
{
    protected static string $resource = LeagueTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
