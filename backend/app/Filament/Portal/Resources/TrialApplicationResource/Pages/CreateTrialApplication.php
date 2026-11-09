<?php

namespace App\Filament\Portal\Resources\TrialApplicationResource\Pages;

use App\Filament\Portal\Resources\TrialApplicationResource;
use App\Models\Program;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTrialApplication extends CreateRecord
{
    protected static string $resource = TrialApplicationResource::class;

    protected static ?string $title = 'Apply for a trial';

    protected static bool $canCreateAnother = false;

    // Owner, player and programme are re-checked here on the server: the player must belong
    // to the signed-in parent, whatever the browser sent.
    protected function handleRecordCreation(array $data): Model
    {
        $parent = auth()->user();
        $player = $parent->players()->findOrFail($data['player_id']);
        $program = Program::findOrFail($data['program_id']);

        return $parent->trialApplications()->create([
            'player_id' => $player->id,
            'program_id' => $program->id,
            'program_name' => $program->name,
            'centre' => $data['centre'] ?? null,
            'parent_note' => $data['parent_note'] ?? null,
            'consent_at' => now(),
        ]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Application sent — we\'ve emailed you a confirmation';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
