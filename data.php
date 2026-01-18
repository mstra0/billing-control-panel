<?php
// ============================================================
// DATA ACCESS FUNCTIONS
// Business logic, data retrieval, calculations
// ============================================================

function get_dashboard_alerts()
{
    $alerts = [];

    // 1. Upcoming escalator anniversaries (within 30 days)
    $upcoming_escalators = get_upcoming_escalators(30);
    foreach ($upcoming_escalators as $esc) {
        $alerts[] = [
            "type" => "warning",
            "icon" => "calendar",
            "message" =>
                "Escalator Year " .
                $esc["year_number"] .
                " for <strong>" .
                h($esc["customer_name"]) .
                "</strong> activates on " .
                $esc["effective_date"],
            "link" =>
                "?action=escalator_edit&customer_id=" . $esc["customer_id"],
        ];
    }

    // 2. Customers with masked business rules
    $masked_rules = get_customers_with_masked_rules();
    foreach ($masked_rules as $mr) {
        $alerts[] = [
            "type" => "info",
            "icon" => "mask",
            "message" =>
                "<strong>" .
                h($mr["customer_name"]) .
                "</strong> has " .
                $mr["masked_count"] .
                " masked business rule(s)",
            "link" =>
                "?action=business_rule_edit&customer_id=" . $mr["customer_id"],
        ];
    }

    // 3. Paused customers reminder
    $paused = sqlite_query(
        "SELECT id, name FROM customers WHERE status = 'paused'"
    );
    foreach ($paused as $p) {
        $alerts[] = [
            "type" => "warning",
            "icon" => "pause",
            "message" =>
                "<strong>" . h($p["name"]) . "</strong> is currently paused",
            "link" => "?action=pricing_customer_edit&customer_id=" . $p["id"],
        ];
    }

    // 4. Annualized tier resets coming up (within 30 days)
    $upcoming_resets = get_upcoming_annualized_resets(30);
    foreach ($upcoming_resets as $reset) {
        $alerts[] = [
            "type" => "info",
            "icon" => "refresh",
            "message" =>
                "Annualized tier reset for <strong>" .
                h($reset["customer_name"]) .
                "</strong> on " .
                $reset["reset_date"],
            "link" =>
                "?action=pricing_customer_edit&customer_id=" .
                $reset["customer_id"] .
                "&tab=settings",
        ];
    }

    return $alerts;
}

/**
 * Get escalators coming up in the next N days
 */
function get_upcoming_escalators($days = 30)
{
    $results = [];

    // Get all customers with escalators
    $customers = sqlite_query(
        "SELECT DISTINCT c.id, c.name FROM customers c
         INNER JOIN customer_escalators ce ON c.id = ce.customer_id
         WHERE c.status = 'active'"
    );

    $today = date("Y-m-d");
    $future = date("Y-m-d", strtotime("+$days days"));

    foreach ($customers as $customer) {
        $escalators = get_current_escalators($customer["id"]);
        if (empty($escalators)) {
            continue;
        }

        $start_date = $escalators[0]["escalator_start_date"];
        if (!$start_date) {
            continue;
        }

        // Check each year's anniversary
        foreach ($escalators as $esc) {
            $year = $esc["year_number"];
            $delay_months = get_total_delay_months($customer["id"], $year);

            // Calculate when this escalator takes effect
            $anniversary = date(
                "Y-m-d",
                strtotime(
                    $start_date .
                        " + " .
                        ($year - 1) .
                        " years + $delay_months months"
                )
            );

            // Check if it's within the window
            if ($anniversary >= $today && $anniversary <= $future) {
                $results[] = [
                    "customer_id" => $customer["id"],
                    "customer_name" => $customer["name"],
                    "year_number" => $year,
                    "effective_date" => $anniversary,
                    "percentage" => $esc["escalator_percentage"],
                ];
            }
        }
    }

    // Sort by date
    usort($results, function ($a, $b) {
        return strcmp($a["effective_date"], $b["effective_date"]);
    });

    return array_slice($results, 0, 5); // Limit to 5
}

/**
 * Get customers with masked business rules
 */
function get_customers_with_masked_rules()
{
    $results = [];

    $customers = sqlite_query(
        "SELECT DISTINCT c.id, c.name FROM customers c
         INNER JOIN business_rules br ON c.id = br.customer_id
         WHERE c.status = 'active'"
    );

    foreach ($customers as $customer) {
        $rules = get_customer_rules($customer["id"]);
        $masked_count = 0;

        foreach ($rules as $rule) {
            if (get_rule_mask_status($customer["id"], $rule["rule_name"])) {
                $masked_count++;
            }
        }

        if ($masked_count > 0) {
            $results[] = [
                "customer_id" => $customer["id"],
                "customer_name" => $customer["name"],
                "masked_count" => $masked_count,
            ];
        }
    }

    return $results;
}

/**
 * Get annualized tier resets coming up
 */
function get_upcoming_annualized_resets($days = 30)
{
    $results = [];

    $settings = sqlite_query(
        "SELECT cs.*, c.name as customer_name
         FROM customer_settings cs
         INNER JOIN customers c ON cs.customer_id = c.id
         WHERE cs.uses_annualized = 1
         AND cs.annualized_start_date IS NOT NULL
         AND c.status = 'active'
         AND cs.effective_date = (
             SELECT MAX(cs2.effective_date) FROM customer_settings cs2
             WHERE cs2.customer_id = cs.customer_id AND cs2.effective_date <= date('now')
         )"
    );

    $today = date("Y-m-d");
    $future = date("Y-m-d", strtotime("+$days days"));
    $current_year = date("Y");

    foreach ($settings as $setting) {
        if (!$setting["annualized_start_date"]) {
            continue;
        }

        // Calculate next reset date (same month/day as start, in current or next year)
        $start = $setting["annualized_start_date"];
        $start_md = substr($start, 5); // MM-DD

        $reset_this_year = $current_year . "-" . $start_md;
        $reset_next_year = $current_year + 1 . "-" . $start_md;

        $reset_date =
            $reset_this_year >= $today ? $reset_this_year : $reset_next_year;

        if ($reset_date >= $today && $reset_date <= $future) {
            $results[] = [
                "customer_id" => $setting["customer_id"],
                "customer_name" => $setting["customer_name"],
                "reset_date" => $reset_date,
            ];
        }
    }

    return $results;
}

// ============================================================
// BILLING CALENDAR FUNCTIONS
// ============================================================

/**
 * Get events for a specific month (escalators, resets, etc.)
 */
