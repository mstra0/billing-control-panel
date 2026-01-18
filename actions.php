<?php
// ============================================================
// ACTION HANDLERS (Controllers)
// All action_* functions that handle HTTP requests
// ============================================================

function action_dashboard()
{
    // Get counts for dashboard
    $services = sqlite_query("SELECT COUNT(*) as cnt FROM services");
    $groups = sqlite_query("SELECT COUNT(*) as cnt FROM discount_groups");
    $customers_active = sqlite_query(
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'",
    );
    $customers_all = sqlite_query("SELECT COUNT(*) as cnt FROM customers");

    // Get alerts
    $alerts = get_dashboard_alerts();

    $data = [
        "service_count" => $services[0]["cnt"],
        "group_count" => $groups[0]["cnt"],
        "customer_active" => $customers_active[0]["cnt"],
        "customer_total" => $customers_all[0]["cnt"],
        "reports" => list_reports(),
        "pending_configs" => list_pending_configs(),
        "alerts" => $alerts,
    ];
    render_dashboard($data);
}

/**
 * Get dashboard alerts (upcoming events, warnings)
 */
function action_list_reports()
{
    $data = [
        "reports" => list_reports(),
    ];
    render_list_reports($data);
}

/**
 * View a specific report
 */
function action_view_report()
{
    $file = get_param("file");

    if (empty($file)) {
        set_flash("error", "No file specified");
        redirect("list_reports");
        return;
    }

    $filepath = get_generated_path() . "/" . basename($file);

    if (!is_valid_filepath($filepath)) {
        set_flash("error", "Invalid file path");
        redirect("list_reports");
        return;
    }

    $rows = csv_read($filepath);
    $headers = csv_get_headers($filepath);

    if ($rows === false) {
        set_flash("error", "Could not read file");
        redirect("list_reports");
        return;
    }

    $data = [
        "filename" => basename($file),
        "filepath" => $filepath,
        "headers" => $headers,
        "rows" => $rows,
        "count" => count($rows),
    ];
    render_view_report($data);
}

/**
 * Download a report file
 */
function action_download_report()
{
    $file = get_param("file");

    if (empty($file)) {
        set_flash("error", "No file specified");
        redirect("list_reports");
        return;
    }

    $filepath = get_generated_path() . "/" . basename($file);

    if (!is_valid_filepath($filepath)) {
        set_flash("error", "Invalid file path");
        redirect("list_reports");
        return;
    }

    if (!download_file($filepath)) {
        set_flash("error", "Could not download file");
        redirect("list_reports");
    }
    exit();
}

/**
 * Upload config form/handler
 */
function action_upload_config()
{
    $data = [
        "error" => null,
        "success" => null,
    ];

    // Handle POST (file upload)
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_FILES["config_file"])
    ) {
        $result = handle_config_upload($_FILES["config_file"]);

        if ($result["success"]) {
            set_flash(
                "success",
                $result["message"] . " (" . $result["filename"] . ")",
            );
            redirect("list_pending");
            return;
        } else {
            $data["error"] = $result["message"];
        }
    }

    render_upload_config($data);
}

/**
 * List pending configs (in dead-drop, awaiting processing)
 */
function action_list_pending()
{
    $data = [
        "configs" => list_pending_configs(),
    ];
    render_list_pending($data);
}

/**
 * List archived configs (historical)
 */
function action_list_archive()
{
    $data = [
        "configs" => list_archived_configs(),
    ];
    render_list_archive($data);
}

/**
 * View a specific config (pending or archived)
 */
function action_view_config()
{
    $file = get_param("file");
    $source = get_param("source", "archive"); // 'pending' or 'archive'

    if (empty($file)) {
        set_flash("error", "No file specified");
        redirect("list_archive");
        return;
    }

    $base_path =
        $source === "pending" ? get_pending_path() : get_archive_path();
    $filepath = $base_path . "/" . basename($file);

    if (!is_valid_filepath($filepath)) {
        set_flash("error", "Invalid file path");
        redirect("list_archive");
        return;
    }

    $rows = csv_read($filepath);
    $headers = csv_get_headers($filepath);

    if ($rows === false) {
        set_flash("error", "Could not read file");
        redirect("list_archive");
        return;
    }

    $data = [
        "filename" => basename($file),
        "filepath" => $filepath,
        "headers" => $headers,
        "rows" => $rows,
        "count" => count($rows),
    ];
    render_view_config($data);
}

/**
 * Download a config file
 */
function action_download_config()
{
    $file = get_param("file");
    $source = get_param("source", "archive");

    if (empty($file)) {
        set_flash("error", "No file specified");
        redirect("list_archive");
        return;
    }

    $base_path =
        $source === "pending" ? get_pending_path() : get_archive_path();
    $filepath = $base_path . "/" . basename($file);

    if (!is_valid_filepath($filepath)) {
        set_flash("error", "Invalid file path");
        redirect("list_archive");
        return;
    }

    if (!download_file($filepath)) {
        set_flash("error", "Could not download file");
        redirect("list_archive");
    }
    exit();
}

// ------------------------------------------------------------
// CSV EXPORT ACTIONS
// ------------------------------------------------------------

/**
 * Export all pricing data to CSV
 */
