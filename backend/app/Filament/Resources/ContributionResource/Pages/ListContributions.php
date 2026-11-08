<?php

namespace App\Filament\Resources\ContributionResource\Pages;

use App\Filament\Resources\ContributionResource;
use Filament\Resources\Pages\ListRecords;

class ListContributions extends ListRecords
{
    protected static string $resource = ContributionResource::class;

    public function getSubheading(): ?string
    {
        $t = ContributionResource::totals();

        return sprintf(
            'Verified live contributions: ₦%s from %d payment%s (₦%s restricted to a chosen purpose). Pending, failed and test-mode payments are not counted. Paystack fees are not deducted here.',
            number_format($t['total']), $t['count'], $t['count'] === 1 ? '' : 's', number_format($t['restricted'])
        );
    }
}