function get_month_events($year, $month)
{
    $events = [
        "escalators" => [],
        "resets" => [],
        "new_customers" => [],
        "paused_customers" => [],
        "warnings" => [],
    ];

    $month_start = sprintf("%04d-%02d-01", $year, $month);
    $month_end = date("Y-m-t", strtotime($month_start));

    // Get escalators for this month
    $customers = sqlite_query(
        "SELECT DISTINCT c.id, c.name, c.contract_start_date FROM customers c
         INNER JOIN customer_escalators ce ON c.id = ce.customer_id
         WHERE c.status = 'active'"
    );

    foreach ($customers as $customer) {
        $escalators = get_current_escalators($customer["id"]);
        if (empty($escalators)) {
            continue;
        }

        $start_date = $escalators[0]["escalator_start_date"];
        if (!$start_date) {
            continue;
        }

        foreach ($escalators as $esc) {
            $esc_year = $esc["year_number"];
            if ($esc_year <= 1) {
                continue;
            } // Year 1 is baseline, no escalation event

            $delay_months = get_total_delay_months($customer["id"], $esc_year);
            $anniversary = date(
                "Y-m-d",
                strtotime(
                    $start_date .
                        " + " .
                        ($esc_year - 1) .
                        " years + $delay_months months"
                )
            );

            // Check if this anniversary falls in our month
            if ($anniversary >= $month_start && $anniversary <= $month_end) {
                $events["escalators"][] = [
                    "customer_id" => $customer["id"],
                    "customer_name" => $customer["name"],
                    "year_number" => $esc_year,
                    "effective_date" => $anniversary,
                    "percentage" => $esc["escalator_percentage"],
                    "fixed_adjustment" => $esc["fixed_adjustment"],
                    "has_delay" => $delay_months > 0,
                    "delay_months" => $delay_months,
                ];
            }
        }
    }

    // Get annualized resets for this month
    $settings = sqlite_query(
        "SELECT cs.*, c.name as customer_name
         FROM customer_settings cs
         INNER JOIN customers c ON cs.customer_id = c.id
         WHERE cs.uses_annualized = 1
         AND cs.annualized_start_date IS NOT NULL
         AND c.status = 'active'
         AND cs.effective_date = (
             SELECT MAX(cs2.effective_date) FROM customer_settings cs2
             WHERE cs2.customer_id = cs.customer_id AND cs2.effective_date <= date('now')
         )"
    );

    foreach ($settings as $setting) {
        if (!$setting["annualized_start_date"]) {
            continue;
        }

        $start_md = substr($setting["annualized_start_date"], 5); // MM-DD
        $reset_date = sprintf("%04d-%s", $year, $start_md);

        if ($reset_date >= $month_start && $reset_date <= $month_end) {
            $events["resets"][] = [
                "customer_id" => $setting["customer_id"],
                "customer_name" => $setting["customer_name"],
                "reset_date" => $reset_date,
            ];
        }
    }

    // Get paused customers
    $paused = sqlite_query(
        "SELECT id, name FROM customers WHERE status = 'paused'"
    );
    $events["paused_customers"] = $paused;

    // Check for warnings
    // - Customers without LMS
    $no_lms = sqlite_query(
        "SELECT id, name FROM customers WHERE status = 'active' AND (lms_id IS NULL OR lms_id = 0)"
    );
    foreach ($no_lms as $c) {
        $events["warnings"][] = [
            "type" => "no_lms",
            "message" => $c["name"] . " has no LMS assigned",
            "customer_id" => $c["id"],
            "customer_name" => $c["name"],
        ];
    }

    return $events;
}

/**
 * Check if a month is "complete" (monthly report ingested)
 */
function is_month_complete($year, $month)
{
    $result = sqlite_query(
        "SELECT COUNT(*) as cnt FROM billing_reports
         WHERE report_type = 'monthly' AND report_year = ? AND report_month = ?",
        [$year, $month]
    );
    return $result[0]["cnt"] > 0;
}

/**
 * Get calendar summary for a full year
 */
function get_calendar_year_summary($year)
{
    $months = [];

    for ($m = 1; $m <= 12; $m++) {
        $events = get_month_events($year, $m);

        $event_count = count($events["escalators"]) + count($events["resets"]);
        $warning_count =
            count($events["warnings"]) + count($events["paused_customers"]);
        $has_escalators = count($events["escalators"]) > 0;

        $months[$m] = [
            "year" => $year,
            "month" => $m,
            "month_name" => date("M", mktime(0, 0, 0, $m, 1)),
            "event_count" => $event_count,
            "warning_count" => $warning_count,
            "has_escalators" => $has_escalators,
            "is_complete" => is_month_complete($year, $m),
            "is_current" => $year == date("Y") && $m == date("n"),
            "is_past" =>
                $year < date("Y") || ($year == date("Y") && $m < date("n")),
        ];
    }

    return $months;
}

/**
 * Get the next incomplete month for "What's Next?" button
 */
function get_next_incomplete_month()
{
    $year = date("Y");
    $month = date("n");

    // Check current month first
    if (!is_month_complete($year, $month)) {
        return ["year" => $year, "month" => $month];
    }

    // Check future months this year
    for ($m = $month + 1; $m <= 12; $m++) {
        if (!is_month_complete($year, $m)) {
            return ["year" => $year, "month" => $m];
        }
    }

    // Check next year
    for ($m = 1; $m <= 12; $m++) {
        if (!is_month_complete($year + 1, $m)) {
            return ["year" => $year + 1, "month" => $m];
        }
    }

    return ["year" => $year, "month" => $month];
}

/**
 * Get new customers since a reference date
 */
function get_new_customers_since($since_date)
{
    return sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.created_at >= ?
         ORDER BY c.created_at DESC",
        [$since_date]
    );
}

/**
 * Get configuration changes since a reference date
 */
function get_config_changes_since($since_date)
{
    $changes = [];

    // Pricing changes (customer-level only)
    $pricing = sqlite_query(
        "SELECT 'pricing' as type, c.name as customer_name, pt.level_id as customer_id,
                pt.effective_date, s.name as service_name
         FROM pricing_tiers pt
         JOIN customers c ON pt.level_id = c.id
         JOIN services s ON pt.service_id = s.id
         WHERE pt.effective_date >= ? AND pt.level = 'customer'
         ORDER BY pt.effective_date DESC",
        [$since_date]
    );
    foreach ($pricing as $p) {
        $changes[] = [
            "type" => "pricing",
            "description" =>
                $p["customer_name"] .
                ": pricing changed for " .
                $p["service_name"],
            "customer_id" => $p["customer_id"],
            "date" => $p["effective_date"],
        ];
    }

    // Settings changes
    $settings = sqlite_query(
        "SELECT cs.*, c.name as customer_name
         FROM customer_settings cs
         JOIN customers c ON cs.customer_id = c.id
         WHERE cs.effective_date >= ?
         ORDER BY cs.effective_date DESC",
        [$since_date]
    );
    foreach ($settings as $s) {
        $changes[] = [
            "type" => "settings",
            "description" => $s["customer_name"] . ": settings updated",
            "customer_id" => $s["customer_id"],
            "date" => $s["effective_date"],
        ];
    }

    // Sort by date descending
    usort($changes, function ($a, $b) {
        return strcmp($b["date"], $a["date"]);
    });

    return $changes;
}

/**
 * Get month-to-date billing summary from daily reports
 */
function get_mtd_summary($year, $month)
{
    $result = sqlite_query(
        "SELECT
            COUNT(DISTINCT brl.customer_id) as customer_count,
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT br.id) as report_count,
            MAX(br.report_date) as latest_date
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE br.report_type = 'daily'
         AND br.report_year = ?
         AND br.report_month = ?",
        [$year, $month]
    );

    return $result[0];
}

/**
 * Get daily breakdown of MTD billing data
 */
function get_mtd_daily_breakdown($year, $month)
{
    return sqlite_query(
        "SELECT
            br.report_date,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE br.report_type = 'daily'
         AND br.report_year = ?
         AND br.report_month = ?
         GROUP BY br.report_date
         ORDER BY br.report_date",
        [$year, $month]
    );
}

/**
 * Get service breakdown of MTD billing data
 */
function get_mtd_service_breakdown($year, $month)
{
    return sqlite_query(
        "SELECT
            brl.efx_code,
            s.name as service_name,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         LEFT JOIN transaction_types tt ON brl.efx_code = tt.efx_code
         LEFT JOIN services s ON tt.service_id = s.id
         WHERE br.report_type = 'daily'
         AND br.report_year = ?
         AND br.report_month = ?
         GROUP BY brl.efx_code, s.name
         ORDER BY revenue DESC",
        [$year, $month]
    );
}

/**
 * Get customer breakdown of MTD billing data
 */
function get_mtd_customer_breakdown($year, $month)
{
    return sqlite_query(
        "SELECT
            brl.customer_id,
            brl.customer_name,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE br.report_type = 'daily'
         AND br.report_year = ?
         AND br.report_month = ?
         GROUP BY brl.customer_id, brl.customer_name
         ORDER BY revenue DESC",
        [$year, $month]
    );
}

/**
 * Get previous month's MTD summary (for same day range comparison)
 */
function get_previous_month_mtd($year, $month, $day)
{
    // Calculate previous month
    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year = $year - 1;
    }

    // Get sum up to the same day in previous month
    $result = sqlite_query(
        "SELECT
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE br.report_type = 'daily'
         AND br.report_year = ?
         AND br.report_month = ?
         AND CAST(substr(br.report_date, 9, 2) AS INTEGER) <= ?",
        [$prev_year, $prev_month, $day]
    );

    return $result[0];
}

/**
 * List reports action
 */
function get_all_services()
{
    return sqlite_query("SELECT * FROM services ORDER BY name");
}

/**
 * Get system default tiers for a service
 */