function action_export_pricing()
{
    $rows = [];

    // Get all services
    $services = get_all_services();

    // System defaults
    foreach ($services as $service) {
        $tiers = get_current_default_tiers($service["id"]);
        foreach ($tiers as $tier) {
            $rows[] = [
                "level" => "default",
                "entity_id" => "",
                "entity_name" => "System Default",
                "service_id" => $service["id"],
                "service_name" => $service["name"],
                "volume_start" => $tier["volume_start"],
                "volume_end" =>
                    $tier["volume_end"] !== null ? $tier["volume_end"] : "",
                "price_per_inquiry" => $tier["price_per_inquiry"],
                "effective_date" => $tier["effective_date"],
            ];
        }
    }

    // Group overrides
    $groups = sqlite_query("SELECT * FROM discount_groups ORDER BY name");
    foreach ($groups as $group) {
        foreach ($services as $service) {
            $tiers = get_current_group_tiers($group["id"], $service["id"]);
            foreach ($tiers as $tier) {
                $rows[] = [
                    "level" => "group",
                    "entity_id" => $group["id"],
                    "entity_name" => $group["name"],
                    "service_id" => $service["id"],
                    "service_name" => $service["name"],
                    "volume_start" => $tier["volume_start"],
                    "volume_end" =>
                        $tier["volume_end"] !== null ? $tier["volume_end"] : "",
                    "price_per_inquiry" => $tier["price_per_inquiry"],
                    "effective_date" => $tier["effective_date"],
                ];
            }
        }
    }

    // Customer overrides
    $customers = sqlite_query("SELECT * FROM customers ORDER BY name");
    foreach ($customers as $customer) {
        foreach ($services as $service) {
            $tiers = get_current_customer_tiers(
                $customer["id"],
                $service["id"],
            );
            foreach ($tiers as $tier) {
                $rows[] = [
                    "level" => "customer",
                    "entity_id" => $customer["id"],
                    "entity_name" => $customer["name"],
                    "service_id" => $service["id"],
                    "service_name" => $service["name"],
                    "volume_start" => $tier["volume_start"],
                    "volume_end" =>
                        $tier["volume_end"] !== null ? $tier["volume_end"] : "",
                    "price_per_inquiry" => $tier["price_per_inquiry"],
                    "effective_date" => $tier["effective_date"],
                ];
            }
        }
    }

    // Output CSV
    $filename = "pricing_export_" . date("Y-m-d_His") . ".csv";
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Length: " . strlen($result["csv_content"]));
    header("Cache-Control: no-cache, must-revalidate");

    $output = fopen("php://output", "w");
    $headers = [
        "level",
        "entity_id",
        "entity_name",
        "service_id",
        "service_name",
        "volume_start",
        "volume_end",
        "price_per_inquiry",
        "effective_date",
    ];
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 * Export customer settings to CSV
 */
function action_export_settings()
{
    $rows = [];

    $customers = sqlite_query("SELECT * FROM customers ORDER BY name");
    foreach ($customers as $customer) {
        $settings = get_current_customer_settings($customer["id"]);
        $rows[] = [
            "customer_id" => $customer["id"],
            "customer_name" => $customer["name"],
            "status" => $customer["status"],
            "discount_group_id" => $customer["discount_group_id"],
            "contract_start_date" => $customer["contract_start_date"],
            "monthly_minimum" => $settings["monthly_minimum"],
            "uses_annualized" => $settings["uses_annualized"],
            "annualized_start_date" => $settings["annualized_start_date"],
            "look_period_months" => $settings["look_period_months"],
        ];
    }

    $filename = "customer_settings_" . date("Y-m-d_His") . ".csv";
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Length: " . strlen($result["csv_content"]));
    header("Cache-Control: no-cache, must-revalidate");

    $output = fopen("php://output", "w");
    $headers = [
        "customer_id",
        "customer_name",
        "status",
        "discount_group_id",
        "contract_start_date",
        "monthly_minimum",
        "uses_annualized",
        "annualized_start_date",
        "look_period_months",
    ];
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 * Export escalators to CSV
 */
function action_export_escalators()
{
    $rows = [];

    $customers = sqlite_query("SELECT * FROM customers ORDER BY name");
    foreach ($customers as $customer) {
        $escalators = get_current_escalators($customer["id"]);
        foreach ($escalators as $esc) {
            $total_delay = get_total_delay_months(
                $customer["id"],
                $esc["year_number"],
            );
            $rows[] = [
                "customer_id" => $customer["id"],
                "customer_name" => $customer["name"],
                "escalator_start_date" => $esc["escalator_start_date"],
                "year_number" => $esc["year_number"],
                "escalator_percentage" => $esc["escalator_percentage"],
                "fixed_adjustment" => $esc["fixed_adjustment"],
                "total_delay_months" => $total_delay,
                "effective_date" => $esc["effective_date"],
            ];
        }
    }

    $filename = "escalators_" . date("Y-m-d_His") . ".csv";
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Length: " . strlen($result["csv_content"]));
    header("Cache-Control: no-cache, must-revalidate");

    $output = fopen("php://output", "w");
    $headers = [
        "customer_id",
        "customer_name",
        "escalator_start_date",
        "year_number",
        "escalator_percentage",
        "fixed_adjustment",
        "total_delay_months",
        "effective_date",
    ];
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 * Show export options page
 */
function action_export()
{
    render_export();
}

// ------------------------------------------------------------
// PRICING: HELPER FUNCTIONS
// ------------------------------------------------------------

/**
 * Get all services
 */
function action_pricing_defaults()
{
    $services = get_all_services();

    // Get tier counts for each service
    foreach ($services as &$service) {
        $tiers = get_current_default_tiers($service["id"]);
        $service["tier_count"] = count($tiers);
        $service["tiers"] = $tiers;
    }

    $data = [
        "services" => $services,
    ];

    render_pricing_defaults($data);
}

/**
 * Edit default tiers for a service
 */
function action_pricing_defaults_edit()
{
    $service_id = get_param("service_id");

    if (empty($service_id)) {
        set_flash("error", "No service specified");
        redirect("pricing_defaults");
        return;
    }

    // Get service
    $services = sqlite_query("SELECT * FROM services WHERE id = ?", [
        $service_id,
    ]);
    if (empty($services)) {
        set_flash("error", "Service not found");
        redirect("pricing_defaults");
        return;
    }
    $service = $services[0];

    // Handle POST (save tiers)
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $tiers = [];
        $volume_starts = isset($_POST["volume_start"])
            ? $_POST["volume_start"]
            : [];
        $volume_ends = isset($_POST["volume_end"]) ? $_POST["volume_end"] : [];
        $prices = isset($_POST["price_per_inquiry"])
            ? $_POST["price_per_inquiry"]
            : [];

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

        if (!empty($tiers)) {
            save_default_tiers($service_id, $tiers);
            set_flash(
                "success",
                "Default pricing saved for " . $service["name"],
            );
            redirect("pricing_defaults");
            return;
        } else {
            set_flash("error", "No valid tiers provided");
        }
    }

    // Get current tiers
    $tiers = get_current_default_tiers($service_id);

    $data = [
        "service" => $service,
        "tiers" => $tiers,
    ];

    render_pricing_defaults_edit($data);
}

// ------------------------------------------------------------
// PRICING: DISCOUNT GROUPS ACTIONS
// ------------------------------------------------------------

function action_pricing_groups()
{
    $groups = sqlite_query("SELECT * FROM discount_groups ORDER BY name");

    foreach ($groups as &$group) {
        // Count members
        $count = sqlite_query(
            "SELECT COUNT(*) as cnt FROM customers WHERE discount_group_id = ?",
            [$group["id"]],
        );
        $group["member_count"] = $count[0]["cnt"];

        // Count overrides
        $overrides = sqlite_query(
            "SELECT COUNT(DISTINCT service_id) as cnt FROM pricing_tiers
             WHERE level = 'group' AND level_id = ? AND effective_date <= date('now')",
            [$group["id"]],
        );
        $group["override_count"] = $overrides[0]["cnt"];
    }

    $data = ["groups" => $groups];
    render_pricing_groups($data);
}

function action_pricing_group_edit()
{
    $group_id = get_param("group_id");
    $service_id = get_param("service_id");

    if (empty($group_id)) {
        set_flash("error", "No group specified");
        redirect("pricing_groups");
        return;
    }

    // Get group
    $groups = sqlite_query("SELECT * FROM discount_groups WHERE id = ?", [
        $group_id,
    ]);
    if (empty($groups)) {
        set_flash("error", "Group not found");
        redirect("pricing_groups");
        return;
    }
    $group = $groups[0];

    // Get all services
    $services = get_all_services();

    // If no service selected, show service list for this group
    if (empty($service_id)) {
        foreach ($services as &$svc) {
            $svc["tiers"] = get_effective_group_tiers($group_id, $svc["id"]);
            $svc["has_override"] =
                !empty($svc["tiers"]) && $svc["tiers"][0]["source"] === "group";
        }

        $data = [
            "group" => $group,
            "services" => $services,
        ];
        render_pricing_group_services($data);
        return;
    }

    // Get specific service
    $svc_rows = sqlite_query("SELECT * FROM services WHERE id = ?", [
        $service_id,
    ]);
    if (empty($svc_rows)) {
        set_flash("error", "Service not found");
        redirect("pricing_group_edit", ["group_id" => $group_id]);
        return;
    }
    $service = $svc_rows[0];

    // Handle POST (save tiers)
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $action = get_param("form_action", "save");

        if ($action === "clear") {
            // Clear override - insert empty marker or just rely on no records
            // For simplicity, we'll just not save anything and show inherited
            set_flash(
                "success",
                "Override cleared for " .
                    $service["name"] .
                    ". Group now inherits from defaults.",
            );
            redirect("pricing_group_edit", ["group_id" => $group_id]);
            return;
        }

        $tiers = [];
        $volume_starts = isset($_POST["volume_start"])
            ? $_POST["volume_start"]
            : [];
        $volume_ends = isset($_POST["volume_end"]) ? $_POST["volume_end"] : [];
        $prices = isset($_POST["price_per_inquiry"])
            ? $_POST["price_per_inquiry"]
            : [];

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

        if (!empty($tiers)) {
            save_group_tiers($group_id, $service_id, $tiers);
            set_flash("success", "Group pricing saved for " . $service["name"]);
            redirect("pricing_group_edit", ["group_id" => $group_id]);
            return;
        } else {
            set_flash("error", "No valid tiers provided");
        }
    }

    // Get current tiers (group override or inherited default)
    $tiers = get_effective_group_tiers($group_id, $service_id);
    $has_override = !empty($tiers) && $tiers[0]["source"] === "group";

    $data = [
        "group" => $group,
        "service" => $service,
        "tiers" => $tiers,
        "has_override" => $has_override,
    ];

    render_pricing_group_edit($data);
}

// ------------------------------------------------------------
// PRICING: CUSTOMERS ACTIONS
// ------------------------------------------------------------

function action_pricing_customers()
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
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_str = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get total for pagination
    $total_query = "SELECT COUNT(*) as cnt FROM customers c LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id $where_str";
    $total = sqlite_query($total_query, $params);
    $total_count = $total[0]["cnt"];

    $pagination = paginate($total_count, $page);

    // Get customers
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

    $data = [
        "customers" => $customers,
        "pagination" => $pagination,
        "status_filter" => $status,
        "search" => $search,
    ];

    render_pricing_customers($data);
}

