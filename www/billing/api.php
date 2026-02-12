<?php
// ============================================================
// STANDALONE AJAX API ENDPOINT
// Independent of route() — loads only helpers, db, data
// PHP 5.6 Compatible
// ============================================================

if (php_sapi_name() === "cli") {
    die("HTTP only");
}

define("API_MODE", true);
require_once __DIR__ . "/control_panel.php";
// Loaded: session, constants, helpers.php, db.php, data.php
// NOT loaded: actions.php, views.php

api_dispatch();

// ============================================================
// SHARED HELPERS
// ============================================================

/**
 * Parse tier data from POST arrays and validate ranges.
 * Returns tiers array, or sends a needs_confirmation response and exits.
 */
function parse_and_validate_tiers()
{
    $volume_starts = isset($_POST["volume_start"])
        ? $_POST["volume_start"]
        : [];
    $volume_ends = isset($_POST["volume_end"]) ? $_POST["volume_end"] : [];
    $prices = isset($_POST["price_per_inquiry"])
        ? $_POST["price_per_inquiry"]
        : [];

    $tiers = [];
    for ($i = 0; $i < count($volume_starts); $i++) {
        if ($volume_starts[$i] !== "" && $prices[$i] !== "") {
            $tiers[] = [
                "volume_start" => (int) $volume_starts[$i],
                "volume_end" =>
                    $volume_ends[$i] !== "" ? (int) $volume_ends[$i] : null,
                "price_per_inquiry" => (float) $prices[$i],
            ];
        }
    }

    $validation = validate_tier_ranges($tiers);
    if (!empty($validation["errors"])) {
        $confirm = get_param("confirm_overlap", "");
        if (empty($confirm)) {
            api_response([
                "needs_confirmation" => true,
                "validation" => $validation,
                "tiers" => $tiers,
            ]);
            // api_response calls exit(), so this is unreachable
        }
    }

    return $tiers;
}

// ============================================================
// DISPATCHER
// ============================================================

function api_dispatch()
{
    $action = isset($_GET["action"]) ? $_GET["action"] : "";
    if (empty($action)) {
        api_error("No action specified");
    }

    $api_routes = [
        // --- Read endpoints ---
        "dashboard" => "api_dashboard",
        "pricing_defaults" => "api_pricing_defaults",
        "pricing_defaults_edit" => "api_pricing_defaults_edit",
        "pricing_groups" => "api_pricing_groups",
        "pricing_group_edit" => "api_pricing_group_edit",
        "pricing_customers" => "api_pricing_customers",
        "pricing_customer_edit" => "api_pricing_customer_edit",
        "pricing_customer_settings" => "api_pricing_customer_settings",
        "escalators" => "api_escalators",
        "escalator_edit" => "api_escalator_edit",
        "business_rules" => "api_business_rules",
        "business_rules_all" => "api_business_rules_all",
        "business_rule_edit" => "api_business_rule_edit",
        "history" => "api_history",
        "calendar" => "api_calendar",
        "calendar_month" => "api_calendar_month",
        "billing_reports" => "api_billing_reports",
        "view_billing_report" => "api_view_billing_report",
        "billing_intelligence" => "api_billing_intelligence",
        "billing_month" => "api_billing_month",
        "billing_customer" => "api_billing_customer",
        "billing_customer_daily" => "api_billing_customer_daily",
        "billing_service" => "api_billing_service",
        "lms" => "api_lms",
        "lms_edit" => "api_lms_edit",
        "lms_settings" => "api_lms_settings",
        "lms_report" => "api_lms_report",
        "minimums" => "api_minimums",
        "annualized" => "api_annualized",
        "customer_pricing" => "api_customer_pricing",
        "ingestion" => "api_ingestion",
        "ingestion_view" => "api_ingestion_view",
        "line_audit" => "api_line_audit",
        "report_audit" => "api_report_audit",
        "generation" => "api_generation",
        "generation_types" => "api_generation_types",
        "billing_flags" => "api_billing_flags",
        "admin" => "api_admin",
        "admin_explore_remote" => "api_admin_explore_remote",

        // --- Write endpoints (POST) ---
        "save_default_tiers" => "api_save_default_tiers",
        "save_group_tiers" => "api_save_group_tiers",
        "clear_group_tiers" => "api_clear_group_tiers",
        "save_customer_tiers" => "api_save_customer_tiers",
        "clear_customer_tiers" => "api_clear_customer_tiers",
        "save_customer_settings" => "api_save_customer_settings",
        "save_escalators" => "api_save_escalators",
        "save_escalator_delay" => "api_save_escalator_delay",
        "toggle_business_rule" => "api_toggle_business_rule",
        "save_lms" => "api_save_lms",
        "save_lms_settings" => "api_save_lms_settings",
        "save_billing_flags" => "api_save_billing_flags",
        "save_generation_types" => "api_save_generation_types",
        "admin_sync" => "api_admin_sync",
        "admin_clear" => "api_admin_clear",
        "admin_clear_entity" => "api_admin_clear_entity",
        "admin_fix_directories" => "api_admin_fix_directories",

        // --- Job system ---
        "job_start" => "api_job_start",
        "job_status" => "api_job_status",
    ];

    if (!isset($api_routes[$action])) {
        api_error("Unknown action: " . $action, 404);
    }

    $handler = $api_routes[$action];
    if (!function_exists($handler)) {
        api_error("Handler not implemented: " . $handler, 501);
    }

    call_user_func($handler);
}

// ============================================================
// READ ENDPOINTS
// ============================================================

function api_dashboard()
{
    $services = sqlite_query("SELECT COUNT(*) as cnt FROM services");
    $groups = sqlite_query("SELECT COUNT(*) as cnt FROM discount_groups");
    $customers_active = sqlite_query(
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'",
    );
    $customers_all = sqlite_query("SELECT COUNT(*) as cnt FROM customers");
    $alerts = get_dashboard_alerts();

    api_response([
        "service_count" => $services[0]["cnt"],
        "group_count" => $groups[0]["cnt"],
        "customer_active" => $customers_active[0]["cnt"],
        "customer_total" => $customers_all[0]["cnt"],
        "reports" => list_reports(),
        "pending_configs" => list_pending_configs(),
        "alerts" => $alerts,
    ]);
}

function api_pricing_defaults()
{
    $services = get_all_services();

    foreach ($services as &$service) {
        $tiers = get_current_default_tiers($service["id"]);
        $service["tier_count"] = count($tiers);
        $service["tiers"] = $tiers;
    }

    api_response(["services" => $services]);
}

function api_pricing_defaults_edit()
{
    $service_id = get_param("service_id");
    if (empty($service_id)) {
        api_error("No service specified");
    }

    $services = sqlite_query("SELECT * FROM services WHERE id = ?", [
        $service_id,
    ]);
    if (empty($services)) {
        api_error("Service not found", 404);
    }
    $service = $services[0];

    $tiers = get_current_default_tiers($service_id);

    api_response([
        "service" => $service,
        "tiers" => $tiers,
        "validation" => validate_tier_ranges($tiers),
    ]);
}

function api_pricing_groups()
{
    $groups = sqlite_query("SELECT * FROM discount_groups ORDER BY name");

    foreach ($groups as &$group) {
        $count = sqlite_query(
            "SELECT COUNT(*) as cnt FROM customers WHERE discount_group_id = ?",
            [$group["id"]],
        );
        $group["member_count"] = $count[0]["cnt"];

        $overrides = sqlite_query(
            "SELECT COUNT(DISTINCT service_id) as cnt FROM pricing_tiers
             WHERE level = 'group' AND level_id = ? AND effective_date <= date('now')",
            [$group["id"]],
        );
        $group["override_count"] = $overrides[0]["cnt"];
    }

    api_response(["groups" => $groups]);
}

function api_pricing_group_edit()
{
    $group_id = get_param("group_id");
    if (empty($group_id)) {
        api_error("No group specified");
    }

    $groups = sqlite_query("SELECT * FROM discount_groups WHERE id = ?", [
        $group_id,
    ]);
    if (empty($groups)) {
        api_error("Group not found", 404);
    }
    $group = $groups[0];

    $service_id = get_param("service_id");

    if (empty($service_id)) {
        // Service list view
        $services = get_all_services();
        foreach ($services as &$svc) {
            $svc["tiers"] = get_effective_group_tiers($group_id, $svc["id"]);
            $svc["has_override"] =
                !empty($svc["tiers"]) && $svc["tiers"][0]["source"] === "group";
        }
        api_response([
            "group" => $group,
            "services" => $services,
        ]);
    } else {
        // Specific service edit view
        $svc_rows = sqlite_query("SELECT * FROM services WHERE id = ?", [
            $service_id,
        ]);
        if (empty($svc_rows)) {
            api_error("Service not found", 404);
        }
        $service = $svc_rows[0];
        $tiers = get_effective_group_tiers($group_id, $service_id);
        $has_override = !empty($tiers) && $tiers[0]["source"] === "group";

        api_response([
            "group" => $group,
            "service" => $service,
            "tiers" => $tiers,
            "has_override" => $has_override,
            "validation" => validate_tier_ranges($tiers),
        ]);
    }
}

