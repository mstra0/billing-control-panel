<?php
/**
 * Load Test Data into Control Panel Database
 *
 * This script:
 * 1. Backs up the existing database (if any)
 * 2. Clears existing data
 * 3. Loads generated test data
 *
 * Usage: php load_test_data.php [--confirm] [--no-backup]
 */

// Check for confirmation flag
$confirmed = in_array("--confirm", $argv);
$no_backup = in_array("--no-backup", $argv);

if (!$confirmed) {
    echo "WARNING: This will CLEAR all existing data and load test data.\n\n";
    echo "Run with --confirm to proceed:\n";
    echo "  php load_test_data.php --confirm\n\n";
    echo "Add --no-backup to skip backup:\n";
    echo "  php load_test_data.php --confirm --no-backup\n";
    exit(1);
}

$base_dir = dirname(__DIR__);
$db_path = $base_dir . "/test_shared/control_panel.db";
$sql_file = __DIR__ . "/test_data.sql";

// Check if SQL file exists
if (!file_exists($sql_file)) {
    echo "ERROR: test_data.sql not found. Run generate_test_data.php first.\n";
    exit(1);
}

// Backup existing database
if (!$no_backup && file_exists($db_path)) {
    $backup_path = $db_path . ".backup_" . date("Ymd_His");
    echo "Backing up database to: $backup_path\n";
    copy($db_path, $backup_path);
}

// Include control panel to get database functions
echo "Loading control panel...\n";

// We need to prevent it from running the main router
$_SERVER["REQUEST_METHOD"] = "CLI";
$_GET["action"] = "cli_load_data";

// Define a flag to prevent auto-execution
define("CLI_MODE", true);

// Include just enough to get database functions
// We'll manually initialize the database

$data_dir = $base_dir . "/test_shared";
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

echo "Opening database: $db_path\n";
$db = new SQLite3($db_path);
$db->enableExceptions(true);

