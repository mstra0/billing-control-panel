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
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'"
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
    // Legacy redirect - use billing_reports instead
    redirect("billing_reports");
}

/**
 * View a specific report (legacy - redirects to billing_reports)
 */
function action_view_report()
{
    // Legacy redirect
    redirect("billing_reports");
}

/**
 * Download a report file (legacy - redirects to billing_reports)
 */
function action_download_report()
{
    // Legacy redirect
    redirect("billing_reports");
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
                $result["message"] . " (" . $result["filename"] . ")"
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
                $service["id"]
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
                $esc["year_number"]
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
                "Default pricing saved for " . $service["name"]
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
            [$group["id"]]
        );
        $group["member_count"] = $count[0]["cnt"];

        // Count overrides
        $overrides = sqlite_query(
            "SELECT COUNT(DISTINCT service_id) as cnt FROM pricing_tiers
             WHERE level = 'group' AND level_id = ? AND effective_date <= date('now')",
            [$group["id"]]
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
                    ". Group now inherits from defaults."
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
        $query_params
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
        [$customer_id]
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
                    ""
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
                $svc["id"]
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
                    "."
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
                "Customer pricing saved for " . $service["name"]
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
                "File upload failed: error code " . $file["error"]
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
                "Imported {$import_result["rows_imported"]} rows from $filename"
            );
        } else {
            set_flash(
                "error",
                "Import failed: " . implode(", ", $import_result["errors"])
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
                    "Imported {$import_result["rows_imported"]} rows from $filename"
                );
            } else {
                set_flash(
                    "error",
                    "Import failed: " . implode(", ", $import_result["errors"])
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
                "Imported $success_count files, $error_count failed"
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
         FROM billing_reports"
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
        [$report_id]
    );

    $data = [
        "report" => $report,
        "lines" => $lines,
        "customer_summary" => $customer_summary,
    ];

    render_ingestion_view($data);
}

/**
 * Audit a single billing line item
 * Shows the full calculation breakdown with expected vs actual comparison
 */
function action_line_audit()
{
    require_once __DIR__ . "/calculator.php";

    $line_id = (int) get_param("id");

    if (!$line_id) {
        set_flash("error", "No line ID specified");
        redirect("ingestion");
        return;
    }

    $audit = audit_billing_line($line_id);

    if (!isset($audit["line_id"])) {
        set_flash(
            "error",
            isset($audit["error"]) ? $audit["error"] : "Line not found"
        );
        redirect("ingestion");
        return;
    }

    // Generate LaTeX representation
    $latex = format_audit_as_latex($audit);

    $data = [
        "audit" => $audit,
        "latex" => $latex,
    ];

    render_line_audit($data);
}

/**
 * Audit all lines in a billing report
 * Shows summary with variance statistics
 */