function api_pricing_customers()
{
    $page = (int) get_param("page", 1);
    $status = get_param("status", "active");
    $search = get_param("search", "");

    $where = [];
    $params = [];

    if ($status !== "all") {
        $where[] = "c.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where[] = "(c.name LIKE ? OR dg.name LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }

    $where_str = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $total = sqlite_query(
        "SELECT COUNT(*) as cnt FROM customers c LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id $where_str",
        $params,
    );
    $total_count = $total[0]["cnt"];
    $pagination = paginate($total_count, $page);

    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $query_params = $params;
    $query_params[] = ITEMS_PER_PAGE;
    $query_params[] = $offset;

    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         $where_str
         ORDER BY c.name
         LIMIT ? OFFSET ?",
        $query_params,
    );

    api_response([
        "customers" => $customers,
        "pagination" => $pagination,
        "status_filter" => $status,
        "search" => $search,
    ]);
}

function api_pricing_customer_edit()
{
    $customer_id = get_param("customer_id");
    if (empty($customer_id)) {
        api_error("No customer specified");
    }

    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );
    if (empty($customers)) {
        api_error("Customer not found", 404);
    }
    $customer = $customers[0];

    $tab = get_param("tab", "services");
    $service_id = get_param("service_id");

    if ($tab === "settings") {
        $current_settings = get_current_customer_settings($customer_id);
        $all_lms = get_all_lms();

        api_response([
            "customer" => $customer,
            "settings" => $current_settings,
            "all_lms" => $all_lms,
            "tab" => "settings",
        ]);
    } elseif (!empty($service_id)) {
        // Specific service edit
        $svc_rows = sqlite_query("SELECT * FROM services WHERE id = ?", [
            $service_id,
        ]);
        if (empty($svc_rows)) {
            api_error("Service not found", 404);
        }
        $service = $svc_rows[0];
        $tiers = get_effective_customer_tiers($customer_id, $service_id);
        $has_override = !empty($tiers) && $tiers[0]["source"] === "customer";
        $source = !empty($tiers) ? $tiers[0]["source"] : "default";

        api_response([
            "customer" => $customer,
            "service" => $service,
            "tiers" => $tiers,
            "has_override" => $has_override,
            "source" => $source,
            "validation" => validate_tier_ranges($tiers),
        ]);
    } else {
        // Service list
        $services = get_all_services();
        foreach ($services as &$svc) {
            $svc["tiers"] = get_effective_customer_tiers(
                $customer_id,
                $svc["id"],
            );
            $svc["has_override"] =
                !empty($svc["tiers"]) &&
                $svc["tiers"][0]["source"] === "customer";
            $svc["source"] = !empty($svc["tiers"])
                ? $svc["tiers"][0]["source"]
                : "default";
        }
        $current_settings = get_current_customer_settings($customer_id);

        api_response([
            "customer" => $customer,
            "services" => $services,
            "settings" => $current_settings,
            "tab" => "services",
        ]);
    }
}

function api_pricing_customer_settings()
{
    $customer_id = get_param("customer_id");
    if (empty($customer_id)) {
        api_error("No customer specified");
    }

    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );
    if (empty($customers)) {
        api_error("Customer not found", 404);
    }
    $customer = $customers[0];

    $settings = get_current_customer_settings($customer_id);
    $all_lms = get_all_lms();

    api_response([
        "customer" => $customer,
        "settings" => $settings,
        "all_lms" => $all_lms,
        "tab" => "settings",
    ]);
}

function api_escalators()
{
    $page = (int) get_param("page", 1);
    $search = get_param("search", "");

    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(c.name LIKE ? OR dg.name LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }

    $where_str = !empty($where) ? "AND " . implode(" AND ", $where) : "";

    $total = sqlite_query(
        "SELECT COUNT(DISTINCT c.id) as cnt FROM customers c
         INNER JOIN customer_escalators ce ON c.id = ce.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str",
        $params,
    );
    $total_count = $total[0]["cnt"];
    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    $query_params = $params;
    $query_params[] = ITEMS_PER_PAGE;
    $query_params[] = $offset;

    $customers = sqlite_query(
        "SELECT DISTINCT c.*, dg.name as group_name
         FROM customers c
         INNER JOIN customer_escalators ce ON c.id = ce.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str
         ORDER BY c.name
         LIMIT ? OFFSET ?",
        $query_params,
    );

    foreach ($customers as &$customer) {
        $escalators = get_current_escalators($customer["id"]);
        $customer["escalator_count"] = count($escalators);
        if (!empty($escalators)) {
            $customer["start_date"] = $escalators[0]["escalator_start_date"];
        }
    }

    api_response([
        "customers" => $customers,
        "pagination" => $pagination,
        "search" => $search,
    ]);
}

function api_escalator_edit()
{
    $customer_id = get_param("customer_id");
    if (empty($customer_id)) {
        api_error("No customer specified");
    }

    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );
    if (empty($customers)) {
        api_error("Customer not found", 404);
    }
    $customer = $customers[0];

    $escalators = get_current_escalators($customer_id);
    foreach ($escalators as &$esc) {
        $esc["total_delay"] = get_total_delay_months(
            $customer_id,
            $esc["year_number"],
        );
    }

    api_response([
        "customer" => $customer,
        "escalators" => $escalators,
    ]);
}

function api_business_rules()
{
    $page = (int) get_param("page", 1);
    $search = get_param("search", "");

    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(c.name LIKE ? OR dg.name LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }

    $where_str = !empty($where) ? "AND " . implode(" AND ", $where) : "";

    $total = sqlite_query(
        "SELECT COUNT(DISTINCT c.id) as cnt FROM customers c
         INNER JOIN customer_business_rules cbr ON c.id = cbr.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str",
        $params,
    );
    $total_count = $total[0]["cnt"];
    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    $query_params = $params;
    $query_params[] = ITEMS_PER_PAGE;
    $query_params[] = $offset;

    $customers = sqlite_query(
        "SELECT DISTINCT c.*, dg.name as group_name
         FROM customers c
         INNER JOIN customer_business_rules cbr ON c.id = cbr.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str
         ORDER BY c.name
         LIMIT ? OFFSET ?",
        $query_params,
    );

    foreach ($customers as &$customer) {
        $rules = get_customer_rules($customer["id"]);
        $customer["rule_count"] = count($rules);
        $masked_count = 0;
        foreach ($rules as $rule) {
            if (get_rule_mask_status($customer["id"], $rule["name"])) {
                $masked_count++;
            }
        }
        $customer["masked_count"] = $masked_count;
    }

    api_response([
        "customers" => $customers,
        "pagination" => $pagination,
        "search" => $search,
    ]);
}

function api_business_rules_all()
{
    $page = (int) get_param("page", 1);
    $filter_masked = get_param("masked");
    $search = get_param("search", "");

    $where = [];
    $params = [];

    if ($filter_masked === "1") {
        $where[] =
            "EXISTS (SELECT 1 FROM business_rule_masks brm WHERE brm.customer_id = cbr.customer_id AND brm.rule_name = br.name AND brm.is_masked = 1)";
    } elseif ($filter_masked === "0") {
        $where[] =
            "NOT EXISTS (SELECT 1 FROM business_rule_masks brm WHERE brm.customer_id = cbr.customer_id AND brm.rule_name = br.name AND brm.is_masked = 1)";
    }

    if (!empty($search)) {
        $where[] = "(br.name LIKE ? OR c.name LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }

    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $total = sqlite_query(
        "SELECT COUNT(*) as cnt FROM business_rules br
         JOIN customer_business_rules cbr ON cbr.business_rule_id = br.id
         JOIN customers c ON c.id = cbr.customer_id
         $where_clause",
        $params,
    );
    $total_count = $total[0]["cnt"];
    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    $query_params = $params;
    $query_params[] = ITEMS_PER_PAGE;
    $query_params[] = $offset;

    $rules = sqlite_query(
        "SELECT br.*, br.name as rule_name, cbr.customer_id, c.name as customer_name, c.status as customer_status
         FROM business_rules br
         JOIN customer_business_rules cbr ON cbr.business_rule_id = br.id
         JOIN customers c ON c.id = cbr.customer_id
         $where_clause
         ORDER BY c.name, br.name
         LIMIT ? OFFSET ?",
        $query_params,
    );

    foreach ($rules as &$rule) {
        $rule["is_masked"] = get_rule_mask_status(
            $rule["customer_id"],
            $rule["rule_name"],
        );
    }

    $stats = [
        "total_rules" => sqlite_query(
            "SELECT COUNT(*) as cnt FROM business_rules",
        )[0]["cnt"],
        "masked_rules" => sqlite_query(
            "SELECT COUNT(*) as cnt FROM business_rule_masks WHERE is_masked = 1",
        )[0]["cnt"],
        "customers_with_rules" => sqlite_query(
            "SELECT COUNT(DISTINCT customer_id) as cnt FROM customer_business_rules",
        )[0]["cnt"],
    ];

    api_response([
        "rules" => $rules,
        "pagination" => $pagination,
        "filter_masked" => $filter_masked,
        "search" => $search,
        "stats" => $stats,
    ]);
}

function api_business_rule_edit()
{
    $customer_id = get_param("customer_id");
    if (empty($customer_id)) {
        api_error("No customer specified");
    }

    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );
    if (empty($customers)) {
        api_error("Customer not found", 404);
    }
    $customer = $customers[0];

    $rules = get_customer_rules($customer_id);
    foreach ($rules as &$rule) {
        $rule["rule_name"] = $rule["name"];
        $rule["is_masked"] = get_rule_mask_status($customer_id, $rule["name"]);
    }

    api_response([
        "customer" => $customer,
        "rules" => $rules,
    ]);
}

