<?php
/**
 * Test Data Generator for Control Panel
 *
 * Generates realistic test data for all entities:
 * - Services (3-letter codes)
 * - Discount Groups (holding company names)
 * - LMS (technology integrators)
 * - Customers (random company names)
 * - Pricing Tiers (default, group, customer)
 * - Customer Settings (minimums, annualized)
 * - Escalators
 * - Business Rules
 * - Transaction Types
 * - Billing Flags
 * - COGS
 * - System Settings
 *
 * Usage: php generate_test_data.php [--seed=N] [--customers=N]
 */

// ============================================================
// CONFIGURATION
// ============================================================

$CONFIG = [
    'seed' => 12345,  // For reproducible randomness
    'customer_count' => 100,
    'service_count' => 12,
    'group_count' => 8,
    'lms_count' => 15,

    // Distribution percentages
    'customer_active_pct' => 75,
    'customer_paused_pct' => 15,
    'customer_decommissioned_pct' => 10,
    'customer_in_group_pct' => 60,
    'customer_has_override_pct' => 25,
    'customer_has_minimum_pct' => 40,
    'customer_has_annualized_pct' => 20,
    'customer_has_escalator_pct' => 50,
    'group_has_override_pct' => 70,

    // Pricing ranges
    'price_min' => 0.02,
    'price_max' => 0.85,
    'cogs_margin' => 0.4,  // COGS is 40% of price (we keep 60%)
    'minimum_min' => 250,
    'minimum_max' => 5000,

    // Commission
    'default_commission_rate' => 10.0,
    'lms_override_pct' => 30,
    'commission_min' => 8.0,
    'commission_max' => 18.0,
];

// Parse command line args
foreach ($argv as $arg) {
    if (preg_match('/^--seed=(\d+)$/', $arg, $m)) {
        $CONFIG['seed'] = (int)$m[1];
    }
    if (preg_match('/^--customers=(\d+)$/', $arg, $m)) {
        $CONFIG['customer_count'] = (int)$m[1];
    }
}

// Seed random
mt_srand($CONFIG['seed']);

// ============================================================
// NAME GENERATORS
// ============================================================

// 3-letter service codes
function generate_service_codes($count) {
    $codes = [];
    $used = [];

    // Some realistic prefixes
    $prefixes = ['CR', 'ID', 'AD', 'PH', 'BK', 'FN', 'VR', 'AU', 'DC', 'EM', 'SS', 'TX', 'LN', 'AC', 'RP'];
    $suffixes = ['V', 'X', 'K', 'P', 'S', 'T', 'R', 'N', 'M', 'L', 'C', 'B', 'A', 'D', 'E'];

    while (count($codes) < $count) {
        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $suffix = $suffixes[mt_rand(0, count($suffixes) - 1)];
        $code = $prefix . $suffix;

        if (!isset($used[$code])) {
            $used[$code] = true;
            $codes[] = $code;
        }
    }

    return $codes;
}

// Service names based on codes
function code_to_service_name($code) {
    $names = [
        'CR' => 'Credit',
        'ID' => 'Identity',
        'AD' => 'Address',
        'PH' => 'Phone',
        'BK' => 'Bank',
        'FN' => 'Financial',
        'VR' => 'Verification',
        'AU' => 'Authentication',
        'DC' => 'Document',
        'EM' => 'Employment',
        'SS' => 'Social Security',
        'TX' => 'Tax',
        'LN' => 'Lending',
        'AC' => 'Account',
        'RP' => 'Report',
    ];

    $suffixes = [
        'V' => 'Verify',
        'X' => 'Extended',
        'K' => 'Check',
        'P' => 'Plus',
        'S' => 'Standard',
        'T' => 'Total',
        'R' => 'Review',
        'N' => 'Network',
        'M' => 'Monitor',
        'L' => 'Lookup',
        'C' => 'Core',
        'B' => 'Basic',
        'A' => 'Advanced',
        'D' => 'Data',
        'E' => 'Express',
    ];

    $prefix = substr($code, 0, 2);
    $suffix = substr($code, 2, 1);

    $base = isset($names[$prefix]) ? $names[$prefix] : $prefix;
    $mod = isset($suffixes[$suffix]) ? $suffixes[$suffix] : $suffix;

    return $base . ' ' . $mod;
}

