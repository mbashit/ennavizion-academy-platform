<?php

/*
 * "Ennavizion — Support a Future Footballer" (business plan 2027, section 7).
 * Amounts and purposes are planned allocations shown to donors, not a promise of a specific
 * item or beneficiary. The site says so next to them.
 */
return [
    'programme_name' => 'Support a Future Footballer',

    'options' => [
        ['amount' => 5000, 'purpose' => 'Toward shared training equipment', 'allocation' => 'equipment'],
        ['amount' => 10000, 'purpose' => "Toward a player's training kit", 'allocation' => 'kit'],
        ['amount' => 25000, 'purpose' => 'One month of Foundation training', 'allocation' => 'training_fees'],
        ['amount' => 35000, 'purpose' => 'One month of Development training', 'allocation' => 'training_fees'],
        ['amount' => 50000, 'purpose' => 'One month of Elite Pathway training', 'allocation' => 'training_fees'],
        ['amount' => 150000, 'purpose' => 'Toward a longer-term player sponsorship', 'allocation' => 'sponsorship'],
    ],

    // What a donor can restrict their contribution to. "greatest_need" = unrestricted.
    'allocations' => [
        'greatest_need' => 'Where it is needed most',
        'equipment' => 'Training equipment',
        'kit' => 'Player kit',
        'training_fees' => 'Training fees for supported players',
        'sponsorship' => 'Longer-term player sponsorship',
        'community' => 'Community football clinics',
    ],

    // Targets for the first operating year — shown as goals, never as results.
    'goals' => [
        'year' => 2027,
        'items' => [
            ['value' => '250', 'label' => 'Young people reached through the academy and outreach'],
            ['value' => '30', 'label' => 'Players receiving full or partial financial support'],
            ['value' => '4', 'label' => 'Community football clinics'],
            ['value' => '₦3,000,000', 'label' => 'Raised in contributions and sponsorship'],
        ],
    ],

    'min_naira' => 500,
    'max_naira' => 10_000_000,
];
