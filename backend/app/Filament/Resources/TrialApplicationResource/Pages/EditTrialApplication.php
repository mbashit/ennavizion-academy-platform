<?php

namespace App\Filament\Resources\TrialApplicationResource\Pages;

use App\Filament\Resources\TrialApplicationResource;
use Filament\Resources\Pages\EditRecord;

class EditTrialApplication extends EditRecord
{
    protected static string $resource = TrialApplicationResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->record->wasChanged(['status', 'trial_at', 'trial_location', 'message_to_parent'])
            ? 'Saved — the parent has been emailed'
            : 'Saved';
    }
}