// Holding company names
function generate_group_names($count) {
    $prefixes = [
        'Atlas', 'Beacon', 'Crown', 'Dynasty', 'Empire', 'Fortress', 'Global', 'Horizon',
        'Imperial', 'Jupiter', 'Kingston', 'Legacy', 'Meridian', 'Nexus', 'Omega', 'Pinnacle',
        'Quantum', 'Regent', 'Sterling', 'Titan', 'Unity', 'Vanguard', 'Windsor', 'Apex',
        'Capital', 'Diamond', 'Evergreen', 'First', 'Golden', 'Highland'
    ];

    $suffixes = [
        'Holdings', 'Group', 'Partners', 'Capital', 'Enterprises', 'Corporation',
        'Ventures', 'Industries', 'Consortium', 'Alliance'
    ];

    $names = [];
    $used = [];

    while (count($names) < $count) {
        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $suffix = $suffixes[mt_rand(0, count($suffixes) - 1)];
        $name = $prefix . ' ' . $suffix;

        if (!isset($used[$name])) {
            $used[$name] = true;
            $names[] = $name;
        }
    }

    return $names;
}

// LMS names (technology integrators in banking/finance)
function generate_lms_names($count) {
    $prefixes = [
        'Fiserv', 'Jack Henry', 'Temenos', 'FIS', 'Finastra', 'NCR', 'ACI', 'Bottomline',
        'Q2', 'Alkami', 'nCino', 'Blend', 'Plaid', 'MX', 'Yodlee', 'Finicity',
        'CoreCard', 'Mambu', 'Thought Machine', 'Galileo', 'Marqeta', 'Stripe Treasury',
        'Unit', 'Treasury Prime', 'Synctera', 'Bond', 'Column', 'Lead Bank Tech',
        'Cross River Digital', 'Green Dot Platform'
    ];

    $names = [];
    $used = [];

    // Use real-ish names first, then generate if needed
    shuffle($prefixes);

    for ($i = 0; $i < min($count, count($prefixes)); $i++) {
        $names[] = $prefixes[$i];
        $used[$prefixes[$i]] = true;
    }

    // Generate more if needed
    $tech_words = ['Tech', 'Systems', 'Solutions', 'Connect', 'Link', 'Bridge', 'Core'];
    while (count($names) < $count) {
        $base = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $suffix = $tech_words[mt_rand(0, count($tech_words) - 1)];
        $name = $base . ' ' . $suffix;

        if (!isset($used[$name])) {
            $used[$name] = true;
            $names[] = $name;
        }
    }

    return $names;
}

