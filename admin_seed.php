<?php
/**
 * Admin Seed Script - Audit-Compatible Test Data Generator
 *
 * Generates realistic test data where billing reports use prices
 * calculated from our pricing tiers, with controlled variance:
 * - 85% exact matches
 * - 10% small variances (rounding errors)
 * - 5% large variances (wrong tier, missing escalator, etc.)
 *
 * Usage:
 *   php admin_seed.php --confirm              # Full reseed
 *   php admin_seed.php --confirm --clear      # Clear and reseed
 *   php admin_seed.php --confirm --days=90    # Custom days of history
 *
 * Or call from web via Admin panel
 */

// ============================================================
// CONFIGURATION
// ============================================================

$SEED_CONFIG = [
    "seed" => 42, // For reproducible randomness
    "days_of_history" => 90,
    "customer_count" => 100,
    "service_count" => 12,
    "group_count" => 8,
    "lms_count" => 15,

    // Variance distribution for billing lines
    "exact_match_pct" => 85,
    "small_variance_pct" => 10,
    "large_variance_pct" => 5,

    // Customer distribution
    "customer_active_pct" => 75,
    "customer_paused_pct" => 15,
    "customer_in_group_pct" => 60,
    "customer_has_override_pct" => 25,
    "customer_has_minimum_pct" => 40,
    "customer_has_escalator_pct" => 50,
    "group_has_override_pct" => 70,

    // Pricing ranges
    "price_min" => 0.08,
    "price_max" => 0.65,
    "cogs_margin" => 0.4,
    "minimum_min" => 250,
    "minimum_max" => 5000,

    // Commission
    "default_commission_rate" => 10.0,
    "lms_override_pct" => 30,
    "commission_min" => 8.0,
    "commission_max" => 18.0,

    // Billing generation
    "tx_types_per_customer_min" => 4,
    "tx_types_per_customer_max" => 10,
    "count_min" => 50,
    "count_max" => 2000,
    "count_high_volume_pct" => 10,
    "count_high_volume_max" => 8000,
];

// ============================================================
// BOOTSTRAP
// ============================================================

$is_cli = php_sapi_name() === "cli";
$is_web = !$is_cli;

if ($is_cli) {
    // Parse CLI arguments
    $confirmed = in_array("--confirm", $argv);
    $clear_first = in_array("--clear", $argv);

    foreach ($argv as $arg) {
        if (preg_match('/^--days=(\d+)$/', $arg, $m)) {
            $SEED_CONFIG["days_of_history"] = (int) $m[1];
        }
        if (preg_match('/^--customers=(\d+)$/', $arg, $m)) {
            $SEED_CONFIG["customer_count"] = (int) $m[1];
        }
        if (preg_match('/^--seed=(\d+)$/', $arg, $m)) {
            $SEED_CONFIG["seed"] = (int) $m[1];
        }
        if (preg_match('/^--exact=(\d+)$/', $arg, $m)) {
            $SEED_CONFIG["exact_match_pct"] = (int) $m[1];
        }
    }

    if (!$confirmed) {
        echo "Admin Seed Script - Audit-Compatible Test Data Generator\n";
        echo "=========================================================\n\n";
        echo "This will generate test data with billing reports that use\n";
        echo "calculated prices from the pricing tier system.\n\n";
        echo "Options:\n";
        echo "  --confirm           Required to run\n";
        echo "  --clear             Clear database before seeding\n";
        echo "  --days=N            Days of billing history (default: 90)\n";
        echo "  --customers=N       Number of customers (default: 100)\n";
        echo "  --seed=N            Random seed (default: 42)\n";
        echo "  --exact=N           Exact match percentage (default: 85)\n";
        echo "\nExample:\n";
        echo "  php admin_seed.php --confirm --clear --days=90\n\n";
        exit(1);
    }
}

// Include required files
if (!defined("MOCK_MODE")) {
    define("MOCK_MODE", true);
}
require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/data.php";

// We need calculator for price lookups, but it requires data.php functions
// which we're including above

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function seed_log($message)
{
    global $is_cli, $seed_log_buffer;
    if (isset($is_cli) && $is_cli) {
        echo $message . "\n";
    } else {
        if (!isset($seed_log_buffer)) {
            $seed_log_buffer = [];
        }
        $seed_log_buffer[] = $message;
    }
}

function weighted_choice($choices)
{
    $total = array_sum($choices);
    $rand = mt_rand(1, $total);
    $cumulative = 0;
    foreach ($choices as $key => $weight) {
        $cumulative += $weight;
        if ($rand <= $cumulative) {
            return $key;
        }
    }
    return array_keys($choices)[0];
}