function get_default_tiers($service_id)
{
    return sqlite_query(
        "SELECT * FROM pricing_tiers
         WHERE level = 'default' AND level_id IS NULL AND service_id = ?
         AND effective_date <= date('now')
         ORDER BY effective_date DESC, volume_start ASC",
        [$service_id]
    );
}

/**
 * Get current default tiers (latest effective) for a service
 */
function get_current_default_tiers($service_id)
{
    // Get the latest effective_date for this service's defaults
    $latest = sqlite_query(
        "SELECT MAX(effective_date) as max_date FROM pricing_tiers
         WHERE level = 'default' AND level_id IS NULL AND service_id = ?
         AND effective_date <= date('now')",
        [$service_id]
    );

    if (empty($latest) || !$latest[0]["max_date"]) {
        return [];
    }

    return sqlite_query(
        "SELECT * FROM pricing_tiers
         WHERE level = 'default' AND level_id IS NULL AND service_id = ?
         AND effective_date = ?
         ORDER BY volume_start ASC",
        [$service_id, $latest[0]["max_date"]]
    );
}

/**
 * Save default tiers for a service (append new effective set)
 */
function save_default_tiers($service_id, $tiers, $effective_date = null)
{
    if ($effective_date === null) {
        $effective_date = date("Y-m-d");
    }

    foreach ($tiers as $tier) {
        sqlite_execute(
            "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date)
             VALUES ('default', NULL, ?, ?, ?, ?, ?)",
            [
                $service_id,
                $tier["volume_start"],
                $tier["volume_end"],
                $tier["price_per_inquiry"],
                $effective_date,
            ]
        );
    }

    return true;
}

/**
 * Get current group tiers for a service (group-level overrides only)
 */
function get_current_group_tiers($group_id, $service_id)
{
    $latest = sqlite_query(
        "SELECT MAX(effective_date) as max_date FROM pricing_tiers
         WHERE level = 'group' AND level_id = ? AND service_id = ?
         AND effective_date <= date('now')",
        [$group_id, $service_id]
    );

    if (empty($latest) || !$latest[0]["max_date"]) {
        return [];
    }

    return sqlite_query(
        "SELECT * FROM pricing_tiers
         WHERE level = 'group' AND level_id = ? AND service_id = ?
         AND effective_date = ?
         ORDER BY volume_start ASC",
        [$group_id, $service_id, $latest[0]["max_date"]]
    );
}

/**
 * Save group tiers for a service (append new effective set)
 */
function save_group_tiers(
    $group_id,
    $service_id,
    $tiers,
    $effective_date = null
) {
    if ($effective_date === null) {
        $effective_date = date("Y-m-d");
    }

    foreach ($tiers as $tier) {
        sqlite_execute(
            "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date)
             VALUES ('group', ?, ?, ?, ?, ?, ?)",
            [
                $group_id,
                $service_id,
                $tier["volume_start"],
                $tier["volume_end"],
                $tier["price_per_inquiry"],
                $effective_date,
            ]
        );
    }

    return true;
}

/**
 * Clear group overrides for a service (by inserting empty set - append only)
 * We mark it cleared by inserting a special record or just not having records for new date
 */
function clear_group_tiers($group_id, $service_id)
{
    // In append-only model, "clearing" means the group now inherits from default
    // We don't actually delete, we just don't have overrides for current date
    // The UI will check if group has overrides, if not, show inherited
    return true;
}

/**
 * Get effective tiers for a group+service (group override or default fallback)
 */
function get_effective_group_tiers($group_id, $service_id)
{
    $group_tiers = get_current_group_tiers($group_id, $service_id);

    if (!empty($group_tiers)) {
        // Mark as overridden
        foreach ($group_tiers as &$tier) {
            $tier["source"] = "group";
        }
        return $group_tiers;
    }

    // Fall back to defaults
    $default_tiers = get_current_default_tiers($service_id);
    foreach ($default_tiers as &$tier) {
        $tier["source"] = "default";
    }
    return $default_tiers;
}

/**
 * Get current customer tiers for a service (customer-level overrides only)
 */
function get_current_customer_tiers($customer_id, $service_id)
{
    $latest = sqlite_query(
        "SELECT MAX(effective_date) as max_date FROM pricing_tiers
         WHERE level = 'customer' AND level_id = ? AND service_id = ?
         AND effective_date <= date('now')",
        [$customer_id, $service_id]
    );

    if (empty($latest) || !$latest[0]["max_date"]) {
        return [];
    }

    return sqlite_query(
        "SELECT * FROM pricing_tiers
         WHERE level = 'customer' AND level_id = ? AND service_id = ?
         AND effective_date = ?
         ORDER BY volume_start ASC",
        [$customer_id, $service_id, $latest[0]["max_date"]]
    );
}

/**
 * Save customer tiers for a service (append new effective set)
 */
function save_customer_tiers(
    $customer_id,
    $service_id,
    $tiers,
    $effective_date = null
) {
    if ($effective_date === null) {
        $effective_date = date("Y-m-d");
    }

    foreach ($tiers as $tier) {
        sqlite_execute(
            "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date)
             VALUES ('customer', ?, ?, ?, ?, ?, ?)",
            [
                $customer_id,
                $service_id,
                $tier["volume_start"],
                $tier["volume_end"],
                $tier["price_per_inquiry"],
                $effective_date,
            ]
        );
    }

    return true;
}

/**
 * Get effective tiers for a customer+service (full inheritance: customer -> group -> default)
 */
function get_effective_customer_tiers($customer_id, $service_id)
{
    // First check customer override
    $customer_tiers = get_current_customer_tiers($customer_id, $service_id);

    if (!empty($customer_tiers)) {
        foreach ($customer_tiers as &$tier) {
            $tier["source"] = "customer";
        }
        return $customer_tiers;
    }

    // Check if customer belongs to a group
    $customer = sqlite_query(
        "SELECT discount_group_id FROM customers WHERE id = ?",
        [$customer_id]
    );

    if (!empty($customer) && $customer[0]["discount_group_id"]) {
        $group_id = $customer[0]["discount_group_id"];
        $group_tiers = get_current_group_tiers($group_id, $service_id);

        if (!empty($group_tiers)) {
            foreach ($group_tiers as &$tier) {
                $tier["source"] = "group";
            }
            return $group_tiers;
        }
    }

    // Fall back to defaults
    $default_tiers = get_current_default_tiers($service_id);
    foreach ($default_tiers as &$tier) {
        $tier["source"] = "default";
    }
    return $default_tiers;
}

/**
 * Get customer settings (monthly minimum, annualized, etc.)
 */
function get_current_customer_settings($customer_id)
{
    $settings = sqlite_query(
        "SELECT * FROM customer_settings
         WHERE customer_id = ? AND effective_date <= date('now')
         ORDER BY effective_date DESC, id DESC LIMIT 1",
        [$customer_id]
    );

    if (!empty($settings)) {
        return $settings[0];
    }

    // Return defaults
    return [
        "customer_id" => $customer_id,
        "monthly_minimum" => null,
        "uses_annualized" => 0,
        "annualized_start_date" => null,
        "look_period_months" => null,
    ];
}

/**
 * Save customer settings (append-only)
 */
function save_customer_settings($customer_id, $settings)
{
    $effective_date = date("Y-m-d");

    sqlite_execute(
        "INSERT INTO customer_settings (customer_id, effective_date, monthly_minimum, uses_annualized, annualized_start_date, look_period_months)
         VALUES (?, ?, ?, ?, ?, ?)",
        [
            $customer_id,
            $effective_date,
            isset($settings["monthly_minimum"]) &&
            $settings["monthly_minimum"] !== ""
                ? (float) $settings["monthly_minimum"]
                : null,
            isset($settings["uses_annualized"])
                ? (int) $settings["uses_annualized"]
                : 0,
            isset($settings["annualized_start_date"]) &&
            $settings["annualized_start_date"] !== ""
                ? $settings["annualized_start_date"]
                : null,
            isset($settings["look_period_months"]) &&
            $settings["look_period_months"] !== ""
                ? (int) $settings["look_period_months"]
                : null,
        ]
    );

    return true;
}

