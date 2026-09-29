<?php

namespace App\Filament\Resources\LeagueFixtureResource\Pages;

use App\Filament\Resources\LeagueFixtureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeagueFixture extends EditRecord
{
    protected static string $resource = LeagueFixtureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
