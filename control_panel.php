<?php
// ============================================================
// CONTROL PANEL - Entry Point
// PHP 5.6 Compatible
// ============================================================

// Set timezone to avoid warnings in PHP 5.6
date_default_timezone_set("UTC");

// Start session early (needed for mock mode persistence)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// ENVIRONMENT DETECTION
// ------------------------------------------------------------
// CODE_ENVIRONMENT can be: dev, rc, live, mock_prod (default)
// Set via environment variable, .env file, or defaults to mock_prod

$_detected_env = getenv("CODE_ENVIRONMENT");
if ($_detected_env === false || $_detected_env === "") {
    // Try $_ENV as fallback
    $_detected_env = isset($_ENV["CODE_ENVIRONMENT"])
        ? $_ENV["CODE_ENVIRONMENT"]
        : "";
}
if ($_detected_env === "") {
    // Try $_SERVER as fallback (Apache SetEnv)
    $_detected_env = isset($_SERVER["CODE_ENVIRONMENT"])
        ? $_SERVER["CODE_ENVIRONMENT"]
        : "";
}
// Validate and default to "default" (not mock_prod)
$_valid_envs = ["dev", "rc", "live", "mock_prod", "default"];
if (!in_array($_detected_env, $_valid_envs)) {
    $_detected_env = "default";
}
define("CODE_ENVIRONMENT", $_detected_env);

// ------------------------------------------------------------
// ENVIRONMENT-SPECIFIC CONFIGURATION
// ------------------------------------------------------------

// Environment configurations: paths and settings per environment
$_env_configs = [
    // Default: local default_shared folder, starts EMPTY, requires fix button
    // This is what you get if no CODE_ENVIRONMENT is set
    "default" => [
        "shared_base_path" => __DIR__ . "/default_shared",
        "default_mock_mode" => false,
        "db_path" => __DIR__ . "/default_shared/control_panel.db",
        "remote_db_host" => "",
        "remote_db_name" => "",
        "remote_db_user" => "",
        "remote_db_pass" => "",
        "description" => "Default (local folder, no test data)",
    ],

    // Development: local test_shared folder, always mock mode
    "dev" => [
        "shared_base_path" => __DIR__ . "/test_shared",
        "default_mock_mode" => true,
        "db_path" => __DIR__ . "/test_shared/control_panel.db",
        "remote_db_host" => "",
        "remote_db_name" => "",
        "remote_db_user" => "",
        "remote_db_pass" => "",
        "description" => "Development (local test data)",
    ],

    // Release Candidate: staging paths, can toggle mock
    "rc" => [
        "shared_base_path" => "/var/www/rc/shared",
        "default_mock_mode" => true,
        "db_path" => "/var/www/rc/data/control_panel.db",
        "remote_db_host" => "staging-db.internal",
        "remote_db_name" => "billing_staging",
        "remote_db_user" => "billing_ro",
        "remote_db_pass" => getenv("RC_DB_PASS") ?: "",
        "description" => "Release Candidate (staging)",
    ],

    // Live/Production: real paths, real database
    "live" => [
        "shared_base_path" => "/mnt/billing_share",
        "default_mock_mode" => false,
        "db_path" => "/var/www/billing/data/control_panel.db",
        "remote_db_host" => "prod-db.internal",
        "remote_db_name" => "billing_prod",
        "remote_db_user" => "billing_ro",
        "remote_db_pass" => getenv("PROD_DB_PASS") ?: "",
        "description" => "Production (live data)",
    ],

    // Mock Production: simulates production structure WITH test data
    "mock_prod" => [
        "shared_base_path" => __DIR__ . "/test_shared",
        "default_mock_mode" => true,
        "db_path" => __DIR__ . "/test_shared/control_panel.db",
        "remote_db_host" => "",
        "remote_db_name" => "",
        "remote_db_user" => "",
        "remote_db_pass" => "",
        "description" => "Mock Production (local with test data)",
    ],
];

