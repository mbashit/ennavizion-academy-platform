<?php

namespace App\Filament\Resources\LeagueSeasonResource\Pages;

use App\Filament\Resources\LeagueSeasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeagueSeason extends EditRecord
{
    protected static string $resource = LeagueSeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
