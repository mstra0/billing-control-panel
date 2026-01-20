<?php
// ============================================================
// HELPER FUNCTIONS
// Utilities, CSV, file management, request handling
// ============================================================

// ------------------------------------------------------------
// PAGINATION & UI HELPERS
// ------------------------------------------------------------

/**
 * Simple pagination info
 *
 * @param int $total      Total records
 * @param int $page       Current page
 * @param int $per_page   Items per page
 * @return array          Pagination info
 */
function paginate($total, $page = 1, $per_page = ITEMS_PER_PAGE)
{
    $total_pages = max(1, ceil($total / $per_page));
    $page = max(1, min($page, $total_pages));
    return [
        "total" => $total,
        "per_page" => $per_page,
        "current" => $page,
        "total_pages" => $total_pages,
        "has_prev" => $page > 1,
        "has_next" => $page < $total_pages,
        "from" => $total > 0 ? ($page - 1) * $per_page + 1 : 0,
        "to" => min($page * $per_page, $total),
    ];
}

/**
 * Render pagination HTML with numbered pages
 * @param array $pagination - from paginate()
 * @param string $base_url - base URL with action (e.g., "?action=customers")
 * @param array $params - additional query params to preserve (e.g., search, filters)
 */
function render_pagination($pagination, $base_url, $params = [])
{
    if ($pagination["total_pages"] <= 1) {
        return;
    }

    $current = $pagination["current"];
    $total_pages = $pagination["total_pages"];

    // Build URL helper
    $build_url = function ($page) use ($base_url, $params) {
        $params["page"] = $page;
        $query = http_build_query($params);
        return $base_url .
            (strpos($base_url, "?") !== false ? "&" : "?") .
            $query;
    };

    echo '<div class="pagination">';

    // Previous
    if ($pagination["has_prev"]) {
        echo '<a href="' . h($build_url($current - 1)) . '">&laquo; Prev</a>';
    } else {
        echo '<span class="disabled">&laquo; Prev</span>';
    }

    // Page numbers with ellipsis
    $range = 2;
    $start = max(1, $current - $range);
    $end = min($total_pages, $current + $range);

    // Always show first page
    if ($start > 1) {
        echo '<a href="' . h($build_url(1)) . '">1</a>';
        if ($start > 2) {
            echo '<span class="ellipsis">...</span>';
        }
    }

    // Middle pages
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current) {
            echo '<span class="active">' . $i . "</span>";
        } else {
            echo '<a href="' . h($build_url($i)) . '">' . $i . "</a>";
        }
    }

    // Always show last page
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            echo '<span class="ellipsis">...</span>';
        }
        echo '<a href="' .
            h($build_url($total_pages)) .
            '">' .
            $total_pages .
            "</a>";
    }

    // Next
    if ($pagination["has_next"]) {
        echo '<a href="' . h($build_url($current + 1)) . '">Next &raquo;</a>';
    } else {
        echo '<span class="disabled">Next &raquo;</span>';
    }

    echo "</div>";

    // Info text
    echo '<div class="pagination-info">';
    echo "Showing " .
        $pagination["from"] .
        "-" .
        $pagination["to"] .
        " of " .
        $pagination["total"];
    echo "</div>";
}

/**
 * Render search bar HTML
 * @param string $action - the action name
 * @param array $options - search options
 */
function render_search_bar($action, $options = [])
{
    $search = isset($options["search"]) ? $options["search"] : "";
    $placeholder = isset($options["placeholder"])
        ? $options["placeholder"]
        : "Search...";
    $filters = isset($options["filters"]) ? $options["filters"] : [];
    $extra_params = isset($options["params"]) ? $options["params"] : [];

    echo '<form method="GET" class="search-bar">';
    echo '<input type="hidden" name="action" value="' . h($action) . '">';

    // Extra hidden params
    foreach ($extra_params as $key => $value) {
        echo '<input type="hidden" name="' .
            h($key) .
            '" value="' .
            h($value) .
            '">';
    }

    // Search input
    echo '<input type="text" name="search" value="' .
        h($search) .
        '" placeholder="' .
        h($placeholder) .
        '">';

    // Filter dropdowns
    foreach ($filters as $filter) {
        $name = $filter["name"];
        $label = isset($filter["label"]) ? $filter["label"] : "";
        $filter_options = $filter["options"];
        $current = isset($filter["current"]) ? $filter["current"] : "";

        echo '<select name="' . h($name) . '">';
        foreach ($filter_options as $value => $text) {
            $selected = $current === (string) $value ? " selected" : "";
            echo '<option value="' .
                h($value) .
                '"' .
                $selected .
                ">" .
                h($text) .
                "</option>";
        }
        echo "</select>";
    }

    echo '<button type="submit" class="btn">Search</button>';

    // Clear button if search or filters are active
    $has_active = !empty($search);
    foreach ($filters as $filter) {
        if (!empty($filter["current"])) {
            $has_active = true;
            break;
        }
    }
    if ($has_active) {
        echo '<a href="?action=' .
            h($action) .
            '" class="btn" style="background: #6c757d;">Clear</a>';
    }

    echo "</form>";
}