// Get current environment config
$_current_config = $_env_configs[CODE_ENVIRONMENT];

// ------------------------------------------------------------
// APPLY CONFIGURATION
// ------------------------------------------------------------

// Shared drive path (environment-specific)
define("SHARED_BASE_PATH", $_current_config["shared_base_path"]);

// Database path
define("SQLITE_DB_PATH", $_current_config["db_path"]);

// Remote database connection (for production sync)
define("REMOTE_DB_HOST", $_current_config["remote_db_host"]);
define("REMOTE_DB_NAME", $_current_config["remote_db_name"]);
define("REMOTE_DB_USER", $_current_config["remote_db_user"]);
define("REMOTE_DB_PASS", $_current_config["remote_db_pass"]);

// Environment description (for UI display)
define("ENV_DESCRIPTION", $_current_config["description"]);

// ------------------------------------------------------------
// MOCK MODE CONFIGURATION
// ------------------------------------------------------------

// MOCK MODE: Can be toggled via URL parameter ?mock=1 or ?mock=0
// Persists in session, defaults based on environment
if (isset($_GET["mock"])) {
    $_SESSION["mock_mode"] = $_GET["mock"] === "1" || $_GET["mock"] === "true";
}
if (!isset($_SESSION["mock_mode"])) {
    $_SESSION["mock_mode"] = $_current_config["default_mock_mode"];
}
define("MOCK_MODE", $_SESSION["mock_mode"]);

// Default mock mode setting (used when session is cleared)
define("DEFAULT_MOCK_MODE", $_current_config["default_mock_mode"]);

// Subdirectories
define("PATH_GENERATED", SHARED_BASE_PATH . "/generated");
define("PATH_PENDING", SHARED_BASE_PATH . "/pending");
define("PATH_ARCHIVE", SHARED_BASE_PATH . "/archive");

// File patterns
define("REPORT_PREFIX", "DataX_");
define("CONFIG_PREFIX", "config_");

// Pagination
define("ITEMS_PER_PAGE", 50);

// ------------------------------------------------------------
// INCLUDE FILES
// Order matters - dependencies must be loaded first
// ------------------------------------------------------------

require_once __DIR__ . "/helpers.php"; // Utilities, CSV, paths (no dependencies)
require_once __DIR__ . "/db.php"; // Database functions (depends on helpers for paths)
require_once __DIR__ . "/data.php"; // Data access (depends on db, helpers)
require_once __DIR__ . "/actions.php"; // Controllers (depends on all above)
require_once __DIR__ . "/views.php"; // Views (depends on helpers)

// ------------------------------------------------------------
// RUN APPLICATION
// ------------------------------------------------------------

// ------------------------------------------------------------
// ENVIRONMENT CHECK
// ------------------------------------------------------------

// Check for shared directory issues (only in production mode)
// Handle fix_shared_directory action BEFORE database init
$current_action = isset($_GET["action"]) ? $_GET["action"] : "";
$is_fix_action = $current_action === "fix_shared_directory";

if (!MOCK_MODE) {
    $shared_path = SHARED_BASE_PATH;

    if (!is_dir($shared_path) || !is_readable($shared_path)) {
        if ($is_fix_action) {
            // Run fix action directly - can't use normal routing since DB isn't available
            require_once __DIR__ . "/helpers.php";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $result = fix_shared_directory($shared_path);
                if ($result["success"]) {
                    set_flash("success", $result["message"]);
                } else {
                    set_flash("error", $result["message"]);
                }
            }
            // Redirect to dashboard (will show error page again if fix failed)
            header("Location: ?action=dashboard");
            exit();
        }

        // Show friendly error page with fix button
        render_shared_directory_error($shared_path);
        exit();
    }
}

// ------------------------------------------------------------
// INITIALIZE
// ------------------------------------------------------------

// Initialize mock data if in mock mode
init_mock_data();

// Initialize SQLite and seed mock data
sqlite_db();
sqlite_seed_mock_data();

// Route the request
route();