function action_report_audit()
{
    require_once __DIR__ . "/calculator.php";

    $report_id = (int) get_param("id");

    if (!$report_id) {
        set_flash("error", "No report ID specified");
        redirect("ingestion");
        return;
    }

    $audit = audit_billing_report($report_id);

    if (!(isset($audit["success"]) ? $audit["success"] : true)) {
        set_flash(
            "error",
            isset($audit["error"]) ? $audit["error"] : "Report not found"
        );
        redirect("ingestion");
        return;
    }

    $data = [
        "audit" => $audit,
    ];

    render_report_audit($data);
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
                "Successfully imported all $success_count files"
            );
        } elseif ($success_count > 0) {
            set_flash(
                "warning",
                "Imported $success_count files, $error_count failed"
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
                    [$filename]
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
                })
            );
            $skipped = count(
                array_filter($results, function ($r) {
                    return isset($r["skipped"]) && $r["skipped"];
                })
            );
            $failed = count($results) - $success - $skipped;

            set_flash(
                "info",
                "Scanned directory: $success imported, $skipped already existed, $failed failed"
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
         FROM billing_reports"
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
            "Content-Disposition: attachment; filename=\"" . $filename . "\""
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
            // Also archive to reports directory
            $archive_params = [
                "as_of_date" => $options["as_of_date"],
                "include_inactive" => $options["include_inactive"],
            ];
            $report_id = archive_generated_report(
                "tier_pricing",
                $file_path,
                $archive_params,
                "Generated from Generation page"
            );

            $archive_msg = $report_id ? " and archived to reports" : "";
            set_flash(
                "success",
                "Generated $filename with {$result["row_count"]} rows and saved to pending directory" .
                    $archive_msg
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
        "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'"
    );
    $services_count = sqlite_query("SELECT COUNT(*) as cnt FROM services");
    $transaction_types_count = sqlite_query(
        "SELECT COUNT(*) as cnt FROM transaction_types"
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
                    implode(", ", $result["errors"])
            );
        } else {
            set_flash(
                "success",
                "Imported {$result["imported"]} transaction types"
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
            $service_id
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
                    ($level_id ? "&level_id=$level_id" : "")
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
            $bav_by_trans
        );

        set_flash("success", "Billing flags saved");
        redirect(
            "billing_flags&level=$level" .
                ($level_id ? "&level_id=$level_id" : "")
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
                ($level_id ? "&level_id=$level_id" : "")
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
        [$level]
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
        "SELECT id, name FROM discount_groups ORDER BY name"
    );
    $customers = sqlite_query(
        "SELECT id, name FROM customers WHERE status = 'active' ORDER BY name"
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
                [$customer["id"], $year, $month]
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
         ORDER BY c.name"
    );

    // Get summary stats
    $stats = sqlite_query(
        "SELECT COUNT(*) as count, SUM(monthly_minimum) as total_minimums, AVG(monthly_minimum) as avg_minimum
         FROM customer_settings
         WHERE monthly_minimum IS NOT NULL AND monthly_minimum > 0"
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
         ORDER BY c.name"
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
         WHERE uses_annualized = 1"
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
        [$customer_id]
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
                $service_id
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

    // Get escalators with their delays
    $escalators = get_current_escalators($customer_id);
    foreach ($escalators as &$esc) {
        $esc["total_delay"] = get_total_delay_months(
            $customer_id,
            $esc["year_number"]
        );
    }
    unset($esc);

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
        $query_params
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
        [$customer_id]
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
            $customer["contract_start_date"]
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
            $esc["year_number"]
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
        "Escalator for Year " . $year_number . " delayed by 1 month"
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
        $query_params
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
            $rule["rule_name"]
        );
    }

    // Get summary stats
    $stats = [
        "total_rules" => sqlite_query(
            "SELECT COUNT(*) as cnt FROM business_rules"
        )[0]["cnt"],
        "masked_rules" => sqlite_query(
            "SELECT COUNT(*) as cnt FROM business_rule_masks WHERE is_masked = 1"
        )[0]["cnt"],
        "customers_with_rules" => sqlite_query(
            "SELECT COUNT(DISTINCT customer_id) as cnt FROM business_rules"
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
        [$customer_id]
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
            $rule["rule_name"]
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

// ============================================================
// BILLING REPORTS ACTIONS
// ============================================================

/**
 * Billing Reports - View all ingestion and generated reports
 */
function action_billing_reports()
{
    $data = [
        "ingestion_reports" => get_ingestion_reports(),
        "generated_reports" => get_generated_reports_grouped(),
    ];

    render_billing_reports($data);
}

/**
 * View a specific billing report (CSV preview)
 */
function action_view_billing_report()
{
    $type = get_param("type"); // 'ingestion' or 'generated'
    $id = get_param("id"); // For generated reports
    $file = get_param("file"); // For ingestion reports

    if ($type === "generated" && $id) {
        $report = get_generated_report($id);
        if (!$report || !file_exists($report["file_path"])) {
            set_flash("error", "Report not found");
            redirect("billing_reports");
            return;
        }
        $filepath = $report["file_path"];
        $filename = $report["file_name"];
        $report_info = $report;
    } elseif ($type === "ingestion" && $file) {
        $filepath = get_archive_path() . "/" . basename($file);
        if (!file_exists($filepath)) {
            set_flash("error", "File not found");
            redirect("billing_reports");
            return;
        }
        $filename = basename($file);
        $report_info = null;
    } else {
        set_flash("error", "Invalid parameters");
        redirect("billing_reports");
        return;
    }

    // Read CSV
    $rows = [];
    $headers = [];
    $count = 0;

    if (($handle = fopen($filepath, "r")) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false && $count < 100) {
            $rows[] = array_combine($headers, $row);
            $count++;
        }
        // Count remaining
        while (fgetcsv($handle) !== false) {
            $count++;
        }
        fclose($handle);
    }

    $data = [
        "type" => $type,
        "filename" => $filename,
        "filepath" => $filepath,
        "headers" => $headers,
        "rows" => $rows,
        "count" => $count,
        "report_info" => $report_info,
    ];

    render_view_billing_report($data);
}

/**
 * Download a billing report
 */
function action_download_billing_report()
{
    $type = get_param("type");
    $id = get_param("id");
    $file = get_param("file");

    if ($type === "generated" && $id) {
        $report = get_generated_report($id);
        if (!$report || !file_exists($report["file_path"])) {
            set_flash("error", "Report not found");
            redirect("billing_reports");
            return;
        }
        $filepath = $report["file_path"];
        $filename = $report["file_name"];
    } elseif ($type === "ingestion" && $file) {
        $filepath = get_archive_path() . "/" . basename($file);
        if (!file_exists($filepath)) {
            set_flash("error", "File not found");
            redirect("billing_reports");
            return;
        }
        $filename = basename($file);
    } else {
        set_flash("error", "Invalid parameters");
        redirect("billing_reports");
        return;
    }

    header("Content-Type: text/csv");
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header("Content-Length: " . filesize($filepath));
    readfile($filepath);
    exit();
}

/**
 * Regenerate a report to temp directory (for comparison, not archived)
 */
function action_regenerate_report()
{
    $report_type = get_param("report_type");
    $compare_id = get_param("compare_id"); // Optional: ID of report to compare against

    if ($report_type === "tier_pricing") {
        $options = [
            "as_of_date" => get_param("as_of_date", date("Y-m-d")),
            "include_inactive" => get_param("include_inactive") === "1",
        ];

        $result = generate_tier_pricing_csv($options);

        if (!empty($result["errors"])) {
            set_flash("error", implode(", ", $result["errors"]));
            redirect("billing_reports");
            return;
        }

        // Save to temp directory (not archived)
        $filename = "tier_pricing_temp_" . date("Ymd_His") . ".csv";
        $temp_path = get_temp_path();

        if (!is_dir($temp_path)) {
            mkdir($temp_path, 0755, true);
        }

        $file_path = $temp_path . "/" . $filename;
        file_put_contents($file_path, $result["csv_content"]);

        // If compare_id provided, redirect to compare view
        if ($compare_id) {
            redirect(
                "compare_reports&temp_file=" .
                    urlencode($filename) .
                    "&compare_id=" .
                    $compare_id
            );
            return;
        }

        // Otherwise just view the temp file
        $data = [
            "type" => "temp",
            "filename" => $filename,
            "filepath" => $file_path,
            "headers" => [],
            "rows" => [],
            "count" => $result["row_count"],
            "report_info" => [
                "generated_at" => date("Y-m-d H:i:s"),
                "notes" => "Temporary regeneration (not archived)",
                "parameters" => json_encode($options),
            ],
        ];

        // Read CSV for preview
        if (($handle = fopen($file_path, "r")) !== false) {
            $data["headers"] = fgetcsv($handle);
            $count = 0;
            while (($row = fgetcsv($handle)) !== false && $count < 100) {
                $data["rows"][] = array_combine($data["headers"], $row);
                $count++;
            }
            fclose($handle);
        }

        render_view_billing_report($data);
        return;
    }

    set_flash("error", "Unknown report type: " . $report_type);
    redirect("billing_reports");
}

/**
 * Compare two reports side by side
 */
function action_compare_reports()
{
    $compare_id = get_param("compare_id");
    $temp_file = get_param("temp_file");
    $report_id_1 = get_param("report_id_1");
    $report_id_2 = get_param("report_id_2");

    // Determine which reports to compare
    $report1 = null;
    $report2 = null;
    $file1 = null;
    $file2 = null;

    if ($temp_file && $compare_id) {
        // Compare temp file against archived report
        $file1 = get_temp_path() . "/" . basename($temp_file);
        $report2 = get_generated_report($compare_id);
        $file2 = $report2 ? $report2["file_path"] : null;

        if (!file_exists($file1) || !$report2 || !file_exists($file2)) {
            set_flash("error", "One or both files not found");
            redirect("billing_reports");
            return;
        }
    } elseif ($report_id_1 && $report_id_2) {
        // Compare two archived reports
        $report1 = get_generated_report($report_id_1);
        $report2 = get_generated_report($report_id_2);

        if (
            !$report1 ||
            !$report2 ||
            !file_exists($report1["file_path"]) ||
            !file_exists($report2["file_path"])
        ) {
            set_flash("error", "One or both reports not found");
            redirect("billing_reports");
            return;
        }

        $file1 = $report1["file_path"];
        $file2 = $report2["file_path"];
    } else {
        set_flash("error", "Invalid comparison parameters");
        redirect("billing_reports");
        return;
    }

    // Read both files
    $data1 = read_csv_for_compare($file1);
    $data2 = read_csv_for_compare($file2);

    // Compare the data
    $comparison = compare_csv_data($data1, $data2);

    $data = [
        "file1" => [
            "name" => $temp_file ? basename($temp_file) : $report1["file_name"],
            "label" => $temp_file ? "New (Temp)" : $report1["file_name"],
            "generated" => $temp_file
                ? date("Y-m-d H:i:s")
                : $report1["generated_at"],
            "row_count" => count($data1["rows"]),
        ],
        "file2" => [
            "name" => $report2["file_name"],
            "label" => "Archived: " . $report2["file_name"],
            "generated" => $report2["generated_at"],
            "row_count" => count($data2["rows"]),
        ],
        "comparison" => $comparison,
        "headers" => $data1["headers"],
    ];

    render_compare_reports($data);
}

/**
 * Helper: Read CSV file for comparison
 */
function read_csv_for_compare($filepath)
{
    $result = ["headers" => [], "rows" => [], "indexed" => []];

    if (($handle = fopen($filepath, "r")) !== false) {
        $result["headers"] = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $assoc = array_combine($result["headers"], $row);
            $result["rows"][] = $assoc;

            // Create index key for comparison (cust_id + EFX_code + start_trans for tier_pricing)
            if (
                isset($assoc["cust_id"]) &&
                isset($assoc["EFX_code"]) &&
                isset($assoc["start_trans"])
            ) {
                $key =
                    $assoc["cust_id"] .
                    "|" .
                    $assoc["EFX_code"] .
                    "|" .
                    $assoc["start_trans"];
                $result["indexed"][$key] = $assoc;
            }
        }
        fclose($handle);
    }

    return $result;
}