function api_history()
{
    $filter = get_param("filter", "all");
    $customer_id = get_param("customer_id", "");
    $page = (int) get_param("page", 1);

    $history = [];

    if ($filter === "all" || $filter === "pricing") {
        $pricing_history = get_pricing_history($customer_id);
        foreach ($pricing_history as $item) {
            $item["category"] = "pricing";
            $history[] = $item;
        }
    }
    if ($filter === "all" || $filter === "settings") {
        $settings_history = get_settings_history($customer_id);
        foreach ($settings_history as $item) {
            $item["category"] = "settings";
            $history[] = $item;
        }
    }
    if ($filter === "all" || $filter === "escalators") {
        $escalator_history = get_escalator_history($customer_id);
        foreach ($escalator_history as $item) {
            $item["category"] = "escalators";
            $history[] = $item;
        }
    }
    if ($filter === "all" || $filter === "rules") {
        $rule_history = get_rule_mask_history($customer_id);
        foreach ($rule_history as $item) {
            $item["category"] = "rules";
            $history[] = $item;
        }
    }

    usort($history, function ($a, $b) {
        return strcmp($b["date"], $a["date"]);
    });

    $total = count($history);
    $pagination = paginate($total, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $history = array_slice($history, $offset, ITEMS_PER_PAGE);

    $customers = sqlite_query("SELECT id, name FROM customers ORDER BY name");

    api_response([
        "history" => $history,
        "pagination" => $pagination,
        "filter" => $filter,
        "customer_id" => $customer_id,
        "customers" => $customers,
    ]);
}

function api_calendar()
{
    $year = (int) get_param("year", date("Y"));

    $months = get_calendar_year_summary($year);
    $next_incomplete = get_next_incomplete_month();

    $total_escalators = 0;
    $total_resets = 0;
    $completed_months = 0;

    foreach ($months as $m) {
        if ($m["is_complete"]) {
            $completed_months++;
        }
        $events = get_month_events($year, $m["month"]);
        $total_escalators += count($events["escalators"]);
        $total_resets += count($events["resets"]);
    }

    api_response([
        "year" => $year,
        "months" => $months,
        "next_incomplete" => $next_incomplete,
        "total_escalators" => $total_escalators,
        "total_resets" => $total_resets,
        "completed_months" => $completed_months,
    ]);
}

function api_calendar_month()
{
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));

    $events = get_month_events($year, $month);
    $is_complete = is_month_complete($year, $month);

    $last_month_start = date("Y-m-01", strtotime("$year-$month-01 -1 month"));
    $new_customers = get_new_customers_since($last_month_start);
    $config_changes = get_config_changes_since($last_month_start);
    $mtd = get_mtd_summary($year, $month);

    // Build checklist sections
    $checklist = [];

    $whats_new = [];
    foreach ($new_customers as $c) {
        $whats_new[] = [
            "type" => "new_customer",
            "message" => "NEW CUSTOMER: " . $c["name"],
            "customer_id" => $c["id"],
            "severity" => "info",
        ];
    }
    $checklist["whats_new"] = $whats_new;

    $whats_changing = [];
    foreach ($events["escalators"] as $e) {
        $desc =
            "ESCALATOR: " . $e["customer_name"] . " Year " . $e["year_number"];
        $desc .= " (" . format_percentage($e["percentage"]) . ")";
        if ($e["has_delay"]) {
            $desc .= " [delayed " . $e["delay_months"] . " mo]";
        }
        $whats_changing[] = [
            "type" => "escalator",
            "message" => $desc,
            "customer_id" => $e["customer_id"],
            "effective_date" => $e["effective_date"],
            "severity" => "warning",
        ];
    }
    foreach ($events["resets"] as $r) {
        $whats_changing[] = [
            "type" => "reset",
            "message" =>
                "TIER RESET: " . $r["customer_name"] . " annualized reset",
            "customer_id" => $r["customer_id"],
            "effective_date" => $r["reset_date"],
            "severity" => "info",
        ];
    }
    $checklist["whats_changing"] = $whats_changing;

    $whats_excluded = [];
    if (isset($events["paused_customers"])) {
        foreach ($events["paused_customers"] as $p) {
            $whats_excluded[] = [
                "type" => "paused",
                "message" => "PAUSED: " . $p["name"] . " - will NOT be billed",
                "customer_id" => $p["id"],
                "severity" => "danger",
            ];
        }
    }
    $checklist["whats_excluded"] = $whats_excluded;

    $whats_different = [];
    foreach ($config_changes as $c) {
        $whats_different[] = [
            "type" => $c["type"],
            "message" => "CONFIG: " . $c["description"],
            "customer_id" => $c["customer_id"],
            "date" => $c["date"],
            "severity" => "info",
        ];
    }
    $checklist["whats_different"] = $whats_different;

    $warnings = [];
    if (isset($events["warnings"])) {
        foreach ($events["warnings"] as $w) {
            $warnings[] = [
                "type" => $w["type"],
                "message" => "WARNING: " . $w["message"],
                "customer_id" => isset($w["customer_id"])
                    ? $w["customer_id"]
                    : null,
                "severity" => "danger",
            ];
        }
    }
    $checklist["warnings"] = $warnings;

    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year--;
    }
    $next_month_num = $month + 1;
    $next_year = $year;
    if ($next_month_num > 12) {
        $next_month_num = 1;
        $next_year++;
    }

    api_response([
        "year" => $year,
        "month" => $month,
        "month_name" => date("F", mktime(0, 0, 0, $month, 1)),
        "is_complete" => $is_complete,
        "is_current" => $year == date("Y") && $month == date("n"),
        "events" => $events,
        "checklist" => $checklist,
        "mtd" => $mtd,
        "prev" => ["year" => $prev_year, "month" => $prev_month],
        "next" => ["year" => $next_year, "month" => $next_month_num],
        "total_items" =>
            count($whats_new) +
            count($whats_changing) +
            count($whats_excluded) +
            count($whats_different) +
            count($warnings),
    ]);
}

function api_billing_reports()
{
    api_response([
        "ingestion_reports" => get_ingestion_reports(),
        "generated_reports" => get_generated_reports_grouped(),
    ]);
}

function api_view_billing_report()
{
    $type = get_param("type");
    $id = get_param("id");
    $file = get_param("file");

    $filepath = "";
    $filename = "";
    $report_info = null;

    if ($type === "generated" && $id) {
        $report = get_generated_report($id);
        if (!$report) {
            api_error("Report not found", 404);
        }
        $filepath = $report["file_path"];
        $filename = $report["file_name"];
        $report_info = $report;
    } elseif ($type === "ingestion" && $file) {
        $filepath = get_archive_path() . "/" . basename($file);
        $filename = basename($file);
    } else {
        api_error("Invalid report type or missing parameters");
    }

    if (!file_exists($filepath)) {
        api_error("Report file not found", 404);
    }

    $rows = [];
    $headers = [];
    $count = 0;

    if (($handle = fopen($filepath, "r")) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false && $count < 100) {
            $rows[] = array_combine($headers, $row);
            $count++;
        }
        while (fgetcsv($handle) !== false) {
            $count++;
        }
        fclose($handle);
    }

    api_response([
        "type" => $type,
        "filename" => $filename,
        "headers" => $headers,
        "rows" => $rows,
        "count" => $count,
        "report_info" => $report_info,
    ]);
}