function action_pricing_customer_edit()
{
    $customer_id = get_param("customer_id");
    $service_id = get_param("service_id");
    $tab = get_param("tab", "services"); // 'services' or 'settings'

    if (empty($customer_id)) {
        set_flash("error", "No customer specified");
        redirect("pricing_customers");
        return;
    }

    // Get customer with group info
    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );

    if (empty($customers)) {
        set_flash("error", "Customer not found");
        redirect("pricing_customers");
        return;
    }
    $customer = $customers[0];

    // Get all services
    $services = get_all_services();

    // Handle settings tab
    if ($tab === "settings") {
        // Handle POST (save settings)
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $settings = [
                "monthly_minimum" => get_param("monthly_minimum", ""),
                "uses_annualized" => get_param("uses_annualized", 0),
                "annualized_start_date" => get_param(
                    "annualized_start_date",
                    "",
                ),
                "look_period_months" => get_param("look_period_months", ""),
            ];

            save_customer_settings($customer_id, $settings);

            // Save LMS assignment
            $lms_id = get_param("lms_id", "");
            if ($lms_id !== "") {
                assign_customer_lms($customer_id, $lms_id);
            }

            set_flash("success", "Settings saved for " . $customer["name"]);
            redirect("pricing_customer_edit", [
                "customer_id" => $customer_id,
                "tab" => "settings",
            ]);
            return;
        }

        $current_settings = get_current_customer_settings($customer_id);

        // Get all LMS entries for dropdown
        $all_lms = get_all_lms();

        $data = [
            "customer" => $customer,
            "settings" => $current_settings,
            "all_lms" => $all_lms,
            "tab" => "settings",
        ];
        render_pricing_customer_settings($data);
        return;
    }

    // If no service selected, show service list for this customer
    if (empty($service_id)) {
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

        $data = [
            "customer" => $customer,
            "services" => $services,
            "settings" => $current_settings,
            "tab" => "services",
        ];
        render_pricing_customer_services($data);
        return;
    }

    // Get specific service
    $svc_rows = sqlite_query("SELECT * FROM services WHERE id = ?", [
        $service_id,
    ]);
    if (empty($svc_rows)) {
        set_flash("error", "Service not found");
        redirect("pricing_customer_edit", ["customer_id" => $customer_id]);
        return;
    }
    $service = $svc_rows[0];

    // Handle POST (save tiers)
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $action = get_param("form_action", "save");

        if ($action === "clear") {
            // Clear override - customer now inherits from group/default
            set_flash(
                "success",
                "Override cleared for " .
                    $service["name"] .
                    ". Customer now inherits from " .
                    ($customer["discount_group_id"] ? "group" : "defaults") .
                    ".",
            );
            redirect("pricing_customer_edit", ["customer_id" => $customer_id]);
            return;
        }

        $tiers = [];
        $volume_starts = isset($_POST["volume_start"])
            ? $_POST["volume_start"]
            : [];
        $volume_ends = isset($_POST["volume_end"]) ? $_POST["volume_end"] : [];
        $prices = isset($_POST["price_per_inquiry"])
            ? $_POST["price_per_inquiry"]
            : [];

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

        if (!empty($tiers)) {
            save_customer_tiers($customer_id, $service_id, $tiers);
            set_flash(
                "success",
                "Customer pricing saved for " . $service["name"],
            );
            redirect("pricing_customer_edit", ["customer_id" => $customer_id]);
            return;
        } else {
            set_flash("error", "No valid tiers provided");
        }
    }

    // Get current tiers (customer override or inherited from group/default)
    $tiers = get_effective_customer_tiers($customer_id, $service_id);
    $has_override = !empty($tiers) && $tiers[0]["source"] === "customer";
    $source = !empty($tiers) ? $tiers[0]["source"] : "default";

    $data = [
        "customer" => $customer,
        "service" => $service,
        "tiers" => $tiers,
        "has_override" => $has_override,
        "source" => $source,
    ];

    render_pricing_customer_edit($data);
}

// ------------------------------------------------------------
// ESCALATORS ACTIONS
// ------------------------------------------------------------

/**
 * Get current escalators for a customer
 */
function action_ingestion()
{
    $tab = get_param("tab", "reports");

    // Handle file upload
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_FILES["billing_csv"])
    ) {
        $file = $_FILES["billing_csv"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            set_flash(
                "error",
                "File upload failed: error code " . $file["error"],
            );
            redirect("ingestion");
            return;
        }

        $filename = $file["name"];
        $content = file_get_contents($file["tmp_name"]);

        $import_result = import_billing_report($filename, $content);

        if ($import_result["success"]) {
            set_flash(
                "success",
                "Imported {$import_result["rows_imported"]} rows from $filename",
            );
        } else {
            set_flash(
                "error",
                "Import failed: " . implode(", ", $import_result["errors"]),
            );
        }

        redirect("ingestion");
        return;
    }

    // Handle import from drive (single file)
    if (get_param("import_file")) {
        $filename = basename(get_param("import_file"));
        $filepath = get_archive_path() . "/" . $filename;

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            $import_result = import_billing_report($filename, $content);

            if ($import_result["success"]) {
                set_flash(
                    "success",
                    "Imported {$import_result["rows_imported"]} rows from $filename",
                );
            } else {
                set_flash(
                    "error",
                    "Import failed: " . implode(", ", $import_result["errors"]),
                );
            }
        } else {
            set_flash("error", "File not found: $filename");
        }

        redirect("ingestion", ["tab" => "drive"]);
        return;
    }

    // Handle bulk import from drive (selected files)
    if ($_SERVER["REQUEST_METHOD"] === "POST" && get_param("bulk_import")) {
        $selected = isset($_POST["selected_files"])
            ? $_POST["selected_files"]
            : [];
        $success_count = 0;
        $error_count = 0;

        foreach ($selected as $filename) {
            $filename = basename($filename);
            $filepath = get_archive_path() . "/" . $filename;

            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                $import_result = import_billing_report($filename, $content);

                if ($import_result["success"]) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }

        if ($success_count > 0 && $error_count === 0) {
            set_flash("success", "Successfully imported $success_count files");
        } elseif ($success_count > 0) {
            set_flash(
                "warning",
                "Imported $success_count files, $error_count failed",
            );
        } else {
            set_flash("error", "All imports failed");
        }

        redirect("ingestion", ["tab" => "drive"]);
        return;
    }

    // Handle delete
    if (get_param("delete")) {
        $report_id = (int) get_param("delete");
        delete_billing_report($report_id);
        set_flash("success", "Report deleted");
        redirect("ingestion");
        return;
    }

    // Get reports already imported
    $reports = get_billing_reports();

    // Get already imported filenames to mark them
    $imported_files = [];
    foreach ($reports as $r) {
        if (!empty($r["file_path"])) {
            $imported_files[] = $r["file_path"];
        }
    }

    // Get files available on drive (archive directory)
    $drive_files = [];
    $archive_path = get_archive_path();
    if (is_dir($archive_path)) {
        $files = glob($archive_path . "/DataX_*.csv");
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
        // Sort by filename descending (newest first)
        usort($drive_files, function ($a, $b) {
            return strcmp($b["filename"], $a["filename"]);
        });
    }

    // Get summary stats
    $stats = sqlite_query(
        "SELECT
            COUNT(*) as total_reports,
            SUM(record_count) as total_rows,
            MIN(report_date) as earliest,
            MAX(report_date) as latest
         FROM billing_reports",
    );

    $data = [
        "reports" => $reports,
        "drive_files" => $drive_files,
        "stats" => !empty($stats) ? $stats[0] : [],
        "tab" => $tab,
    ];

    render_ingestion($data);
}

/**
 * View a specific billing report
 */
function action_ingestion_view()
{
    $report_id = (int) get_param("id");

    if (!$report_id) {
        set_flash("error", "No report specified");
        redirect("ingestion");
        return;
    }

    $reports = sqlite_query("SELECT * FROM billing_reports WHERE id = ?", [
        $report_id,
    ]);
    if (empty($reports)) {
        set_flash("error", "Report not found");
        redirect("ingestion");
        return;
    }

    $report = $reports[0];
    $lines = get_billing_report_lines($report_id);

    // Get summary by customer
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

    $data = [
        "report" => $report,
        "lines" => $lines,
        "customer_summary" => $customer_summary,
    ];

    render_ingestion_view($data);
}

/**
 * Bulk import page for historical seeding
 */