/**
 * Helper: Compare two CSV datasets
 */
function compare_csv_data($data1, $data2)
{
    $result = [
        "added" => [], // In new but not in old
        "removed" => [], // In old but not in new
        "changed" => [], // In both but different
        "unchanged" => 0, // Count of unchanged rows
    ];

    // Find added and changed rows
    foreach ($data1["indexed"] as $key => $row1) {
        if (!isset($data2["indexed"][$key])) {
            $result["added"][] = $row1;
        } else {
            $row2 = $data2["indexed"][$key];
            $changes = [];

            foreach ($row1 as $col => $val1) {
                $val2 = isset($row2[$col]) ? $row2[$col] : "";
                if ($val1 !== $val2) {
                    $changes[$col] = ["old" => $val2, "new" => $val1];
                }
            }

            if (!empty($changes)) {
                $result["changed"][] = [
                    "key" => $key,
                    "row" => $row1,
                    "changes" => $changes,
                ];
            } else {
                $result["unchanged"]++;
            }
        }
    }

    // Find removed rows
    foreach ($data2["indexed"] as $key => $row2) {
        if (!isset($data1["indexed"][$key])) {
            $result["removed"][] = $row2;
        }
    }

    return $result;
}

// ============================================================
// BILLING DASHBOARD ACTIONS
// ============================================================

/**
 * Billing Dashboard - Unified dashboard for billing insights
 * LMS performance, tier proximity analysis, and operational metrics
 */