/**
 * Calculate monthly minimum gap for a customer
 * Given usage amount, returns the gap needed to reach minimum (or 0 if above minimum)
 *
 * @param int   $customer_id    Customer ID
 * @param float $usage_amount   Current month's calculated usage amount
 * @return array                ['minimum' => float, 'usage' => float, 'gap' => float, 'has_minimum' => bool]
 */
function calculate_monthly_minimum_gap($customer_id, $usage_amount)
{
    $settings = get_current_customer_settings($customer_id);

    $result = [
        "minimum" => 0,
        "usage" => $usage_amount,
        "gap" => 0,
        "has_minimum" => false,
    ];

    if ($settings["monthly_minimum"] && $settings["monthly_minimum"] > 0) {
        $result["has_minimum"] = true;
        $result["minimum"] = (float) $settings["monthly_minimum"];

        if ($usage_amount < $result["minimum"]) {
            $result["gap"] = $result["minimum"] - $usage_amount;
        }
    }

    return $result;
}

/**
 * Get all customers with monthly minimums for overview
 */
function get_customers_with_minimums()
{
    return sqlite_query(
        "SELECT c.id, c.name, c.status, cs.monthly_minimum
         FROM customers c
         INNER JOIN customer_settings cs ON c.id = cs.customer_id
         WHERE cs.monthly_minimum IS NOT NULL
         AND cs.monthly_minimum > 0
         AND cs.effective_date = (
             SELECT MAX(cs2.effective_date) FROM customer_settings cs2
             WHERE cs2.customer_id = cs.customer_id AND cs2.effective_date <= date('now')
         )
         ORDER BY c.name"
    );
}

// ------------------------------------------------------------
// PRICING: SYSTEM DEFAULTS ACTIONS
// ------------------------------------------------------------

/**
 * List all services with their default pricing
 */
function get_current_escalators($customer_id)
{
    // Get the latest escalator set by finding max effective_date
    $latest = sqlite_query(
        "SELECT MAX(effective_date) as max_date FROM customer_escalators
         WHERE customer_id = ? AND effective_date <= date('now')",
        [$customer_id]
    );

    if (empty($latest) || !$latest[0]["max_date"]) {
        return [];
    }

    return sqlite_query(
        "SELECT * FROM customer_escalators
         WHERE customer_id = ? AND effective_date = ?
         ORDER BY year_number ASC",
        [$customer_id, $latest[0]["max_date"]]
    );
}

/**
 * Save escalators for a customer (append-only)
 */
function save_escalators($customer_id, $escalators, $escalator_start_date)
{
    $effective_date = date("Y-m-d");

    foreach ($escalators as $esc) {
        sqlite_execute(
            "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $customer_id,
                $escalator_start_date,
                $esc["year_number"],
                isset($esc["escalator_percentage"])
                    ? (float) $esc["escalator_percentage"]
                    : 0,
                isset($esc["fixed_adjustment"])
                    ? (float) $esc["fixed_adjustment"]
                    : 0,
                $effective_date,
            ]
        );
    }

    return true;
}

/**
 * Get delays for a customer's escalators
 */
function get_escalator_delays($customer_id)
{
    return sqlite_query(
        "SELECT * FROM escalator_delays
         WHERE customer_id = ?
         ORDER BY year_number ASC, applied_date DESC",
        [$customer_id]
    );
}

/**
 * Apply a delay to a specific year's escalator
 */
function apply_escalator_delay($customer_id, $year_number, $delay_months = 1)
{
    sqlite_execute(
        "INSERT INTO escalator_delays (customer_id, year_number, delay_months, applied_date)
         VALUES (?, ?, ?, date('now'))",
        [$customer_id, $year_number, $delay_months]
    );
    return true;
}

/**
 * Save escalator delay (alias for apply_escalator_delay with optional reason)
 */
function save_escalator_delay(
    $customer_id,
    $year_number,
    $delay_months = 1,
    $reason = null
) {
    return apply_escalator_delay($customer_id, $year_number, $delay_months);
}

/**
 * Calculate total delay for a specific year
 */
function get_total_delay_months($customer_id, $year_number)
{
    $delays = sqlite_query(
        "SELECT SUM(delay_months) as total FROM escalator_delays
         WHERE customer_id = ? AND year_number = ?",
        [$customer_id, $year_number]
    );

    return !empty($delays) && $delays[0]["total"]
        ? (int) $delays[0]["total"]
        : 0;
}

// ============================================================
// LMS (Loan Management System) FUNCTIONS
// ============================================================

/**
 * Get all LMS entries
 */
function get_all_lms()
{
    return sqlite_query("SELECT * FROM lms ORDER BY name");
}

/**
 * Get a single LMS by ID
 */
function get_lms($lms_id)
{
    $rows = sqlite_query("SELECT * FROM lms WHERE id = ?", [$lms_id]);
    return !empty($rows) ? $rows[0] : null;
}

/**
 * Get default commission rate from system settings
 */
function get_default_commission_rate()
{
    $rows = sqlite_query(
        "SELECT value FROM system_settings WHERE key = 'default_commission_rate'"
    );
    return !empty($rows) ? (float) $rows[0]["value"] : 10.0; // Default 10%
}

/**
 * Save default commission rate to system settings
 */
function save_default_commission_rate($rate)
{
    // Try update first
    $result = sqlite_execute(
        "UPDATE system_settings SET value = ?, updated_at = datetime('now')
         WHERE key = 'default_commission_rate'",
        [$rate]
    );

    // If no row updated, insert
    $changes = sqlite_db()->changes();
    if ($changes === 0) {
        sqlite_execute(
            "INSERT INTO system_settings (key, value, updated_at) VALUES ('default_commission_rate', ?, datetime('now'))",
            [$rate]
        );
    }
    return true;
}

/**
 * Get effective commission rate for an LMS (inherits from default if NULL)
 */
function get_effective_commission_rate($lms_id)
{
    $lms = get_lms($lms_id);
    if ($lms && $lms["commission_rate"] !== null) {
        return (float) $lms["commission_rate"];
    }
    return get_default_commission_rate();
}

/**
 * Save LMS (insert or update)
 */
function save_lms($id, $name, $commission_rate = null)
{
    // Check if exists
    $existing = get_lms($id);

    if ($existing) {
        sqlite_execute(
            "UPDATE lms SET name = ?, commission_rate = ?, updated_at = datetime('now') WHERE id = ?",
            [$name, $commission_rate, $id]
        );
    } else {
        sqlite_execute(
            "INSERT INTO lms (id, name, commission_rate, last_synced, created_at, updated_at)
             VALUES (?, ?, ?, datetime('now'), datetime('now'), datetime('now'))",
            [$id, $name, $commission_rate]
        );
    }
    return true;
}

/**
 * Sync LMS from remote database
 *
 * SOURCE TABLES (Production):
 *   - connectors table: id, name, status (active/paused/decommissioned)
 *   - [second source TBD]
 *
 * LMS is a unified view of these two source tables.
 * We pull id, name, and status. Commission rates are stored locally only.
 */
