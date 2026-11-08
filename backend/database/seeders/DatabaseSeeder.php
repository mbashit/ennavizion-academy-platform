<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * First-deploy content. Deliberately minimal: only the site's identity and the programme
 * structure by age group (with no fees). Everything else — staff, centres, fees, league
 * seasons, stats, testimonials, photos, products, news — is added by the academy in the
 * admin panel once it is real. See docs/MISSING-BUSINESS-INFO.md.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'site_name' => 'Ennavizion',
            'domain' => 'ennavizion.com',
            'tagline' => 'Talent seen early. Talent built right.',
            'email' => 'info@ennavizion.com',
            'currency' => '₦',
        ] as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $programs = [
            ['foundation', 'Foundation', 'Ages 6–9', 'Ball mastery, coordination and enjoyment of the game for younger players.'],
            ['development', 'Development', 'Ages 10–13', 'Positional play, small-sided games and quicker decision-making.'],
            ['elite', 'Elite Pathway', 'Ages 14–18', 'Advanced technical, tactical and physical work for older players aiming at competitive football.'],
            ['keepers', 'Goalkeeper Unit', 'Ages 10–18', 'Specialist goalkeeper coaching: handling, distribution, positioning and decision-making.'],
        ];
        foreach ($programs as $i => [$slug, $name, $ages, $description]) {
            Program::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'ages' => $ages, 'fee' => null, 'period' => null,
                'description' => $description, 'sort_order' => $i,
            ]);
        }
    }
}