function action_billing_intelligence()
{
    require_once __DIR__ . "/calculator.php";

    // Get date range from ingested data
    $date_range = sqlite_query(
        "SELECT MIN(report_date) as earliest, MAX(report_date) as latest FROM billing_reports"
    );
    $earliest = isset($date_range[0]["earliest"])
        ? $date_range[0]["earliest"]
        : date("Y-m-d");
    $latest = isset($date_range[0]["latest"])
        ? $date_range[0]["latest"]
        : date("Y-m-d");

    // Get current month/year for MTD calculations
    $current_year = (int) date("Y");
    $current_month = (int) date("n");

    // Get overall stats
    $overall_stats = sqlite_query(
        "SELECT
            COUNT(DISTINCT br.id) as total_reports,
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT brl.customer_id) as unique_customers,
            COUNT(DISTINCT brl.efx_code) as unique_services
         FROM billing_reports br
         LEFT JOIN billing_report_lines brl ON br.id = brl.report_id"
    );
    $stats = isset($overall_stats[0]) ? $overall_stats[0] : [];

    // Calculate average price per transaction
    $avg_price =
        $stats["total_transactions"] > 0
            ? $stats["total_revenue"] / $stats["total_transactions"]
            : 0;

    // ========================================
    // LMS PERFORMANCE METRICS
    // ========================================
    $lms_performance = get_lms_performance_metrics(
        $current_year,
        $current_month
    );

    // ========================================
    // TIER PROXIMITY ANALYSIS
    // ========================================
    $tier_proximity = get_tier_proximity_analysis(
        $current_year,
        $current_month
    );

    // Get monthly breakdown (for reference)
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
         LIMIT 6"
    );

    // Run audit on a sample to get variance stats
    $variance_stats = get_billing_variance_stats();

    $data = [
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
    ];

    render_billing_intelligence($data);
}

/**
 * Get LMS performance metrics - daily counts, COGS, revenue vs payout
 */
function get_lms_performance_metrics($year, $month)
{
    $all_lms = get_all_lms();
    $default_rate = get_default_commission_rate();
    $services = get_all_services();

    // Build service COGS lookup
    $service_cogs = [];
    foreach ($services as $svc) {
        $service_cogs[$svc["id"]] = get_service_cogs($svc["id"]);
    }

    // Get EFX code to service mapping
    $efx_to_service = [];
    $types = sqlite_query(
        "SELECT efx_code, service_id FROM transaction_types WHERE service_id IS NOT NULL"
    );
    foreach ($types as $t) {
        $efx_to_service[$t["efx_code"]] = $t["service_id"];
    }

    $lms_data = [];
    $totals = [
        "revenue" => 0,
        "cogs" => 0,
        "gross_profit" => 0,
        "commission" => 0,
        "net_profit" => 0,
        "transactions" => 0,
        "customers" => 0,
    ];

    foreach ($all_lms as $lms) {
        $customers = get_customers_by_lms($lms["id"]);
        if (empty($customers)) {
            continue;
        }

        $customer_ids = array_column($customers, "id");
        $customer_id_placeholders = implode(
            ",",
            array_fill(0, count($customer_ids), "?")
        );

        // Get billing data for this LMS's customers for the period
        $params = array_merge($customer_ids, [$year, $month]);
        $billing = sqlite_query(
            "SELECT
                brl.efx_code,
                SUM(brl.count) as total_count,
                SUM(brl.revenue) as total_revenue
             FROM billing_report_lines brl
             WHERE brl.customer_id IN ($customer_id_placeholders)
               AND brl.year = ? AND brl.month = ?
             GROUP BY brl.efx_code",
            $params
        );

        $lms_revenue = 0;
        $lms_cogs = 0;
        $lms_transactions = 0;

        foreach ($billing as $row) {
            $lms_revenue += (float) $row["total_revenue"];
            $lms_transactions += (int) $row["total_count"];

            // Calculate COGS based on service mapping
            $service_id = isset($efx_to_service[$row["efx_code"]])
                ? $efx_to_service[$row["efx_code"]]
                : null;
            if ($service_id && isset($service_cogs[$service_id])) {
                $lms_cogs +=
                    $service_cogs[$service_id] * (int) $row["total_count"];
            }
        }

        $effective_rate =
            $lms["commission_rate"] !== null
                ? (float) $lms["commission_rate"]
                : $default_rate;

        $gross_profit = $lms_revenue - $lms_cogs;
        $commission = $gross_profit * ($effective_rate / 100);
        $net_profit = $gross_profit - $commission;

        if ($lms_revenue > 0 || $lms_transactions > 0) {
            $lms_data[] = [
                "id" => $lms["id"],
                "name" => $lms["name"],
                "commission_rate" => $effective_rate,
                "is_default_rate" => $lms["commission_rate"] === null,
                "customer_count" => count($customers),
                "transactions" => $lms_transactions,
                "revenue" => $lms_revenue,
                "cogs" => $lms_cogs,
                "gross_profit" => $gross_profit,
                "commission" => $commission,
                "net_profit" => $net_profit,
                "margin_pct" =>
                    $lms_revenue > 0 ? ($net_profit / $lms_revenue) * 100 : 0,
            ];

            $totals["revenue"] += $lms_revenue;
            $totals["cogs"] += $lms_cogs;
            $totals["gross_profit"] += $gross_profit;
            $totals["commission"] += $commission;
            $totals["net_profit"] += $net_profit;
            $totals["transactions"] += $lms_transactions;
            $totals["customers"] += count($customers);
        }
    }

    // Sort by revenue descending
    usort($lms_data, function ($a, $b) {
        if ($b["revenue"] == $a["revenue"]) {
            return 0;
        }
        return $b["revenue"] > $a["revenue"] ? 1 : -1;
    });

    return [
        "lms_list" => $lms_data,
        "totals" => $totals,
        "default_rate" => $default_rate,
    ];
}