// Random company names
function generate_company_names($count) {
    $first_parts = [
        'Acme', 'Alpha', 'Apex', 'Arrow', 'Atlas', 'Axis', 'Azure', 'Bay', 'Bear', 'Blue',
        'Bolt', 'Bridge', 'Bright', 'Canyon', 'Cedar', 'Central', 'Circle', 'City', 'Clear', 'Cloud',
        'Coastal', 'Copper', 'Core', 'Crest', 'Crown', 'Crystal', 'Delta', 'Diamond', 'Digital', 'Direct',
        'Eagle', 'East', 'Edge', 'Elite', 'Elm', 'Emerald', 'Empire', 'Energy', 'Epic', 'Evergreen',
        'Express', 'Falcon', 'First', 'Five', 'Flex', 'Focus', 'Forest', 'Forge', 'Fort', 'Forward',
        'Foundation', 'Four', 'Fox', 'Freedom', 'Fresh', 'Front', 'Future', 'Gateway', 'Genesis', 'Global',
        'Gold', 'Grand', 'Granite', 'Great', 'Green', 'Grid', 'Gulf', 'Harbor', 'Hawk', 'Heart',
        'Heritage', 'High', 'Highland', 'Hill', 'Home', 'Honor', 'Horizon', 'Hub', 'Hudson', 'Icon',
        'Ideal', 'Impact', 'Independence', 'Infinity', 'Inland', 'Inner', 'Innovation', 'Insight', 'Integral', 'Iron',
        'Island', 'Ivy', 'Jade', 'Jet', 'Journey', 'Key', 'Keystone', 'King', 'Knight', 'Lake',
        'Lakeside', 'Land', 'Landmark', 'Latitude', 'Leaf', 'Legacy', 'Liberty', 'Light', 'Lincoln', 'Lion',
        'Lone', 'Lotus', 'Lunar', 'Madison', 'Main', 'Maple', 'Marine', 'Market', 'Mason', 'Matrix',
        'Meadow', 'Merit', 'Metro', 'Mid', 'Midwest', 'Mission', 'Modern', 'Monarch', 'Mountain', 'National',
        'Native', 'Navigator', 'Nest', 'Network', 'New', 'Next', 'Noble', 'North', 'Northern', 'Nova',
        'Oak', 'Ocean', 'Omega', 'One', 'Open', 'Optimal', 'Orange', 'Orbit', 'Pacific', 'Palm',
        'Park', 'Pathway', 'Peak', 'Pearl', 'Peninsula', 'Phoenix', 'Pine', 'Pioneer', 'Pivot', 'Plains',
        'Planet', 'Platinum', 'Plaza', 'Point', 'Polar', 'Port', 'Power', 'Prairie', 'Premier', 'Pride',
        'Prime', 'Pro', 'Progress', 'Prosperity', 'Pulse', 'Pure', 'Purple', 'Quality', 'Quest', 'Quick',
        'Radiant', 'Rapid', 'Raven', 'Ray', 'Red', 'Reef', 'Regional', 'Relay', 'Republic', 'Reserve',
        'Ridge', 'River', 'Road', 'Rock', 'Rocky', 'Rose', 'Royal', 'Ruby', 'Sage', 'Sail',
        'Sapphire', 'Scale', 'Scarlet', 'Sea', 'Sequoia', 'Shadow', 'Sharp', 'Shield', 'Shore', 'Sierra',
        'Signal', 'Silver', 'Simple', 'Sky', 'Slate', 'Smart', 'Solar', 'Solid', 'Solution', 'South',
        'Southern', 'Space', 'Spark', 'Spectrum', 'Spirit', 'Spring', 'Square', 'Stable', 'Standard', 'Star',
        'Steel', 'Stone', 'Storm', 'Strategic', 'Stream', 'Street', 'Strong', 'Summit', 'Sun', 'Sunrise',
        'Superior', 'Sure', 'Swift', 'Synergy', 'Target', 'Terra', 'Third', 'Thunder', 'Tide', 'Tiger',
        'Timber', 'Titan', 'Top', 'Tower', 'Trail', 'Trans', 'Tree', 'Triangle', 'Tribute', 'Trinity',
        'True', 'Trust', 'Twin', 'Ultra', 'Union', 'United', 'Unity', 'Universal', 'Up', 'Upper',
        'Urban', 'Valley', 'Value', 'Vector', 'Velocity', 'Venture', 'Verde', 'Vertex', 'Victory', 'View',
        'Village', 'Vine', 'Vision', 'Vista', 'Vital', 'Vivid', 'Volt', 'Voyager', 'Wave', 'West',
        'Western', 'White', 'Wild', 'Willow', 'Wind', 'Wing', 'Wise', 'Wolf', 'Wonder', 'World',
        'Yellow', 'Zenith', 'Zero', 'Zone'
    ];

    $second_parts = [
        'Financial', 'Lending', 'Credit', 'Bank', 'Mortgage', 'Funding', 'Capital', 'Finance',
        'Services', 'Solutions', 'Systems', 'Group', 'Corp', 'Inc', 'LLC', 'Co',
        'Partners', 'Associates', 'Advisors', 'Consulting', 'Management', 'Holdings',
        'Trust', 'Savings', 'Investment', 'Securities', 'Insurance', 'Leasing',
        'Technologies', 'Digital', 'Online', 'Direct', 'Express', 'Plus', 'Pro',
        'National', 'Regional', 'Community', 'United', 'American', 'Global', 'International'
    ];

    $names = [];
    $used = [];

    while (count($names) < $count) {
        $first = $first_parts[mt_rand(0, count($first_parts) - 1)];
        $second = $second_parts[mt_rand(0, count($second_parts) - 1)];
        $name = $first . ' ' . $second;

        if (!isset($used[$name])) {
            $used[$name] = true;
            $names[] = $name;
        }
    }

    return $names;
}

// Generate random date in range
function random_date($start, $end) {
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    $random_ts = mt_rand($start_ts, $end_ts);
    return date('Y-m-d', $random_ts);
}

// Weighted random choice
function weighted_choice($choices, $weights) {
    $total = array_sum($weights);
    $rand = mt_rand(1, $total);

    $cumulative = 0;
    foreach ($choices as $i => $choice) {
        $cumulative += $weights[$i];
        if ($rand <= $cumulative) {
            return $choice;
        }
    }

    return $choices[0];
}

// Generate tier structure for a service
function generate_tiers($base_price, $tier_count = null) {
    if ($tier_count === null) {
        $tier_count = mt_rand(3, 5);
    }

    $tiers = [];
    $volume_starts = [0, 1000, 5000, 10000, 25000, 50000, 100000];

    // Pick tier boundaries
    $boundaries = [0];
    $available = array_slice($volume_starts, 1);
    shuffle($available);
    $available = array_slice($available, 0, $tier_count - 1);
    sort($available);
    $boundaries = array_merge($boundaries, $available);

    // Generate prices (decreasing with volume)
    $current_price = $base_price;
    for ($i = 0; $i < count($boundaries); $i++) {
        $volume_start = $boundaries[$i];
        $volume_end = isset($boundaries[$i + 1]) ? $boundaries[$i + 1] - 1 : null;

        $tiers[] = [
            'volume_start' => $volume_start,
            'volume_end' => $volume_end,
            'price' => round($current_price, 4),
        ];

        // Discount for next tier (5-15%)
        $discount = 1 - (mt_rand(5, 15) / 100);
        $current_price = $current_price * $discount;
    }

    return $tiers;
}