// ------------------------------------------------------------
// PATH HELPER FUNCTIONS
// ------------------------------------------------------------

/**
 * Get the shared base path
 *
 * @return string
 */
function get_shared_path()
{
    if (MOCK_MODE) {
        return dirname(__FILE__) . "/test_shared";
    }

    // Fall back to local test_shared if SHARED_BASE_PATH doesn't exist or isn't writable
    if (!is_dir(SHARED_BASE_PATH) || !is_writable(SHARED_BASE_PATH)) {
        $fallback = dirname(__FILE__) . "/test_shared";
        if (!is_dir($fallback)) {
            mkdir($fallback, 0755, true);
        }
        return $fallback;
    }

    return SHARED_BASE_PATH;
}

/**
 * Get path for generated reports (cron output)
 *
 * @return string
 */
function get_generated_path()
{
    $path = get_shared_path() . "/generated";
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Get path for pending configs (dead-drop)
 *
 * @return string
 */
function get_pending_path()
{
    $path = get_shared_path() . "/pending";
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Get path for archived configs (historical)
 *
 * @return string
 */
function get_archive_path()
{
    $path = get_shared_path() . "/archive";
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Get path for reports archive
 *
 * @param string $subdir Optional subdirectory (tier_pricing, displayname_to_type, custom, ingestion)
 * @return string
 */
function get_reports_path($subdir = null)
{
    $base = get_shared_path() . "/reports";
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    if ($subdir) {
        $path = $base . "/" . $subdir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }
    return $base;
}

/**
 * Get path for temp files (regeneration, comparison)
 *
 * @return string
 */
function get_temp_path()
{
    $path = get_shared_path() . "/temp";
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

/**
 * Ensure all required directories exist
 *
 * @return array Errors if any directories couldn't be created
 */
function ensure_directories()
{
    $errors = [];
    $paths = [
        get_generated_path(),
        get_pending_path(),
        get_archive_path(),
        get_reports_path(),
        get_reports_path("tier_pricing"),
        get_reports_path("displayname_to_type"),
        get_reports_path("custom"),
        get_reports_path("ingestion"),
        get_temp_path(),
    ];

    foreach ($paths as $path) {
        if (!is_dir($path)) {
            if (!@mkdir($path, 0755, true)) {
                $errors[] = "Could not create directory: $path";
            }
        }
    }

    return $errors;
}

/**
 * Fix shared directory - attempts to create or symlink the shared directory
 *
 * This is a STUB - customize for your production environment
 * Options:
 *   1. Create the directory structure locally
 *   2. Create a symlink to an existing mount point
 *   3. Mount a network share
 *   4. Run a setup script
 *
 * @param string $path The expected shared directory path
 * @return array ['success' => bool, 'message' => string]
 */
function fix_shared_directory($path)
{
    // Environment-aware directory fix
    // Behavior depends on CODE_ENVIRONMENT: default, dev, rc, live, mock_prod

    $env = defined("CODE_ENVIRONMENT") ? CODE_ENVIRONMENT : "default";
    $subdirs = ["archive", "pending", "generated", "reports", "temp"];

    switch ($env) {
        case "default":
            // Default: Create local default_shared folder (starts empty)
            // User must click fix button to create the structure
            return _fix_create_local_dirs($path, $subdirs);

        case "dev":
        case "mock_prod":
            // Development/Mock: Just create local directories (test_shared)
            return _fix_create_local_dirs($path, $subdirs);

        case "rc":
            // Release Candidate: Try create dirs, fall back to symlink
            $result = _fix_create_local_dirs($path, $subdirs);
            if ($result["success"]) {
                return $result;
            }
            // Try symlink to a staging mount
            return _fix_try_symlink($path, "/mnt/staging_share");

        case "live":
            // Production: Check mount, try remount, or fail gracefully
            return _fix_production_share($path, $subdirs);

        default:
            // Unknown environment: treat like default
            return _fix_create_local_dirs($path, $subdirs);
    }
}

/**
 * Helper: Create local directory structure
 */
function _fix_create_local_dirs($path, $subdirs)
{
    // Try to create the main directory
    if (!is_dir($path)) {
        if (!@mkdir($path, 0755, true)) {
            return [
                "success" => false,
                "message" => "Could not create directory: $path",
            ];
        }
    }

    // Create subdirectories
    $created = [];
    $failed = [];
    foreach ($subdirs as $subdir) {
        $subpath = $path . "/" . $subdir;
        if (!is_dir($subpath)) {
            if (@mkdir($subpath, 0755, true)) {
                $created[] = $subdir;
            } else {
                $failed[] = $subdir;
            }
        }
    }

    if (empty($failed)) {
        $msg = empty($created)
            ? "Directory structure already exists at: $path"
            : "Created directory structure at: $path (" .
                implode(", ", $created) .
                ")";
        return ["success" => true, "message" => $msg];
    }

    return [
        "success" => false,
        "message" =>
            "Could not create subdirectories: " . implode(", ", $failed),
    ];
}

/**
 * Helper: Try to create symlink to existing mount
 */
function _fix_try_symlink($path, $target)
{
    // Check if target exists
    if (!is_dir($target)) {
        return [
            "success" => false,
            "message" => "Symlink target does not exist: $target",
        ];
    }

    // Remove existing path if it's not a directory
    if (file_exists($path) && !is_dir($path)) {
        @unlink($path);
    }

    // Create symlink
    if (!file_exists($path) && @symlink($target, $path)) {
        return [
            "success" => true,
            "message" => "Created symlink: $path -> $target",
        ];
    }

    return [
        "success" => false,
        "message" => "Could not create symlink from $path to $target",
    ];
}

/**
 * Helper: Fix production share (mount check, remount attempt)
 */
function _fix_production_share($path, $subdirs)
{
    // Check if already mounted and accessible
    if (is_dir($path) && is_readable($path) && is_writable($path)) {
        // Verify subdirs exist
        $missing = [];
        foreach ($subdirs as $subdir) {
            if (!is_dir($path . "/" . $subdir)) {
                $missing[] = $subdir;
            }
        }
        if (empty($missing)) {
            return [
                "success" => true,
                "message" => "Production share is mounted and accessible at: $path",
            ];
        }
        // Try to create missing subdirs
        foreach ($missing as $subdir) {
            @mkdir($path . "/" . $subdir, 0755, true);
        }
        return [
            "success" => true,
            "message" =>
                "Created missing subdirectories: " . implode(", ", $missing),
        ];
    }

    // Check if mount point exists but not mounted
    if (is_dir($path) && !is_readable($path)) {
        // Attempt remount via script (if available)
        $mount_script = __DIR__ . "/scripts/mount_share.sh";
        if (file_exists($mount_script) && is_executable($mount_script)) {
            exec(
                "bash " . escapeshellarg($mount_script) . " 2>&1",
                $output,
                $return_code
            );
            if ($return_code === 0) {
                return [
                    "success" => true,
                    "message" => "Remounted production share via script",
                ];
            }
            return [
                "success" => false,
                "message" => "Mount script failed: " . implode("\n", $output),
            ];
        }

        return [
            "success" => false,
            "message" => "Share appears unmounted. Please run: mount $path (or check /etc/fstab)",
        ];
    }

    // Path doesn't exist at all
    return [
        "success" => false,
        "message" => "Production share path does not exist: $path. Check mount configuration.",
    ];
}

// ------------------------------------------------------------
// UTILITY FUNCTIONS
// ------------------------------------------------------------

/**
 * Safe string for filenames
 *
 * @param string $str
 * @return string
 */
function safe_filename($str)
{
    return preg_replace("/[^a-zA-Z0-9_\-\.]/", "_", $str);
}

/**
 * Generate timestamped filename
 *
 * @param string $prefix
 * @param string $extension
 * @return string
 */
function generate_filename($prefix, $extension = "csv")
{
    return $prefix . date("Y-m-d_His") . "." . $extension;
}

/**
 * Get human-readable file size
 *
 * @param int $bytes
 * @return string
 */
function format_filesize($bytes)
{
    $units = ["B", "KB", "MB", "GB"];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . " " . $units[$i];
}

/**
 * Sanitize output for HTML
 *
 * @param string $str
 * @return string
 */
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
}

/**
 * Format percentage
 *
 * @param float $float
 * @return string
 */
function format_percentage($float)
{
    return number_format($float, 1) . "%";
}

// ------------------------------------------------------------
// CSV FUNCTIONS
// ------------------------------------------------------------

/**
 * Parse a CSV file into array of associative arrays
 *
 * @param string $filepath
 * @return array|false Array of rows, or false on failure
 */
function csv_read($filepath)
{
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }

    $handle = fopen($filepath, "r");
    if ($handle === false) {
        return false;
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return false;
    }

    // Clean headers
    $headers = array_map(function ($h) {
        $h = trim($h);
        $h = preg_replace("/^\xEF\xBB\xBF/", "", $h);
        return $h;
    }, $headers);

    $rows = [];
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) === 1 && empty($data[0])) {
            continue;
        }

        $data = array_pad($data, count($headers), "");
        $data = array_slice($data, 0, count($headers));

        $rows[] = array_combine($headers, $data);
    }

    fclose($handle);
    return $rows;
}