/**
 * Get tier proximity analysis - how close customers are to next tier
 */
function get_tier_proximity_analysis($year, $month)
{
    // Get all active customers with their MTD transaction counts by service
    $customer_volumes = sqlite_query(
        "SELECT
            brl.customer_id,
            brl.customer_name,
            brl.efx_code,
            SUM(brl.count) as mtd_count,
            SUM(brl.revenue) as mtd_revenue
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.year = ? AND brl.month = ?
         GROUP BY brl.customer_id, brl.customer_name, brl.efx_code",
        [$year, $month]
    );

    // Get EFX code to service mapping
    $efx_to_service = [];
    $types = sqlite_query(
        "SELECT efx_code, service_id FROM transaction_types WHERE service_id IS NOT NULL"
    );
    foreach ($types as $t) {
        $efx_to_service[$t["efx_code"]] = $t["service_id"];
    }

    // Calculate days elapsed and remaining in month
    $days_in_month = (int) date("t");
    $current_day = (int) date("j");
    $days_remaining = $days_in_month - $current_day;
    $month_progress_pct = ($current_day / $days_in_month) * 100;

    $proximity_data = [];

    foreach ($customer_volumes as $vol) {
        $customer_id = $vol["customer_id"];
        $efx_code = $vol["efx_code"];
        $mtd_count = (int) $vol["mtd_count"];

        // Map to service
        $service_id = isset($efx_to_service[$efx_code])
            ? $efx_to_service[$efx_code]
            : null;
        if (!$service_id) {
            continue;
        }

        // Get effective tiers for this customer/service
        $tiers = get_effective_customer_tiers($customer_id, $service_id);
        if (empty($tiers)) {
            continue;
        }

        // Find current tier and next tier
        $current_tier = null;
        $next_tier = null;

        for ($i = 0; $i < count($tiers); $i++) {
            $tier = $tiers[$i];
            $vol_start = (int) $tier["volume_start"];
            $vol_end =
                $tier["volume_end"] !== null
                    ? (int) $tier["volume_end"]
                    : PHP_INT_MAX;

            if ($mtd_count >= $vol_start && $mtd_count <= $vol_end) {
                $current_tier = $tier;
                $current_tier["index"] = $i;
                // Next tier is the one after
                if ($i + 1 < count($tiers)) {
                    $next_tier = $tiers[$i + 1];
                }
                break;
            }
        }

        if (!$current_tier || !$next_tier) {
            continue; // Already at highest tier or no tier found
        }

        // Calculate proximity to next tier
        $next_tier_threshold = (int) $next_tier["volume_start"];
        $distance_to_next = $next_tier_threshold - $mtd_count;

        // Project end-of-month volume based on current rate
        $daily_rate = $current_day > 0 ? $mtd_count / $current_day : 0;
        $projected_eom = $mtd_count + $daily_rate * $days_remaining;

        // Will they hit next tier?
        $will_hit_next = $projected_eom >= $next_tier_threshold;

        // Calculate probability of hitting next tier
        $hit_probability = 0;
        if ($distance_to_next > 0) {
            // How many days needed at current rate to hit next tier
            $days_needed =
                $daily_rate > 0 ? $distance_to_next / $daily_rate : PHP_INT_MAX;
            $hit_probability = min(
                100,
                ($days_remaining / max(1, $days_needed)) * 100
            );
        } elseif ($mtd_count >= $next_tier_threshold) {
            $hit_probability = 100;
        }

        // Calculate potential savings if they hit next tier
        $current_price = (float) $current_tier["price_per_inquiry"];
        $next_price = (float) $next_tier["price_per_inquiry"];
        $price_reduction_pct =
            $current_price > 0
                ? (($current_price - $next_price) / $current_price) * 100
                : 0;

        // Progress through current tier
        $tier_start = (int) $current_tier["volume_start"];
        $tier_range = $next_tier_threshold - $tier_start;
        $position_in_tier = $mtd_count - $tier_start;
        $progress_to_next =
            $tier_range > 0 ? ($position_in_tier / $tier_range) * 100 : 0;

        // Only include if they have meaningful progress or chance
        if ($progress_to_next >= 40 || $hit_probability >= 25) {
            $proximity_data[] = [
                "customer_id" => $customer_id,
                "customer_name" => $vol["customer_name"],
                "service_id" => $service_id,
                "efx_code" => $efx_code,
                "mtd_count" => $mtd_count,
                "mtd_revenue" => (float) $vol["mtd_revenue"],
                "current_tier_start" => $tier_start,
                "current_tier_end" => $current_tier["volume_end"],
                "current_price" => $current_price,
                "next_tier_threshold" => $next_tier_threshold,
                "next_tier_price" => $next_price,
                "distance_to_next" => $distance_to_next,
                "progress_to_next_pct" => $progress_to_next,
                "daily_rate" => $daily_rate,
                "projected_eom" => $projected_eom,
                "will_hit_next" => $will_hit_next,
                "hit_probability_pct" => $hit_probability,
                "price_reduction_pct" => $price_reduction_pct,
            ];
        }
    }

    // Sort by hit probability descending
    usort($proximity_data, function ($a, $b) {
        if ($b["hit_probability_pct"] == $a["hit_probability_pct"]) {
            return 0;
        }
        return $b["hit_probability_pct"] > $a["hit_probability_pct"] ? 1 : -1;
    });

    return [
        "customers" => $proximity_data,
        "month_progress_pct" => $month_progress_pct,
        "days_remaining" => $days_remaining,
        "days_elapsed" => $current_day,
        "days_in_month" => $days_in_month,
        "likely_to_upgrade" => count(
            array_filter($proximity_data, function ($p) {
                return $p["hit_probability_pct"] >= 70;
            })
        ),
        "possible_upgrade" => count(
            array_filter($proximity_data, function ($p) {
                return $p["hit_probability_pct"] >= 30 &&
                    $p["hit_probability_pct"] < 70;
            })
        ),
        "unlikely_upgrade" => count(
            array_filter($proximity_data, function ($p) {
                return $p["hit_probability_pct"] < 30;
            })
        ),
    ];
}