function api_billing_intelligence()
{
    require_once __DIR__ . "/calculator.php";

    $date_range = sqlite_query(
        "SELECT MIN(report_date) as earliest, MAX(report_date) as latest FROM billing_reports",
    );
    $earliest = isset($date_range[0]["earliest"])
        ? $date_range[0]["earliest"]
        : date("Y-m-d");
    $latest = isset($date_range[0]["latest"])
        ? $date_range[0]["latest"]
        : date("Y-m-d");

    $current_year = (int) date("Y");
    $current_month = (int) date("n");

    $overall_stats = sqlite_query(
        "SELECT
            COUNT(DISTINCT br.id) as total_reports,
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT brl.customer_id) as unique_customers,
            COUNT(DISTINCT brl.efx_code) as unique_services
         FROM billing_reports br
         LEFT JOIN billing_report_lines brl ON br.id = brl.report_id",
    );
    $stats = isset($overall_stats[0]) ? $overall_stats[0] : [];

    $avg_price =
        isset($stats["total_transactions"]) && $stats["total_transactions"] > 0
            ? $stats["total_revenue"] / $stats["total_transactions"]
            : 0;

    $lms_performance = get_lms_performance_metrics(
        $current_year,
        $current_month,
    );
    $tier_proximity = get_tier_proximity_analysis(
        $current_year,
        $current_month,
    );

    $monthly_data = sqlite_query(
        "SELECT
            brl.year,
            brl.month,
            COUNT(DISTINCT br.id) as report_count,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue,
            COUNT(DISTINCT brl.customer_id) as customers
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         GROUP BY brl.year, brl.month
         ORDER BY brl.year DESC, brl.month DESC
         LIMIT 6",
    );

    $variance_stats = get_billing_variance_stats();

    api_response([
        "date_range" => ["earliest" => $earliest, "latest" => $latest],
        "current_period" => [
            "year" => $current_year,
            "month" => $current_month,
        ],
        "stats" => [
            "total_reports" => isset($stats["total_reports"])
                ? $stats["total_reports"]
                : 0,
            "total_transactions" => isset($stats["total_transactions"])
                ? $stats["total_transactions"]
                : 0,
            "total_revenue" => isset($stats["total_revenue"])
                ? $stats["total_revenue"]
                : 0,
            "unique_customers" => isset($stats["unique_customers"])
                ? $stats["unique_customers"]
                : 0,
            "unique_services" => isset($stats["unique_services"])
                ? $stats["unique_services"]
                : 0,
            "avg_price" => $avg_price,
        ],
        "lms_performance" => $lms_performance,
        "tier_proximity" => $tier_proximity,
        "monthly_data" => $monthly_data,
        "variance_stats" => $variance_stats,
    ]);
}

function api_billing_month()
{
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));

    $month_stats = sqlite_query(
        "SELECT
            COUNT(DISTINCT br.id) as report_count,
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT brl.customer_id) as unique_customers
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.year = ? AND brl.month = ?",
        [$year, $month],
    );

    $daily_data = sqlite_query(
        "SELECT
            br.report_date,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue,
            COUNT(DISTINCT brl.customer_id) as customers
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.year = ? AND brl.month = ? AND br.report_type = 'daily'
         GROUP BY br.report_date
         ORDER BY br.report_date",
        [$year, $month],
    );

    $customer_breakdown = sqlite_query(
        "SELECT
            brl.customer_id,
            brl.customer_name,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.year = ? AND brl.month = ?
         GROUP BY brl.customer_id, brl.customer_name
         ORDER BY revenue DESC",
        [$year, $month],
    );

    $service_breakdown = sqlite_query(
        "SELECT
            brl.efx_code,
            tt.efx_displayname as service_name,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         LEFT JOIN transaction_types tt ON brl.efx_code = tt.efx_code
         WHERE brl.year = ? AND brl.month = ?
         GROUP BY brl.efx_code
         ORDER BY revenue DESC",
        [$year, $month],
    );

    $reports = sqlite_query(
        "SELECT br.*
         FROM billing_reports br
         WHERE br.report_year = ? AND br.report_month = ?
         ORDER BY br.report_date DESC",
        [$year, $month],
    );

    api_response([
        "year" => $year,
        "month" => $month,
        "month_name" => date("F", mktime(0, 0, 0, $month, 1)),
        "stats" => isset($month_stats[0]) ? $month_stats[0] : [],
        "daily_data" => $daily_data,
        "customer_breakdown" => $customer_breakdown,
        "service_breakdown" => $service_breakdown,
        "reports" => $reports,
    ]);
}

function api_billing_customer()
{
    $customer_id = get_param("id");
    if (empty($customer_id)) {
        api_error("No customer ID specified");
    }

    $customer_info = sqlite_query(
        "SELECT DISTINCT customer_id, customer_name
         FROM billing_report_lines
         WHERE customer_id = ?
         LIMIT 1",
        [$customer_id],
    );
    if (empty($customer_info)) {
        api_error("Customer not found in billing data", 404);
    }
    $customer = $customer_info[0];

    $stats = sqlite_query(
        "SELECT
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT br.id) as report_count,
            MIN(br.report_date) as first_seen,
            MAX(br.report_date) as last_seen
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ?",
        [$customer_id],
    );

    $monthly_trend = sqlite_query(
        "SELECT
            brl.year,
            brl.month,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue
         FROM billing_report_lines brl
         WHERE brl.customer_id = ?
         GROUP BY brl.year, brl.month
         ORDER BY brl.year DESC, brl.month DESC",
        [$customer_id],
    );

    $service_breakdown = sqlite_query(
        "SELECT
            brl.efx_code,
            tt.efx_displayname as service_name,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue,
            AVG(brl.actual_unit_cost) as avg_unit_cost
         FROM billing_report_lines brl
         LEFT JOIN transaction_types tt ON brl.efx_code = tt.efx_code
         WHERE brl.customer_id = ?
         GROUP BY brl.efx_code
         ORDER BY revenue DESC",
        [$customer_id],
    );

    $recent_lines = sqlite_query(
        "SELECT brl.*, br.report_date, br.report_type
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ?
         ORDER BY br.report_date DESC, brl.id DESC
         LIMIT 50",
        [$customer_id],
    );

    api_response([
        "customer" => $customer,
        "stats" => isset($stats[0]) ? $stats[0] : [],
        "monthly_trend" => $monthly_trend,
        "service_breakdown" => $service_breakdown,
        "recent_lines" => $recent_lines,
    ]);
}