function sync_lms_from_remote()
{
    if (MOCK_MODE) {
        // Insert mock LMS data (simulating connectors table)
        // In production, this comes from: SELECT id, name, status FROM connectors
        $mock_lms = [
            ["id" => 1, "name" => "First National LMS", "status" => "active"],
            ["id" => 2, "name" => "Pacific Lending", "status" => "active"],
            ["id" => 3, "name" => "Midwest Finance Corp", "status" => "active"],
            [
                "id" => 4,
                "name" => "Atlantic Mortgage Services",
                "status" => "paused",
            ],
            [
                "id" => 5,
                "name" => "Southwest Credit Union",
                "status" => "decommissioned",
            ],
        ];

        foreach ($mock_lms as $lms) {
            // Only insert if not exists (don't override local commission rates)
            $existing = get_lms($lms["id"]);
            if (!$existing) {
                sqlite_execute(
                    "INSERT INTO lms (id, name, status, last_synced, created_at, updated_at)
                     VALUES (?, ?, ?, datetime('now'), datetime('now'), datetime('now'))",
                    [$lms["id"], $lms["name"], $lms["status"]]
                );
            } else {
                // Update name and status, preserve local commission_rate
                sqlite_execute(
                    "UPDATE lms SET name = ?, status = ?, last_synced = datetime('now'), updated_at = datetime('now') WHERE id = ?",
                    [$lms["name"], $lms["status"], $lms["id"]]
                );
            }
        }

        // Log the sync
        sqlite_execute(
            "INSERT INTO sync_log (entity_type, record_count, status) VALUES ('lms', ?, 'success')",
            [count($mock_lms)]
        );

        return count($mock_lms);
    }

    // Production: query remote DB
    // SOURCE: connectors table (+ second source TBD)
    // TODO: Update query when second source is identified
    $remote_lms = remote_db_query(
        "SELECT id, name, status FROM connectors ORDER BY name"
    );

    foreach ($remote_lms as $lms) {
        $status = isset($lms["status"]) ? $lms["status"] : "active";
        $existing = get_lms($lms["id"]);
        if (!$existing) {
            sqlite_execute(
                "INSERT INTO lms (id, name, status, last_synced, created_at, updated_at)
                 VALUES (?, ?, ?, datetime('now'), datetime('now'), datetime('now'))",
                [$lms["id"], $lms["name"], $status]
            );
        } else {
            sqlite_execute(
                "UPDATE lms SET name = ?, status = ?, last_synced = datetime('now'), updated_at = datetime('now') WHERE id = ?",
                [$lms["name"], $status, $lms["id"]]
            );
        }
    }

    sqlite_execute(
        "INSERT INTO sync_log (entity_type, record_count, status) VALUES ('lms', ?, 'success')",
        [count($remote_lms)]
    );

    return count($remote_lms);
}

/**
 * Get customers by LMS
 */
function get_customers_by_lms($lms_id)
{
    return sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.lms_id = ?
         ORDER BY c.name",
        [$lms_id]
    );
}

/**
 * Get customers without LMS assignment
 */
function get_customers_without_lms()
{
    return sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.lms_id IS NULL OR c.lms_id = 0
         ORDER BY c.name"
    );
}

/**
 * Assign customer to LMS
 */
function assign_customer_lms($customer_id, $lms_id)
{
    sqlite_execute(
        "UPDATE customers SET lms_id = ?, updated_at = datetime('now') WHERE id = ?",
        [$lms_id, $customer_id]
    );
    return true;
}

/**
 * Get COGS rate for a service (current effective)
 */
function get_service_cogs($service_id)
{
    $rows = sqlite_query(
        "SELECT cogs_rate FROM service_cogs
         WHERE service_id = ? AND effective_date <= date('now')
         ORDER BY effective_date DESC, id DESC LIMIT 1",
        [$service_id]
    );
    return !empty($rows) ? (float) $rows[0]["cogs_rate"] : 0.0;
}

/**
 * Save COGS rate for a service (append-only)
 */
function save_service_cogs($service_id, $cogs_rate, $effective_date = null)
{
    if ($effective_date === null) {
        $effective_date = date("Y-m-d");
    }

    sqlite_execute(
        "INSERT INTO service_cogs (service_id, cogs_rate, effective_date) VALUES (?, ?, ?)",
        [$service_id, $cogs_rate, $effective_date]
    );
    return true;
}

/**
 * Sync COGS from remote database
 */
function sync_cogs_from_remote()
{
    if (MOCK_MODE) {
        // Get current services and assign mock COGS
        $services = get_all_services();
        $count = 0;

        foreach ($services as $service) {
            // Check if COGS already exists for this service
            $existing = get_service_cogs($service["id"]);
            if ($existing == 0) {
                // Assign a mock COGS (roughly 30-50% of typical price)
                $mock_cogs = round(mt_rand(10, 35) / 100, 2); // $0.10 - $0.35
                save_service_cogs($service["id"], $mock_cogs);
                $count++;
            }
        }

        sqlite_execute(
            "INSERT INTO sync_log (entity_type, record_count, status) VALUES ('cogs', ?, 'success')",
            [$count]
        );

        return $count;
    }

    // Production: query remote DB
    $remote_cogs = remote_db_query(
        "SELECT service_id, cogs_rate FROM service_cogs"
    );
    $count = 0;

    foreach ($remote_cogs as $cogs) {
        save_service_cogs($cogs["service_id"], $cogs["cogs_rate"]);
        $count++;
    }

    sqlite_execute(
        "INSERT INTO sync_log (entity_type, record_count, status) VALUES ('cogs', ?, 'success')",
        [count($remote_cogs)]
    );

    return count($remote_cogs);
}

// ============================================================
// BILLING REPORT INGESTION
// ============================================================

/**
 * Parse billing report filename to extract date info and type
 *
 * Patterns:
 *   Daily:   DataX_2025_01_1_humanreadable.csv   (YYYY_MM_D)
 *   Monthly: DataX_2025_01_2025_01_humanreadable.csv (YYYY_MM_YYYY_MM)
 *
 * @param string $filename
 * @return array|false Array with type, year, month, day (if daily) or false on failure
 */
function parse_billing_filename($filename)
{
    // Monthly pattern: DataX_YYYY_MM_YYYY_MM_*.csv
    if (
        preg_match(
            "/^DataX_(\d{4})_(\d{2})_(\d{4})_(\d{2})_/",
            $filename,
            $matches
        )
    ) {
        return [
            "type" => "monthly",
            "year" => (int) $matches[1],
            "month" => (int) $matches[2],
            "end_year" => (int) $matches[3],
            "end_month" => (int) $matches[4],
            "day" => null,
        ];
    }

    // Daily pattern: DataX_YYYY_MM_D_*.csv
    if (
        preg_match("/^DataX_(\d{4})_(\d{2})_(\d{1,2})_/", $filename, $matches)
    ) {
        return [
            "type" => "daily",
            "year" => (int) $matches[1],
            "month" => (int) $matches[2],
            "day" => (int) $matches[3],
            "end_year" => null,
            "end_month" => null,
        ];
    }

    return false;
}

/**
 * Parse billing report CSV content
 *
 * Expected header:
 *   y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id
 *
 * @param string $csv_content Raw CSV content
 * @return array Array with 'headers', 'rows', 'errors'
 */
function parse_billing_csv($csv_content)
{
    $result = [
        "headers" => [],
        "rows" => [],
        "errors" => [],
        "row_count" => 0,
    ];

    $lines = explode("\n", $csv_content);
    if (empty($lines)) {
        $result["errors"][] = "Empty file";
        return $result;
    }

    // Parse header
    $header_line = trim(array_shift($lines));
    $result["headers"] = str_getcsv($header_line);

    // Expected headers
    $expected = [
        "y",
        "m",
        "cust_id",
        "cust_name",
        "hit_code",
        "tran_displayname",
        "actual_unit_cost",
        "count",
        "revenue",
        "EFX_code",
        "billing_id",
    ];

    // Validate headers
    $header_map = [];
    foreach ($expected as $field) {
        $idx = array_search($field, $result["headers"]);
        if ($idx === false) {
            $result["errors"][] = "Missing required column: $field";
        } else {
            $header_map[$field] = $idx;
        }
    }

    if (!empty($result["errors"])) {
        return $result;
    }

    // Parse rows
    $line_num = 1;
    foreach ($lines as $line) {
        $line_num++;
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        $fields = str_getcsv($line);

        // Map to associative array
        $row = [];
        foreach ($header_map as $name => $idx) {
            $row[$name] = isset($fields[$idx]) ? $fields[$idx] : null;
        }

        // Basic validation
        if (empty($row["cust_id"])) {
            $result["errors"][] = "Line $line_num: Missing cust_id";
            continue;
        }

        // Type conversions
        $row["y"] = (int) $row["y"];
        $row["m"] = (int) $row["m"];
        $row["cust_id"] = (int) $row["cust_id"];
        $row["actual_unit_cost"] = (float) $row["actual_unit_cost"];
        $row["count"] = (int) $row["count"];
        $row["revenue"] = (float) $row["revenue"];

        $result["rows"][] = $row;
    }

    $result["row_count"] = count($result["rows"]);
    return $result;
}