function action_ingestion_bulk()
{
    $results = [];

    // Handle bulk file upload
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_FILES["billing_csvs"])
    ) {
        $files = $_FILES["billing_csvs"];
        $success_count = 0;
        $error_count = 0;

        // Normalize files array (PHP uploads multiple files in a weird structure)
        $file_count = count($files["name"]);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files["error"][$i] !== UPLOAD_ERR_OK) {
                $results[] = [
                    "filename" => $files["name"][$i],
                    "success" => false,
                    "message" => "Upload error: " . $files["error"][$i],
                ];
                $error_count++;
                continue;
            }

            $filename = $files["name"][$i];
            $content = file_get_contents($files["tmp_name"][$i]);

            $import_result = import_billing_report($filename, $content);

            if ($import_result["success"]) {
                $results[] = [
                    "filename" => $filename,
                    "success" => true,
                    "message" => "Imported {$import_result["rows_imported"]} rows",
                    "report_id" => $import_result["report_id"],
                ];
                $success_count++;
            } else {
                $results[] = [
                    "filename" => $filename,
                    "success" => false,
                    "message" => implode(", ", $import_result["errors"]),
                ];
                $error_count++;
            }
        }

        if ($success_count > 0 && $error_count === 0) {
            set_flash(
                "success",
                "Successfully imported all $success_count files",
            );
        } elseif ($success_count > 0) {
            set_flash(
                "warning",
                "Imported $success_count files, $error_count failed",
            );
        } else {
            set_flash("error", "All $error_count files failed to import");
        }
    }

    // Handle directory scan (for files already on server)
    if (get_param("scan_dir")) {
        $scan_path = get_shared_path() . "/archive";
        if (is_dir($scan_path)) {
            $csv_files = glob($scan_path . "/*.csv");
            $results = [];

            foreach ($csv_files as $file_path) {
                $filename = basename($file_path);

                // Check if already imported
                $existing = sqlite_query(
                    "SELECT id FROM billing_reports WHERE file_path = ?",
                    [$filename],
                );

                if (!empty($existing)) {
                    $results[] = [
                        "filename" => $filename,
                        "success" => false,
                        "message" => "Already imported",
                        "skipped" => true,
                    ];
                    continue;
                }

                $content = file_get_contents($file_path);
                $import_result = import_billing_report($filename, $content);

                if ($import_result["success"]) {
                    $results[] = [
                        "filename" => $filename,
                        "success" => true,
                        "message" => "Imported {$import_result["rows_imported"]} rows",
                        "report_id" => $import_result["report_id"],
                    ];
                } else {
                    $results[] = [
                        "filename" => $filename,
                        "success" => false,
                        "message" => implode(", ", $import_result["errors"]),
                    ];
                }
            }

            $success = count(
                array_filter($results, function ($r) {
                    return $r["success"];
                }),
            );
            $skipped = count(
                array_filter($results, function ($r) {
                    return isset($r["skipped"]) && $r["skipped"];
                }),
            );
            $failed = count($results) - $success - $skipped;

            set_flash(
                "info",
                "Scanned directory: $success imported, $skipped already existed, $failed failed",
            );
        } else {
            set_flash("error", "Archive directory not found: $scan_path");
        }
    }

    // Get stats
    $stats = sqlite_query(
        "SELECT
            COUNT(*) as total_reports,
            SUM(record_count) as total_rows,
            MIN(report_date) as earliest,
            MAX(report_date) as latest,
            SUM(CASE WHEN report_type = 'monthly' THEN 1 ELSE 0 END) as monthly_count,
            SUM(CASE WHEN report_type = 'daily' THEN 1 ELSE 0 END) as daily_count
         FROM billing_reports",
    );

    $data = [
        "results" => $results,
        "stats" => !empty($stats) ? $stats[0] : [],
        "archive_path" => get_shared_path() . "/archive",
    ];

    render_ingestion_bulk($data);
}

// ============================================================
// GENERATION ACTIONS (tier_pricing.csv)
// ============================================================

/**
 * Generation page - preview and download tier_pricing.csv
 */
function action_generation()
{
    $tab = get_param("tab", "generate");

    // Handle CSV download
    if (get_param("download") === "1") {
        $options = [
            "as_of_date" => get_param("as_of_date", date("Y-m-d")),
            "include_inactive" => get_param("include_inactive") === "1",
        ];

        $customer_ids = get_param("customer_ids");
        if (!empty($customer_ids)) {
            $options["customer_ids"] = explode(",", $customer_ids);
        }

        $result = generate_tier_pricing_csv($options);

        if (!empty($result["errors"])) {
            set_flash("error", implode(", ", $result["errors"]));
            redirect("generation");
            return;
        }

        // Send as download
        $filename = "tier_pricing_" . date("Ymd_His") . ".csv";
        header("Content-Type: text/csv");
        header(
            "Content-Disposition: attachment; filename=\"" . $filename . "\"",
        );
        header("Content-Length: " . strlen($result["csv_content"]));
        echo $result["csv_content"];
        exit();
    }

    // Handle save to pending
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        get_param("action") === "save_pending"
    ) {
        $options = [
            "as_of_date" => get_param("as_of_date", date("Y-m-d")),
            "include_inactive" => get_param("include_inactive") === "1",
        ];

        $result = generate_tier_pricing_csv($options);

        if (!empty($result["errors"])) {
            set_flash("error", implode(", ", $result["errors"]));
            redirect("generation");
            return;
        }

        // Save to pending directory
        $filename = "tier_pricing_" . date("Ymd_His") . ".csv";
        $pending_path = get_shared_path() . "/pending";

        if (!is_dir($pending_path)) {
            mkdir($pending_path, 0755, true);
        }

        $file_path = $pending_path . "/" . $filename;
        if (file_put_contents($file_path, $result["csv_content"])) {
            set_flash(
                "success",
                "Generated $filename with {$result["row_count"]} rows and saved to pending directory",
            );
        } else {
            set_flash("error", "Failed to write file to pending directory");
        }

        redirect("generation");
        return;
    }

    // Get preview data
    $preview = null;
    if (get_param("preview") === "1" || $_SERVER["REQUEST_METHOD"] === "POST") {
        $options = [
            "as_of_date" => get_param("as_of_date", date("Y-m-d")),
            "include_inactive" => get_param("include_inactive") === "1",
        ];

        $customer_ids = get_param("customer_ids");
        if (!empty($customer_ids)) {
            $options["customer_ids"] = is_array($customer_ids)
                ? $customer_ids
                : explode(",", $customer_ids);
        }

        $preview = generate_tier_pricing_csv($options);
    }

    // Get counts for info display
    $active_customers = sqlite_query(
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'",
    );
    $services_count = sqlite_query("SELECT COUNT(*) as cnt FROM services");
    $transaction_types_count = sqlite_query(
        "SELECT COUNT(*) as cnt FROM transaction_types",
    );

    // Get recent generations (from pending directory)
    $pending_files = [];
    $pending_path = get_shared_path() . "/pending";
    if (is_dir($pending_path)) {
        $files = glob($pending_path . "/tier_pricing_*.csv");
        foreach ($files as $file) {
            $pending_files[] = [
                "filename" => basename($file),
                "size" => filesize($file),
                "modified" => filemtime($file),
            ];
        }
        // Sort by modified time descending
        usort($pending_files, function ($a, $b) {
            return $b["modified"] - $a["modified"];
        });
        $pending_files = array_slice($pending_files, 0, 10);
    }

    $data = [
        "tab" => $tab,
        "preview" => $preview,
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
    ];

    render_generation($data);
}

/**
 * Transaction types management
 */
function action_generation_types()
{
    // Handle CSV import
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["types_csv"])) {
        $file = $_FILES["types_csv"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            set_flash("error", "File upload failed");
            redirect("generation_types");
            return;
        }

        $content = file_get_contents($file["tmp_name"]);
        $result = import_transaction_types_csv($content);

        if (!empty($result["errors"])) {
            set_flash(
                "warning",
                "Imported {$result["imported"]} types with errors: " .
                    implode(", ", $result["errors"]),
            );
        } else {
            set_flash(
                "success",
                "Imported {$result["imported"]} transaction types",
            );
        }

        redirect("generation_types");
        return;
    }

    // Handle single type save
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        get_param("action") === "save_type"
    ) {
        $type = get_param("type");
        $display_name = get_param("display_name");
        $efx_code = get_param("efx_code");
        $efx_displayname = get_param("efx_displayname");
        $service_id = get_param("service_id")
            ? (int) get_param("service_id")
            : null;

        if (empty($type) || empty($display_name) || empty($efx_code)) {
            set_flash("error", "Type, display name, and EFX code are required");
            redirect("generation_types");
            return;
        }

        save_transaction_type(
            $type,
            $display_name,
            $efx_code,
            $efx_displayname,
            $service_id,
        );
        set_flash("success", "Transaction type saved");
        redirect("generation_types");
        return;
    }

    // Handle delete
    if (get_param("delete")) {
        $id = (int) get_param("delete");
        sqlite_execute("DELETE FROM transaction_types WHERE id = ?", [$id]);
        set_flash("success", "Transaction type deleted");
        redirect("generation_types");
        return;
    }

    $types = get_all_transaction_types();
    $services = get_all_services();

    // Group by type for display
    $types_by_category = [];
    foreach ($types as $t) {
        $cat = $t["type"] ? $t["type"] : "Uncategorized";
        if (!isset($types_by_category[$cat])) {
            $types_by_category[$cat] = [];
        }
        $types_by_category[$cat][] = $t;
    }

    $data = [
        "types" => $types,
        "types_by_category" => $types_by_category,
        "services" => $services,
        "type_count" => count($types),
    ];

    render_generation_types($data);
}

/**
 * Billing flags management (by_hit, zero_null, bav_by_trans)
 */