// ============================================================
// DATA GENERATION
// ============================================================

function generate_all_data($config) {
    $data = [];

    echo "Generating test data with seed: {$config['seed']}\n";
    echo "Customer count: {$config['customer_count']}\n\n";

    // 1. SERVICES
    echo "Generating services...\n";
    $service_codes = generate_service_codes($config['service_count']);
    $data['services'] = [];
    foreach ($service_codes as $i => $code) {
        $data['services'][] = [
            'id' => $i + 1,
            'code' => $code,
            'name' => code_to_service_name($code),
        ];
    }

    // 2. DISCOUNT GROUPS
    echo "Generating discount groups...\n";
    $group_names = generate_group_names($config['group_count']);
    $data['discount_groups'] = [];
    foreach ($group_names as $i => $name) {
        $data['discount_groups'][] = [
            'id' => $i + 1,
            'name' => $name,
        ];
    }

    // 3. LMS
    echo "Generating LMS...\n";
    $lms_names = generate_lms_names($config['lms_count']);
    $data['lms'] = [];
    foreach ($lms_names as $i => $name) {
        $has_override = mt_rand(1, 100) <= $config['lms_override_pct'];
        $commission = $has_override
            ? round($config['commission_min'] + (mt_rand(0, 100) / 100) * ($config['commission_max'] - $config['commission_min']), 2)
            : null;

        $data['lms'][] = [
            'id' => $i + 1,
            'name' => $name,
            'status' => 'active',
            'commission_rate' => $commission,
        ];
    }

    // 4. CUSTOMERS
    echo "Generating customers...\n";
    $customer_names = generate_company_names($config['customer_count']);
    $data['customers'] = [];

    $statuses = ['active', 'paused', 'decommissioned'];
    $status_weights = [
        $config['customer_active_pct'],
        $config['customer_paused_pct'],
        $config['customer_decommissioned_pct'],
    ];

    foreach ($customer_names as $i => $name) {
        $status = weighted_choice($statuses, $status_weights);
        $has_group = mt_rand(1, 100) <= $config['customer_in_group_pct'];
        $group_id = $has_group ? mt_rand(1, $config['group_count']) : null;
        $lms_id = mt_rand(1, $config['lms_count']);
        $contract_start = random_date('2018-01-01', '2025-12-31');

        $data['customers'][] = [
            'id' => $i + 1,
            'name' => $name,
            'discount_group_id' => $group_id,
            'lms_id' => $lms_id,
            'status' => $status,
            'contract_start_date' => $contract_start,
        ];
    }

    // 5. DEFAULT PRICING TIERS
    echo "Generating default pricing tiers...\n";
    $data['pricing_tiers_default'] = [];
    foreach ($data['services'] as $service) {
        $base_price = $config['price_min'] + (mt_rand(0, 100) / 100) * ($config['price_max'] - $config['price_min']);
        $tiers = generate_tiers($base_price);

        foreach ($tiers as $tier) {
            $data['pricing_tiers_default'][] = [
                'level' => 'default',
                'level_id' => null,
                'service_id' => $service['id'],
                'volume_start' => $tier['volume_start'],
                'volume_end' => $tier['volume_end'],
                'price_per_inquiry' => $tier['price'],
            ];
        }
    }

    // 6. GROUP PRICING OVERRIDES
    echo "Generating group pricing overrides...\n";
    $data['pricing_tiers_group'] = [];
    foreach ($data['discount_groups'] as $group) {
        if (mt_rand(1, 100) > $config['group_has_override_pct']) {
            continue; // This group uses defaults
        }

        // Pick random services to override (30-70% of services)
        $override_count = mt_rand(
            (int)($config['service_count'] * 0.3),
            (int)($config['service_count'] * 0.7)
        );
        $service_ids = range(1, $config['service_count']);
        shuffle($service_ids);
        $override_service_ids = array_slice($service_ids, 0, $override_count);

        foreach ($override_service_ids as $service_id) {
            // Find default tiers for this service
            $default_tiers = array_filter($data['pricing_tiers_default'], function($t) use ($service_id) {
                return $t['service_id'] === $service_id;
            });

            // Apply discount (5-20%)
            $discount = 1 - (mt_rand(5, 20) / 100);

            foreach ($default_tiers as $tier) {
                $data['pricing_tiers_group'][] = [
                    'level' => 'group',
                    'level_id' => $group['id'],
                    'service_id' => $service_id,
                    'volume_start' => $tier['volume_start'],
                    'volume_end' => $tier['volume_end'],
                    'price_per_inquiry' => round($tier['price_per_inquiry'] * $discount, 4),
                ];
            }
        }
    }

    // 7. CUSTOMER PRICING OVERRIDES
    echo "Generating customer pricing overrides...\n";
    $data['pricing_tiers_customer'] = [];
    foreach ($data['customers'] as $customer) {
        if (mt_rand(1, 100) > $config['customer_has_override_pct']) {
            continue; // This customer uses group/default
        }

        // Pick 1-3 services to override
        $override_count = mt_rand(1, 3);
        $service_ids = range(1, $config['service_count']);
        shuffle($service_ids);
        $override_service_ids = array_slice($service_ids, 0, $override_count);

        foreach ($override_service_ids as $service_id) {
            // Find default tiers for this service
            $default_tiers = array_filter($data['pricing_tiers_default'], function($t) use ($service_id) {
                return $t['service_id'] === $service_id;
            });

            // Apply custom pricing (could be discount or premium)
            $modifier = 1 + (mt_rand(-25, 10) / 100); // -25% to +10%

            foreach ($default_tiers as $tier) {
                $data['pricing_tiers_customer'][] = [
                    'level' => 'customer',
                    'level_id' => $customer['id'],
                    'service_id' => $service_id,
                    'volume_start' => $tier['volume_start'],
                    'volume_end' => $tier['volume_end'],
                    'price_per_inquiry' => round($tier['price_per_inquiry'] * $modifier, 4),
                ];
            }
        }
    }

    // 8. CUSTOMER SETTINGS
    echo "Generating customer settings...\n";
    $data['customer_settings'] = [];
    foreach ($data['customers'] as $customer) {
        $has_minimum = mt_rand(1, 100) <= $config['customer_has_minimum_pct'];
        $has_annualized = mt_rand(1, 100) <= $config['customer_has_annualized_pct'];

        if (!$has_minimum && !$has_annualized) {
            continue; // No settings for this customer
        }

        $data['customer_settings'][] = [
            'customer_id' => $customer['id'],
            'monthly_minimum' => $has_minimum
                ? round($config['minimum_min'] + (mt_rand(0, 100) / 100) * ($config['minimum_max'] - $config['minimum_min']), 2)
                : null,
            'uses_annualized' => $has_annualized ? 1 : 0,
            'annualized_start_date' => $has_annualized ? $customer['contract_start_date'] : null,
            'look_period_months' => $has_annualized ? (mt_rand(0, 1) ? 3 : 6) : null,
        ];
    }

    // 9. ESCALATORS
    echo "Generating escalators...\n";
    $data['escalators'] = [];
    $data['escalator_delays'] = [];
    foreach ($data['customers'] as $customer) {
        if (mt_rand(1, 100) > $config['customer_has_escalator_pct']) {
            continue;
        }

        $year_count = mt_rand(3, 5);
        $base_pct = mt_rand(3, 6); // 3-6% typical escalator

        for ($year = 1; $year <= $year_count; $year++) {
            $pct = ($year === 1) ? 0 : $base_pct + mt_rand(-1, 1);
            $fixed = (mt_rand(1, 100) <= 20) ? mt_rand(10, 50) : 0; // 20% chance of fixed adjustment

            $data['escalators'][] = [
                'customer_id' => $customer['id'],
                'escalator_start_date' => $customer['contract_start_date'],
                'year_number' => $year,
                'escalator_percentage' => $pct,
                'fixed_adjustment' => $fixed,
            ];
        }

        // 10% chance of delay
        if (mt_rand(1, 100) <= 10) {
            $data['escalator_delays'][] = [
                'customer_id' => $customer['id'],
                'year_number' => 2,
                'delay_months' => mt_rand(1, 3),
            ];
        }
    }

    // 10. BUSINESS RULES
    echo "Generating business rules...\n";
    $data['business_rules'] = [];
    $data['business_rule_masks'] = [];

    foreach ($data['customers'] as $customer) {
        // Each customer gets rules for services they use
        $service_count = mt_rand(3, 8);
        $service_ids = range(1, $config['service_count']);
        shuffle($service_ids);
        $customer_services = array_slice($service_ids, 0, $service_count);

        foreach ($customer_services as $service_id) {
            $service = $data['services'][$service_id - 1];
            $rule_name = strtoupper(str_replace(' ', '_', $customer['name'])) . '_' . $service['code'];
            // Sanitize rule name
            $rule_name = preg_replace('/[^A-Z0-9_]/', '', $rule_name);
            $rule_name = substr($rule_name, 0, 50);

            $data['business_rules'][] = [
                'customer_id' => $customer['id'],
                'rule_name' => $rule_name,
                'rule_description' => $customer['name'] . ' - ' . $service['name'] . ' integration',
            ];

            // 15% chance of being masked
            if (mt_rand(1, 100) <= 15) {
                $data['business_rule_masks'][] = [
                    'customer_id' => $customer['id'],
                    'rule_name' => $rule_name,
                    'is_masked' => 1,
                ];
            }
        }
    }

    // 11. TRANSACTION TYPES
    echo "Generating transaction types...\n";
    $data['transaction_types'] = [];
    $type_suffixes = ['HIT', 'MISS', 'ERR', 'NULL', 'RETRY', 'BATCH'];

    foreach ($data['services'] as $service) {
        $type_count = mt_rand(2, 4);
        $selected_suffixes = array_slice($type_suffixes, 0, $type_count);

        foreach ($selected_suffixes as $suffix) {
            $efx_code = $service['code'] . '_' . $suffix;
            $data['transaction_types'][] = [
                'type' => strtolower($service['code']),
                'display_name' => $service['name'] . ' ' . ucfirst(strtolower($suffix)),
                'efx_code' => $efx_code,
                'efx_displayname' => substr($efx_code, 0, 15),
                'service_id' => $service['id'],
            ];
        }
    }

    // 12. BILLING FLAGS (defaults only for now)
    echo "Generating billing flags...\n";
    $data['billing_flags'] = [];
    foreach ($data['transaction_types'] as $tt) {
        $data['billing_flags'][] = [
            'level' => 'default',
            'level_id' => null,
            'service_id' => $tt['service_id'],
            'efx_code' => $tt['efx_code'],
            'by_hit' => 1,
            'zero_null' => mt_rand(0, 1),
            'bav_by_trans' => mt_rand(0, 1),
        ];
    }

    // 13. COGS
    echo "Generating COGS...\n";
    $data['service_cogs'] = [];
    foreach ($data['services'] as $service) {
        // Find average default price for this service
        $service_tiers = array_filter($data['pricing_tiers_default'], function($t) use ($service) {
            return $t['service_id'] === $service['id'];
        });
        $avg_price = 0;
        foreach ($service_tiers as $tier) {
            $avg_price += $tier['price_per_inquiry'];
        }
        $avg_price = $avg_price / count($service_tiers);

        // COGS is ~40% of price
        $cogs = round($avg_price * $config['cogs_margin'], 4);

        $data['service_cogs'][] = [
            'service_id' => $service['id'],
            'cogs_rate' => $cogs,
        ];
    }

    // 15. SYSTEM SETTINGS
    echo "Generating system settings...\n";
    $data['system_settings'] = [
        ['key' => 'default_commission_rate', 'value' => (string)$config['default_commission_rate']],
    ];

    return $data;
}

