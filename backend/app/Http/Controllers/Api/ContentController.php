<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\GalleryItem;
use App\Models\ImpactStat;
use App\Models\LeagueFixture;
use App\Models\LeagueResult;
use App\Models\LeagueTeam;
use App\Models\Post;
use App\Models\Product;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\Testimonial;
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
    public function index()
    {
        return response()->json([
            'SITE' => $this->site(),
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
            'IMPACT' => ImpactStat::orderBy('sort_order')->get(['value', 'label']),
            'TESTIMONIALS' => Testimonial::where('is_published', true)->latest()->get()->map(fn (Testimonial $t) => [
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
        $teams = LeagueTeam::all();

        return array_filter([
            'season' => SiteSetting::get('league_season'),
            // Same tuple order as data.js (team, P, W, D, L, GF, GA) plus an explicit
            // "is us" flag, so highlighting doesn't depend on the team name's prefix.
            'table' => $teams->map(fn (LeagueTeam $t) => [
                $t->name, $t->played, $t->won, $t->drawn, $t->lost, $t->goals_for, $t->goals_against, $t->is_ennavizion,
            ])->values(),
            'fixtures' => LeagueFixture::with('homeTeam', 'awayTeam')
                ->whereDate('date', '>=', now()->toDateString())
                ->orderBy('date')->orderBy('time')
                ->get()
                ->map(fn (LeagueFixture $f) => [
                    'date' => $f->date->toDateString(),
                    'time' => substr($f->time, 0, 5),
                    'home' => $f->homeTeam->name,
                    'away' => $f->awayTeam->name,
                    'venue' => $f->venue,
                ]),
            'results' => LeagueResult::with('homeTeam', 'awayTeam')
                ->orderByDesc('date')
                ->get()
                ->map(fn (LeagueResult $r) => [
                    'date' => $r->date->toDateString(),
                    'home' => $r->homeTeam->name,
                    'hs' => $r->home_score,
                    'away' => $r->awayTeam->name,
                    'as' => $r->away_score,
                ]),
        ], fn ($v) => $v !== null);
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