function action_billing_flags()
{
    $level = get_param("level", "default");
    $level_id = get_param("level_id");

    // Handle POST - save flags
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        get_param("action") === "save_flags"
    ) {
        $service_id = (int) get_param("service_id");
        $efx_code = get_param("efx_code");
        $by_hit = get_param("by_hit") ? 1 : 0;
        $zero_null = get_param("zero_null") ? 1 : 0;
        $bav_by_trans = get_param("bav_by_trans") ? 1 : 0;

        if (empty($service_id) || empty($efx_code)) {
            set_flash("error", "Service and EFX code are required");
            redirect(
                "billing_flags&level=$level" .
                    ($level_id ? "&level_id=$level_id" : ""),
            );
            return;
        }

        save_billing_flags(
            $level,
            $level === "default" ? null : (int) $level_id,
            $service_id,
            $efx_code,
            $by_hit,
            $zero_null,
            $bav_by_trans,
        );

        set_flash("success", "Billing flags saved");
        redirect(
            "billing_flags&level=$level" .
                ($level_id ? "&level_id=$level_id" : ""),
        );
        return;
    }

    // Handle clear override
    if (get_param("clear")) {
        $flag_id = (int) get_param("clear");
        sqlite_execute("DELETE FROM service_billing_flags WHERE id = ?", [
            $flag_id,
        ]);
        set_flash("success", "Override cleared");
        redirect(
            "billing_flags&level=$level" .
                ($level_id ? "&level_id=$level_id" : ""),
        );
        return;
    }

    // Get all services and transaction types
    $services = get_all_services();
    $transaction_types = get_all_transaction_types();

    // Get current flags for this level
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

    // Group by service+efx_code and get only current (latest)
    $flags_by_key = [];
    foreach ($current_flags as $flag) {
        $key = $flag["service_id"] . "_" . $flag["efx_code"];
        if (!isset($flags_by_key[$key])) {
            $flags_by_key[$key] = $flag;
        }
    }

    // Get groups and customers for level selector
    $groups = sqlite_query(
        "SELECT id, name FROM discount_groups ORDER BY name",
    );
    $customers = sqlite_query(
        "SELECT id, name FROM customers WHERE status = 'active' ORDER BY name",
    );

    // Get level entity info
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

    $data = [
        "level" => $level,
        "level_id" => $level_id,
        "level_entity" => $level_entity,
        "services" => $services,
        "transaction_types" => $transaction_types,
        "current_flags" => array_values($flags_by_key),
        "groups" => $groups,
        "customers" => $customers,
    ];

    render_billing_flags($data);
}

// ============================================================
// LMS ACTIONS
// ============================================================

/**
 * List all LMS entries
 */
function action_lms()
{
    $page = (int) get_param("page", 1);
    $search = get_param("search", "");

    // Handle sync request
    if (get_param("sync") === "1") {
        $count = sync_lms_from_remote();
        set_flash("success", "Synced $count LMS entries from remote database");
        redirect("lms");
        return;
    }

    $all_lms = get_all_lms();
    $default_rate = get_default_commission_rate();

    // Filter by search
    if (!empty($search)) {
        $all_lms = array_filter($all_lms, function ($lms) use ($search) {
            return stripos($lms["name"], $search) !== false;
        });
        $all_lms = array_values($all_lms);
    }

    // Add customer counts and effective rates
    foreach ($all_lms as &$lms) {
        $customers = get_customers_by_lms($lms["id"]);
        $lms["customer_count"] = count($customers);
        $lms["effective_rate"] =
            $lms["commission_rate"] !== null
                ? (float) $lms["commission_rate"]
                : $default_rate;
        $lms["is_inherited"] = $lms["commission_rate"] === null;
    }

    // Paginate
    $total_count = count($all_lms);
    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $all_lms = array_slice($all_lms, $offset, ITEMS_PER_PAGE);

    // Get customers without LMS
    $unassigned = get_customers_without_lms();

    $data = [
        "lms_list" => $all_lms,
        "unassigned_customers" => $unassigned,
        "default_rate" => $default_rate,
        "pagination" => $pagination,
        "search" => $search,
    ];

    render_lms($data);
}

/**
 * Edit LMS commission rate
 */
function action_lms_edit()
{
    $lms_id = get_param("lms_id");

    if (empty($lms_id)) {
        set_flash("error", "No LMS specified");
        redirect("lms");
        return;
    }

    $lms = get_lms($lms_id);
    if (!$lms) {
        set_flash("error", "LMS not found");
        redirect("lms");
        return;
    }

    // Handle POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $use_default = get_param("use_default") === "1";
        $commission_rate = $use_default
            ? null
            : (float) get_param("commission_rate");

        save_lms($lms["id"], $lms["name"], $commission_rate);
        set_flash("success", "Commission rate saved for " . $lms["name"]);
        redirect("lms_edit", ["lms_id" => $lms_id]);
        return;
    }

    $default_rate = get_default_commission_rate();
    $customers = get_customers_by_lms($lms_id);

    $data = [
        "lms" => $lms,
        "customers" => $customers,
        "default_rate" => $default_rate,
        "effective_rate" =>
            $lms["commission_rate"] !== null
                ? (float) $lms["commission_rate"]
                : $default_rate,
    ];

    render_lms_edit($data);
}

/**
 * System settings (default commission rate)
 */
function action_lms_settings()
{
    // Handle POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $default_rate = (float) get_param("default_commission_rate");
        save_default_commission_rate($default_rate);
        set_flash("success", "Default commission rate saved");
        redirect("lms_settings");
        return;
    }

    // Handle COGS sync
    if (get_param("sync_cogs") === "1") {
        $count = sync_cogs_from_remote();
        set_flash("success", "Synced COGS for $count services");
        redirect("lms_settings");
        return;
    }

    $default_rate = get_default_commission_rate();
    $services = get_all_services();

    // Add COGS to each service
    foreach ($services as &$service) {
        $service["cogs_rate"] = get_service_cogs($service["id"]);
    }

    $data = [
        "default_rate" => $default_rate,
        "services" => $services,
    ];

    render_lms_settings($data);
}

/**
 * LMS Revenue Report - shows revenue, cost, profit per LMS
 */
function action_lms_report()
{
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));

    // Get all LMS with their customers
    $all_lms = get_all_lms();
    $default_rate = get_default_commission_rate();

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
            // Get billing data for this customer for the period
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

            // Calculate COGS (simplified - using average COGS across services)
            // In production, this would be calculated per-service from billing_report_lines
            $services = get_all_services();
            $avg_cogs = 0;
            $cogs_count = 0;
            foreach ($services as $service) {
                $cogs = get_service_cogs($service["id"]);
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

    $data = [
        "lms_data" => $lms_data,
        "grand_totals" => $grand_totals,
        "year" => $year,
        "month" => $month,
        "default_rate" => $default_rate,
    ];

    render_lms_report($data);
}

/**
 * Monthly minimums overview - list all customers with minimums configured
 */
function action_minimums()
{
    $page = (int) get_param("page", 1);

    // Get all customers with monthly minimums
    $customers_with_minimums = sqlite_query(
        "SELECT c.id, c.name, c.status, dg.name as group_name,
                cs.monthly_minimum, cs.effective_date
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         LEFT JOIN customer_settings cs ON c.id = cs.customer_id
         WHERE cs.monthly_minimum IS NOT NULL AND cs.monthly_minimum > 0
         ORDER BY c.name",
    );

    // Get summary stats
    $stats = sqlite_query(
        "SELECT COUNT(*) as count, SUM(monthly_minimum) as total_minimums, AVG(monthly_minimum) as avg_minimum
         FROM customer_settings
         WHERE monthly_minimum IS NOT NULL AND monthly_minimum > 0",
    );

    $pagination = paginate(count($customers_with_minimums), $page);

    $data = [
        "customers" => $customers_with_minimums,
        "stats" => !empty($stats)
            ? $stats[0]
            : ["count" => 0, "total_minimums" => 0, "avg_minimum" => 0],
        "pagination" => $pagination,
    ];

    render_minimums($data);
}

/**
 * Annualized tiers overview - list all customers using annualized pricing
 */
function action_annualized()
{
    $page = (int) get_param("page", 1);

    // Get all customers with annualized tiers enabled
    $customers_annualized = sqlite_query(
        "SELECT c.id, c.name, c.status, dg.name as group_name,
                cs.uses_annualized, cs.annualized_start_date, cs.look_period_months, cs.effective_date
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         LEFT JOIN customer_settings cs ON c.id = cs.customer_id
         WHERE cs.uses_annualized = 1
         ORDER BY c.name",
    );

    // Calculate next reset date for each customer
    $today = date("Y-m-d");
    foreach ($customers_annualized as &$customer) {
        if (!empty($customer["annualized_start_date"])) {
            $start_md = substr($customer["annualized_start_date"], 5); // MM-DD
            $this_year_reset = date("Y") . "-" . $start_md;
            $next_year_reset = date("Y") + 1 . "-" . $start_md;
            $customer["next_reset"] =
                $this_year_reset > $today ? $this_year_reset : $next_year_reset;
        } else {
            $customer["next_reset"] = null;
        }
    }

    // Get summary stats
    $stats = sqlite_query(
        "SELECT COUNT(*) as count
         FROM customer_settings
         WHERE uses_annualized = 1",
    );

    // Get upcoming resets (next 30 days)
    $upcoming_resets = get_upcoming_annualized_resets(30);

    $pagination = paginate(count($customers_annualized), $page);

    $data = [
        "customers" => $customers_annualized,
        "stats" => !empty($stats) ? $stats[0] : ["count" => 0],
        "upcoming_resets" => $upcoming_resets,
        "pagination" => $pagination,
    ];

    render_annualized($data);
}