function api_billing_customer_daily()
{
    $customer_id = get_param("id");
    if (empty($customer_id)) {
        api_error("No customer ID specified");
    }

    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));
    $efx_code = get_param("efx_code", "");

    $customer_info = sqlite_query(
        "SELECT DISTINCT customer_id, customer_name
         FROM billing_report_lines
         WHERE customer_id = ?
         LIMIT 1",
        [$customer_id],
    );
    if (empty($customer_info)) {
        api_error("Customer not found in billing data", 404);
    }
    $customer = $customer_info[0];

    $available_services = sqlite_query(
        "SELECT DISTINCT brl.efx_code, tt.efx_displayname as service_name
         FROM billing_report_lines brl
         LEFT JOIN transaction_types tt ON brl.efx_code = tt.efx_code
         WHERE brl.customer_id = ? AND brl.year = ? AND brl.month = ?
         ORDER BY brl.efx_code",
        [$customer_id, $year, $month],
    );

    $efx_filter = "";
    $params = [$customer_id, $year, $month];
    if (!empty($efx_code)) {
        $efx_filter = " AND brl.efx_code = ?";
        $params[] = $efx_code;
    }

    $daily_cumulative = sqlite_query(
        "SELECT
            br.report_date,
            CAST(strftime('%d', br.report_date) AS INTEGER) as day_num,
            SUM(brl.count) as cumulative_count,
            SUM(brl.revenue) as cumulative_revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ?
           AND brl.year = ? AND brl.month = ?
           AND br.report_type = 'daily'
           $efx_filter
         GROUP BY br.report_date
         ORDER BY br.report_date",
        $params,
    );

    $chart_data = [];
    $prev_cumulative = 0;
    $prev_revenue = 0;

    foreach ($daily_cumulative as $day) {
        $delta_count = $day["cumulative_count"] - $prev_cumulative;
        $delta_revenue = $day["cumulative_revenue"] - $prev_revenue;

        $chart_data[] = [
            "date" => $day["report_date"],
            "day" => $day["day_num"],
            "cumulative" => (int) $day["cumulative_count"],
            "delta" => $delta_count,
            "cumulative_revenue" => (float) $day["cumulative_revenue"],
            "delta_revenue" => $delta_revenue,
        ];

        $prev_cumulative = $day["cumulative_count"];
        $prev_revenue = $day["cumulative_revenue"];
    }

    $month_stats = sqlite_query(
        "SELECT
            SUM(brl.count) as total_count,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT br.id) as report_count
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ?
           AND brl.year = ? AND brl.month = ?
           $efx_filter",
        $params,
    );

    $total_days = count($chart_data);
    $avg_daily =
        $total_days > 0
            ? array_sum(array_column($chart_data, "delta")) / $total_days
            : 0;
    $max_daily = $total_days > 0 ? max(array_column($chart_data, "delta")) : 0;
    $min_daily = $total_days > 0 ? min(array_column($chart_data, "delta")) : 0;

    $available_months = sqlite_query(
        "SELECT DISTINCT brl.year, brl.month
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ? AND br.report_type = 'daily'
         ORDER BY brl.year DESC, brl.month DESC",
        [$customer_id],
    );

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $is_current_month = $year == date("Y") && $month == date("n");
    $projected_eom = 0;
    if ($is_current_month && $total_days > 0) {
        $current_day = (int) date("j");
        $days_remaining = $days_in_month - $current_day;
        $last = end($chart_data);
        $projected_eom = $last["cumulative"] + $avg_daily * $days_remaining;
    }

    api_response([
        "customer" => $customer,
        "year" => $year,
        "month" => $month,
        "month_name" => date("F", mktime(0, 0, 0, $month, 1, $year)),
        "efx_code" => $efx_code,
        "available_services" => $available_services,
        "chart_data" => $chart_data,
        "stats" => [
            "total_count" => isset($month_stats[0]["total_count"])
                ? $month_stats[0]["total_count"]
                : 0,
            "total_revenue" => isset($month_stats[0]["total_revenue"])
                ? $month_stats[0]["total_revenue"]
                : 0,
            "report_count" => isset($month_stats[0]["report_count"])
                ? $month_stats[0]["report_count"]
                : 0,
            "avg_daily" => $avg_daily,
            "max_daily" => $max_daily,
            "min_daily" => $min_daily,
            "total_days" => $total_days,
        ],
        "available_months" => $available_months,
        "days_in_month" => $days_in_month,
        "is_current_month" => $is_current_month,
        "projected_eom" => $projected_eom,
    ]);
}

function api_billing_service()
{
    $efx_code = get_param("code");
    if (empty($efx_code)) {
        api_error("No service code specified");
    }

    $service_info = sqlite_query(
        "SELECT tt.*, s.name as mapped_service_name
         FROM transaction_types tt
         LEFT JOIN services s ON tt.service_id = s.id
         WHERE tt.efx_code = ?",
        [$efx_code],
    );
    $service = isset($service_info[0])
        ? $service_info[0]
        : ["efx_code" => $efx_code, "efx_displayname" => $efx_code];

    $stats = sqlite_query(
        "SELECT
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            AVG(brl.actual_unit_cost) as avg_unit_cost,
            COUNT(DISTINCT brl.customer_id) as unique_customers,
            COUNT(DISTINCT br.id) as report_count
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.efx_code = ?",
        [$efx_code],
    );

    $monthly_trend = sqlite_query(
        "SELECT
            brl.year,
            brl.month,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue,
            AVG(brl.actual_unit_cost) as avg_unit_cost
         FROM billing_report_lines brl
         WHERE brl.efx_code = ?
         GROUP BY brl.year, brl.month
         ORDER BY brl.year DESC, brl.month DESC",
        [$efx_code],
    );

    $customer_breakdown = sqlite_query(
        "SELECT
            brl.customer_id,
            brl.customer_name,
            SUM(brl.count) as transactions,
            SUM(brl.revenue) as revenue,
            AVG(brl.actual_unit_cost) as avg_unit_cost
         FROM billing_report_lines brl
         WHERE brl.efx_code = ?
         GROUP BY brl.customer_id, brl.customer_name
         ORDER BY revenue DESC",
        [$efx_code],
    );

    api_response([
        "service" => $service,
        "stats" => isset($stats[0]) ? $stats[0] : [],
        "monthly_trend" => $monthly_trend,
        "customer_breakdown" => $customer_breakdown,
    ]);
}

function api_lms()
{
    $page = (int) get_param("page", 1);
    $search = get_param("search", "");

    $all_lms = get_all_lms();
    $default_rate = get_default_commission_rate();

    if (!empty($search)) {
        $filtered = [];
        foreach ($all_lms as $lms) {
            if (stripos($lms["name"], $search) !== false) {
                $filtered[] = $lms;
            }
        }
        $all_lms = $filtered;
    }

    foreach ($all_lms as &$lms) {
        $customers = get_customers_by_lms($lms["id"]);
        $lms["customer_count"] = count($customers);
        $lms["effective_rate"] =
            $lms["commission_rate"] !== null
                ? (float) $lms["commission_rate"]
                : $default_rate;
        $lms["is_inherited"] = $lms["commission_rate"] === null;
    }

    $total_count = count($all_lms);
    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $all_lms = array_slice($all_lms, $offset, ITEMS_PER_PAGE);

    $unassigned = get_customers_without_lms();

    api_response([
        "lms_list" => $all_lms,
        "unassigned_customers" => $unassigned,
        "default_rate" => $default_rate,
        "pagination" => $pagination,
        "search" => $search,
    ]);
}

function api_lms_edit()
{
    $lms_id = get_param("lms_id");
    if (empty($lms_id)) {
        api_error("No LMS specified");
    }

    $lms = get_lms($lms_id);
    if (!$lms) {
        api_error("LMS not found", 404);
    }

    $default_rate = get_default_commission_rate();
    $customers = get_customers_by_lms($lms_id);

    api_response([
        "lms" => $lms,
        "customers" => $customers,
        "default_rate" => $default_rate,
        "effective_rate" =>
            $lms["commission_rate"] !== null
                ? (float) $lms["commission_rate"]
                : $default_rate,
    ]);
}

function api_lms_settings()
{
    $default_rate = get_default_commission_rate();
    $services = get_all_services();

    foreach ($services as &$service) {
        $service["cogs_rate"] = get_service_cogs($service["id"]);
    }

    api_response([
        "default_rate" => $default_rate,
        "services" => $services,
    ]);
}

function api_lms_report()
{
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));

    $all_lms = get_all_lms();
    $default_rate = get_default_commission_rate();
    $services = get_all_services();

    $lms_data = [];
    $grand_totals = [
        "revenue" => 0,
        "cogs" => 0,
        "profit" => 0,
        "commission" => 0,
        "customer_count" => 0,
    ];

    foreach ($all_lms as $lms) {
        $customers = get_customers_by_lms($lms["id"]);
        $effective_rate =
            $lms["commission_rate"] !== null
                ? (float) $lms["commission_rate"]
                : $default_rate;

        $lms_totals = [
            "id" => $lms["id"],
            "name" => $lms["name"],
            "commission_rate" => $effective_rate,
            "is_inherited" => $lms["commission_rate"] === null,
            "customers" => [],
            "revenue" => 0,
            "cogs" => 0,
            "profit" => 0,
            "commission" => 0,
        ];

        foreach ($customers as $customer) {
            $billing = sqlite_query(
                "SELECT SUM(revenue) as total_revenue, SUM(count) as total_count
                 FROM billing_report_lines
                 WHERE customer_id = ? AND year = ? AND month = ?",
                [$customer["id"], $year, $month],
            );

            $customer_revenue =
                !empty($billing) && $billing[0]["total_revenue"]
                    ? (float) $billing[0]["total_revenue"]
                    : 0;
            $customer_count =
                !empty($billing) && $billing[0]["total_count"]
                    ? (int) $billing[0]["total_count"]
                    : 0;

            $avg_cogs = 0;
            $cogs_count = 0;
            foreach ($services as $svc) {
                $cogs = get_service_cogs($svc["id"]);
                if ($cogs > 0) {
                    $avg_cogs += $cogs;
                    $cogs_count++;
                }
            }
            $avg_cogs = $cogs_count > 0 ? $avg_cogs / $cogs_count : 0;
            $customer_cogs = $avg_cogs * $customer_count;
            $customer_profit = $customer_revenue - $customer_cogs;
            $customer_commission = $customer_profit * ($effective_rate / 100);

            $lms_totals["customers"][] = [
                "id" => $customer["id"],
                "name" => $customer["name"],
                "status" => $customer["status"],
                "revenue" => $customer_revenue,
                "cogs" => $customer_cogs,
                "profit" => $customer_profit,
                "commission" => $customer_commission,
                "count" => $customer_count,
            ];

            $lms_totals["revenue"] += $customer_revenue;
            $lms_totals["cogs"] += $customer_cogs;
            $lms_totals["profit"] += $customer_profit;
            $lms_totals["commission"] += $customer_commission;
        }

        $lms_data[] = $lms_totals;

        $grand_totals["revenue"] += $lms_totals["revenue"];
        $grand_totals["cogs"] += $lms_totals["cogs"];
        $grand_totals["profit"] += $lms_totals["profit"];
        $grand_totals["commission"] += $lms_totals["commission"];
        $grand_totals["customer_count"] += count($customers);
    }

    api_response([
        "lms_data" => $lms_data,
        "grand_totals" => $grand_totals,
        "year" => $year,
        "month" => $month,
        "default_rate" => $default_rate,
    ]);
}