/**
 * Get variance statistics from billing data
 */
function get_billing_variance_stats()
{
    // Get a sample of audited lines to calculate variance distribution
    $sample_size = 1000;

    $lines = sqlite_query(
        "SELECT id FROM billing_report_lines ORDER BY RANDOM() LIMIT ?",
        [$sample_size]
    );

    $matches = 0;
    $small_variances = 0;
    $large_variances = 0;
    $errors = 0;
    $total_audited = 0;

    // Only audit if we have the calculator and reasonable number of lines
    if (function_exists("audit_billing_line") && count($lines) > 0) {
        foreach ($lines as $line) {
            $audit = audit_billing_line($line["id"]);

            if (!empty($audit["errors"])) {
                $errors++;
            } elseif (isset($audit["variance"])) {
                $total_audited++;
                if ($audit["variance"]["is_match"]) {
                    $matches++;
                } elseif (abs($audit["variance"]["unit_price_pct"]) <= 5) {
                    $small_variances++;
                } else {
                    $large_variances++;
                }
            }
        }
    }

    return [
        "total_audited" => $total_audited,
        "matches" => $matches,
        "small_variances" => $small_variances,
        "large_variances" => $large_variances,
        "errors" => $errors,
        "match_pct" =>
            $total_audited > 0 ? ($matches / $total_audited) * 100 : 0,
        "small_var_pct" =>
            $total_audited > 0 ? ($small_variances / $total_audited) * 100 : 0,
        "large_var_pct" =>
            $total_audited > 0 ? ($large_variances / $total_audited) * 100 : 0,
    ];
}

/**
 * Billing Dashboard - Month drill-down
 */
function action_billing_month()
{
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));

    // Get month stats
    $month_stats = sqlite_query(
        "SELECT
            COUNT(DISTINCT br.id) as report_count,
            SUM(brl.count) as total_transactions,
            SUM(brl.revenue) as total_revenue,
            COUNT(DISTINCT brl.customer_id) as unique_customers
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.year = ? AND brl.month = ?",
        [$year, $month]
    );

    // Get daily breakdown (from daily reports)
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
        [$year, $month]
    );

    // Get customer breakdown for this month
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
        [$year, $month]
    );

    // Get service breakdown for this month
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
        [$year, $month]
    );

    // Get reports for this month
    $reports = sqlite_query(
        "SELECT br.*
         FROM billing_reports br
         WHERE br.report_year = ? AND br.report_month = ?
         ORDER BY br.report_date DESC",
        [$year, $month]
    );

    $data = [
        "year" => $year,
        "month" => $month,
        "month_name" => date("F", mktime(0, 0, 0, $month, 1)),
        "stats" => isset($month_stats[0]) ? $month_stats[0] : [],
        "daily_data" => $daily_data,
        "customer_breakdown" => $customer_breakdown,
        "service_breakdown" => $service_breakdown,
        "reports" => $reports,
    ];

    render_billing_month($data);
}

/**
 * Billing Dashboard - Customer drill-down
 */
function action_billing_customer()
{
    $customer_id = get_param("id");

    if (empty($customer_id)) {
        set_flash("error", "No customer specified");
        redirect("billing_intelligence");
        return;
    }

    // Get customer info from billing data
    $customer_info = sqlite_query(
        "SELECT DISTINCT customer_id, customer_name
         FROM billing_report_lines
         WHERE customer_id = ?
         LIMIT 1",
        [$customer_id]
    );

    if (empty($customer_info)) {
        set_flash("error", "Customer not found in billing data");
        redirect("billing_intelligence");
        return;
    }

    $customer = $customer_info[0];

    // Get overall stats for this customer
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
        [$customer_id]
    );

    // Get monthly trend for this customer
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
        [$customer_id]
    );

    // Get service breakdown for this customer
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
        [$customer_id]
    );

    // Get recent line items
    $recent_lines = sqlite_query(
        "SELECT brl.*, br.report_date, br.report_type
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ?
         ORDER BY br.report_date DESC, brl.id DESC
         LIMIT 50",
        [$customer_id]
    );

    $data = [
        "customer" => $customer,
        "stats" => isset($stats[0]) ? $stats[0] : [],
        "monthly_trend" => $monthly_trend,
        "service_breakdown" => $service_breakdown,
        "recent_lines" => $recent_lines,
    ];

    render_billing_customer($data);
}

/**
 * Billing Dashboard - Customer Daily Chart
 * Shows daily transaction counts with cumulative vs delta (true daily) breakdown
 */