/**
 * Write array of associative arrays to CSV
 *
 * @param string $filepath
 * @param array  $rows      Array of associative arrays
 * @param array  $headers   Optional headers
 * @return bool
 */
function csv_write($filepath, $rows, $headers = null)
{
    if (empty($rows) && empty($headers)) {
        return false;
    }

    $handle = fopen($filepath, "w");
    if ($handle === false) {
        return false;
    }

    if ($headers === null && !empty($rows)) {
        $headers = array_keys($rows[0]);
    }

    fputcsv($handle, $headers);

    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $header) {
            $line[] = isset($row[$header]) ? $row[$header] : "";
        }
        fputcsv($handle, $line);
    }

    fclose($handle);
    return true;
}

/**
 * Get CSV headers only
 *
 * @param string $filepath
 * @return array|false
 */
function csv_get_headers($filepath)
{
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }

    $handle = fopen($filepath, "r");
    if ($handle === false) {
        return false;
    }

    $headers = fgetcsv($handle);
    fclose($handle);

    if ($headers === false) {
        return false;
    }

    return array_map(function ($h) {
        $h = trim($h);
        $h = preg_replace("/^\xEF\xBB\xBF/", "", $h);
        return $h;
    }, $headers);
}

/**
 * Count rows in CSV (excluding header)
 *
 * @param string $filepath
 * @return int|false
 */