function api_minimums()
{
    $page = (int) get_param("page", 1);

    $customers_with_minimums = sqlite_query(
        "SELECT c.id, c.name, c.status, dg.name as group_name,
                cs.monthly_minimum, cs.effective_date
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         LEFT JOIN customer_settings cs ON c.id = cs.customer_id
         WHERE cs.monthly_minimum IS NOT NULL AND cs.monthly_minimum > 0
         ORDER BY c.name",
    );

    $stats = sqlite_query(
        "SELECT COUNT(*) as count, SUM(monthly_minimum) as total_minimums, AVG(monthly_minimum) as avg_minimum
         FROM customer_settings
         WHERE monthly_minimum IS NOT NULL AND monthly_minimum > 0",
    );

    $pagination = paginate(count($customers_with_minimums), $page);

    api_response([
        "customers" => $customers_with_minimums,
        "stats" => !empty($stats)
            ? $stats[0]
            : ["count" => 0, "total_minimums" => 0, "avg_minimum" => 0],
        "pagination" => $pagination,
    ]);
}

function api_annualized()
{
    $page = (int) get_param("page", 1);

    $customers_annualized = sqlite_query(
        "SELECT c.id, c.name, c.status, dg.name as group_name,
                cs.uses_annualized, cs.annualized_start_date, cs.look_period_months, cs.effective_date
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         LEFT JOIN customer_settings cs ON c.id = cs.customer_id
         WHERE cs.uses_annualized = 1
         ORDER BY c.name",
    );

    $today = date("Y-m-d");
    foreach ($customers_annualized as &$customer) {
        if (!empty($customer["annualized_start_date"])) {
            $start_md = substr($customer["annualized_start_date"], 5);
            $this_year_reset = date("Y") . "-" . $start_md;
            $next_year_reset = date("Y") + 1 . "-" . $start_md;
            $customer["next_reset"] =
                $this_year_reset > $today ? $this_year_reset : $next_year_reset;
        } else {
            $customer["next_reset"] = null;
        }
    }

    $stats = sqlite_query(
        "SELECT COUNT(*) as count FROM customer_settings WHERE uses_annualized = 1",
    );
    $upcoming_resets = get_upcoming_annualized_resets(30);
    $pagination = paginate(count($customers_annualized), $page);

    api_response([
        "customers" => $customers_annualized,
        "stats" => !empty($stats) ? $stats[0] : ["count" => 0],
        "upcoming_resets" => $upcoming_resets,
        "pagination" => $pagination,
    ]);
}

function api_customer_pricing()
{
    $customer_id = get_param("id");
    if (empty($customer_id)) {
        api_error("No customer ID specified");
    }

    $customer = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );
    if (empty($customer)) {
        api_error("Customer not found", 404);
    }
    $customer = $customer[0];

    $services = get_all_services();
    $pricing_data = [];

    foreach ($services as $service) {
        $service_id = $service["id"];
        $default_tiers = get_current_default_tiers($service_id);
        $group_tiers = [];
        $customer_tiers = get_current_customer_tiers($customer_id, $service_id);

        if ($customer["discount_group_id"]) {
            $group_tiers = get_current_group_tiers(
                $customer["discount_group_id"],
                $service_id,
            );
        }

        $effective_tiers = [];
        foreach ($default_tiers as $tier) {
            $key =
                $tier["volume_start"] .
                "-" .
                ($tier["volume_end"] ? $tier["volume_end"] : "unlimited");
            $effective_tiers[$key] = [
                "volume_start" => $tier["volume_start"],
                "volume_end" => $tier["volume_end"],
                "price" => $tier["price_per_inquiry"],
                "source" => "default",
                "default_price" => $tier["price_per_inquiry"],
                "group_price" => null,
                "customer_price" => null,
            ];
        }
        foreach ($group_tiers as $tier) {
            $key =
                $tier["volume_start"] .
                "-" .
                ($tier["volume_end"] ? $tier["volume_end"] : "unlimited");
            if (isset($effective_tiers[$key])) {
                $effective_tiers[$key]["price"] = $tier["price_per_inquiry"];
                $effective_tiers[$key]["source"] = "group";
                $effective_tiers[$key]["group_price"] =
                    $tier["price_per_inquiry"];
            } else {
                $effective_tiers[$key] = [
                    "volume_start" => $tier["volume_start"],
                    "volume_end" => $tier["volume_end"],
                    "price" => $tier["price_per_inquiry"],
                    "source" => "group",
                    "default_price" => null,
                    "group_price" => $tier["price_per_inquiry"],
                    "customer_price" => null,
                ];
            }
        }
        foreach ($customer_tiers as $tier) {
            $key =
                $tier["volume_start"] .
                "-" .
                ($tier["volume_end"] ? $tier["volume_end"] : "unlimited");
            if (isset($effective_tiers[$key])) {
                $effective_tiers[$key]["price"] = $tier["price_per_inquiry"];
                $effective_tiers[$key]["source"] = "customer";
                $effective_tiers[$key]["customer_price"] =
                    $tier["price_per_inquiry"];
            } else {
                $effective_tiers[$key] = [
                    "volume_start" => $tier["volume_start"],
                    "volume_end" => $tier["volume_end"],
                    "price" => $tier["price_per_inquiry"],
                    "source" => "customer",
                    "default_price" => null,
                    "group_price" => null,
                    "customer_price" => $tier["price_per_inquiry"],
                ];
            }
        }

        usort($effective_tiers, function ($a, $b) {
            return $a["volume_start"] - $b["volume_start"];
        });

        $pricing_data[] = [
            "service" => $service,
            "tiers" => array_values($effective_tiers),
            "has_customer_override" => !empty($customer_tiers),
            "has_group_override" => !empty($group_tiers),
            "tier_count" => count($effective_tiers),
        ];
    }

    $settings = get_current_customer_settings($customer_id);
    $escalators = get_current_escalators($customer_id);
    foreach ($escalators as &$esc) {
        $esc["total_delay"] = get_total_delay_months(
            $customer_id,
            $esc["year_number"],
        );
    }

    $summary = [
        "total_services" => count($services),
        "customer_overrides" => 0,
        "group_overrides" => 0,
        "using_defaults" => 0,
    ];
    foreach ($pricing_data as $pd) {
        if ($pd["has_customer_override"]) {
            $summary["customer_overrides"]++;
        } elseif ($pd["has_group_override"]) {
            $summary["group_overrides"]++;
        } else {
            $summary["using_defaults"]++;
        }
    }

    api_response([
        "customer" => $customer,
        "pricing_data" => $pricing_data,
        "settings" => $settings,
        "escalators" => $escalators,
        "summary" => $summary,
    ]);
}

function api_ingestion()
{
    $tab = get_param("tab", "reports");

    $reports = get_billing_reports();

    $imported_files = [];
    foreach ($reports as $r) {
        if (!empty($r["file_path"])) {
            $imported_files[] = basename($r["file_path"]);
        }
    }

    $drive_files = [];
    $archive_path = get_archive_path();
    if (is_dir($archive_path)) {
        $files = glob($archive_path . "/DataX_*.csv");
        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file);
                $drive_files[] = [
                    "filename" => $filename,
                    "path" => $file,
                    "size" => filesize($file),
                    "modified" => filemtime($file),
                    "imported" => in_array($filename, $imported_files),
                ];
            }
            usort($drive_files, function ($a, $b) {
                return strcmp($b["filename"], $a["filename"]);
            });
        }
    }

    $stats = sqlite_query(
        "SELECT
            COUNT(*) as total_reports,
            SUM(record_count) as total_rows,
            MIN(report_date) as earliest,
            MAX(report_date) as latest
         FROM billing_reports",
    );

    api_response([
        "reports" => $reports,
        "drive_files" => $drive_files,
        "stats" => !empty($stats) ? $stats[0] : [],
        "tab" => $tab,
    ]);
}

