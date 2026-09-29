<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use Filament\Resources\Pages\ListRecords;

// No create action: submissions only ever arrive from the public site's forms (see
// SubmissionResource's canCreate() override), there's nothing for this button to link to.
class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