/**
 * Import billing report into database
 *
 * @param string $filename Original filename
 * @param string $csv_content Raw CSV content
 * @param string $report_type 'daily' or 'monthly' (auto-detected if null)
 * @return array Result with 'success', 'report_id', 'rows_imported', 'errors'
 */
function import_billing_report($filename, $csv_content, $report_type = null)
{
    $result = [
        "success" => false,
        "report_id" => null,
        "rows_imported" => 0,
        "rows_skipped" => 0,
        "errors" => [],
    ];

    // Parse filename for date info
    $file_info = parse_billing_filename($filename);
    if ($file_info === false) {
        // Try to detect from content or use provided type
        if ($report_type === null) {
            $result[
                "errors"
            ][] = "Cannot determine report type from filename: $filename";
            return $result;
        }
        $file_info = [
            "type" => $report_type,
            "year" => (int) date("Y"),
            "month" => (int) date("n"),
            "day" => $report_type === "daily" ? (int) date("j") : null,
        ];
    } else {
        $report_type = $file_info["type"];
    }

    // Parse CSV
    $parsed = parse_billing_csv($csv_content);
    if (!empty($parsed["errors"])) {
        $result["errors"] = array_merge($result["errors"], $parsed["errors"]);
        return $result;
    }

    if (empty($parsed["rows"])) {
        $result["errors"][] = "No valid rows found in CSV";
        return $result;
    }

    // Build report date
    $report_date = sprintf(
        "%04d-%02d-%02d",
        $file_info["year"],
        $file_info["month"],
        $file_info["day"] ? $file_info["day"] : 1
    );

    // Check for duplicate import
    $existing = sqlite_query(
        "SELECT id FROM billing_reports
         WHERE report_type = ? AND report_year = ? AND report_month = ?
         AND (report_date = ? OR ? = 'monthly')",
        [
            $report_type,
            $file_info["year"],
            $file_info["month"],
            $report_date,
            $report_type,
        ]
    );

    if (!empty($existing) && $report_type === "monthly") {
        // For monthly, we might want to replace - for now, skip
        $result[
            "errors"
        ][] = "Monthly report for {$file_info["year"]}-{$file_info["month"]} already imported (ID: {$existing[0]["id"]})";
        return $result;
    }

    // Create report record
    sqlite_execute(
        "INSERT INTO billing_reports (report_type, report_year, report_month, report_date, file_path, record_count)
         VALUES (?, ?, ?, ?, ?, ?)",
        [
            $report_type,
            $file_info["year"],
            $file_info["month"],
            $report_date,
            $filename,
            count($parsed["rows"]),
        ]
    );
    $report_id = sqlite_last_id();
    $result["report_id"] = $report_id;

    // Import rows
    foreach ($parsed["rows"] as $row) {
        sqlite_execute(
            "INSERT INTO billing_report_lines
             (report_id, year, month, customer_id, customer_name, hit_code, tran_displayname,
              actual_unit_cost, count, revenue, efx_code, billing_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $report_id,
                $row["y"],
                $row["m"],
                $row["cust_id"],
                $row["cust_name"],
                $row["hit_code"],
                $row["tran_displayname"],
                $row["actual_unit_cost"],
                $row["count"],
                $row["revenue"],
                $row["EFX_code"],
                $row["billing_id"],
            ]
        );
        $result["rows_imported"]++;
    }

    $result["success"] = true;
    return $result;
}

/**
 * Get all imported billing reports
 */
function get_billing_reports($type = null, $limit = 50)
{
    $sql = "SELECT * FROM billing_reports";
    $params = [];

    if ($type !== null) {
        $sql .= " WHERE report_type = ?";
        $params[] = $type;
    }

    $sql .=
        " ORDER BY report_year DESC, report_month DESC, report_date DESC LIMIT ?";
    $params[] = $limit;

    return sqlite_query($sql, $params);
}

/**
 * Get billing report lines for a specific report
 */
function get_billing_report_lines($report_id)
{
    return sqlite_query(
        "SELECT * FROM billing_report_lines WHERE report_id = ? ORDER BY customer_name, tran_displayname",
        [$report_id]
    );
}

/**
 * Delete a billing report and its lines
 */
function delete_billing_report($report_id)
{
    sqlite_execute("DELETE FROM billing_report_lines WHERE report_id = ?", [
        $report_id,
    ]);
    sqlite_execute("DELETE FROM billing_reports WHERE id = ?", [$report_id]);
    return true;
}

/**
 * Get billing summary by customer for a period
 */
function get_billing_summary_by_customer(
    $year,
    $month,
    $report_type = "monthly"
) {
    return sqlite_query(
        "SELECT
            brl.customer_id,
            brl.customer_name,
            SUM(brl.count) as total_count,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT brl.tran_displayname) as transaction_types
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE br.report_year = ? AND br.report_month = ? AND br.report_type = ?
         GROUP BY brl.customer_id, brl.customer_name
         ORDER BY total_revenue DESC",
        [$year, $month, $report_type]
    );
}

// ============================================================
// GENERATION HELPERS (tier_pricing.csv)
// ============================================================

/**
 * Get all transaction types (from displayname_to_type.csv data)
 */
function get_all_transaction_types()
{
    return sqlite_query(
        "SELECT * FROM transaction_types ORDER BY type, display_name"
    );
}

/**
 * Get transaction type by EFX code
 */
function get_transaction_type_by_efx($efx_code)
{
    $result = sqlite_query(
        "SELECT * FROM transaction_types WHERE efx_code = ? LIMIT 1",
        [$efx_code]
    );
    return !empty($result) ? $result[0] : null;
}

/**
 * Save/update a transaction type
 */
function save_transaction_type(
    $type,
    $display_name,
    $efx_code,
    $efx_displayname = null,
    $service_id = null
) {
    // Check if exists
    $existing = sqlite_query(
        "SELECT id FROM transaction_types WHERE efx_code = ? AND display_name = ?",
        [$efx_code, $display_name]
    );

    if (!empty($existing)) {
        sqlite_execute(
            "UPDATE transaction_types SET type = ?, efx_displayname = ?, service_id = ? WHERE id = ?",
            [$type, $efx_displayname, $service_id, $existing[0]["id"]]
        );
        return $existing[0]["id"];
    }

    sqlite_execute(
        "INSERT INTO transaction_types (type, display_name, efx_code, efx_displayname, service_id)
         VALUES (?, ?, ?, ?, ?)",
        [$type, $display_name, $efx_code, $efx_displayname, $service_id]
    );

    return sqlite_db()->lastInsertRowID();
}

/**
 * Import transaction types from CSV content
 * Expected format: type,display_name,EFX_code,EFX_displayname
 */
function import_transaction_types_csv($csv_content)
{
    $lines = explode("\n", $csv_content);
    $header = str_getcsv(trim(array_shift($lines)));

    $imported = 0;
    $errors = [];

    foreach ($lines as $line_num => $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        $fields = str_getcsv($line);
        if (count($fields) < 3) {
            $errors[] = "Line " . ($line_num + 2) . ": insufficient fields";
            continue;
        }

        $type = $fields[0];
        $display_name = $fields[1];
        $efx_code = $fields[2];
        $efx_displayname = isset($fields[3]) ? $fields[3] : null;

        save_transaction_type(
            $type,
            $display_name,
            $efx_code,
            $efx_displayname
        );
        $imported++;
    }

    return ["imported" => $imported, "errors" => $errors];
}

/**
 * Get billing flags for a service/EFX code with inheritance
 * Resolution: customer -> group -> default
 */