function api_ingestion_view()
{
    $report_id = (int) get_param("id");
    if (empty($report_id)) {
        api_error("No report ID specified");
    }

    $reports = sqlite_query("SELECT * FROM billing_reports WHERE id = ?", [
        $report_id,
    ]);
    if (empty($reports)) {
        api_error("Report not found", 404);
    }
    $report = $reports[0];
    $lines = get_billing_report_lines($report_id);

    $customer_summary = sqlite_query(
        "SELECT
            customer_id,
            customer_name,
            SUM(count) as total_count,
            SUM(revenue) as total_revenue,
            COUNT(*) as line_count
         FROM billing_report_lines
         WHERE report_id = ?
         GROUP BY customer_id, customer_name
         ORDER BY total_revenue DESC",
        [$report_id],
    );

    api_response([
        "report" => $report,
        "lines" => $lines,
        "customer_summary" => $customer_summary,
    ]);
}

function api_line_audit()
{
    require_once __DIR__ . "/calculator.php";

    $line_id = (int) get_param("id");
    if (empty($line_id)) {
        api_error("No line ID specified");
    }

    $audit = audit_billing_line($line_id);
    $latex = format_audit_as_latex($audit);

    api_response([
        "audit" => $audit,
        "latex" => $latex,
    ]);
}

function api_report_audit()
{
    require_once __DIR__ . "/calculator.php";

    $report_id = (int) get_param("id");
    if (empty($report_id)) {
        api_error("No report ID specified");
    }

    $audit = audit_billing_report($report_id);

    api_response(["audit" => $audit]);
}

function api_generation()
{
    $tab = get_param("tab", "generate");

    $active_customers = sqlite_query(
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'",
    );
    $services_count = sqlite_query("SELECT COUNT(*) as cnt FROM services");
    $transaction_types_count = sqlite_query(
        "SELECT COUNT(*) as cnt FROM transaction_types",
    );

    $pending_files = [];
    $pending_path = get_shared_path() . "/pending";
    if (is_dir($pending_path)) {
        $files = glob($pending_path . "/tier_pricing_*.csv");
        if ($files) {
            foreach ($files as $file) {
                $pending_files[] = [
                    "filename" => basename($file),
                    "size" => filesize($file),
                    "modified" => filemtime($file),
                ];
            }
            usort($pending_files, function ($a, $b) {
                return $b["modified"] - $a["modified"];
            });
            $pending_files = array_slice($pending_files, 0, 10);
        }
    }

    api_response([
        "tab" => $tab,
        "active_customers" => !empty($active_customers)
            ? $active_customers[0]["cnt"]
            : 0,
        "services_count" => !empty($services_count)
            ? $services_count[0]["cnt"]
            : 0,
        "transaction_types_count" => !empty($transaction_types_count)
            ? $transaction_types_count[0]["cnt"]
            : 0,
        "pending_files" => $pending_files,
        "as_of_date" => get_param("as_of_date", date("Y-m-d")),
        "include_inactive" => get_param("include_inactive") === "1",
    ]);
}

function api_generation_types()
{
    $types = get_all_transaction_types();
    $services = get_all_services();

    $types_by_category = [];
    foreach ($types as $t) {
        $cat = $t["type"] ? $t["type"] : "Uncategorized";
        if (!isset($types_by_category[$cat])) {
            $types_by_category[$cat] = [];
        }
        $types_by_category[$cat][] = $t;
    }

    api_response([
        "types" => $types,
        "types_by_category" => $types_by_category,
        "services" => $services,
        "type_count" => count($types),
    ]);
}

function api_billing_flags()
{
    $level = get_param("level", "default");
    $level_id = get_param("level_id");

    $services = get_all_services();
    $transaction_types = get_all_transaction_types();

    $level_id_cond =
        $level === "default"
            ? "level_id IS NULL"
            : "level_id = " . (int) $level_id;
    $current_flags = sqlite_query(
        "SELECT sbf.*, s.name as service_name
         FROM service_billing_flags sbf
         LEFT JOIN services s ON sbf.service_id = s.id
         WHERE sbf.level = ? AND $level_id_cond
         ORDER BY sbf.service_id, sbf.efx_code, sbf.effective_date DESC",
        [$level],
    );

    $flags_by_key = [];
    foreach ($current_flags as $flag) {
        $key = $flag["service_id"] . "_" . $flag["efx_code"];
        if (!isset($flags_by_key[$key])) {
            $flags_by_key[$key] = $flag;
        }
    }

    $groups = sqlite_query(
        "SELECT id, name FROM discount_groups ORDER BY name",
    );
    $customers = sqlite_query(
        "SELECT id, name FROM customers WHERE status = 'active' ORDER BY name",
    );

    $level_entity = null;
    if ($level === "group" && $level_id) {
        $result = sqlite_query("SELECT * FROM discount_groups WHERE id = ?", [
            $level_id,
        ]);
        $level_entity = !empty($result) ? $result[0] : null;
    } elseif ($level === "customer" && $level_id) {
        $result = sqlite_query("SELECT * FROM customers WHERE id = ?", [
            $level_id,
        ]);
        $level_entity = !empty($result) ? $result[0] : null;
    }

    api_response([
        "level" => $level,
        "level_id" => $level_id,
        "level_entity" => $level_entity,
        "services" => $services,
        "transaction_types" => $transaction_types,
        "current_flags" => array_values($flags_by_key),
        "groups" => $groups,
        "customers" => $customers,
    ]);
}

function api_admin()
{
    require_once __DIR__ . "/admin_seed.php";

    $tab = get_param("tab", "overview");

    api_response([
        "tab" => $tab,
        "stats" => get_database_stats(),
        "sync_status" => get_sync_status(),
        "sync_log" => get_sync_log(15),
        "filesystem" => get_filesystem_status(),
        "environment" => get_environment_status(),
    ]);
}

function api_admin_explore_remote()
{
    $filter = get_param("filter", "");
    $table = get_param("table", "");

    $data = [
        "filter" => $filter,
        "selected_table" => $table,
        "tables" => [],
        "columns" => [],
        "sample_data" => [],
        "error" => null,
        "connected" => false,
        "db_name" => defined("REMOTE_DB_NAME")
            ? REMOTE_DB_NAME
            : "(not configured)",
    ];

    try {
        $data["tables"] = remote_db_list_tables($filter);
        $data["connected"] = true;

        if (!empty($table) && in_array($table, $data["tables"])) {
            $data["columns"] = remote_db_describe_table($table);
            try {
                $data["sample_data"] = remote_db_query(
                    "SELECT * FROM `" . $table . "` LIMIT 10",
                );
            } catch (Exception $e) {
                $data["sample_data"] = [];
            }
        }
    } catch (Exception $e) {
        $data["error"] = $e->getMessage();
    }

    api_response($data);
}

// ============================================================
// WRITE ENDPOINTS (POST)
// ============================================================

function api_save_default_tiers()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $service_id = get_param("service_id");
    if (empty($service_id)) {
        api_error("No service specified");
    }

    $services = sqlite_query("SELECT * FROM services WHERE id = ?", [
        $service_id,
    ]);
    if (empty($services)) {
        api_error("Service not found", 404);
    }

    $tiers = parse_and_validate_tiers();
    save_default_tiers($service_id, $tiers);
    api_response([
        "success" => true,
        "message" => "Default pricing saved for " . $services[0]["name"],
    ]);
}

function api_save_group_tiers()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $group_id = get_param("group_id");
    $service_id = get_param("service_id");
    if (empty($group_id) || empty($service_id)) {
        api_error("Group ID and service ID required");
    }

    $tiers = parse_and_validate_tiers();
    save_group_tiers($group_id, $service_id, $tiers);
    api_response(["success" => true, "message" => "Group pricing saved"]);
}

function api_clear_group_tiers()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $group_id = get_param("group_id");
    $service_id = get_param("service_id");
    if (empty($group_id) || empty($service_id)) {
        api_error("Group ID and service ID required");
    }

    clear_group_tiers($group_id, $service_id);
    api_response(["success" => true, "message" => "Group override cleared"]);
}

function api_save_customer_tiers()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $customer_id = get_param("customer_id");
    $service_id = get_param("service_id");
    if (empty($customer_id) || empty($service_id)) {
        api_error("Customer ID and service ID required");
    }

    $tiers = parse_and_validate_tiers();
    save_customer_tiers($customer_id, $service_id, $tiers);
    api_response(["success" => true, "message" => "Customer pricing saved"]);
}