function csv_count_rows($filepath)
{
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }

    $count = 0;
    $handle = fopen($filepath, "r");
    if ($handle === false) {
        return false;
    }

    fgetcsv($handle);

    while (($data = fgetcsv($handle)) !== false) {
        if (!(count($data) === 1 && empty($data[0]))) {
            $count++;
        }
    }

    fclose($handle);
    return $count;
}

/**
 * Escape value for CSV output
 *
 * @param mixed $value
 * @return string
 */
function csv_escape($value)
{
    if ($value === null) {
        return "";
    }
    $value = (string) $value;
    if (
        strpos($value, ",") !== false ||
        strpos($value, '"') !== false ||
        strpos($value, "\n") !== false
    ) {
        return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
}

// ------------------------------------------------------------
// FILE LISTING FUNCTIONS
// ------------------------------------------------------------

/**
 * List files in a directory matching a pattern
 *
 * @param string $directory
 * @param string $pattern    Glob pattern
 * @param string $sort       'name', 'date', 'size'
 * @param string $order      'asc', 'desc'
 * @return array
 */
function list_files(
    $directory,
    $pattern = "*.csv",
    $sort = "date",
    $order = "desc"
) {
    $files = [];

    if (!is_dir($directory)) {
        return $files;
    }

    $glob_pattern = rtrim($directory, "/") . "/" . $pattern;
    $matches = glob($glob_pattern);

    if ($matches === false) {
        return $files;
    }

    foreach ($matches as $filepath) {
        if (is_file($filepath)) {
            $files[] = [
                "path" => $filepath,
                "name" => basename($filepath),
                "size" => filesize($filepath),
                "modified" => filemtime($filepath),
                "readable" => is_readable($filepath),
            ];
        }
    }

    usort($files, function ($a, $b) use ($sort, $order) {
        switch ($sort) {
            case "name":
                $cmp = strcasecmp($a["name"], $b["name"]);
                break;
            case "size":
                $cmp = $a["size"] - $b["size"];
                break;
            case "date":
            default:
                $cmp = $a["modified"] - $b["modified"];
                break;
        }
        return $order === "desc" ? -$cmp : $cmp;
    });

    return $files;
}

/**
 * List report files
 *
 * @return array
 */
function list_reports()
{
    return list_files(
        get_generated_path(),
        REPORT_PREFIX . "*.csv",
        "date",
        "desc"
    );
}

/**
 * List archived config files
 *
 * @return array
 */
function list_archived_configs()
{
    return list_files(
        get_archive_path(),
        CONFIG_PREFIX . "*.csv",
        "date",
        "desc"
    );
}

/**
 * List pending config files
 *
 * @return array
 */
function list_pending_configs()
{
    return list_files(
        get_pending_path(),
        CONFIG_PREFIX . "*.csv",
        "date",
        "desc"
    );
}

// ------------------------------------------------------------
// FILE OPERATIONS
// ------------------------------------------------------------

/**
 * Handle config CSV upload
 *
 * @param array  $uploaded_file  $_FILES array element
 * @param string $description    Optional description
 * @return array
 */
function handle_config_upload($uploaded_file, $description = "")
{
    $result = [
        "success" => false,
        "message" => "",
        "filename" => "",
    ];

    if ($uploaded_file["error"] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize",
            UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "Upload stopped by extension",
        ];
        $result["message"] = isset($errors[$uploaded_file["error"]])
            ? $errors[$uploaded_file["error"]]
            : "Unknown upload error";
        return $result;
    }

    $ext = strtolower(pathinfo($uploaded_file["name"], PATHINFO_EXTENSION));
    if ($ext !== "csv") {
        $result["message"] = "Only CSV files are allowed";
        return $result;
    }

    $headers = csv_get_headers($uploaded_file["tmp_name"]);
    if ($headers === false || empty($headers)) {
        $result["message"] = "Invalid CSV file: could not read headers";
        return $result;
    }

    $filename = generate_filename(CONFIG_PREFIX, "csv");

    $dir_errors = ensure_directories();
    if (!empty($dir_errors)) {
        $result["message"] = implode("; ", $dir_errors);
        return $result;
    }

    $pending_path = get_pending_path() . "/" . $filename;
    if (!move_uploaded_file($uploaded_file["tmp_name"], $pending_path)) {
        $result["message"] =
            "Failed to move uploaded file to pending directory";
        return $result;
    }

    $archive_path = get_archive_path() . "/" . $filename;
    if (!copy($pending_path, $archive_path)) {
        error_log("Warning: Could not archive config file to $archive_path");
    }

    $result["success"] = true;
    $result["message"] = "Config file uploaded successfully";
    $result["filename"] = $filename;

    return $result;
}