function get_effective_billing_flags(
    $service_id,
    $efx_code,
    $customer_id = null,
    $group_id = null
) {
    // Default flags
    $flags = [
        "by_hit" => 1,
        "zero_null" => 0,
        "bav_by_trans" => 0,
        "source" => "system_default",
    ];

    // Check system default level
    $default_flags = sqlite_query(
        "SELECT by_hit, zero_null, bav_by_trans FROM service_billing_flags
         WHERE level = 'default' AND level_id IS NULL
         AND service_id = ? AND efx_code = ?
         AND effective_date <= date('now')
         ORDER BY effective_date DESC, id DESC LIMIT 1",
        [$service_id, $efx_code]
    );

    if (!empty($default_flags)) {
        $flags = array_merge($flags, $default_flags[0]);
        $flags["source"] = "default";
    }

    // Check group level if applicable
    if ($group_id !== null) {
        $group_flags = sqlite_query(
            "SELECT by_hit, zero_null, bav_by_trans FROM service_billing_flags
             WHERE level = 'group' AND level_id = ?
             AND service_id = ? AND efx_code = ?
             AND effective_date <= date('now')
             ORDER BY effective_date DESC, id DESC LIMIT 1",
            [$group_id, $service_id, $efx_code]
        );

        if (!empty($group_flags)) {
            $flags = array_merge($flags, $group_flags[0]);
            $flags["source"] = "group";
        }
    }

    // Check customer level if applicable
    if ($customer_id !== null) {
        $customer_flags = sqlite_query(
            "SELECT by_hit, zero_null, bav_by_trans FROM service_billing_flags
             WHERE level = 'customer' AND level_id = ?
             AND service_id = ? AND efx_code = ?
             AND effective_date <= date('now')
             ORDER BY effective_date DESC, id DESC LIMIT 1",
            [$customer_id, $service_id, $efx_code]
        );

        if (!empty($customer_flags)) {
            $flags = array_merge($flags, $customer_flags[0]);
            $flags["source"] = "customer";
        }
    }

    return $flags;
}

/**
 * Save billing flags at a specific level
 */
function save_billing_flags(
    $level,
    $level_id,
    $service_id,
    $efx_code,
    $by_hit,
    $zero_null,
    $bav_by_trans,
    $effective_date = null
) {
    if ($effective_date === null) {
        $effective_date = date("Y-m-d");
    }

    sqlite_execute(
        "INSERT INTO service_billing_flags (level, level_id, service_id, efx_code, by_hit, zero_null, bav_by_trans, effective_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $level,
            $level_id,
            $service_id,
            $efx_code,
            $by_hit ? 1 : 0,
            $zero_null ? 1 : 0,
            $bav_by_trans ? 1 : 0,
            $effective_date,
        ]
    );

    return true;
}

/**
 * Calculate the adjusted price after applying escalators
 *
 * @param float  $base_price  Original tier price
 * @param int    $customer_id Customer ID
 * @param string $as_of_date  Date to calculate for (YYYY-MM-DD)
 * @return float Adjusted price
 */
function calculate_escalated_price(
    $base_price,
    $customer_id,
    $as_of_date = null
) {
    if ($as_of_date === null) {
        $as_of_date = date("Y-m-d");
    }

    $escalators = get_current_escalators($customer_id);
    if (empty($escalators)) {
        return $base_price;
    }

    // Get escalator start date from first entry
    $escalator_start = $escalators[0]["escalator_start_date"];
    if (empty($escalator_start)) {
        return $base_price;
    }

    // Calculate which year we're in
    $start_ts = strtotime($escalator_start);
    $as_of_ts = strtotime($as_of_date);

    if ($as_of_ts < $start_ts) {
        return $base_price; // Before escalators start
    }

    $years_elapsed = floor(($as_of_ts - $start_ts) / (365.25 * 24 * 60 * 60));
    $current_year = $years_elapsed + 1;

    // Find applicable escalator year (with delays)
    $adjusted_price = $base_price;

    foreach ($escalators as $esc) {
        $year_num = (int) $esc["year_number"];

        // Check for delays on this year
        $delay_months = get_total_delay_months($customer_id, $year_num);
        $delay_days = $delay_months * 30;

        // Calculate effective date for this year's escalator
        $year_start_ts = strtotime("+" . ($year_num - 1) . " years", $start_ts);
        $effective_ts = strtotime("+$delay_days days", $year_start_ts);

        // Normalize to 1st of month
        $effective_month_start = strtotime(date("Y-m-01", $effective_ts));
        if ($effective_ts > $effective_month_start) {
            $effective_ts = strtotime("+1 month", $effective_month_start);
        }

        // Apply if we're past the effective date
        if ($as_of_ts >= $effective_ts) {
            $percentage = (float) $esc["escalator_percentage"];
            $fixed = (float) $esc["fixed_adjustment"];

            // Apply percentage escalation (compounding from base)
            if ($percentage > 0) {
                $adjusted_price = $base_price * (1 + $percentage / 100);
            }

            // Apply fixed adjustment
            $adjusted_price += $fixed;
        }
    }

    return round($adjusted_price, 6);
}

/**
 * Generate tier_pricing.csv content
 *
 * Output format:
 * cust_id,discount_group,start_date,end_date,EFX_code,type,start_trans,end_trans,adj_price,base_price,by_hit,zero_null,bav_by_trans
 *
 * @param array $options Generation options
 *   - customer_ids: array of customer IDs (null = all active)
 *   - as_of_date: date for calculations
 *   - include_inactive: bool, include paused/decommissioned
 * @return array Array with 'csv_content', 'row_count', 'errors'
 */
function generate_tier_pricing_csv($options = [])
{
    $result = [
        "csv_content" => "",
        "rows" => [],
        "row_count" => 0,
        "errors" => [],
    ];

    $as_of_date = isset($options["as_of_date"])
        ? $options["as_of_date"]
        : date("Y-m-d");
    $include_inactive = isset($options["include_inactive"])
        ? $options["include_inactive"]
        : false;

    // Get customers
    $status_filter = $include_inactive ? "" : "WHERE c.status = 'active'";
    if (!empty($options["customer_ids"])) {
        $ids = implode(",", array_map("intval", $options["customer_ids"]));
        $status_filter = $include_inactive
            ? "WHERE c.id IN ($ids)"
            : "WHERE c.status = 'active' AND c.id IN ($ids)";
    }

    $customers = sqlite_query(
        "SELECT c.id, c.name, c.discount_group_id, c.contract_start_date,
                dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         $status_filter
         ORDER BY c.name"
    );

    if (empty($customers)) {
        $result["errors"][] = "No customers found";
        return $result;
    }

    // Get all services
    $services = get_all_services();
    if (empty($services)) {
        $result["errors"][] = "No services defined";
        return $result;
    }

    // Get all transaction types (needed for EFX_code -> type mapping)
    $transaction_types = get_all_transaction_types();
    $efx_to_type = [];
    foreach ($transaction_types as $tt) {
        $efx_to_type[$tt["efx_code"]] = $tt["type"];
    }

    // Build CSV header
    $header = [
        "cust_id",
        "discount_group",
        "start_date",
        "end_date",
        "EFX_code",
        "type",
        "start_trans",
        "end_trans",
        "adj_price",
        "base_price",
        "by_hit",
        "zero_null",
        "bav_by_trans",
    ];
    $csv_lines = [implode(",", $header)];

    // Calculate far future end date (100 years = indefinite)
    $far_future = date("Y-m-d", strtotime("+100 years"));

    foreach ($customers as $customer) {
        $cust_id = $customer["id"];
        $group_name = $customer["group_name"] ? $customer["group_name"] : "";
        $group_id = $customer["discount_group_id"];
        $contract_start = $customer["contract_start_date"]
            ? $customer["contract_start_date"]
            : $as_of_date;

        foreach ($services as $service) {
            $service_id = $service["id"];

            // Get effective tiers for this customer+service (with inheritance)
            $tiers = get_effective_customer_tiers($cust_id, $service_id);

            if (empty($tiers)) {
                continue; // No pricing defined for this service
            }

            // Get EFX codes for this service from transaction_types
            $service_efx_codes = sqlite_query(
                "SELECT DISTINCT efx_code FROM transaction_types WHERE service_id = ?",
                [$service_id]
            );

            // If no mapped EFX codes, use service name as EFX code placeholder
            if (empty($service_efx_codes)) {
                $service_efx_codes = [
                    ["efx_code" => strtoupper($service["name"])],
                ];
            }

            foreach ($service_efx_codes as $efx_row) {
                $efx_code = $efx_row["efx_code"];
                $type = isset($efx_to_type[$efx_code])
                    ? $efx_to_type[$efx_code]
                    : "";

                // Get billing flags with inheritance
                $flags = get_effective_billing_flags(
                    $service_id,
                    $efx_code,
                    $cust_id,
                    $group_id
                );

                foreach ($tiers as $tier) {
                    $base_price = (float) $tier["price_per_inquiry"];

                    // Calculate adjusted price with escalators
                    $adj_price = calculate_escalated_price(
                        $base_price,
                        $cust_id,
                        $as_of_date
                    );

                    $row = [
                        $cust_id,
                        csv_escape($group_name),
                        $contract_start,
                        $far_future,
                        csv_escape($efx_code),
                        csv_escape($type),
                        $tier["volume_start"],
                        $tier["volume_end"] !== null ? $tier["volume_end"] : "",
                        number_format($adj_price, 6, ".", ""),
                        number_format($base_price, 6, ".", ""),
                        $flags["by_hit"] ? 1 : 0,
                        $flags["zero_null"] ? 1 : 0,
                        $flags["bav_by_trans"] ? 1 : 0,
                    ];

                    $result["rows"][] = $row;
                    $csv_lines[] = implode(",", $row);
                }
            }
        }
    }

    $result["csv_content"] = implode("\n", $csv_lines) . "\n";
    $result["row_count"] = count($result["rows"]);

    return $result;
}

