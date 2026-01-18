<?php
// ============================================================
// CONTROL PANEL - Entry Point
// PHP 5.6 Compatible
// ============================================================

// Set timezone to avoid warnings in PHP 5.6
date_default_timezone_set("UTC");

// ------------------------------------------------------------
// CONFIGURATION
// ------------------------------------------------------------

// MOCK MODE: Set to true for local testing, false for production
define("MOCK_MODE", true);

// Shared drive symlink path (customize this for production)
define("SHARED_BASE_PATH", "/var/www/html/shared");

// Subdirectories (used when MOCK_MODE is false)
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

// Initialize mock data if in mock mode
init_mock_data();

// Initialize SQLite and seed mock data
sqlite_db();
sqlite_seed_mock_data();

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Route the request
route();
