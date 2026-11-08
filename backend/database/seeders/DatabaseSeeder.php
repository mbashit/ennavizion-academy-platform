<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * First-deploy content. Deliberately minimal: the site's identity and the four programmes
 * with the fees from the 2027 business plan. Everything else — staff, centres, league
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
            // Fees and sessions from the 2027 business plan, section 4.
            ['foundation', 'Foundation', 'Ages 6–9', 25000, 'Ball mastery, coordination and enjoyment of the game for younger players. 2 sessions a week.'],
            ['development', 'Development', 'Ages 10–13', 35000, 'Technical development, small-sided games and quicker decision-making. 3 sessions a week.'],
            ['elite', 'Elite Pathway', 'Ages 14–18', 50000, 'Advanced coaching and competitive exposure for older players. 4 sessions a week.'],
            ['keepers', 'Goalkeeper Unit', 'Ages 10–18', 30000, 'Specialist goalkeeper coaching: handling, distribution, positioning and decision-making. 2 specialist sessions a week.'],
        ];
        foreach ($programs as $i => [$slug, $name, $ages, $fee, $description]) {
            Program::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'ages' => $ages, 'fee' => $fee, 'period' => 'per month',
                'description' => $description, 'sort_order' => $i,
            ]);
        }
    }
}