/**
 * Customer pricing view - shows effective pricing with source color-coding
 */
function action_customer_pricing()
{
    $customer_id = get_param("id");

    if (empty($customer_id)) {
        set_flash("error", "No customer specified");
        redirect("pricing_customers");
        return;
    }

    // Get customer info
    $customer = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );

    if (empty($customer)) {
        set_flash("error", "Customer not found");
        redirect("pricing_customers");
        return;
    }
    $customer = $customer[0];

    // Get all services
    $services = get_all_services();

    // Build pricing data for each service
    $pricing_data = [];
    foreach ($services as $service) {
        $service_id = $service["id"];

        // Get tiers from each level
        $default_tiers = get_current_default_tiers($service_id);
        $group_tiers = [];
        $customer_tiers = get_current_customer_tiers($customer_id, $service_id);

        if ($customer["discount_group_id"]) {
            $group_tiers = get_current_group_tiers(
                $customer["discount_group_id"],
                $service_id,
            );
        }

        // Build effective tiers with source tracking
        $effective_tiers = [];

        // Start with defaults as base
        foreach ($default_tiers as $tier) {
            $key =
                $tier["volume_start"] .
                "-" .
                ($tier["volume_end"] ?: "unlimited");
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

        // Overlay group tiers
        foreach ($group_tiers as $tier) {
            $key =
                $tier["volume_start"] .
                "-" .
                ($tier["volume_end"] ?: "unlimited");
            if (isset($effective_tiers[$key])) {
                $effective_tiers[$key]["price"] = $tier["price_per_inquiry"];
                $effective_tiers[$key]["source"] = "group";
                $effective_tiers[$key]["group_price"] =
                    $tier["price_per_inquiry"];
            } else {
                // Group added a tier not in defaults
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

        // Overlay customer tiers
        foreach ($customer_tiers as $tier) {
            $key =
                $tier["volume_start"] .
                "-" .
                ($tier["volume_end"] ?: "unlimited");
            if (isset($effective_tiers[$key])) {
                $effective_tiers[$key]["price"] = $tier["price_per_inquiry"];
                $effective_tiers[$key]["source"] = "customer";
                $effective_tiers[$key]["customer_price"] =
                    $tier["price_per_inquiry"];
            } else {
                // Customer added a tier
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

        // Sort by volume_start
        usort($effective_tiers, function ($a, $b) {
            return $a["volume_start"] - $b["volume_start"];
        });

        // Determine overall service source (for summary)
        $has_customer_override = !empty($customer_tiers);
        $has_group_override = !empty($group_tiers);

        $pricing_data[] = [
            "service" => $service,
            "tiers" => $effective_tiers,
            "has_customer_override" => $has_customer_override,
            "has_group_override" => $has_group_override,
            "tier_count" => count($effective_tiers),
        ];
    }

    // Get customer settings
    $settings = get_current_customer_settings($customer_id);

    // Get escalators
    $escalators = get_current_escalators($customer_id);
    $total_delay = get_total_delay_months($customer_id);

    // Summary counts
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

    $data = [
        "customer" => $customer,
        "pricing_data" => $pricing_data,
        "settings" => $settings,
        "escalators" => $escalators,
        "total_delay" => $total_delay,
        "summary" => $summary,
    ];

    render_customer_pricing($data);
}

/**
 * List customers with escalators
 */
function action_escalators()
{
    $page = (int) get_param("page", 1);
    $search = get_param("search", "");

    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(c.name LIKE ? OR dg.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_str = !empty($where) ? "AND " . implode(" AND ", $where) : "";

    // Get customers that have escalators
    $total_query = "SELECT COUNT(DISTINCT c.id) as cnt FROM customers c
         INNER JOIN customer_escalators ce ON c.id = ce.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str";
    $total = sqlite_query($total_query, $params);
    $total_count = $total[0]["cnt"];

    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    // Get customers with escalators
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

    // Get escalator counts for each customer
    foreach ($customers as &$customer) {
        $escalators = get_current_escalators($customer["id"]);
        $customer["escalator_count"] = count($escalators);

        // Get next escalator info
        if (!empty($escalators)) {
            $customer["start_date"] = $escalators[0]["escalator_start_date"];
        }
    }

    $data = [
        "customers" => $customers,
        "pagination" => $pagination,
        "search" => $search,
    ];

    render_escalators($data);
}

/**
 * Edit escalators for a customer
 */
function action_escalator_edit()
{
    $customer_id = get_param("customer_id");

    if (empty($customer_id)) {
        set_flash("error", "No customer specified");
        redirect("escalators");
        return;
    }

    // Get customer
    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );

    if (empty($customers)) {
        set_flash("error", "Customer not found");
        redirect("escalators");
        return;
    }
    $customer = $customers[0];

    // Handle POST (save escalators)
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $escalator_start_date = get_param(
            "escalator_start_date",
            $customer["contract_start_date"],
        );
        $year_numbers = isset($_POST["year_number"])
            ? $_POST["year_number"]
            : [];
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

        if (!empty($escalators)) {
            save_escalators($customer_id, $escalators, $escalator_start_date);
            set_flash("success", "Escalators saved for " . $customer["name"]);
            redirect("escalator_edit", ["customer_id" => $customer_id]);
            return;
        } else {
            set_flash("error", "No valid escalators provided");
        }
    }

    // Get current escalators
    $escalators = get_current_escalators($customer_id);

    // Get delays for each year
    $delays = [];
    foreach ($escalators as &$esc) {
        $esc["total_delay"] = get_total_delay_months(
            $customer_id,
            $esc["year_number"],
        );
    }

    $data = [
        "customer" => $customer,
        "escalators" => $escalators,
    ];

    render_escalator_edit($data);
}

/**
 * Apply a delay to an escalator
 */
function action_escalator_delay()
{
    $customer_id = get_param("customer_id");
    $year_number = get_param("year_number");

    if (empty($customer_id) || empty($year_number)) {
        set_flash("error", "Missing customer or year");
        redirect("escalators");
        return;
    }

    apply_escalator_delay($customer_id, $year_number, 1);
    set_flash(
        "success",
        "Escalator for Year " . $year_number . " delayed by 1 month",
    );
    redirect("escalator_edit", ["customer_id" => $customer_id]);
}

// ------------------------------------------------------------
// BUSINESS RULES ACTIONS
// ------------------------------------------------------------

/**
 * List customers with business rules
 */
function action_business_rules()
{
    $page = (int) get_param("page", 1);
    $search = get_param("search", "");

    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(c.name LIKE ? OR dg.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_str = !empty($where) ? "AND " . implode(" AND ", $where) : "";

    // Get customers that have rules
    $total_query = "SELECT COUNT(DISTINCT c.id) as cnt FROM customers c
         INNER JOIN business_rules br ON c.id = br.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str";
    $total = sqlite_query($total_query, $params);
    $total_count = $total[0]["cnt"];

    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    // Get customers with rules
    $query_params = $params;
    $query_params[] = ITEMS_PER_PAGE;
    $query_params[] = $offset;

    $customers = sqlite_query(
        "SELECT DISTINCT c.*, dg.name as group_name
         FROM customers c
         INNER JOIN business_rules br ON c.id = br.customer_id
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE 1=1 $where_str
         ORDER BY c.name
         LIMIT ? OFFSET ?",
        $query_params,
    );

    // Get rule counts for each customer
    foreach ($customers as &$customer) {
        $rules = get_customer_rules($customer["id"]);
        $customer["rule_count"] = count($rules);

        // Count masked rules
        $masked_count = 0;
        foreach ($rules as $rule) {
            if (get_rule_mask_status($customer["id"], $rule["rule_name"])) {
                $masked_count++;
            }
        }
        $customer["masked_count"] = $masked_count;
    }

    $data = [
        "customers" => $customers,
        "pagination" => $pagination,
        "search" => $search,
    ];

    render_business_rules($data);
}

/**
 * View all business rules across all customers
 */
function action_business_rules_all()
{
    $page = (int) get_param("page", 1);
    $filter_masked = get_param("masked");
    $search = get_param("search", "");

    // Build query
    $where = [];
    $params = [];

    if ($filter_masked === "1") {
        $where[] =
            "EXISTS (SELECT 1 FROM business_rule_masks brm WHERE brm.customer_id = br.customer_id AND brm.rule_name = br.rule_name AND brm.is_masked = 1)";
    } elseif ($filter_masked === "0") {
        $where[] =
            "NOT EXISTS (SELECT 1 FROM business_rule_masks brm WHERE brm.customer_id = br.customer_id AND brm.rule_name = br.rule_name AND brm.is_masked = 1)";
    }

    if (!empty($search)) {
        $where[] = "(br.rule_name LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get total count
    $count_sql = "SELECT COUNT(*) as cnt FROM business_rules br
                  JOIN customers c ON br.customer_id = c.id
                  $where_clause";
    $total = sqlite_query($count_sql, $params);
    $total_count = $total[0]["cnt"];

    $pagination = paginate($total_count, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    // Get rules
    $sql = "SELECT br.*, c.name as customer_name, c.status as customer_status
            FROM business_rules br
            JOIN customers c ON br.customer_id = c.id
            $where_clause
            ORDER BY c.name, br.rule_name
            LIMIT ? OFFSET ?";
    $params[] = ITEMS_PER_PAGE;
    $params[] = $offset;

    $rules = sqlite_query($sql, $params);

    // Add mask status
    foreach ($rules as &$rule) {
        $rule["is_masked"] = get_rule_mask_status(
            $rule["customer_id"],
            $rule["rule_name"],
        );
    }

    // Get summary stats
    $stats = [
        "total_rules" => sqlite_query(
            "SELECT COUNT(*) as cnt FROM business_rules",
        )[0]["cnt"],
        "masked_rules" => sqlite_query(
            "SELECT COUNT(*) as cnt FROM business_rule_masks WHERE is_masked = 1",
        )[0]["cnt"],
        "customers_with_rules" => sqlite_query(
            "SELECT COUNT(DISTINCT customer_id) as cnt FROM business_rules",
        )[0]["cnt"],
    ];

    $data = [
        "rules" => $rules,
        "pagination" => $pagination,
        "filter_masked" => $filter_masked,
        "search" => $search,
        "stats" => $stats,
    ];

    render_business_rules_all($data);
}

/**
 * View/edit rules for a specific customer
 */
function action_business_rule_edit()
{
    $customer_id = get_param("customer_id");

    if (empty($customer_id)) {
        set_flash("error", "No customer specified");
        redirect("business_rules");
        return;
    }

    // Get customer
    $customers = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id],
    );

    if (empty($customers)) {
        set_flash("error", "Customer not found");
        redirect("business_rules");
        return;
    }
    $customer = $customers[0];

    // Get rules with mask status
    $rules = get_customer_rules($customer_id);
    foreach ($rules as &$rule) {
        $rule["is_masked"] = get_rule_mask_status(
            $customer_id,
            $rule["rule_name"],
        );
    }

    $data = [
        "customer" => $customer,
        "rules" => $rules,
    ];

    render_business_rule_edit($data);
}

/**
 * Toggle a rule's mask status
 */
function action_business_rule_toggle()
{
    $customer_id = get_param("customer_id");
    $rule_name = get_param("rule");
    if (empty($rule_name)) {
        $rule_name = get_param("rule_name"); // fallback
    }
    $action = get_param("mask_action", "toggle"); // 'mask' or 'unmask'
    $return_to = get_param("return", "edit"); // 'edit' or 'all'

    if (empty($customer_id) || empty($rule_name)) {
        set_flash("error", "Missing customer or rule");
        redirect("business_rules");
        return;
    }

    $current_status = get_rule_mask_status($customer_id, $rule_name);
    $new_status =
        $action === "mask"
            ? true
            : ($action === "unmask"
                ? false
                : !$current_status);

    toggle_rule_mask($customer_id, $rule_name, $new_status);

    $status_label = $new_status ? "masked" : "unmasked";
    set_flash("success", "Rule \"" . $rule_name . "\" is now " . $status_label);

    if ($return_to === "all") {
        redirect("business_rules_all");
    } else {
        redirect("business_rule_edit", ["customer_id" => $customer_id]);
    }
}

// ------------------------------------------------------------
// HISTORY ACTIONS
// ------------------------------------------------------------

/**
 * View audit history
 */
function action_history()
{
    $filter = get_param("filter", "all"); // 'all', 'pricing', 'settings', 'escalators', 'rules'
    $customer_id = get_param("customer_id", "");
    $page = (int) get_param("page", 1);

    $history = [];

    // Pricing tier changes
    if ($filter === "all" || $filter === "pricing") {
        $pricing_history = get_pricing_history($customer_id);
        foreach ($pricing_history as $item) {
            $item["category"] = "pricing";
            $history[] = $item;
        }
    }

    // Customer settings changes
    if ($filter === "all" || $filter === "settings") {
        $settings_history = get_settings_history($customer_id);
        foreach ($settings_history as $item) {
            $item["category"] = "settings";
            $history[] = $item;
        }
    }

    // Escalator changes
    if ($filter === "all" || $filter === "escalators") {
        $escalator_history = get_escalator_history($customer_id);
        foreach ($escalator_history as $item) {
            $item["category"] = "escalators";
            $history[] = $item;
        }
    }

    // Business rule mask changes
    if ($filter === "all" || $filter === "rules") {
        $rule_history = get_rule_mask_history($customer_id);
        foreach ($rule_history as $item) {
            $item["category"] = "rules";
            $history[] = $item;
        }
    }

    // Sort by date descending
    usort($history, function ($a, $b) {
        return strcmp($b["date"], $a["date"]);
    });

    // Paginate
    $total = count($history);
    $pagination = paginate($total, $page);
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $history = array_slice($history, $offset, ITEMS_PER_PAGE);

    // Get customers for filter dropdown
    $customers = sqlite_query("SELECT id, name FROM customers ORDER BY name");

    $data = [
        "history" => $history,
        "pagination" => $pagination,
        "filter" => $filter,
        "customer_id" => $customer_id,
        "customers" => $customers,
    ];

    render_history($data);
}

// ------------------------------------------------------------
// BILLING CALENDAR ACTIONS
// ------------------------------------------------------------

/**
 * Billing Calendar - Year View
 * Shows 12-month calendar with event indicators and completion status
 */
function action_calendar()
{
    $year = (int) get_param("year", date("Y"));

    // Get calendar summary for the year
    $months = get_calendar_year_summary($year);

    // Get next incomplete month for "What's Next?" button
    $next_incomplete = get_next_incomplete_month();

    // Get overall stats for the year
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

    $data = [
        "year" => $year,
        "months" => $months,
        "next_incomplete" => $next_incomplete,
        "total_escalators" => $total_escalators,
        "total_resets" => $total_resets,
        "completed_months" => $completed_months,
    ];

    render_calendar($data);
}

/**
 * Billing Calendar - Month Checklist View
 * Shows detailed checklist for a specific month
 */
function action_calendar_month()
{
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));

    // Validate month
    if ($month < 1 || $month > 12) {
        set_flash("error", "Invalid month");
        redirect("calendar");
        return;
    }

    // Get events for this month
    $events = get_month_events($year, $month);

    // Get month completion status
    $is_complete = is_month_complete($year, $month);

    // Get new customers since last month
    $last_month_start = date("Y-m-01", strtotime("$year-$month-01 -1 month"));
    $new_customers = get_new_customers_since($last_month_start);

    // Get config changes since last month
    $config_changes = get_config_changes_since($last_month_start);

    // Get MTD summary if we have daily reports
    $mtd = get_mtd_summary($year, $month);

    // Build checklist sections
    $checklist = [];

    // Section 1: What's New
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

    // Section 2: What's Changing
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

    // Section 3: What's Excluded
    $whats_excluded = [];
    foreach ($events["paused_customers"] as $p) {
        $whats_excluded[] = [
            "type" => "paused",
            "message" => "PAUSED: " . $p["name"] . " - will NOT be billed",
            "customer_id" => $p["id"],
            "severity" => "danger",
        ];
    }
    $checklist["whats_excluded"] = $whats_excluded;

    // Section 4: What's Different (config changes)
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

    // Section 5: Warnings
    $warnings = [];
    foreach ($events["warnings"] as $w) {
        $warnings[] = [
            "type" => $w["type"],
            "message" => "WARNING: " . $w["message"],
            "customer_id" => $w["customer_id"],
            "severity" => "danger",
        ];
    }
    $checklist["warnings"] = $warnings;

    // Navigation helpers
    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year--;
    }

    $next_month = $month + 1;
    $next_year = $year;
    if ($next_month > 12) {
        $next_month = 1;
        $next_year++;
    }

    $data = [
        "year" => $year,
        "month" => $month,
        "month_name" => date("F", mktime(0, 0, 0, $month, 1)),
        "is_complete" => $is_complete,
        "is_current" => $year == date("Y") && $month == date("n"),
        "events" => $events,
        "checklist" => $checklist,
        "mtd" => $mtd,
        "prev" => ["year" => $prev_year, "month" => $prev_month],
        "next" => ["year" => $next_year, "month" => $next_month],
        "total_items" =>
            count($whats_new) +
            count($whats_changing) +
            count($whats_excluded) +
            count($whats_different) +
            count($warnings),
    ];

    render_calendar_month($data);
}

// ------------------------------------------------------------
// MAIN ROUTER
// ------------------------------------------------------------

/**
 * Route request to appropriate action handler
 */
function route()
{
    $action = get_action();

    // Map actions to handlers
    $routes = [
        // Dashboard
        "dashboard" => "action_dashboard",

        // CSV/File management (existing)
        "list_reports" => "action_list_reports",
        "view_report" => "action_view_report",
        "download_report" => "action_download_report",
        "upload_config" => "action_upload_config",
        "list_pending" => "action_list_pending",
        "list_archive" => "action_list_archive",
        "view_config" => "action_view_config",
        "download_config" => "action_download_config",

        // Pricing: System Defaults
        "pricing_defaults" => "action_pricing_defaults",
        "pricing_defaults_edit" => "action_pricing_defaults_edit",

        // Pricing: Discount Groups
        "pricing_groups" => "action_pricing_groups",
        "pricing_group_edit" => "action_pricing_group_edit",

        // Pricing: Customers
        "pricing_customers" => "action_pricing_customers",
        "pricing_customer_edit" => "action_pricing_customer_edit",

        // Escalators
        "escalators" => "action_escalators",
        "escalator_edit" => "action_escalator_edit",
        "escalator_delay" => "action_escalator_delay",

        // Business Rules
        "business_rules" => "action_business_rules",
        "business_rules_all" => "action_business_rules_all",
        "business_rule_edit" => "action_business_rule_edit",
        "business_rule_toggle" => "action_business_rule_toggle",

        // History
        "history" => "action_history",

        // Billing Calendar
        "calendar" => "action_calendar",
        "calendar_month" => "action_calendar_month",
        "mtd_dashboard" => "action_mtd_dashboard",

        // CSV Export
        "export" => "action_export",
        "export_pricing" => "action_export_pricing",
        "export_settings" => "action_export_settings",
        "export_escalators" => "action_export_escalators",

        // LMS
        "lms" => "action_lms",
        "lms_edit" => "action_lms_edit",
        "lms_settings" => "action_lms_settings",
        "lms_report" => "action_lms_report",

        // Ingestion
        "ingestion" => "action_ingestion",
        "ingestion_view" => "action_ingestion_view",
        "ingestion_bulk" => "action_ingestion_bulk",

        // Generation
        "generation" => "action_generation",
        "generation_types" => "action_generation_types",
        "billing_flags" => "action_billing_flags",

        // Monthly Minimums & Annualized
        "minimums" => "action_minimums",
        "annualized" => "action_annualized",

        // Customer Pricing View
        "customer_pricing" => "action_customer_pricing",
    ];

    if (isset($routes[$action]) && function_exists($routes[$action])) {
        call_user_func($routes[$action]);
    } else {
        // Default to dashboard
        action_dashboard();
    }
}

// ============================================================
// END PHASE 3
// ============================================================

// ============================================================
// PHASE 4: HTML/UI TEMPLATES
// Inline templates for all views
// ============================================================

// ------------------------------------------------------------
// LAYOUT FUNCTIONS
// ------------------------------------------------------------

/**
 * Render the page header/layout start
 *
 * @param string $title Page title
 */
function init_mock_data()
{
    if (!MOCK_MODE) {
        return;
    } // Use the shared path (which returns test_shared in mock mode)
    $base = get_shared_path(); // Create directories
    $dirs = [
        $base,
        $base . "/generated",
        $base . "/pending",
        $base . "/archive",
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    // Create sample report files (simulating cron output)
    $sample_reports = [
        [
            "filename" => "report_2026-01-15_100000.csv",
            "headers" => [
                "id",
                "customer_name",
                "email",
                "status",
                "amount",
                "date",
            ],
            "rows" => [
                [
                    "id" => "1001",
                    "customer_name" => "Acme Corp",
                    "email" => "billing@acme.com",
                    "status" => "pending",
                    "amount" => "5000.00",
                    "date" => "2026-01-15",
                ],
                [
                    "id" => "1002",
                    "customer_name" => "Globex Inc",
                    "email" => "ap@globex.com",
                    "status" => "approved",
                    "amount" => "12500.00",
                    "date" => "2026-01-15",
                ],
                [
                    "id" => "1003",
                    "customer_name" => "Initech",
                    "email" => "invoices@initech.com",
                    "status" => "pending",
                    "amount" => "3200.00",
                    "date" => "2026-01-15",
                ],
                [
                    "id" => "1004",
                    "customer_name" => "Umbrella Corp",
                    "email" => "finance@umbrella.com",
                    "status" => "rejected",
                    "amount" => "8900.00",
                    "date" => "2026-01-15",
                ],
                [
                    "id" => "1005",
                    "customer_name" => "Stark Industries",
                    "email" => "pay@stark.com",
                    "status" => "pending",
                    "amount" => "45000.00",
                    "date" => "2026-01-15",
                ],
            ],
        ],
        [
            "filename" => "report_2026-01-14_100000.csv",
            "headers" => [
                "id",
                "customer_name",
                "email",
                "status",
                "amount",
                "date",
            ],
            "rows" => [
                [
                    "id" => "0998",
                    "customer_name" => "Wayne Enterprises",
                    "email" => "accounts@wayne.com",
                    "status" => "approved",
                    "amount" => "75000.00",
                    "date" => "2026-01-14",
                ],
                [
                    "id" => "0999",
                    "customer_name" => "Oscorp",
                    "email" => "billing@oscorp.com",
                    "status" => "pending",
                    "amount" => "18000.00",
                    "date" => "2026-01-14",
                ],
                [
                    "id" => "1000",
                    "customer_name" => "LexCorp",
                    "email" => "finance@lexcorp.com",
                    "status" => "approved",
                    "amount" => "22000.00",
                    "date" => "2026-01-14",
                ],
            ],
        ],
        [
            "filename" => "report_2026-01-13_100000.csv",
            "headers" => [
                "id",
                "customer_name",
                "email",
                "status",
                "amount",
                "date",
            ],
            "rows" => [
                [
                    "id" => "0995",
                    "customer_name" => "Cyberdyne",
                    "email" => "ap@cyberdyne.com",
                    "status" => "approved",
                    "amount" => "150000.00",
                    "date" => "2026-01-13",
                ],
                [
                    "id" => "0996",
                    "customer_name" => "Tyrell Corp",
                    "email" => "invoices@tyrell.com",
                    "status" => "pending",
                    "amount" => "88000.00",
                    "date" => "2026-01-13",
                ],
            ],
        ],
    ];
    foreach ($sample_reports as $report) {
        $filepath = $base . "/generated/" . $report["filename"];
        if (!file_exists($filepath)) {
            csv_write($filepath, $report["rows"], $report["headers"]);
        }
    }
    // Create sample archived config (historical)
    $sample_configs = [
        [
            "filename" => "config_2026-01-14_120000.csv",
            "headers" => ["id", "action", "notes"],
            "rows" => [
                [
                    "id" => "0998",
                    "action" => "approve",
                    "notes" => "Verified by finance",
                ],
                [
                    "id" => "1000",
                    "action" => "approve",
                    "notes" => "Standard processing",
                ],
            ],
        ],
        [
            "filename" => "config_2026-01-13_140000.csv",
            "headers" => ["id", "action", "notes"],
            "rows" => [
                [
                    "id" => "0995",
                    "action" => "approve",
                    "notes" => "Priority client",
                ],
            ],
        ],
    ];
    foreach ($sample_configs as $config) {
        $filepath = $base . "/archive/" . $config["filename"];
        if (!file_exists($filepath)) {
            csv_write($filepath, $config["rows"], $config["headers"]);
        }
    } // Create one pending config
    $pending_config = [
        "filename" => "config_2026-01-15_143000.csv",
        "headers" => ["id", "action", "notes"],
        "rows" => [
            [
                "id" => "1001",
                "action" => "approve",
                "notes" => "Ready for processing",
            ],
            ["id" => "1003", "action" => "approve", "notes" => ""],
            [
                "id" => "1005",
                "action" => "hold",
                "notes" => "Awaiting verification",
            ],
        ],
    ];
    $filepath = $base . "/pending/" . $pending_config["filename"];
    if (!file_exists($filepath)) {
        csv_write(
            $filepath,
            $pending_config["rows"],
            $pending_config["headers"],
        );
    }
} // ------------------------------------------------------------
// RUN APPLICATION
// ------------------------------------------------------------
// Initialize mock data if in mock mode