/**
 * Download a file
 *
 * @param string $filepath
 * @param string $download_name  Optional custom download filename
 * @return bool
 */
function download_file($filepath, $download_name = null)
{
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }

    if ($download_name === null) {
        $download_name = basename($filepath);
    }

    $mime = "text/csv";
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    if ($ext === "xlsx") {
        $mime =
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
    }

    header("Content-Type: " . $mime);
    header(
        "Content-Disposition: attachment; filename=\"" . $download_name . "\""
    );
    header("Content-Length: " . filesize($filepath));
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    readfile($filepath);
    return true;
}

/**
 * Validate a file path is within allowed directories
 *
 * @param string $filepath
 * @return bool
 */
function is_valid_filepath($filepath)
{
    $real_path = realpath($filepath);
    if ($real_path === false) {
        return false;
    }

    $allowed_dirs = [
        realpath(get_generated_path()),
        realpath(get_pending_path()),
        realpath(get_archive_path()),
    ];

    foreach ($allowed_dirs as $dir) {
        if ($dir !== false && strpos($real_path, $dir) === 0) {
            return true;
        }
    }

    return false;
}

// ------------------------------------------------------------
// REQUEST HANDLING
// ------------------------------------------------------------

/**
 * Get current action from request
 *
 * @return string
 */
function get_action()
{
    return isset($_GET["action"]) ? $_GET["action"] : "dashboard";
}

/**
 * Get request parameter (GET or POST)
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function get_param($key, $default = null)
{
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    return $default;
}

/**
 * Redirect to an action
 *
 * @param string $action
 * @param array  $params
 */
function redirect($action, $params = [])
{
    $params["action"] = $action;
    $url = "?" . http_build_query($params);
    header("Location: " . $url);
    exit();
}

/**
 * Set a flash message
 *
 * @param string $type    'success', 'error', 'info'
 * @param string $message
 */
function set_flash($type, $message)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

/**
 * Get and clear flash message
 *
 * @return array|null
 */
function get_flash()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $flash;
    }
    return null;
}