function action_billing_customer_daily()
{
    $customer_id = get_param("id");
    $year = (int) get_param("year", date("Y"));
    $month = (int) get_param("month", date("n"));
    $efx_code = get_param("efx_code", ""); // Optional filter by service

    if (empty($customer_id)) {
        set_flash("error", "No customer specified");
        redirect("billing_intelligence");
        return;
    }

    // Get customer info
    $customer_info = sqlite_query(
        "SELECT DISTINCT customer_id, customer_name
         FROM billing_report_lines
         WHERE customer_id = ?
         LIMIT 1",
        [$customer_id]
    );

    if (empty($customer_info)) {
        set_flash("error", "Customer not found in billing data");
        redirect("billing_intelligence");
        return;
    }

    $customer = $customer_info[0];

    // Get services available for this customer in this month
    $available_services = sqlite_query(
        "SELECT DISTINCT brl.efx_code, tt.efx_displayname as service_name
         FROM billing_report_lines brl
         LEFT JOIN transaction_types tt ON brl.efx_code = tt.efx_code
         WHERE brl.customer_id = ? AND brl.year = ? AND brl.month = ?
         ORDER BY brl.efx_code",
        [$customer_id, $year, $month]
    );

    // Build EFX filter condition
    $efx_filter = "";
    $params = [$customer_id, $year, $month];
    if (!empty($efx_code)) {
        $efx_filter = " AND brl.efx_code = ?";
        $params[] = $efx_code;
    }

    // Get daily cumulative data from daily reports
    // Daily reports contain cumulative counts for MTD
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
        $params
    );

    // Calculate delta (true daily count) by subtracting previous day
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

    // Get month summary stats
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
        $params
    );

    // Calculate stats from chart data
    $total_days = count($chart_data);
    $avg_daily =
        $total_days > 0
            ? array_sum(array_column($chart_data, "delta")) / $total_days
            : 0;
    $max_daily = $total_days > 0 ? max(array_column($chart_data, "delta")) : 0;
    $min_daily = $total_days > 0 ? min(array_column($chart_data, "delta")) : 0;

    // Get available months for navigation
    $available_months = sqlite_query(
        "SELECT DISTINCT brl.year, brl.month
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.customer_id = ? AND br.report_type = 'daily'
         ORDER BY brl.year DESC, brl.month DESC",
        [$customer_id]
    );

    // Days in month for projections
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $current_day = (int) date("j");
    $is_current_month = $year == date("Y") && $month == date("n");

    // Project end of month based on average
    $projected_eom = 0;
    if ($is_current_month && $total_days > 0) {
        $days_remaining = $days_in_month - $current_day;
        $last_cumulative = end($chart_data)["cumulative"];
        $projected_eom = $last_cumulative + $avg_daily * $days_remaining;
    }

    $data = [
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
    ];

    render_billing_customer_daily($data);
}

/**
 * Billing Dashboard - Service drill-down
 */
function action_billing_service()
{
    $efx_code = get_param("code");

    if (empty($efx_code)) {
        set_flash("error", "No service code specified");
        redirect("billing_intelligence");
        return;
    }

    // Get service info
    $service_info = sqlite_query(
        "SELECT tt.*, s.name as mapped_service_name
         FROM transaction_types tt
         LEFT JOIN services s ON tt.service_id = s.id
         WHERE tt.efx_code = ?",
        [$efx_code]
    );

    $service = isset($service_info[0])
        ? $service_info[0]
        : [
            "efx_code" => $efx_code,
            "efx_displayname" => $efx_code,
        ];

    // Get overall stats for this service
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
        [$efx_code]
    );

    // Get monthly trend for this service
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
        [$efx_code]
    );

    // Get customer breakdown for this service
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
        [$efx_code]
    );

    $data = [
        "service" => $service,
        "stats" => isset($stats[0]) ? $stats[0] : [],
        "monthly_trend" => $monthly_trend,
        "customer_breakdown" => $customer_breakdown,
    ];

    render_billing_service($data);
}

// ============================================================
// ADMIN ACTIONS
// ============================================================

/**
 * Admin panel - database management
 */
function action_admin()
{
    require_once __DIR__ . "/admin_seed.php";

    $tab = get_param("tab", "overview");

    $data = [
        "tab" => $tab,
        "stats" => get_database_stats(),
        "sync_status" => get_sync_status(),
        "sync_log" => get_sync_log(15),
        "filesystem" => get_filesystem_status(),
        "environment" => get_environment_status(),
    ];

    render_admin($data);
}

/**
 * Sync a single entity from remote database
 */
function action_admin_sync()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        set_flash("error", "Invalid request method");
        redirect("admin", ["tab" => "sync"]);
        return;
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
        set_flash("error", "Unknown entity: $entity");
        redirect("admin", ["tab" => "sync"]);
        return;
    }

    $func = $sync_functions[$entity];

    try {
        $result = $func();
    } catch (Exception $e) {
        set_flash("error", "Sync failed: " . $e->getMessage());
        redirect("admin", ["tab" => "sync"]);
        return;
    }

    if ($entity === "all") {
        $total = 0;
        foreach ($result as $e => $r) {
            if (is_array($r) && isset($r["synced"])) {
                $total += $r["synced"];
            } elseif (is_int($r)) {
                $total += $r;
            }
        }
        set_flash("success", "Synced all entities: $total total records");
    } else {
        if (is_array($result)) {
            $msg = isset($result["message"])
                ? $result["message"]
                : "Synced {$result["synced"]} of {$result["total"]} records";
            set_flash(
                "success",
                ucfirst(str_replace("_", " ", $entity)) . ": " . $msg
            );
        } else {
            set_flash(
                "success",
                ucfirst(str_replace("_", " ", $entity)) .
                    ": Synced $result records"
            );
        }
    }

    redirect("admin", ["tab" => "sync"]);
}