function random_date($start, $end)
{
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    return date("Y-m-d", mt_rand($start_ts, $end_ts));
}

function sql_escape($value)
{
    if ($value === null) {
        return "NULL";
    }
    if (is_numeric($value)) {
        return $value;
    }
    return "'" . SQLite3::escapeString($value) . "'";
}

// ============================================================
// NAME GENERATORS (from generate_test_data.php)
// ============================================================

function generate_service_data()
{
    // Fixed service codes and names for consistency
    return [
        ["code" => "SSE", "name" => "Social Security Express"],
        ["code" => "SSM", "name" => "Social Security Monitor"],
        ["code" => "IDS", "name" => "Identity Standard"],
        ["code" => "IDV", "name" => "Identity Verify"],
        ["code" => "CRK", "name" => "Credit Check"],
        ["code" => "CRP", "name" => "Credit Plus"],
        ["code" => "ACS", "name" => "Account Standard"],
        ["code" => "ACB", "name" => "Account Basic"],
        ["code" => "BKV", "name" => "Bank Verify"],
        ["code" => "BKB", "name" => "Bank Basic"],
        ["code" => "FNL", "name" => "Financial Lookup"],
        ["code" => "FNC", "name" => "Financial Core"],
    ];
}

function generate_group_names($count)
{
    $names = [
        "Atlas Holdings",
        "Beacon Group",
        "Crown Partners",
        "Dynasty Capital",
        "Empire Enterprises",
        "Fortress Corporation",
        "Global Ventures",
        "Horizon Industries",
        "Imperial Consortium",
        "Jupiter Alliance",
        "Kingston Holdings",
        "Legacy Partners",
    ];
    return array_slice($names, 0, $count);
}

function generate_lms_names($count)
{
    $names = [
        "Fiserv",
        "Jack Henry",
        "Temenos",
        "FIS",
        "Finastra",
        "NCR",
        "ACI Worldwide",
        "Bottomline",
        "Q2 Holdings",
        "Alkami",
        "nCino",
        "Blend Labs",
        "Plaid",
        "MX Technologies",
        "Yodlee",
        "Finicity",
        "CoreCard",
        "Mambu",
        "Thought Machine",
    ];
    return array_slice($names, 0, $count);
}

function generate_customer_names($count)
{
    $first_parts = [
        "Acme",
        "Alpha",
        "Apex",
        "Arrow",
        "Atlas",
        "Azure",
        "Bay",
        "Bear",
        "Blue",
        "Bolt",
        "Bridge",
        "Bright",
        "Canyon",
        "Cedar",
        "Central",
        "Circle",
        "City",
        "Clear",
        "Cloud",
        "Coastal",
        "Copper",
        "Core",
        "Crest",
        "Crown",
        "Crystal",
        "Delta",
        "Diamond",
        "Digital",
        "Direct",
        "Eagle",
        "East",
        "Edge",
        "Elite",
        "Elm",
        "Emerald",
        "Empire",
        "Energy",
        "Epic",
        "Evergreen",
        "Express",
        "Falcon",
        "First",
        "Five",
        "Flex",
        "Focus",
        "Forest",
        "Forge",
        "Fort",
        "Forward",
        "Foundation",
        "Fox",
        "Freedom",
        "Fresh",
        "Front",
        "Future",
        "Gateway",
        "Genesis",
        "Global",
        "Gold",
        "Grand",
        "Granite",
        "Great",
        "Green",
        "Grid",
        "Gulf",
        "Harbor",
        "Hawk",
        "Heart",
        "Heritage",
        "High",
        "Highland",
        "Hill",
        "Home",
        "Honor",
        "Horizon",
        "Hub",
        "Hudson",
        "Icon",
        "Ideal",
        "Impact",
        "Independence",
        "Infinity",
        "Inland",
        "Inner",
        "Innovation",
        "Insight",
        "Integral",
        "Iron",
        "Island",
        "Ivy",
        "Jade",
        "Jet",
        "Journey",
        "Key",
        "Keystone",
        "King",
        "Knight",
        "Lake",
        "Lakeside",
        "Land",
        "Landmark",
        "Legacy",
        "Liberty",
        "Light",
        "Lincoln",
        "Lion",
        "Lone",
        "Lotus",
        "Lunar",
        "Madison",
    ];

    $second_parts = [
        "Financial",
        "Lending",
        "Credit",
        "Bank",
        "Mortgage",
        "Funding",
        "Capital",
        "Finance",
        "Services",
        "Solutions",
        "Systems",
        "Group",
        "Corp",
        "Inc",
        "LLC",
        "Co",
        "Partners",
        "Associates",
        "Advisors",
        "Trust",
        "Savings",
        "Leasing",
    ];

    $names = [];
    $used = [];
    mt_srand(12345); // Consistent names

    while (count($names) < $count) {
        $first = $first_parts[mt_rand(0, count($first_parts) - 1)];
        $second = $second_parts[mt_rand(0, count($second_parts) - 1)];
        $name = $first . " " . $second;

        if (!isset($used[$name])) {
            $used[$name] = true;
            $names[] = $name;
        }
    }

    return $names;
}

