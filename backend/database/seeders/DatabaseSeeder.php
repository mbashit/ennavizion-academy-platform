<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;

// Ports the real content that was previously hardcoded in js/data.js, so the admin panel
// opens with the actual Ennavizion site content already in it rather than an empty database.
// Values here are exactly the same demo/placeholder content data.js already flagged as
// needing client confirmation — seeding them doesn't change that; see each table's
// is_confirmed / is_verified flags, which are deliberately left false here.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedPrograms();
        $this->seedCenters();
        $this->seedLeague();
        $this->seedGallery();
        $this->seedPosts();
        $this->seedProducts();
        $this->seedTestimonial();
        $this->seedImpactStats();
    }

    private function seedSettings(): void
    {
        foreach ([
            'site_name' => 'Ennavizion',
            'domain' => 'ennavizion.com',
            'tagline' => 'Talent seen early. Talent built right.',
            'email' => 'info@ennavizion.com',
            'phone' => '+234 800 000 0000',
            'whatsapp' => '2348000000000',
            'address' => 'Ennavizion Training Ground, Lagos, Nigeria',
            'hours' => 'Mon–Sat, 7:00 – 18:00',
            'currency' => '₦',
            'league_season' => '2026/27 Season',
            'social_instagram' => 'https://instagram.com/',
            'social_x' => 'https://x.com/',
            'social_youtube' => 'https://youtube.com/',
            'social_facebook' => 'https://facebook.com/',
        ] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function seedPrograms(): void
    {
        $rows = [
            ['foundation', 'Foundation', 'Ages 6–9', 25000, 'Ball mastery, coordination and a love for the game. Two sessions a week plus a Saturday match.'],
            ['development', 'Development', 'Ages 10–13', 35000, 'Positional play, small-sided tactics and weekly video feedback. Three sessions a week.'],
            ['elite', 'Elite Pathway', 'Ages 14–18', 50000, 'Full-time style training, strength work, league fixtures and a scouting profile shared with partner clubs.'],
            ['keepers', 'Goalkeeper Unit', 'Ages 10–18', 30000, 'Specialist keeper coaching: handling, distribution, positioning and decision-making under pressure.'],
        ];

        foreach ($rows as $i => [$slug, $name, $ages, $fee, $description]) {
            Program::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'ages' => $ages, 'fee' => $fee, 'period' => 'per month',
                'description' => $description, 'sort_order' => $i,
            ]);
        }
    }

    private function seedCenters(): void
    {
        $rows = [
            ['Lagos — Surulere', 'Full-size grass pitch', 'Mon, Wed, Fri, Sat'],
            ['Abuja — Wuse', 'Artificial turf, floodlit', 'Tue, Thu, Sat'],
            ['Port Harcourt — GRA', 'Grass pitch + gym', 'Mon, Wed, Sat'],
        ];

        foreach ($rows as $i => [$name, $pitch, $days]) {
            Center::updateOrCreate(['name' => $name], ['pitch' => $pitch, 'days' => $days, 'sort_order' => $i]);
        }
    }

    private function seedLeague(): void
    {
        $teams = [
            ['Ennavizion U17', 8, 6, 1, 1, 19, 7, true],
            ['Lagoon Stars', 8, 5, 2, 1, 15, 8, false],
            ['Northgate FC', 8, 4, 3, 1, 13, 9, false],
            ['Harbour United', 8, 4, 1, 3, 12, 11, false],
            ['Savannah Rovers', 8, 3, 2, 3, 10, 10, false],
            ['Eko Juniors', 8, 2, 2, 4, 9, 13, false],
            ['Delta Rangers', 8, 1, 3, 4, 7, 14, false],
            ['Plateau Youth', 8, 0, 2, 6, 5, 18, false],
        ];

        $byName = [];
        foreach ($teams as [$name, $played, $won, $drawn, $lost, $gf, $ga, $isUs]) {
            $byName[$name] = LeagueTeam::updateOrCreate(['name' => $name], [
                'played' => $played, 'won' => $won, 'drawn' => $drawn, 'lost' => $lost,
                'goals_for' => $gf, 'goals_against' => $ga, 'is_ennavizion' => $isUs,
            ]);
        }

        $fixtures = [
            ['2026-10-03', '10:00', 'Ennavizion U17', 'Northgate FC', 'Surulere'],
            ['2026-10-03', '12:30', 'Lagoon Stars', 'Eko Juniors', 'Wuse'],
            ['2026-10-10', '10:00', 'Harbour United', 'Ennavizion U17', 'GRA'],
            ['2026-10-10', '12:30', 'Delta Rangers', 'Savannah Rovers', 'Surulere'],
        ];
        foreach ($fixtures as [$date, $time, $home, $away, $venue]) {
            LeagueFixture::updateOrCreate(
                ['date' => $date, 'home_team_id' => $byName[$home]->id, 'away_team_id' => $byName[$away]->id],
                ['time' => $time, 'venue' => $venue]
            );
        }

        $results = [
            ['2026-09-26', 'Ennavizion U17', 3, 'Delta Rangers', 0],
            ['2026-09-26', 'Plateau Youth', 1, 'Lagoon Stars', 2],
            ['2026-09-19', 'Savannah Rovers', 1, 'Ennavizion U17', 1],
            ['2026-09-19', 'Northgate FC', 2, 'Harbour United', 2],
        ];
        foreach ($results as [$date, $home, $hs, $away, $as]) {
            LeagueResult::updateOrCreate(
                ['date' => $date, 'home_team_id' => $byName[$home]->id, 'away_team_id' => $byName[$away]->id],
                ['home_score' => $hs, 'away_score' => $as]
            );
        }
    }

    private function seedGallery(): void
    {
        $rows = [
            ['photo-1786088987909-8fca31316c64', 'training', 'Morning rondo session'],
            ['photo-1598880513655-d1c6d4b2dfbf', 'matches', 'U17 league opener'],
            ['photo-1518905332052-b6cfda20ee45', 'training', 'Goalkeeper handling drills'],
            ['photo-1680024436315-fb06267264b2', 'events', 'Open trial day'],
            ['photo-1787535849781-9d35cfde0445', 'matches', 'Last-minute winner'],
            ['photo-1771577125789-93dc33e63f31', 'events', "Parents' evening"],
            ['photo-1502904550040-7534597429ae', 'training', 'Strength and conditioning'],
            ['photo-1715277331664-a546eb85a8e8', 'matches', 'Penalty shoot-out'],
            ['photo-1664325426906-ba7596dd1258', 'events', 'Scouts at the showcase'],
        ];

        foreach ($rows as $i => [$photoId, $category, $caption]) {
            GalleryItem::updateOrCreate(['caption' => $caption], [
                'image_path' => "https://images.unsplash.com/{$photoId}?auto=format&fit=crop&w=800&h=800&q=80",
                'category' => $category, 'sort_order' => $i,
            ]);
        }
    }

    private function seedPosts(): void
    {
        $rows = [
            [
                'why-small-sided-games', 'Why we play small-sided games before 11-a-side',
                '2026-09-12', 'Coaching', 4,
                'More touches, more decisions, more fun. Here is how 4v4 builds better players than full matches at young ages.',
                [
                    'At the youngest ages, a full 11-a-side match can mean a child touches the ball a handful of times. In a 4v4 game, the same player might touch it more than fifty times.',
                    'Every touch is a chance to make a decision: pass, dribble, shoot, or keep the ball. That repetition is what turns ability into instinct.',
                    'Our Foundation and Development groups spend most of their match time in small-sided formats, and move up to larger games as their understanding grows.',
                ],
                'photo-1652665314612-c48e10a01598', 'Young footballers contesting the ball in a tight small-sided training game',
            ],
            [
                'trial-day-guide', 'What to expect at an Ennavizion trial day',
                '2026-08-30', 'Academy', 3,
                'Arrival, warm-up, drills, games and feedback — a simple guide for players and parents before their first visit.',
                [
                    'Trial days start with registration and a relaxed warm-up. Coaches want to see players at their normal level, not nervous.',
                    'Players then rotate through technical stations and finish with small-sided games, where most of the assessment happens.',
                    'Every family gets written feedback within a week, whatever the outcome, with clear next steps.',
                ],
                'photo-1526494661200-9d7cfd4b2404', 'Coach kneeling to help a young player get ready before a training session',
            ],
            [
                'nutrition-for-young-players', 'Simple match-day eating for young players',
                '2026-08-14', 'Player Development', 5,
                'Nothing complicated: what to eat the night before, the morning of, and straight after a game.',
                [
                    'The evening before a match, a normal balanced meal with rice, yam or pasta alongside protein and vegetables is ideal.',
                    'On match morning, eat two to three hours before kick-off and keep it familiar. Water through the morning matters more than any sports drink.',
                    'After the game, a snack with carbohydrates and protein within an hour helps recovery — fruit and a sandwich works.',
                ],
                'photo-1751394212425-f13d2712d98e', 'Footballer drinking from a sports bottle to rehydrate after training',
            ],
            [
                'league-season-preview', 'League preview: eight teams, one trophy',
                '2026-08-01', 'Matches', 3,
                'The new season of the Ennavizion League kicks off with eight youth sides and more scouts in attendance than ever.',
                [
                    'This season the league grows to eight teams across three cities, with fixtures every Saturday from September to March.',
                    'All matches are filmed, and highlight clips are added to each player\'s scouting profile.',
                    'Fixtures and the live table are on the League page, updated after every matchday.',
                ],
                'photo-1722978687695-212eecfa4cbe', 'Young player bursting forward under floodlights during a competitive youth match',
            ],
        ];

        foreach ($rows as [$slug, $title, $date, $category, $minutes, $excerpt, $body, $photoId, $alt]) {
            Post::updateOrCreate(['slug' => $slug], [
                'title' => $title, 'published_at' => $date, 'category' => $category, 'minutes' => $minutes,
                'excerpt' => $excerpt,
                'body' => collect($body)->map(fn ($p) => "<p>{$p}</p>")->implode(''),
                'image_path' => "https://images.unsplash.com/{$photoId}?auto=format&fit=crop&w=1000&q=80",
                'image_alt' => $alt, 'is_published' => true,
            ]);
        }
    }

    private function seedProducts(): void
    {
        $rows = [
            ['home-kit', 'ENV-KIT-HOME-26', 'Home kit 2026', 18000, 'Kit', true, ['Green/Gold'], 'photo-1602674809970-89073c530b0a'],
            ['away-kit', 'ENV-KIT-AWAY-26', 'Away kit 2026', 18000, 'Kit', true, ['Off-white'], 'photo-1689624291789-7b402a15915a'],
            ['training', 'ENV-TRN-TOP-26', 'Training top', 12000, 'Training', true, null, 'photo-1774414489671-9f33b8dff0a9'],
            ['shorts', 'ENV-TRN-SHT-26', 'Training shorts', 7500, 'Training', true, null, 'photo-1789110519888-758d7ab3908e'],
            ['ball', 'ENV-EQP-BALL-5', 'Match ball, size 5', 15000, 'Equipment', true, null, 'photo-1653273056548-9af3f97dfdca'],
            ['bottle', 'ENV-EQP-BTL', 'Water bottle', 4000, 'Equipment', true, null, 'photo-1554415955-3e13eb195c5f'],
            ['cap', 'ENV-MER-CAP', 'Academy cap', 6000, 'Merch', true, null, 'photo-1588850561407-ed78c282e89b'],
            ['bag', 'ENV-MER-BAG', 'Kit bag', 14000, 'Merch', false, null, 'photo-1708622833152-924c6e364138'],
        ];

        foreach ($rows as $i => [$id, $sku, $name, $price, $tag, $inStock, $colors, $photoId]) {
            Product::updateOrCreate(['sku' => $sku], [
                'name' => $name, 'price' => $price, 'tag' => $tag, 'in_stock' => $inStock, 'colors' => $colors,
                'image_path' => "https://images.unsplash.com/{$photoId}?auto=format&fit=crop&w=700&h=700&q=80",
                'sort_order' => $i,
            ]);
        }
    }

    private function seedTestimonial(): void
    {
        // Deliberately generic — see the Testimonial model doc comment. Published because
        // the placeholder wording is itself the honest thing to show; not verified, because
        // it isn't a real quote.
        Testimonial::updateOrCreate(['name' => 'Verified player / parent testimonial'], [
            'quote' => 'Player success story will appear here once supplied and approved by Ennavizion.',
            'role' => 'Pending confirmation',
            'image_path' => 'https://images.unsplash.com/photo-1506277886164-e25aa3f4ef7f?auto=format&fit=crop&w=400&q=80',
            'is_verified' => false,
            'is_published' => true,
        ]);
    }

    private function seedImpactStats(): void
    {
        $rows = [
            ['XX+', 'Players supported'],
            ['₦X.XM', 'Scholarship support'],
            ['XXX+', 'Kits distributed'],
        ];

        foreach ($rows as $i => [$value, $label]) {
            ImpactStat::updateOrCreate(['label' => $label], ['value' => $value, 'is_confirmed' => false, 'sort_order' => $i]);
        }
    }
}