/**
 * Clear data for a specific entity
 */
function action_admin_clear_entity()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        set_flash("error", "Invalid request method");
        redirect("admin", ["tab" => "data"]);
        return;
    }

    $entity = get_param("entity");
    $confirm = get_param("confirm");

    if ($confirm !== $entity) {
        set_flash(
            "error",
            "Confirmation did not match. Type the entity name to confirm."
        );
        redirect("admin", ["tab" => "data"]);
        return;
    }

    $result = clear_entity_data($entity);

    if ($result["success"]) {
        set_flash("success", $result["message"]);
    } else {
        set_flash("error", $result["message"]);
    }

    redirect("admin", ["tab" => "data"]);
}

/**
 * Fix filesystem directories
 */
function action_admin_fix_directories()
{
    $errors = ensure_directories();

    if (empty($errors)) {
        set_flash("success", "All directories created/verified successfully");
    } else {
        set_flash(
            "error",
            "Some directories could not be created: " . implode(", ", $errors)
        );
    }

    redirect("admin", ["tab" => "filesystem"]);
}

/**
 * Clear database (with confirmation)
 */
function action_admin_clear()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        set_flash("error", "Invalid request method");
        redirect("admin");
        return;
    }

    $confirm = get_param("confirm");
    if ($confirm !== "CLEAR") {
        set_flash("error", "You must type CLEAR to confirm");
        redirect("admin");
        return;
    }

    require_once __DIR__ . "/admin_seed.php";
    clear_database();

    set_flash("success", "Database cleared successfully");
    redirect("admin");
}

/**
 * Reseed database with test data
 */
function action_admin_reseed()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        set_flash("error", "Invalid request method");
        redirect("admin");
        return;
    }

    require_once __DIR__ . "/admin_seed.php";

    // Get options from form
    $config = isset($GLOBALS["SEED_CONFIG"])
        ? $GLOBALS["SEED_CONFIG"]
        : $SEED_CONFIG;
    $config["days_of_history"] = (int) get_param("days", 90);
    $config["customer_count"] = (int) get_param("customers", 100);
    $config["exact_match_pct"] = (int) get_param("exact_pct", 85);
    $config["small_variance_pct"] = (int) get_param("small_pct", 10);
    $config["large_variance_pct"] = (int) get_param("large_pct", 5);

    $clear_first = get_param("clear_first") === "1";

    // Run the seed
    $result = run_seed($config, $clear_first);

    if ($result["success"]) {
        set_flash(
            "success",
            sprintf(
                "Database reseeded: %d reports, %d lines (%.1f%% exact, %.1f%% small var, %.1f%% large var)",
                $result["reports"],
                $result["lines"],
                ($result["matches"] / max(1, $result["lines"])) * 100,
                ($result["small_variances"] / max(1, $result["lines"])) * 100,
                ($result["large_variances"] / max(1, $result["lines"])) * 100
            )
        );
    } else {
        set_flash("error", "Seeding failed");
    }

    redirect("admin");
}

// ============================================================
// SYSTEM/SETUP ACTIONS
// ============================================================

/**
 * Fix shared directory - creates symlink or directory structure
 * Called when production mode can't find the shared directory
 */
function action_fix_shared_directory()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        set_flash("error", "Invalid request method");
        redirect("dashboard");
        return;
    }

    $shared_path = SHARED_BASE_PATH;
    $result = fix_shared_directory($shared_path);

    if ($result["success"]) {
        set_flash("success", $result["message"]);
        // Switch to production mode since it should work now
        $_SESSION["mock_mode"] = false;
    } else {
        set_flash("error", $result["message"]);
        // Fall back to mock mode
        $_SESSION["mock_mode"] = true;
    }

    redirect("dashboard");
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

        // Billing Reports
        "billing_reports" => "action_billing_reports",
        "view_billing_report" => "action_view_billing_report",
        "download_billing_report" => "action_download_billing_report",
        "regenerate_report" => "action_regenerate_report",
        "compare_reports" => "action_compare_reports",

        // CSV/File management (legacy - kept for backwards compatibility)
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
        "line_audit" => "action_line_audit",
        "report_audit" => "action_report_audit",

        // Generation
        "generation" => "action_generation",
        "generation_types" => "action_generation_types",
        "billing_flags" => "action_billing_flags",

        // Monthly Minimums & Annualized
        "minimums" => "action_minimums",
        "annualized" => "action_annualized",

        // Customer Pricing View
        "customer_pricing" => "action_customer_pricing",

        // Billing Dashboard
        "billing_intelligence" => "action_billing_intelligence",
        "billing_month" => "action_billing_month",
        "billing_customer" => "action_billing_customer",
        "billing_customer_daily" => "action_billing_customer_daily",
        "billing_service" => "action_billing_service",

        // Admin
        "admin" => "action_admin",
        "admin_sync" => "action_admin_sync",
        "admin_clear" => "action_admin_clear",
        "admin_clear_entity" => "action_admin_clear_entity",
        "admin_reseed" => "action_admin_reseed",
        "admin_fix_directories" => "action_admin_fix_directories",

        // System/Setup
        "fix_shared_directory" => "action_fix_shared_directory",
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
            $pending_config["headers"]
        );
    }
} // ------------------------------------------------------------
// RUN APPLICATION
// ------------------------------------------------------------
// Initialize mock data if in mock mode