// ============================================================
// TIER GENERATION
// ============================================================

function generate_tiers($base_price, $tier_count = null)
{
    if ($tier_count === null) {
        $tier_count = mt_rand(3, 5);
    }

    $volume_boundaries = [0, 1000, 5000, 10000, 25000];
    $tiers = [];

    // Pick tier count boundaries
    $boundaries = array_slice($volume_boundaries, 0, $tier_count);

    $current_price = $base_price;
    for ($i = 0; $i < count($boundaries); $i++) {
        $volume_start = $boundaries[$i];
        $volume_end = isset($boundaries[$i + 1])
            ? $boundaries[$i + 1] - 1
            : null;

        $tiers[] = [
            "volume_start" => $volume_start,
            "volume_end" => $volume_end,
            "price" => round($current_price, 4),
        ];

        // Discount for next tier (8-15%)
        $discount = 1 - mt_rand(8, 15) / 100;
        $current_price = $current_price * $discount;
    }

    return $tiers;
}

// ============================================================
// DATABASE OPERATIONS
// ============================================================

function clear_database()
{
    seed_log("Clearing database...");

    // Tables in dependency order (children first, then parents)
    $tables = [
        "billing_report_lines",
        "billing_reports",
        "escalator_delays",
        "customer_escalators",
        "customer_settings",
        "business_rule_masks",
        "business_rules",
        "service_billing_flags",
        "pricing_tiers",
        "service_cogs",
        "transaction_types",
        "customers",
        "discount_groups",
        "decision_connectors",
        "lms",
        "services",
        "system_settings",
    ];

    // DROP tables to ensure schema is fresh
    foreach ($tables as $table) {
        try {
            sqlite_execute("DROP TABLE IF EXISTS $table");
        } catch (Exception $e) {
            seed_log("  Warning: Could not drop $table: " . $e->getMessage());
        }
    }

    seed_log("  Dropped " . count($tables) . " tables");

    // Recreate tables with current schema
    seed_log("  Recreating tables with current schema...");
    require_once __DIR__ . "/db.php";
    init_db();

    seed_log("  Tables recreated successfully");
}

function get_database_stats()
{
    $stats = [];

    $tables = [
        "customers" => "Customers",
        "services" => "Services",
        "discount_groups" => "Discount Groups",
        "lms" => "LMS",
        "pricing_tiers" => "Pricing Tiers",
        "customer_settings" => "Customer Settings",
        "customer_escalators" => "Escalators",
        "billing_reports" => "Billing Reports",
        "billing_report_lines" => "Billing Lines",
        "transaction_types" => "Transaction Types",
    ];

    foreach ($tables as $table => $label) {
        $result = sqlite_query("SELECT COUNT(*) as cnt FROM $table");
        $stats[$table] = [
            "label" => $label,
            "count" => $result[0]["cnt"],
        ];
    }

    // Active customers
    $result = sqlite_query(
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'",
    );
    $stats["customers_active"] = [
        "label" => "Active Customers",
        "count" => $result[0]["cnt"],
    ];

    return $stats;
}

// ============================================================
// MAIN SEEDING FUNCTION
// ============================================================