function api_clear_customer_tiers()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $customer_id = get_param("customer_id");
    $service_id = get_param("service_id");
    if (empty($customer_id) || empty($service_id)) {
        api_error("Customer ID and service ID required");
    }

    clear_customer_tiers($customer_id, $service_id);
    api_response(["success" => true, "message" => "Customer override cleared"]);
}

function api_save_customer_settings()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $customer_id = get_param("customer_id");
    if (empty($customer_id)) {
        api_error("No customer specified");
    }

    $settings = [
        "monthly_minimum" => get_param("monthly_minimum", ""),
        "uses_annualized" => get_param("uses_annualized", 0),
        "annualized_start_date" => get_param("annualized_start_date", ""),
        "look_period_months" => get_param("look_period_months", ""),
    ];

    save_customer_settings($customer_id, $settings);

    $lms_id = get_param("lms_id", "");
    if ($lms_id !== "") {
        assign_customer_lms($customer_id, $lms_id);
    }

    api_response(["success" => true, "message" => "Customer settings saved"]);
}

function api_save_escalators()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $customer_id = get_param("customer_id");
    if (empty($customer_id)) {
        api_error("No customer specified");
    }

    $escalator_start_date = get_param("escalator_start_date", "");
    $year_numbers = isset($_POST["year_number"]) ? $_POST["year_number"] : [];
    $percentages = isset($_POST["escalator_percentage"])
        ? $_POST["escalator_percentage"]
        : [];
    $fixed_adjustments = isset($_POST["fixed_adjustment"])
        ? $_POST["fixed_adjustment"]
        : [];

    $escalators = [];
    for ($i = 0; $i < count($year_numbers); $i++) {
        if ($year_numbers[$i] !== "") {
            $escalators[] = [
                "year_number" => (int) $year_numbers[$i],
                "escalator_percentage" => isset($percentages[$i])
                    ? $percentages[$i]
                    : 0,
                "fixed_adjustment" => isset($fixed_adjustments[$i])
                    ? $fixed_adjustments[$i]
                    : 0,
            ];
        }
    }

    save_escalators($customer_id, $escalators, $escalator_start_date);
    api_response(["success" => true, "message" => "Escalators saved"]);
}

function api_save_escalator_delay()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $customer_id = get_param("customer_id");
    $year_number = get_param("year_number");
    if (empty($customer_id) || empty($year_number)) {
        api_error("Customer ID and year number required");
    }

    apply_escalator_delay($customer_id, $year_number, 1);
    api_response(["success" => true, "message" => "Escalator delay applied"]);
}

function api_toggle_business_rule()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $customer_id = get_param("customer_id");
    $rule_name = get_param("rule");
    if (empty($rule_name)) {
        $rule_name = get_param("rule_name");
    }
    if (empty($customer_id) || empty($rule_name)) {
        api_error("Customer ID and rule name required");
    }

    $action = get_param("mask_action", "toggle");
    $current_status = get_rule_mask_status($customer_id, $rule_name);
    $new_status =
        $action === "mask"
            ? true
            : ($action === "unmask"
                ? false
                : !$current_status);

    toggle_rule_mask($customer_id, $rule_name, $new_status);
    api_response([
        "success" => true,
        "message" => "Rule " . ($new_status ? "masked" : "unmasked"),
        "is_masked" => $new_status,
    ]);
}

function api_save_lms()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $lms_id = get_param("lms_id");
    if (empty($lms_id)) {
        api_error("No LMS specified");
    }

    $lms = get_lms($lms_id);
    if (!$lms) {
        api_error("LMS not found", 404);
    }

    $use_default = get_param("use_default") === "1";
    $commission_rate = $use_default
        ? null
        : (float) get_param("commission_rate");

    save_lms($lms["id"], $lms["name"], $commission_rate);
    api_response(["success" => true, "message" => "LMS commission rate saved"]);
}

function api_save_lms_settings()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    if (get_param("sync_cogs") === "1") {
        $result = sync_cogs_from_remote();
        api_response([
            "success" => true,
            "message" => "COGS synced: " . $result . " records",
        ]);
        return;
    }

    $default_rate = (float) get_param("default_commission_rate");
    save_default_commission_rate($default_rate);
    api_response([
        "success" => true,
        "message" => "Default commission rate saved",
    ]);
}

function api_save_billing_flags()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $level = get_param("level", "default");
    $level_id = get_param("level_id");
    $service_id = get_param("service_id");
    $efx_code = get_param("efx_code");
    $flag_action = get_param("flag_action");

    if ($flag_action === "delete") {
        $flag_id = get_param("flag_id");
        delete_billing_flag($flag_id);
        api_response(["success" => true, "message" => "Flag deleted"]);
        return;
    }

    save_billing_flags(
        $level,
        $level_id,
        $service_id,
        $efx_code,
        get_param("include_exclude"),
        get_param("effective_date", date("Y-m-d")),
    );
    api_response(["success" => true, "message" => "Billing flag saved"]);
}

function api_save_generation_types()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $type_action = get_param("type_action", "save");

    if ($type_action === "delete") {
        $type_id = (int) get_param("type_id");
        delete_transaction_type($type_id);
        api_response([
            "success" => true,
            "message" => "Transaction type deleted",
        ]);
        return;
    }

    $efx_code = get_param("efx_code");
    $efx_displayname = get_param("efx_displayname");
    $type = get_param("type");
    $service_id = get_param("service_id");

    save_transaction_type($efx_code, $efx_displayname, $type, $service_id);
    api_response(["success" => true, "message" => "Transaction type saved"]);
}

function api_admin_sync()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $entity = get_param("entity");
    $sync_functions = [
        "customers" => "sync_customers_from_remote",
        "services" => "sync_services_from_remote",
        "discount_groups" => "sync_discount_groups_from_remote",
        "lms" => "sync_lms_from_remote",
        "cogs" => "sync_cogs_from_remote",
        "business_rules" => "sync_business_rules_from_remote",
        "all" => "sync_all_from_remote",
    ];

    if (!isset($sync_functions[$entity])) {
        api_error("Unknown sync entity: " . $entity);
    }

    $func = $sync_functions[$entity];
    $result = $func();

    if (is_array($result) && isset($result["message"])) {
        $msg = $result["message"];
    } elseif (is_array($result) && isset($result["synced"])) {
        $msg = "Synced " . $result["synced"] . " records";
    } elseif (is_int($result)) {
        $msg = "Synced " . $result . " records";
    } else {
        $msg = "Sync complete";
    }

    api_response([
        "success" => true,
        "message" => ucfirst(str_replace("_", " ", $entity)) . ": " . $msg,
    ]);
}

function api_admin_clear()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $confirm = get_param("confirm_text");
    if ($confirm !== "DELETE ALL DATA") {
        api_error("Confirmation text required: DELETE ALL DATA");
    }

    clear_database();
    api_response(["success" => true, "message" => "Database cleared"]);
}

function api_admin_clear_entity()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $entity = get_param("entity");
    $confirm = get_param("confirm");
    if ($confirm !== "1") {
        api_error("Confirmation required");
    }

    clear_entity_data($entity);
    api_response([
        "success" => true,
        "message" => ucfirst($entity) . " data cleared",
    ]);
}

function api_admin_fix_directories()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    ensure_directories();
    api_response([
        "success" => true,
        "message" => "Directories created/verified",
    ]);
}

// ============================================================
// JOB SYSTEM (migrated from actions.php)
// ============================================================

function api_job_start()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        api_error("POST required", 405);
    }

    $type = get_param("job_type");
    $valid_types = ["seed", "sync", "audit"];
    if (!in_array($type, $valid_types)) {
        api_error("Invalid job type. Valid: " . implode(", ", $valid_types));
    }

    $params_raw = get_param("params", "{}");
    $params = json_decode($params_raw, true);
    if ($params === null) {
        $params = [];
    }

    job_cleanup();
    $job_id = job_create($type, $params);

    $php = PHP_BINARY ? PHP_BINARY : "/usr/bin/php5.6";
    $script = __DIR__ . "/job_runner.php";
    $cmd = sprintf(
        "%s %s --job-id=%s --type=%s > /dev/null 2>&1 &",
        escapeshellarg($php),
        escapeshellarg($script),
        escapeshellarg($job_id),
        escapeshellarg($type),
    );

    $exec_output = [];
    $exec_return = 0;
    exec($cmd, $exec_output, $exec_return);

    if ($exec_return !== 0) {
        job_fail(
            $job_id,
            "Failed to launch background process (exit code: $exec_return)",
        );
    }

    api_response(["job_id" => $job_id]);
}

function api_job_status()
{
    $job_id = get_param("id");
    if (empty($job_id)) {
        api_error("No job ID specified");
    }

    $job = job_read($job_id);
    if (!$job) {
        api_error("Job not found", 404);
    }

    api_response($job);
}
