<?php

namespace App\Filament\Portal\Resources\TrialApplicationResource\Pages;

use App\Filament\Portal\Resources\TrialApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrialApplications extends ListRecords
{
    protected static string $resource = TrialApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Apply for a trial')];
    }
}
