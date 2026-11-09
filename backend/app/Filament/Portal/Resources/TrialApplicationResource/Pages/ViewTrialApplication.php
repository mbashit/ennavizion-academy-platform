<?php

namespace App\Filament\Portal\Resources\TrialApplicationResource\Pages;

use App\Filament\Portal\Resources\TrialApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrialApplication extends ViewRecord
{
    protected static string $resource = TrialApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [TrialApplicationResource::withdrawAction(Actions\Action::make('withdraw'))];
    }
}