// ============================================================
// OUTPUT FUNCTIONS
// ============================================================

function output_sql($data) {
    $sql = "-- Generated Test Data\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    // Services
    $sql .= "-- SERVICES\n";
    foreach ($data['services'] as $s) {
        $sql .= "INSERT INTO services (id, name) VALUES ({$s['id']}, " . sql_quote($s['name']) . ");\n";
    }
    $sql .= "\n";

    // Discount Groups
    $sql .= "-- DISCOUNT GROUPS\n";
    foreach ($data['discount_groups'] as $g) {
        $sql .= "INSERT INTO discount_groups (id, name) VALUES ({$g['id']}, " . sql_quote($g['name']) . ");\n";
    }
    $sql .= "\n";

    // LMS
    $sql .= "-- LMS\n";
    foreach ($data['lms'] as $l) {
        $commission = $l['commission_rate'] === null ? 'NULL' : $l['commission_rate'];
        $sql .= "INSERT INTO lms (id, name, status, commission_rate) VALUES ({$l['id']}, " . sql_quote($l['name']) . ", 'active', $commission);\n";
    }
    $sql .= "\n";

    // Customers
    $sql .= "-- CUSTOMERS\n";
    foreach ($data['customers'] as $c) {
        $group_id = $c['discount_group_id'] === null ? 'NULL' : $c['discount_group_id'];
        $sql .= "INSERT INTO customers (id, name, discount_group_id, lms_id, status, contract_start_date) VALUES ";
        $sql .= "({$c['id']}, " . sql_quote($c['name']) . ", $group_id, {$c['lms_id']}, '{$c['status']}', '{$c['contract_start_date']}');\n";
    }
    $sql .= "\n";

    // Pricing Tiers - Default
    $sql .= "-- PRICING TIERS (DEFAULT)\n";
    foreach ($data['pricing_tiers_default'] as $t) {
        $end = $t['volume_end'] === null ? 'NULL' : $t['volume_end'];
        $sql .= "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date) VALUES ";
        $sql .= "('default', NULL, {$t['service_id']}, {$t['volume_start']}, $end, {$t['price_per_inquiry']}, date('now'));\n";
    }
    $sql .= "\n";

    // Pricing Tiers - Group
    $sql .= "-- PRICING TIERS (GROUP OVERRIDES)\n";
    foreach ($data['pricing_tiers_group'] as $t) {
        $end = $t['volume_end'] === null ? 'NULL' : $t['volume_end'];
        $sql .= "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date) VALUES ";
        $sql .= "('group', {$t['level_id']}, {$t['service_id']}, {$t['volume_start']}, $end, {$t['price_per_inquiry']}, date('now'));\n";
    }
    $sql .= "\n";

    // Pricing Tiers - Customer
    $sql .= "-- PRICING TIERS (CUSTOMER OVERRIDES)\n";
    foreach ($data['pricing_tiers_customer'] as $t) {
        $end = $t['volume_end'] === null ? 'NULL' : $t['volume_end'];
        $sql .= "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date) VALUES ";
        $sql .= "('customer', {$t['level_id']}, {$t['service_id']}, {$t['volume_start']}, $end, {$t['price_per_inquiry']}, date('now'));\n";
    }
    $sql .= "\n";

    // Customer Settings
    $sql .= "-- CUSTOMER SETTINGS\n";
    foreach ($data['customer_settings'] as $s) {
        $min = $s['monthly_minimum'] === null ? 'NULL' : $s['monthly_minimum'];
        $start = $s['annualized_start_date'] === null ? 'NULL' : "'{$s['annualized_start_date']}'";
        $look = $s['look_period_months'] === null ? 'NULL' : $s['look_period_months'];
        $sql .= "INSERT INTO customer_settings (customer_id, monthly_minimum, uses_annualized, annualized_start_date, look_period_months, effective_date) VALUES ";
        $sql .= "({$s['customer_id']}, $min, {$s['uses_annualized']}, $start, $look, date('now'));\n";
    }
    $sql .= "\n";

    // Escalators
    $sql .= "-- ESCALATORS\n";
    foreach ($data['escalators'] as $e) {
        $sql .= "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date) VALUES ";
        $sql .= "({$e['customer_id']}, '{$e['escalator_start_date']}', {$e['year_number']}, {$e['escalator_percentage']}, {$e['fixed_adjustment']}, date('now'));\n";
    }
    $sql .= "\n";

    // Escalator Delays
    $sql .= "-- ESCALATOR DELAYS\n";
    foreach ($data['escalator_delays'] as $d) {
        $sql .= "INSERT INTO escalator_delays (customer_id, year_number, delay_months) VALUES ";
        $sql .= "({$d['customer_id']}, {$d['year_number']}, {$d['delay_months']});\n";
    }
    $sql .= "\n";

    // Business Rules
    $sql .= "-- BUSINESS RULES\n";
    foreach ($data['business_rules'] as $r) {
        $sql .= "INSERT INTO business_rules (customer_id, rule_name, rule_description) VALUES ";
        $sql .= "({$r['customer_id']}, " . sql_quote($r['rule_name']) . ", " . sql_quote($r['rule_description']) . ");\n";
    }
    $sql .= "\n";

    // Business Rule Masks
    $sql .= "-- BUSINESS RULE MASKS\n";
    foreach ($data['business_rule_masks'] as $m) {
        $sql .= "INSERT INTO business_rule_masks (customer_id, rule_name, is_masked, effective_date) VALUES ";
        $sql .= "({$m['customer_id']}, " . sql_quote($m['rule_name']) . ", {$m['is_masked']}, date('now'));\n";
    }
    $sql .= "\n";

    // Transaction Types
    $sql .= "-- TRANSACTION TYPES\n";
    foreach ($data['transaction_types'] as $t) {
        $sql .= "INSERT INTO transaction_types (type, display_name, efx_code, efx_displayname, service_id) VALUES ";
        $sql .= "(" . sql_quote($t['type']) . ", " . sql_quote($t['display_name']) . ", " . sql_quote($t['efx_code']) . ", " . sql_quote($t['efx_displayname']) . ", {$t['service_id']});\n";
    }
    $sql .= "\n";

    // Billing Flags
    $sql .= "-- BILLING FLAGS\n";
    foreach ($data['billing_flags'] as $f) {
        $sql .= "INSERT INTO service_billing_flags (level, level_id, service_id, efx_code, by_hit, zero_null, bav_by_trans, effective_date) VALUES ";
        $sql .= "('default', NULL, {$f['service_id']}, " . sql_quote($f['efx_code']) . ", {$f['by_hit']}, {$f['zero_null']}, {$f['bav_by_trans']}, date('now'));\n";
    }
    $sql .= "\n";

    // Service COGS
    $sql .= "-- SERVICE COGS\n";
    foreach ($data['service_cogs'] as $c) {
        $sql .= "INSERT INTO service_cogs (service_id, cogs_rate, effective_date) VALUES ";
        $sql .= "({$c['service_id']}, {$c['cogs_rate']}, date('now'));\n";
    }
    $sql .= "\n";

    // System Settings
    $sql .= "-- SYSTEM SETTINGS\n";
    foreach ($data['system_settings'] as $s) {
        $sql .= "INSERT OR REPLACE INTO system_settings (key, value) VALUES ";
        $sql .= "(" . sql_quote($s['key']) . ", " . sql_quote($s['value']) . ");\n";
    }

    return $sql;
}