// ============================================================
// BILLING DATA FUNCTIONS
// ============================================================

/**
 * Billing reports list and upload page
 */
function get_customer_rules($customer_id)
{
    return sqlite_query(
        "SELECT * FROM business_rules WHERE customer_id = ? ORDER BY rule_name",
        [$customer_id]
    );
}

/**
 * Get current mask status for a rule
 */
function get_rule_mask_status($customer_id, $rule_name)
{
    $mask = sqlite_query(
        "SELECT is_masked FROM business_rule_masks
         WHERE customer_id = ? AND rule_name = ? AND effective_date <= date('now')
         ORDER BY effective_date DESC, id DESC LIMIT 1",
        [$customer_id, $rule_name]
    );

    return !empty($mask) ? (bool) $mask[0]["is_masked"] : false;
}

/**
 * Toggle rule mask status (append new record)
 */
function toggle_rule_mask($customer_id, $rule_name, $is_masked)
{
    sqlite_execute(
        "INSERT INTO business_rule_masks (customer_id, rule_name, is_masked, effective_date)
         VALUES (?, ?, ?, date('now'))",
        [$customer_id, $rule_name, $is_masked ? 1 : 0]
    );
    return true;
}

/**
 * List customers with business rules
 */
function get_pricing_history($customer_id = "")
{
    $results = [];

    $where = "";
    $params = [];

    if ($customer_id) {
        $where = "WHERE (pt.level = 'customer' AND pt.level_id = ?) OR
                  (pt.level = 'group' AND pt.level_id IN (SELECT discount_group_id FROM customers WHERE id = ?))";
        $params = [$customer_id, $customer_id];
    }

    $rows = sqlite_query(
        "SELECT pt.*, s.name as service_name,
                CASE pt.level
                    WHEN 'default' THEN 'System Default'
                    WHEN 'group' THEN (SELECT name FROM discount_groups WHERE id = pt.level_id)
                    WHEN 'customer' THEN (SELECT name FROM customers WHERE id = pt.level_id)
                END as entity_name
         FROM pricing_tiers pt
         LEFT JOIN services s ON pt.service_id = s.id
         $where
         ORDER BY pt.created_at DESC
         LIMIT 100",
        $params
    );

    foreach ($rows as $row) {
        $results[] = [
            "date" => $row["created_at"],
            "effective_date" => $row["effective_date"],
            "description" =>
                $row["level"] .
                " pricing for " .
                $row["service_name"] .
                ($row["entity_name"] ? " (" . $row["entity_name"] . ")" : "") .
                ": $" .
                number_format($row["price_per_inquiry"], 2) .
                " for volume " .
                $row["volume_start"] .
                "-" .
                ($row["volume_end"] ?: "unlimited"),
            "level" => $row["level"],
            "entity_name" => $row["entity_name"],
        ];
    }

    return $results;
}

/**
 * Get customer settings change history
 */
function get_settings_history($customer_id = "")
{
    $results = [];

    $where = $customer_id ? "WHERE cs.customer_id = ?" : "";
    $params = $customer_id ? [$customer_id] : [];

    $rows = sqlite_query(
        "SELECT cs.*, c.name as customer_name
         FROM customer_settings cs
         INNER JOIN customers c ON cs.customer_id = c.id
         $where
         ORDER BY cs.created_at DESC
         LIMIT 100",
        $params
    );

    foreach ($rows as $row) {
        $details = [];
        if ($row["monthly_minimum"]) {
            $details[] = "min $" . number_format($row["monthly_minimum"], 2);
        }
        if ($row["uses_annualized"]) {
            $details[] = "annualized";
        }
        if ($row["look_period_months"]) {
            $details[] = $row["look_period_months"] . " month look";
        }

        $results[] = [
            "date" => $row["created_at"],
            "effective_date" => $row["effective_date"],
            "description" =>
                "Settings for " .
                $row["customer_name"] .
                ": " .
                (!empty($details) ? implode(", ", $details) : "defaults"),
            "customer_name" => $row["customer_name"],
        ];
    }

    return $results;
}

/**
 * Get escalator change history
 */
function get_escalator_history($customer_id = "")
{
    $results = [];

    $where = $customer_id ? "WHERE ce.customer_id = ?" : "";
    $params = $customer_id ? [$customer_id] : [];

    $rows = sqlite_query(
        "SELECT ce.*, c.name as customer_name
         FROM customer_escalators ce
         INNER JOIN customers c ON ce.customer_id = c.id
         $where
         ORDER BY ce.created_at DESC
         LIMIT 100",
        $params
    );

    foreach ($rows as $row) {
        $results[] = [
            "date" => $row["created_at"],
            "effective_date" => $row["effective_date"],
            "description" =>
                "Escalator Year " .
                $row["year_number"] .
                " for " .
                $row["customer_name"] .
                ": " .
                $row["escalator_percentage"] .
                "%" .
                ($row["fixed_adjustment"]
                    ? " + $" . number_format($row["fixed_adjustment"], 2)
                    : ""),
            "customer_name" => $row["customer_name"],
        ];
    }

    // Also get delay history
    $delay_where = $customer_id ? "WHERE ed.customer_id = ?" : "";
    $delay_params = $customer_id ? [$customer_id] : [];

    $delays = sqlite_query(
        "SELECT ed.*, c.name as customer_name
         FROM escalator_delays ed
         INNER JOIN customers c ON ed.customer_id = c.id
         $delay_where
         ORDER BY ed.created_at DESC
         LIMIT 50",
        $delay_params
    );

    foreach ($delays as $row) {
        $results[] = [
            "date" => $row["created_at"],
            "effective_date" => $row["applied_date"],
            "description" =>
                "Delay applied to Year " .
                $row["year_number"] .
                " for " .
                $row["customer_name"] .
                ": +" .
                $row["delay_months"] .
                " month(s)",
            "customer_name" => $row["customer_name"],
        ];
    }

    return $results;
}

/**
 * Get business rule mask change history
 */
function get_rule_mask_history($customer_id = "")
{
    $results = [];

    $where = $customer_id ? "WHERE brm.customer_id = ?" : "";
    $params = $customer_id ? [$customer_id] : [];

    $rows = sqlite_query(
        "SELECT brm.*, c.name as customer_name
         FROM business_rule_masks brm
         INNER JOIN customers c ON brm.customer_id = c.id
         $where
         ORDER BY brm.created_at DESC
         LIMIT 100",
        $params
    );

    foreach ($rows as $row) {
        $action = $row["is_masked"] ? "Masked" : "Unmasked";
        $results[] = [
            "date" => $row["created_at"],
            "effective_date" => $row["effective_date"],
            "description" =>
                $action .
                ' rule "' .
                $row["rule_name"] .
                '" for ' .
                $row["customer_name"],
            "customer_name" => $row["customer_name"],
        ];
    }

    return $results;
}

// ------------------------------------------------------------
// BILLING CALENDAR ACTIONS
// ------------------------------------------------------------

/**
 * Billing Calendar - Year View
 * Shows 12-month calendar with event indicators and completion status
 */
