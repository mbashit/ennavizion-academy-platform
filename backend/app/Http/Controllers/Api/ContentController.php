<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\GalleryItem;
use App\Models\ImpactStat;
use App\Models\LeagueMatch;
use App\Models\LeagueSeason;
use App\Models\Post;
use App\Models\Product;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Services\LeagueStandings;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Storage;

/**
 * Everything the static site renders, in one response. The shape deliberately mirrors the
 * globals in ennavizion/js/data.js (SITE, PROGRAMS, CENTERS, LEAGUE, ...) so main.js can
 * swap this in over its bundled copy without any renderer knowing where the data came from.
 * One request rather than one per page: the whole payload is small and every page needs
 * the SITE block for the header/footer anyway.
 */
class ContentController extends Controller
{
    public function __construct(
        private readonly LeagueStandings $standings,
        private readonly PaystackService $paystack,
    ) {
    }

    public function index()
    {
        return response()->json([
            'SITE' => $this->site(),
            'CONTRIBUTIONS' => [
                'programme' => config('contributions.programme_name'),
                'options' => config('contributions.options'),
                'allocations' => config('contributions.allocations'),
                'min' => config('contributions.min_naira'),
            ],
            'GOALS' => config('contributions.goals'),
            'PROGRAMS' => Program::orderBy('sort_order')->get()->map(fn (Program $p) => [
                'id' => $p->slug,
                'name' => $p->name,
                'ages' => $p->ages,
                'fee' => $p->fee,
                'period' => $p->period,
                'text' => $p->description,
            ]),
            'CENTERS' => Center::orderBy('sort_order')->get(['name', 'pitch', 'days']),
            'LEAGUE' => $this->league(),
            // Only figures staff have marked as confirmed, and only real, consented quotes.
            'IMPACT' => ImpactStat::where('is_confirmed', true)->orderBy('sort_order')->get(['value', 'label']),
            'TESTIMONIALS' => Testimonial::where('is_published', true)->where('is_verified', true)->latest()->get()->map(fn (Testimonial $t) => [
                'quote' => $t->quote,
                'name' => $t->name,
                'role' => $t->role,
                'image' => $this->imageUrl($t->image_path),
            ]),
            'GALLERY' => GalleryItem::orderBy('sort_order')->get()->map(fn (GalleryItem $g) => [
                'src' => $this->imageUrl($g->image_path),
                'category' => $g->category,
                'caption' => $g->caption,
            ]),
            'PRODUCTS' => Product::orderBy('sort_order')->get()->map(fn (Product $p) => [
                'id' => $p->sku,
                'sku' => $p->sku,
                'name' => $p->name,
                'price' => $p->price,
                'tag' => $p->tag,
                'stock' => $p->in_stock,
                'colors' => $p->colors ?: null,
                'image' => $this->imageUrl($p->image_path),
            ]),
            'POSTS' => Post::where('is_published', true)
                ->whereDate('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->get()
                ->map(fn (Post $p) => [
                    'id' => $p->slug,
                    'title' => $p->title,
                    'date' => $p->published_at->toDateString(),
                    'category' => $p->category,
                    'minutes' => $p->minutes,
                    'image' => $this->imageUrl($p->image_path),
                    'imageAlt' => $p->image_alt,
                    'excerpt' => $p->excerpt,
                    // RichEditor HTML written by admins in Filament, rendered as-is by post().
                    'bodyHtml' => $p->body,
                ]),
        ]);
    }

    /** Only keys an admin has actually filled in — blanks fall back to data.js's SITE. */
    private function site(): array
    {
        $s = SiteSetting::pluck('value', 'key')->filter(fn ($v) => filled($v));

        return array_filter([
            'name' => $s['site_name'] ?? null,
            'domain' => $s['domain'] ?? null,
            'tagline' => $s['tagline'] ?? null,
            'email' => $s['email'] ?? null,
            'phone' => $s['phone'] ?? null,
            'whatsapp' => $s['whatsapp'] ?? null,
            'address' => $s['address'] ?? null,
            'hours' => $s['hours'] ?? null,
            'currency' => $s['currency'] ?? null,
            // off | test | live (see PaystackService::mode). Checkout only opens in test or live;
            // test mode is labelled on the page and never counted as a real contribution.
            'donations' => $this->paystack->isConfigured(),
            'donationMode' => $this->paystack->mode(),
            'socials' => array_filter([
                'instagram' => $s['social_instagram'] ?? null,
                'x' => $s['social_x'] ?? null,
                'youtube' => $s['social_youtube'] ?? null,
                'facebook' => $s['social_facebook'] ?? null,
            ]) ?: null,
        ], fn ($v) => $v !== null);
    }

    private function league(): array
    {
        $season = LeagueSeason::current();
        if (! $season) {
            return ['season' => '', 'table' => [], 'teams' => [], 'fixtures' => [], 'results' => []];
        }
        $season->load('teams');

        $match = fn (LeagueMatch $m) => [
            'date' => $m->date->toDateString(),
            'time' => $m->time ? substr($m->time, 0, 5) : null,
            'venue' => $m->venue,
            'home' => $m->homeTeam->name,
            'away' => $m->awayTeam->name,
            'status' => $m->status,
            'hs' => $m->home_score,
            'as' => $m->away_score,
        ];

        return [
            'season' => trim($season->name.($season->age_group ? ' · '.$season->age_group : '')),
            'rules' => [
                'win' => $season->points_win, 'draw' => $season->points_draw, 'loss' => $season->points_loss,
                'tiebreakers' => $season->tiebreakerOrder(),
            ],
            // Calculated from published results with this season's rules — never typed in.
            'table' => $this->standings->for($season),
            'teams' => $season->teams->map(fn ($t) => ['name' => $t->name, 'us' => $t->is_ennavizion])->values(),
            'fixtures' => $season->matches()->upcoming()->with('homeTeam', 'awayTeam')
                ->orderBy('date')->orderBy('time')->get()->map($match)->values(),
            'results' => $season->matches()->countedResults()->with('homeTeam', 'awayTeam')
                ->orderByDesc('date')->get()->map($match)->values(),
        ];
    }

    /** Seeded rows hold full Unsplash URLs; admin uploads hold a path on the public disk. */
    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }
}
