<?php

namespace App\Filament\Portal\Widgets;

use App\Filament\Portal\Resources\PlayerResource;
use App\Filament\Portal\Resources\TrialApplicationResource;
use App\Models\TrialApplication;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The parent's dashboard summary: their players, open applications and next trial. */
class FamilyOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Rendered with the page (no loading flash) — it's three quick counts.
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user = auth()->user();
        $open = $user->trialApplications()->whereNotIn('status', TrialApplication::CLOSED)->count();
        $next = $user->trialApplications()->with('player')->where('status', 'trial_scheduled')
            ->where('trial_at', '>=', now())->orderBy('trial_at')->first();

        return [
            Stat::make('Players', $user->players()->count())
                ->description($user->players()->exists() ? 'Manage your players' : 'Add your child to get started')
                ->url(PlayerResource::getUrl('index')),
            Stat::make('Open applications', $open)
                ->description('Apply for a free trial')
                ->url(TrialApplicationResource::getUrl($open ? 'index' : 'create')),
            Stat::make('Next trial', $next ? $next->trial_at->format('j M, g:ia') : 'Not scheduled')
                ->description($next ? "{$next->player->first_name} — {$next->trial_location}" : 'We\'ll email you when a trial is booked')
                ->color($next ? 'success' : 'gray'),
        ];
    }
}