function run_seed($config, $clear_first = false)
{
    global $seed_log_buffer;
    $seed_log_buffer = [];

    mt_srand($config["seed"]);

    $start_time = microtime(true);

    if ($clear_first) {
        clear_database();
    }

    // Calculate dates
    $today = date("Y-m-d");
    $history_start = date(
        "Y-m-d",
        strtotime("-{$config["days_of_history"]} days"),
    );
    $pricing_effective_date = date(
        "Y-m-d",
        strtotime("-" . ($config["days_of_history"] + 30) . " days"),
    );

    seed_log("Seeding with configuration:");
    seed_log("  Days of history: {$config["days_of_history"]}");
    seed_log("  Customers: {$config["customer_count"]}");
    seed_log(
        "  Variance: {$config["exact_match_pct"]}% exact, {$config["small_variance_pct"]}% small, {$config["large_variance_pct"]}% large",
    );
    seed_log("  Pricing effective from: $pricing_effective_date");
    seed_log("  Billing history: $history_start to $today");
    seed_log("");

    // ----------------------------------------------------------------
    // 1. SERVICES
    // ----------------------------------------------------------------
    seed_log("1. Creating services...");
    $services = generate_service_data();
    $services = array_slice($services, 0, $config["service_count"]);

    foreach ($services as $i => $service) {
        $id = $i + 1;
        sqlite_execute("INSERT INTO services (id, name) VALUES (?, ?)", [
            $id,
            $service["name"],
        ]);
        $services[$i]["id"] = $id;
    }
    seed_log("   Created " . count($services) . " services");

    // ----------------------------------------------------------------
    // 2. TRANSACTION TYPES (map EFX codes to services)
    // ----------------------------------------------------------------
    seed_log("2. Creating transaction types...");
    $transaction_types = [];
    $suffixes = ["HIT", "MISS", "ERR", "NULL"];

    foreach ($services as $service) {
        foreach ($suffixes as $suffix) {
            $efx_code = $service["code"] . "_" . $suffix;
            sqlite_execute(
                "INSERT INTO transaction_types (type, display_name, efx_code, efx_displayname, service_id)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    strtolower($service["code"]),
                    $service["name"] . " " . ucfirst(strtolower($suffix)),
                    $efx_code,
                    substr($efx_code, 0, 15),
                    $service["id"],
                ],
            );
            $transaction_types[] = [
                "efx_code" => $efx_code,
                "service_id" => $service["id"],
                "display_name" =>
                    $service["name"] . " " . ucfirst(strtolower($suffix)),
            ];
        }
    }
    seed_log("   Created " . count($transaction_types) . " transaction types");

    // ----------------------------------------------------------------
    // 3. DISCOUNT GROUPS
    // ----------------------------------------------------------------
    seed_log("3. Creating discount groups...");
    $group_names = generate_group_names($config["group_count"]);
    $groups = [];

    foreach ($group_names as $i => $name) {
        $id = $i + 1;
        sqlite_execute("INSERT INTO discount_groups (id, name) VALUES (?, ?)", [
            $id,
            $name,
        ]);
        $groups[] = ["id" => $id, "name" => $name];
    }
    seed_log("   Created " . count($groups) . " discount groups");

    // ----------------------------------------------------------------
    // 4. LMS
    // ----------------------------------------------------------------
    seed_log("4. Creating LMS providers...");
    $lms_names = generate_lms_names($config["lms_count"]);
    $lms_list = [];

    foreach ($lms_names as $i => $name) {
        $id = $i + 1;
        $has_override = mt_rand(1, 100) <= $config["lms_override_pct"];
        $commission = $has_override
            ? round(
                $config["commission_min"] +
                    (mt_rand(0, 100) / 100) *
                        ($config["commission_max"] - $config["commission_min"]),
                2,
            )
            : null;

        sqlite_execute(
            "INSERT INTO lms (id, name, status, commission_rate) VALUES (?, ?, 'active', ?)",
            [$id, $name, $commission],
        );
        $lms_list[] = ["id" => $id, "name" => $name];
    }
    seed_log("   Created " . count($lms_list) . " LMS providers");

    // ----------------------------------------------------------------
    // 5. CUSTOMERS
    // ----------------------------------------------------------------
    seed_log("5. Creating customers...");
    $customer_names = generate_customer_names($config["customer_count"]);
    $customers = [];

    $statuses = ["active", "paused", "decommissioned"];
    $status_weights = [
        "active" => $config["customer_active_pct"],
        "paused" => $config["customer_paused_pct"],
        "decommissioned" =>
            100 -
            $config["customer_active_pct"] -
            $config["customer_paused_pct"],
    ];

    foreach ($customer_names as $i => $name) {
        $id = $i + 1;
        $status = weighted_choice($status_weights);
        $has_group = mt_rand(1, 100) <= $config["customer_in_group_pct"];
        $group_id = $has_group ? mt_rand(1, $config["group_count"]) : null;
        $lms_id = mt_rand(1, $config["lms_count"]);

        // Contract start 1-4 years ago
        $contract_start = date(
            "Y-m-d",
            strtotime("-" . mt_rand(365, 365 * 4) . " days"),
        );

        sqlite_execute(
            "INSERT INTO customers (id, name, discount_group_id, lms_id, status, contract_start_date)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$id, $name, $group_id, $lms_id, $status, $contract_start],
        );

        $customers[] = [
            "id" => $id,
            "name" => $name,
            "discount_group_id" => $group_id,
            "status" => $status,
            "contract_start_date" => $contract_start,
        ];
    }

    $active_customers = array_filter($customers, function ($c) {
        return $c["status"] === "active";
    });
    seed_log(
        "   Created " .
            count($customers) .
            " customers (" .
            count($active_customers) .
            " active)",
    );

    // ----------------------------------------------------------------
    // 6. DEFAULT PRICING TIERS
    // ----------------------------------------------------------------
    seed_log("6. Creating default pricing tiers...");
    $tier_count = 0;

    foreach ($services as $service) {
        $base_price =
            $config["price_min"] +
            (mt_rand(0, 100) / 100) *
                ($config["price_max"] - $config["price_min"]);
        $tiers = generate_tiers($base_price);

        foreach ($tiers as $tier) {
            sqlite_execute(
                "INSERT INTO pricing_tiers (effective_date, level, level_id, service_id, volume_start, volume_end, price_per_inquiry)
                 VALUES (?, 'default', NULL, ?, ?, ?, ?)",
                [
                    $pricing_effective_date,
                    $service["id"],
                    $tier["volume_start"],
                    $tier["volume_end"],
                    $tier["price"],
                ],
            );
            $tier_count++;
        }
    }
    seed_log("   Created $tier_count default tiers");

    // ----------------------------------------------------------------
    // 7. GROUP PRICING OVERRIDES
    // ----------------------------------------------------------------
    seed_log("7. Creating group pricing overrides...");
    $group_tier_count = 0;

    foreach ($groups as $group) {
        if (mt_rand(1, 100) > $config["group_has_override_pct"]) {
            continue;
        }

        // Override 30-70% of services
        $override_count = mt_rand(
            (int) ($config["service_count"] * 0.3),
            (int) ($config["service_count"] * 0.7),
        );
        $service_ids = array_column($services, "id");
        shuffle($service_ids);
        $override_service_ids = array_slice($service_ids, 0, $override_count);

        foreach ($override_service_ids as $service_id) {
            // Get default tiers for this service
            $default_tiers = sqlite_query(
                "SELECT * FROM pricing_tiers WHERE level = 'default' AND service_id = ? ORDER BY volume_start",
                [$service_id],
            );

            // Apply discount (5-20%)
            $discount = 1 - mt_rand(5, 20) / 100;

            foreach ($default_tiers as $tier) {
                sqlite_execute(
                    "INSERT INTO pricing_tiers (effective_date, level, level_id, service_id, volume_start, volume_end, price_per_inquiry)
                     VALUES (?, 'group', ?, ?, ?, ?, ?)",
                    [
                        $pricing_effective_date,
                        $group["id"],
                        $service_id,
                        $tier["volume_start"],
                        $tier["volume_end"],
                        round($tier["price_per_inquiry"] * $discount, 4),
                    ],
                );
                $group_tier_count++;
            }
        }
    }
    seed_log("   Created $group_tier_count group tier overrides");

    // ----------------------------------------------------------------
    // 8. CUSTOMER PRICING OVERRIDES
    // ----------------------------------------------------------------
    seed_log("8. Creating customer pricing overrides...");
    $customer_tier_count = 0;

    foreach ($customers as $customer) {
        if (mt_rand(1, 100) > $config["customer_has_override_pct"]) {
            continue;
        }

        // Override 1-3 services
        $override_count = mt_rand(1, 3);
        $service_ids = array_column($services, "id");
        shuffle($service_ids);
        $override_service_ids = array_slice($service_ids, 0, $override_count);

        foreach ($override_service_ids as $service_id) {
            $default_tiers = sqlite_query(
                "SELECT * FROM pricing_tiers WHERE level = 'default' AND service_id = ? ORDER BY volume_start",
                [$service_id],
            );

            // Customer modifier (-25% to +10%)
            $modifier = 1 + mt_rand(-25, 10) / 100;

            foreach ($default_tiers as $tier) {
                sqlite_execute(
                    "INSERT INTO pricing_tiers (effective_date, level, level_id, service_id, volume_start, volume_end, price_per_inquiry)
                     VALUES (?, 'customer', ?, ?, ?, ?, ?)",
                    [
                        $pricing_effective_date,
                        $customer["id"],
                        $service_id,
                        $tier["volume_start"],
                        $tier["volume_end"],
                        round($tier["price_per_inquiry"] * $modifier, 4),
                    ],
                );
                $customer_tier_count++;
            }
        }
    }
    seed_log("   Created $customer_tier_count customer tier overrides");

    // ----------------------------------------------------------------
    // 9. CUSTOMER SETTINGS
    // ----------------------------------------------------------------
    seed_log("9. Creating customer settings...");
    $settings_count = 0;

    foreach ($customers as $customer) {
        $has_minimum = mt_rand(1, 100) <= $config["customer_has_minimum_pct"];
        $has_annualized = mt_rand(1, 100) <= 20; // 20% annualized

        if (!$has_minimum && !$has_annualized) {
            continue;
        }

        sqlite_execute(
            "INSERT INTO customer_settings (customer_id, effective_date, monthly_minimum, uses_annualized, annualized_start_date, look_period_months)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $customer["id"],
                $pricing_effective_date,
                $has_minimum
                    ? round(
                        $config["minimum_min"] +
                            (mt_rand(0, 100) / 100) *
                                ($config["minimum_max"] -
                                    $config["minimum_min"]),
                        2,
                    )
                    : null,
                $has_annualized ? 1 : 0,
                $has_annualized ? $customer["contract_start_date"] : null,
                $has_annualized ? (mt_rand(0, 1) ? 3 : 6) : null,
            ],
        );
        $settings_count++;
    }
    seed_log("   Created $settings_count customer settings");

    // ----------------------------------------------------------------
    // 10. ESCALATORS
    // ----------------------------------------------------------------
    seed_log("10. Creating escalators...");
    $escalator_count = 0;
    $delay_count = 0;
    $customers_with_escalators = [];

    foreach ($customers as $customer) {
        if (mt_rand(1, 100) > $config["customer_has_escalator_pct"]) {
            continue;
        }

        $customers_with_escalators[] = $customer["id"];
        $year_count = mt_rand(3, 5);
        $base_pct = mt_rand(3, 6);

        for ($year = 1; $year <= $year_count; $year++) {
            $pct = $year === 1 ? 0 : $base_pct + mt_rand(-1, 1);
            $fixed =
                mt_rand(1, 100) <= 15 ? round(mt_rand(5, 30) / 1000, 4) : 0; // Small fixed adjustments

            sqlite_execute(
                "INSERT INTO customer_escalators (customer_id, effective_date, escalator_start_date, year_number, escalator_percentage, fixed_adjustment)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $customer["id"],
                    $pricing_effective_date,
                    $customer["contract_start_date"],
                    $year,
                    $pct,
                    $fixed,
                ],
            );
            $escalator_count++;
        }

        // 10% chance of delay
        if (mt_rand(1, 100) <= 10) {
            sqlite_execute(
                "INSERT INTO escalator_delays (customer_id, year_number, delay_months, applied_date)
                 VALUES (?, 2, ?, ?)",
                [$customer["id"], mt_rand(1, 3), $pricing_effective_date],
            );
            $delay_count++;
        }
    }
    seed_log(
        "   Created $escalator_count escalator records ($delay_count with delays)",
    );

    // ----------------------------------------------------------------
    // 11. SERVICE COGS
    // ----------------------------------------------------------------
    seed_log("11. Creating service COGS...");

    foreach ($services as $service) {
        $avg_price = sqlite_query(
            "SELECT AVG(price_per_inquiry) as avg FROM pricing_tiers WHERE level = 'default' AND service_id = ?",
            [$service["id"]],
        )[0]["avg"];

        $cogs = round($avg_price * $config["cogs_margin"], 4);

        sqlite_execute(
            "INSERT INTO service_cogs (service_id, cogs_rate, effective_date) VALUES (?, ?, ?)",
            [$service["id"], $cogs, $pricing_effective_date],
        );
    }
    seed_log("   Created COGS for " . count($services) . " services");

    // ----------------------------------------------------------------
    // 12. SYSTEM SETTINGS
    // ----------------------------------------------------------------
    seed_log("12. Creating system settings...");
    sqlite_execute(
        "INSERT OR REPLACE INTO system_settings (key, value) VALUES ('default_commission_rate', ?)",
        [(string) $config["default_commission_rate"]],
    );
    seed_log("   Created system settings");

    // ----------------------------------------------------------------
    // 13. BILLING REPORTS (The main event!)
    // ----------------------------------------------------------------
    seed_log("");
    seed_log("13. Generating billing reports with audit-compatible pricing...");
    seed_log("    This may take a moment...");

    // Need calculator for price lookups
    require_once __DIR__ . "/calculator.php";

    $report_count = 0;
    $line_count = 0;
    $match_count = 0;
    $small_var_count = 0;
    $large_var_count = 0;
    $error_count = 0;

    // Generate daily reports
    $active_customer_ids = array_column($active_customers, "id");

    for (
        $day_offset = $config["days_of_history"];
        $day_offset >= 0;
        $day_offset--
    ) {
        $report_date = date("Y-m-d", strtotime("-$day_offset days"));
        $report_year = (int) date("Y", strtotime($report_date));
        $report_month = (int) date("m", strtotime($report_date));
        $report_day = (int) date("d", strtotime($report_date));

        // Create report
        sqlite_execute(
            "INSERT INTO billing_reports (report_type, report_year, report_month, report_date, record_count)
             VALUES ('daily', ?, ?, ?, 0)",
            [$report_year, $report_month, $report_date],
        );
        $report_id = sqlite_db()->lastInsertRowID();
        $report_count++;

        $report_line_count = 0;

        // Each active customer gets some transactions
        foreach ($active_customer_ids as $customer_id) {
            // Pick random transaction types for this customer
            $num_types = mt_rand(
                $config["tx_types_per_customer_min"],
                $config["tx_types_per_customer_max"],
            );
            $selected_types = array_rand(
                $transaction_types,
                min($num_types, count($transaction_types)),
            );
            if (!is_array($selected_types)) {
                $selected_types = [$selected_types];
            }

            foreach ($selected_types as $type_idx) {
                $tx_type = $transaction_types[$type_idx];

                // Generate count
                $count = mt_rand($config["count_min"], $config["count_max"]);
                if (mt_rand(1, 100) <= $config["count_high_volume_pct"]) {
                    $count = mt_rand(
                        $config["count_max"],
                        $config["count_high_volume_max"],
                    );
                }

                // Calculate expected price using our pricing engine
                $audit = calculate_price_audit(
                    $report_date,
                    $customer_id,
                    $tx_type["efx_code"],
                    $count,
                );

                $expected_price = $audit["expected_unit_price"];

                if ($expected_price === null || !empty($audit["errors"])) {
                    // Pricing calculation failed - use a fallback price
                    // This simulates data that can't be audited (missing mappings, etc.)
                    $expected_price = 0.15 + mt_rand(0, 100) / 1000;
                    $error_count++;
                    $variance_type = "error";
                } else {
                    // Decide variance type
                    $variance_type = weighted_choice([
                        "exact" => $config["exact_match_pct"],
                        "small" => $config["small_variance_pct"],
                        "large" => $config["large_variance_pct"],
                    ]);
                }

                // Apply variance
                switch ($variance_type) {
                    case "exact":
                        $actual_price = $expected_price;
                        $match_count++;
                        break;
                    case "small":
                        // Off by 0.0001-0.0010 (rounding errors)
                        $actual_price =
                            $expected_price + mt_rand(-10, 10) / 10000;
                        $small_var_count++;
                        break;
                    case "large":
                        // Wrong by 5-30%
                        $actual_price =
                            $expected_price * (1 + mt_rand(-30, 30) / 100);
                        $large_var_count++;
                        break;
                    case "error":
                        $actual_price = $expected_price;
                        break;
                }

                $actual_price = max(0.01, round($actual_price, 4));
                $revenue = round($actual_price * $count, 2);

                // Get customer name
                $customer_name = sqlite_query(
                    "SELECT name FROM customers WHERE id = ?",
                    [$customer_id],
                )[0]["name"];

                // Generate billing ID
                $billing_id = sprintf(
                    "B%04d%02d%02d%05d",
                    $report_year,
                    $report_month,
                    $report_day,
                    $report_line_count + 1,
                );

                // Insert line
                sqlite_execute(
                    "INSERT INTO billing_report_lines
                     (report_id, year, month, customer_id, customer_name, hit_code, tran_displayname, actual_unit_cost, count, revenue, efx_code, billing_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $report_id,
                        $report_year,
                        $report_month,
                        $customer_id,
                        $customer_name,
                        weighted_choice([
                            "H" => 70,
                            "M" => 20,
                            "E" => 5,
                            "N" => 5,
                        ]),
                        $tx_type["display_name"],
                        $actual_price,
                        $count,
                        $revenue,
                        $tx_type["efx_code"],
                        $billing_id,
                    ],
                );

                $report_line_count++;
                $line_count++;
            }
        }

        // Update report record count
        sqlite_execute(
            "UPDATE billing_reports SET record_count = ? WHERE id = ?",
            [$report_line_count, $report_id],
        );

        // Progress indicator
        if ($day_offset % 10 === 0) {
            seed_log("    ... $report_count reports, $line_count lines so far");
        }
    }

    // ----------------------------------------------------------------
    // 14. MONTHLY REPORTS (aggregate)
    // ----------------------------------------------------------------
    seed_log("14. Creating monthly aggregate reports...");

    // Get distinct year/months from daily reports
    $months = sqlite_query(
        "SELECT DISTINCT report_year, report_month FROM billing_reports WHERE report_type = 'daily' ORDER BY report_year, report_month",
    );

    foreach ($months as $month) {
        $year = $month["report_year"];
        $mo = $month["report_month"];
        $report_date = sprintf("%04d-%02d-01", $year, $mo);

        // Create monthly report
        sqlite_execute(
            "INSERT INTO billing_reports (report_type, report_year, report_month, report_date, record_count)
             VALUES ('monthly', ?, ?, ?, 0)",
            [$year, $mo, $report_date],
        );
        $monthly_report_id = sqlite_db()->lastInsertRowID();
        $report_count++;

        // Aggregate daily lines into monthly
        // Group by customer_id, efx_code and sum counts/revenue
        $aggregated = sqlite_query(
            "SELECT
                brl.customer_id, brl.customer_name, brl.efx_code, brl.tran_displayname,
                SUM(brl.count) as total_count,
                SUM(brl.revenue) as total_revenue,
                brl.hit_code
             FROM billing_report_lines brl
             JOIN billing_reports br ON brl.report_id = br.id
             WHERE br.report_type = 'daily' AND br.report_year = ? AND br.report_month = ?
             GROUP BY brl.customer_id, brl.efx_code
             ORDER BY brl.customer_id, brl.efx_code",
            [$year, $mo],
        );

        $monthly_line_count = 0;
        foreach ($aggregated as $agg) {
            $unit_cost =
                $agg["total_count"] > 0
                    ? round($agg["total_revenue"] / $agg["total_count"], 4)
                    : 0;
            $billing_id = sprintf(
                "M%04d%02d%05d",
                $year,
                $mo,
                $monthly_line_count + 1,
            );

            sqlite_execute(
                "INSERT INTO billing_report_lines
                 (report_id, year, month, customer_id, customer_name, hit_code, tran_displayname, actual_unit_cost, count, revenue, efx_code, billing_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $monthly_report_id,
                    $year,
                    $mo,
                    $agg["customer_id"],
                    $agg["customer_name"],
                    $agg["hit_code"],
                    $agg["tran_displayname"],
                    $unit_cost,
                    $agg["total_count"],
                    $agg["total_revenue"],
                    $agg["efx_code"],
                    $billing_id,
                ],
            );
            $monthly_line_count++;
            $line_count++;
        }

        sqlite_execute(
            "UPDATE billing_reports SET record_count = ? WHERE id = ?",
            [$monthly_line_count, $monthly_report_id],
        );
    }

    seed_log("   Created " . count($months) . " monthly reports");

    // ----------------------------------------------------------------
    // SUMMARY
    // ----------------------------------------------------------------
    $elapsed = round(microtime(true) - $start_time, 2);

    seed_log("");
    seed_log("========================================");
    seed_log("SEEDING COMPLETE");
    seed_log("========================================");
    seed_log("Time: {$elapsed}s");
    seed_log("");
    seed_log("Reports: $report_count");
    seed_log("Lines: $line_count");
    seed_log("");
    seed_log("Variance Distribution:");
    seed_log(
        "  Exact matches: $match_count (" .
            round(($match_count / max(1, $line_count)) * 100, 1) .
            "%)",
    );
    seed_log(
        "  Small variance: $small_var_count (" .
            round(($small_var_count / max(1, $line_count)) * 100, 1) .
            "%)",
    );
    seed_log(
        "  Large variance: $large_var_count (" .
            round(($large_var_count / max(1, $line_count)) * 100, 1) .
            "%)",
    );
    seed_log(
        "  Calc errors: $error_count (" .
            round(($error_count / max(1, $line_count)) * 100, 1) .
            "%)",
    );
    seed_log("");

    return [
        "success" => true,
        "elapsed" => $elapsed,
        "reports" => $report_count,
        "lines" => $line_count,
        "matches" => $match_count,
        "small_variances" => $small_var_count,
        "large_variances" => $large_var_count,
        "errors" => $error_count,
        "log" => isset($seed_log_buffer) ? $seed_log_buffer : [],
    ];
}

// ============================================================
// CLI EXECUTION
// ============================================================

if ($is_cli && $confirmed) {
    $result = run_seed(
        $SEED_CONFIG,
        isset($clear_first) ? $clear_first : false,
    );

    if ($result["success"]) {
        echo "\nDone!\n";
        exit(0);
    } else {
        echo "\nFailed!\n";
        exit(1);
    }
}
