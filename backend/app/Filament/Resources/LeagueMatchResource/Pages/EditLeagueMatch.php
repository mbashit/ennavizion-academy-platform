<?php

namespace App\Filament\Resources\LeagueMatchResource\Pages;

use App\Filament\Resources\LeagueMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeagueMatch extends EditRecord
{
    protected static string $resource = LeagueMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