function sql_quote($str) {
    return "'" . str_replace("'", "''", $str) . "'";
}

function output_summary($data) {
    echo "\n========================================\n";
    echo "TEST DATA GENERATION COMPLETE\n";
    echo "========================================\n\n";

    echo "COUNTS:\n";
    echo "  Services:              " . count($data['services']) . "\n";
    echo "  Discount Groups:       " . count($data['discount_groups']) . "\n";
    echo "  LMS:                   " . count($data['lms']) . "\n";
    echo "  Customers:             " . count($data['customers']) . "\n";
    echo "    - Active:            " . count(array_filter($data['customers'], function($c) { return $c['status'] === 'active'; })) . "\n";
    echo "    - Paused:            " . count(array_filter($data['customers'], function($c) { return $c['status'] === 'paused'; })) . "\n";
    echo "    - Decommissioned:    " . count(array_filter($data['customers'], function($c) { return $c['status'] === 'decommissioned'; })) . "\n";
    echo "    - In groups:         " . count(array_filter($data['customers'], function($c) { return $c['discount_group_id'] !== null; })) . "\n";
    echo "  Default Pricing Tiers: " . count($data['pricing_tiers_default']) . "\n";
    echo "  Group Pricing Tiers:   " . count($data['pricing_tiers_group']) . "\n";
    echo "  Customer Pricing Tiers:" . count($data['pricing_tiers_customer']) . "\n";
    echo "  Customer Settings:     " . count($data['customer_settings']) . "\n";
    echo "  Escalators:            " . count($data['escalators']) . "\n";
    echo "  Escalator Delays:      " . count($data['escalator_delays']) . "\n";
    echo "  Business Rules:        " . count($data['business_rules']) . "\n";
    echo "  Business Rule Masks:   " . count($data['business_rule_masks']) . "\n";
    echo "  Transaction Types:     " . count($data['transaction_types']) . "\n";
    echo "  Billing Flags:         " . count($data['billing_flags']) . "\n";
    echo "  Service COGS:          " . count($data['service_cogs']) . "\n";
    echo "\n";

    // Sample data
    echo "SAMPLE SERVICES:\n";
    foreach (array_slice($data['services'], 0, 5) as $s) {
        echo "  [{$s['code']}] {$s['name']}\n";
    }
    echo "\n";

    echo "SAMPLE CUSTOMERS:\n";
    foreach (array_slice($data['customers'], 0, 5) as $c) {
        $group = $c['discount_group_id'] ? "Group #{$c['discount_group_id']}" : "No group";
        echo "  [{$c['status']}] {$c['name']} - $group\n";
    }
    echo "\n";
}

// ============================================================
// MAIN
// ============================================================

$data = generate_all_data($CONFIG);
output_summary($data);

// Generate SQL file
$sql = output_sql($data);
$sql_file = __DIR__ . '/test_data.sql';
file_put_contents($sql_file, $sql);
echo "SQL written to: $sql_file\n";

// Also output as PHP array for direct inclusion
$php_file = __DIR__ . '/test_data.php';
$php_content = "<?php\n// Generated Test Data\n// Generated: " . date('Y-m-d H:i:s') . "\n\n";
$php_content .= "return " . var_export($data, true) . ";\n";
file_put_contents($php_file, $php_content);
echo "PHP data written to: $php_file\n";

echo "\nDone!\n";