// Create schema first (from control_panel.php)
echo "Creating schema...\n";
$schema = "
    CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        discount_group_id INTEGER,
        lms_id INTEGER REFERENCES lms(id),
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'paused', 'decommissioned')),
        contract_start_date TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS discount_groups (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS transaction_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL,
        display_name TEXT NOT NULL,
        efx_code TEXT NOT NULL,
        efx_displayname TEXT,
        service_id INTEGER,
        created_at TEXT DEFAULT (datetime('now')),
        UNIQUE(efx_code, display_name)
    );

    CREATE INDEX IF NOT EXISTS idx_transaction_types_efx ON transaction_types(efx_code);

    CREATE TABLE IF NOT EXISTS service_billing_flags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        level TEXT NOT NULL CHECK(level IN ('default', 'group', 'customer')),
        level_id INTEGER,
        service_id INTEGER NOT NULL,
        efx_code TEXT NOT NULL,
        by_hit INTEGER DEFAULT 1,
        zero_null INTEGER DEFAULT 0,
        bav_by_trans INTEGER DEFAULT 0,
        effective_date TEXT NOT NULL DEFAULT (date('now')),
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (service_id) REFERENCES services(id)
    );

    CREATE INDEX IF NOT EXISTS idx_service_billing_flags_lookup
        ON service_billing_flags(level, level_id, service_id, efx_code, effective_date);

    CREATE TABLE IF NOT EXISTS pricing_tiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        effective_date TEXT NOT NULL DEFAULT (date('now')),
        level TEXT NOT NULL CHECK(level IN ('default', 'group', 'customer')),
        level_id INTEGER,
        service_id INTEGER NOT NULL,
        volume_start INTEGER NOT NULL,
        volume_end INTEGER,
        price_per_inquiry REAL NOT NULL,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (service_id) REFERENCES services(id)
    );

    CREATE INDEX IF NOT EXISTS idx_pricing_tiers_lookup
        ON pricing_tiers(level, level_id, service_id, effective_date);

    CREATE TABLE IF NOT EXISTS customer_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        effective_date TEXT NOT NULL DEFAULT (date('now')),
        monthly_minimum REAL,
        uses_annualized INTEGER DEFAULT 0,
        annualized_start_date TEXT,
        look_period_months INTEGER,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE INDEX IF NOT EXISTS idx_customer_settings_lookup
        ON customer_settings(customer_id, effective_date);

    CREATE TABLE IF NOT EXISTS customer_escalators (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        effective_date TEXT NOT NULL DEFAULT (date('now')),
        escalator_start_date TEXT NOT NULL,
        year_number INTEGER NOT NULL,
        escalator_percentage REAL DEFAULT 0,
        fixed_adjustment REAL DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE INDEX IF NOT EXISTS idx_escalators_lookup
        ON customer_escalators(customer_id, year_number, effective_date);

    CREATE TABLE IF NOT EXISTS escalator_delays (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        year_number INTEGER NOT NULL,
        delay_months INTEGER DEFAULT 1,
        applied_date TEXT DEFAULT (datetime('now')),
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE TABLE IF NOT EXISTS business_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        rule_name TEXT NOT NULL,
        rule_description TEXT,
        synced_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE TABLE IF NOT EXISTS business_rule_masks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id INTEGER NOT NULL,
        rule_name TEXT NOT NULL,
        is_masked INTEGER DEFAULT 0,
        effective_date TEXT NOT NULL DEFAULT (date('now')),
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE INDEX IF NOT EXISTS idx_rule_masks_lookup
        ON business_rule_masks(customer_id, rule_name, effective_date);

    CREATE TABLE IF NOT EXISTS lms (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'paused', 'decommissioned')),
        commission_rate REAL,
        last_synced TEXT,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS system_settings (
        key TEXT PRIMARY KEY,
        value TEXT,
        updated_at TEXT DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS service_cogs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id INTEGER NOT NULL,
        cogs_rate REAL NOT NULL,
        effective_date TEXT NOT NULL DEFAULT (date('now')),
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (service_id) REFERENCES services(id)
    );

    CREATE INDEX IF NOT EXISTS idx_service_cogs_lookup
        ON service_cogs(service_id, effective_date);

    CREATE TABLE IF NOT EXISTS billing_reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        report_type TEXT NOT NULL CHECK(report_type IN ('daily', 'monthly')),
        report_year INTEGER NOT NULL,
        report_month INTEGER NOT NULL,
        report_date TEXT NOT NULL,
        imported_at TEXT DEFAULT (datetime('now')),
        file_path TEXT,
        record_count INTEGER,
        created_at TEXT DEFAULT (datetime('now'))
    );

    CREATE INDEX IF NOT EXISTS idx_billing_reports_lookup
        ON billing_reports(report_type, report_year, report_month);

    CREATE TABLE IF NOT EXISTS billing_report_lines (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        report_id INTEGER NOT NULL,
        year INTEGER NOT NULL,
        month INTEGER NOT NULL,
        customer_id INTEGER NOT NULL,
        customer_name TEXT,
        hit_code TEXT,
        tran_displayname TEXT,
        actual_unit_cost REAL,
        count INTEGER,
        revenue REAL,
        efx_code TEXT,
        billing_id TEXT,
        service_name TEXT,
        FOREIGN KEY (report_id) REFERENCES billing_reports(id),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE INDEX IF NOT EXISTS idx_billing_lines_customer
        ON billing_report_lines(customer_id, year, month);

    CREATE INDEX IF NOT EXISTS idx_billing_lines_report
        ON billing_report_lines(report_id);

    CREATE TABLE IF NOT EXISTS sync_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entity_type TEXT NOT NULL,
        synced_at TEXT DEFAULT (datetime('now')),
        record_count INTEGER,
        status TEXT DEFAULT 'success'
    );
";

$db->exec($schema);

// Clear existing data
echo "Clearing existing data...\n";
$tables = [
    "billing_report_lines",
    "billing_reports",
    "sync_log",
    "service_cogs",
    "system_settings",
    "business_rule_masks",
    "business_rules",
    "escalator_delays",
    "customer_escalators",
    "customer_settings",
    "pricing_tiers",
    "service_billing_flags",
    "transaction_types",
    "customers",
    "lms",
    "discount_groups",
    "services",
];

foreach ($tables as $table) {
    $db->exec("DELETE FROM $table");
}

// Reset sequences
$db->exec("DELETE FROM sqlite_sequence");

// Load test data
echo "Loading test data from: $sql_file\n";
$sql = file_get_contents($sql_file);

// Split into individual statements and execute
$statements = explode(";\n", $sql);
$count = 0;
$errors = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt) || strpos($stmt, "--") === 0) {
        continue;
    }

    try {
        $db->exec($stmt);
        $count++;
    } catch (Exception $e) {
        echo "ERROR executing: " . substr($stmt, 0, 80) . "...\n";
        echo "  " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n========================================\n";
echo "LOAD COMPLETE\n";
echo "========================================\n";
echo "Statements executed: $count\n";
echo "Errors: $errors\n\n";

// Verify counts
echo "VERIFICATION:\n";
$verify_tables = [
    "services",
    "discount_groups",
    "lms",
    "customers",
    "pricing_tiers",
    "customer_settings",
    "customer_escalators",
    "business_rules",
    "transaction_types",
];
foreach ($verify_tables as $table) {
    $result = $db->querySingle("SELECT COUNT(*) FROM $table");
    echo "  $table: $result\n";
}

$db->close();
echo "\nDone!\n";
