<?php
// ============================================================
// DATABASE LAYER
// SQLite database functions, schema, migrations
// ============================================================

$_sqlite_db = null;

/**
 * Execute a SELECT query on REMOTE database, return array of rows
 *
 * @param string $sql    SQL query with ? placeholders
 * @param array  $params Parameters to bind
 * @return array         Array of associative arrays
 * @throws Exception     If remote DB is not configured (in production mode)
 */
function remote_db_query($sql, $params = [])
{
    // Check if remote DB is configured
    $host = defined("REMOTE_DB_HOST") ? REMOTE_DB_HOST : "";

    if (empty($host)) {
        // Not configured - this is an error in production environments
        throw new Exception(
            "Remote database not configured. Set REMOTE_DB_HOST, REMOTE_DB_NAME, " .
                "REMOTE_DB_USER, and REMOTE_DB_PASS in environment config."
        );
    }

    // TODO: Implement actual remote database connection
    // Example for MySQL/MariaDB:
    //
    // $dsn = "mysql:host=" . REMOTE_DB_HOST . ";dbname=" . REMOTE_DB_NAME . ";charset=utf8mb4";
    // $pdo = new PDO($dsn, REMOTE_DB_USER, REMOTE_DB_PASS, [
    //     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // ]);
    // $stmt = $pdo->prepare($sql);
    // $stmt->execute($params);
    // return $stmt->fetchAll();

    throw new Exception(
        "Remote database connection not implemented. " .
            "Edit db.php remote_db_query() to connect to: " .
            $host
    );
}

/**
 * Get SQLite database connection (singleton)
 *
 * @return SQLite3
 */
function sqlite_db()
{
    global $_sqlite_db;

    if ($_sqlite_db === null) {
        // Use test database if TEST_MODE is defined
        if (defined("TEST_MODE") && TEST_MODE && defined("TEST_DB_PATH")) {
            $db_path = TEST_DB_PATH;
        } else {
            $db_path = get_shared_path() . "/control_panel.db";
        }

        $_sqlite_db = new SQLite3($db_path);
        $_sqlite_db->enableExceptions(true);
        $_sqlite_db->busyTimeout(5000);

        // Enable foreign keys
        $_sqlite_db->exec("PRAGMA foreign_keys = ON");

        // Initialize schema if needed
        sqlite_init_schema();

        // Run migrations
        sqlite_run_migrations();
    }

    return $_sqlite_db;
}

/**
 * Execute a SELECT query on local SQLite, return array of rows
 *
 * @param string $sql    SQL query with ? placeholders
 * @param array  $params Parameters to bind
 * @return array         Array of associative arrays
 */
function sqlite_query($sql, $params = [])
{
    $db = sqlite_db();
    $stmt = $db->prepare($sql);

    if ($stmt === false) {
        return [];
    }

    // Bind parameters
    $i = 1;
    foreach ($params as $param) {
        if (is_int($param)) {
            $stmt->bindValue($i, $param, SQLITE3_INTEGER);
        } elseif (is_float($param)) {
            $stmt->bindValue($i, $param, SQLITE3_FLOAT);
        } elseif (is_null($param)) {
            $stmt->bindValue($i, null, SQLITE3_NULL);
        } else {
            $stmt->bindValue($i, $param, SQLITE3_TEXT);
        }
        $i++;
    }

    $result = $stmt->execute();
    $rows = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Execute an INSERT/UPDATE/DELETE on local SQLite
 *
 * @param string $sql    SQL query with ? placeholders
 * @param array  $params Parameters to bind
 * @return bool          Success/failure
 */
function sqlite_execute($sql, $params = [])
{
    $db = sqlite_db();
    $stmt = $db->prepare($sql);

    if ($stmt === false) {
        return false;
    }

    // Bind parameters
    $i = 1;
    foreach ($params as $param) {
        if (is_int($param)) {
            $stmt->bindValue($i, $param, SQLITE3_INTEGER);
        } elseif (is_float($param)) {
            $stmt->bindValue($i, $param, SQLITE3_FLOAT);
        } elseif (is_null($param)) {
            $stmt->bindValue($i, null, SQLITE3_NULL);
        } else {
            $stmt->bindValue($i, $param, SQLITE3_TEXT);
        }
        $i++;
    }

    return $stmt->execute() !== false;
}

/**
 * Get last insert ID from SQLite
 *
 * @return int
 */
function sqlite_last_id()
{
    return sqlite_db()->lastInsertRowID();
}

/**
 * Initialize SQLite schema
 */
function sqlite_init_schema()
{
    global $_sqlite_db;

    $schema = "
    -- ============================================================
    -- CORE ENTITIES (synced from remote or manually managed)
    -- ============================================================

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

    -- ============================================================
    -- TRANSACTION TYPES (from displayname_to_type.csv)
    -- ============================================================

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

    CREATE INDEX IF NOT EXISTS idx_transaction_types_efx
        ON transaction_types(efx_code);

    -- ============================================================
    -- SERVICE BILLING FLAGS
    -- ============================================================

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

    -- ============================================================
    -- PRICING TIERS (append-only, hierarchical)
    -- ============================================================

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

    -- ============================================================
    -- CUSTOMER SETTINGS (append-only)
    -- ============================================================

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

    -- ============================================================
    -- ESCALATORS (append-only)
    -- ============================================================

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

    -- ============================================================
    -- BUSINESS RULES (append-only masks)
    -- ============================================================

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

    -- ============================================================
    -- LMS (Loan Management System)
    -- ============================================================

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

    -- ============================================================
    -- BILLING REPORTS
    -- ============================================================

    CREATE TABLE IF NOT EXISTS billing_reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        report_type TEXT NOT NULL CHECK(report_type IN ('daily', 'monthly')),
        report_year INTEGER NOT NULL,
        report_month INTEGER NOT NULL,
        report_date TEXT NOT NULL,
        imported_at TEXT DEFAULT (datetime('now')),
        file_path TEXT,
        record_count INTEGER
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
        FOREIGN KEY (report_id) REFERENCES billing_reports(id),
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    );

    CREATE INDEX IF NOT EXISTS idx_billing_lines_customer
        ON billing_report_lines(customer_id, year, month);

    CREATE INDEX IF NOT EXISTS idx_billing_lines_report
        ON billing_report_lines(report_id);

    -- ============================================================
    -- SYNC LOG
    -- ============================================================

    CREATE TABLE IF NOT EXISTS sync_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entity_type TEXT NOT NULL,
        synced_at TEXT DEFAULT (datetime('now')),
        record_count INTEGER,
        status TEXT DEFAULT 'success'
    );
    ";

    $_sqlite_db->exec($schema);
}

/**
 * Run database migrations for schema updates
 */
function sqlite_run_migrations()
{
    global $_sqlite_db;

    // Migration 1: Add lms_id column to customers if it doesn't exist
    $result = $_sqlite_db->query("PRAGMA table_info(customers)");
    $has_lms_id = false;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row["name"] === "lms_id") {
            $has_lms_id = true;
            break;
        }
    }
    if (!$has_lms_id) {
        $_sqlite_db->exec(
            "ALTER TABLE customers ADD COLUMN lms_id INTEGER REFERENCES lms(id)"
        );
    }

    // Migration 2: Add status column to lms if it doesn't exist
    $result = $_sqlite_db->query("PRAGMA table_info(lms)");
    $has_status = false;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row["name"] === "status") {
            $has_status = true;
            break;
        }
    }
    if (!$has_status) {
        $_sqlite_db->exec(
            "ALTER TABLE lms ADD COLUMN status TEXT DEFAULT 'active'"
        );
    }

    // Migration 3: Create generated_reports table for report archival
    $_sqlite_db->exec("
        CREATE TABLE IF NOT EXISTS generated_reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            report_type TEXT NOT NULL,
            report_subtype TEXT,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_size INTEGER,
            record_count INTEGER,
            generated_at TEXT DEFAULT (datetime('now')),
            parameters TEXT,
            notes TEXT
        )
    ");

    $_sqlite_db->exec("
        CREATE INDEX IF NOT EXISTS idx_generated_reports_type
            ON generated_reports(report_type, generated_at)
    ");

    // Migration 4: Add notes column to sync_log if it doesn't exist
    $result = $_sqlite_db->query("PRAGMA table_info(sync_log)");
    $has_notes = false;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row["name"] === "notes") {
            $has_notes = true;
            break;
        }
    }
    if (!$has_notes) {
        $_sqlite_db->exec("ALTER TABLE sync_log ADD COLUMN notes TEXT");
    }
}

/**
 * Get the current (latest effective) record for an entity
 *
 * @param string $table      Table name
 * @param array  $conditions Key-value pairs for WHERE clause
 * @return array|null        Single row or null
 */
function sqlite_get_current($table, $conditions)
{
    $where = [];
    $params = [];

    foreach ($conditions as $key => $value) {
        $where[] = "$key = ?";
        $params[] = $value;
    }

    $where[] = "effective_date <= date('now')";
    $where_str = implode(" AND ", $where);

    $sql = "SELECT * FROM $table WHERE $where_str ORDER BY effective_date DESC, id DESC LIMIT 1";
    $rows = sqlite_query($sql, $params);

    return !empty($rows) ? $rows[0] : null;
}

/**
 * Get all current records (latest effective for each unique entity)
 *
 * @param string $table       Table name
 * @param string $group_by    Column(s) to group by
 * @param array  $conditions  Additional WHERE conditions
 * @param int    $page        Page number (1-indexed)
 * @param int    $per_page    Items per page
 * @return array              Array of rows
 */
function sqlite_get_all_current(
    $table,
    $group_by,
    $conditions = [],
    $page = 1,
    $per_page = ITEMS_PER_PAGE
) {
    $where = ["effective_date <= date('now')"];
    $params = [];

    foreach ($conditions as $key => $value) {
        $where[] = "$key = ?";
        $params[] = $value;
    }

    $where_str = implode(" AND ", $where);
    $offset = ($page - 1) * $per_page;

    $sql = "
        SELECT t1.* FROM $table t1
        INNER JOIN (
            SELECT $group_by, MAX(effective_date) as max_date
            FROM $table
            WHERE $where_str
            GROUP BY $group_by
        ) t2 ON t1.$group_by = t2.$group_by AND t1.effective_date = t2.max_date
        WHERE $where_str
        ORDER BY t1.id DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $per_page;
    $params[] = $offset;

    return sqlite_query($sql, $params);
}

/**
 * Count total records for pagination
 *
 * @param string $table      Table name
 * @param array  $conditions WHERE conditions
 * @return int
 */
function sqlite_count($table, $conditions = [])
{
    $where = ["1=1"];
    $params = [];

    foreach ($conditions as $key => $value) {
        $where[] = "$key = ?";
        $params[] = $value;
    }

    $where_str = implode(" AND ", $where);
    $sql = "SELECT COUNT(*) as cnt FROM $table WHERE $where_str";
    $rows = sqlite_query($sql, $params);

    return !empty($rows) ? (int) $rows[0]["cnt"] : 0;
}

/**
 * Seed mock data for testing (only if tables are empty)
 */
function sqlite_seed_mock_data()
{
    if (!MOCK_MODE) {
        return;
    }

    // Check if already seeded
    $customers = sqlite_query("SELECT COUNT(*) as cnt FROM customers");
    if ($customers[0]["cnt"] > 0) {
        return;
    }

    // Seed discount groups
    sqlite_execute(
        "INSERT INTO discount_groups (id, name) VALUES (1, 'Enterprise Partners')"
    );
    sqlite_execute(
        "INSERT INTO discount_groups (id, name) VALUES (2, 'Standard Tier')"
    );
    sqlite_execute(
        "INSERT INTO discount_groups (id, name) VALUES (3, 'Startup Program')"
    );

    // Seed services
    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (1, 'Identity Verification')"
    );
    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (2, 'Address Validation')"
    );
    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (3, 'Phone Lookup')"
    );
    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (4, 'Email Verification')"
    );
    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (5, 'Background Check')"
    );

    // Seed customers
    $customers_data = [
        [1, "Acme Corp", 1, "active", "2024-01-01"],
        [2, "Globex Inc", 1, "active", "2024-03-15"],
        [3, "Initech", 2, "active", "2023-06-01"],
        [4, "Umbrella Corp", 2, "paused", "2023-09-01"],
        [5, "Stark Industries", 1, "active", "2024-06-01"],
        [6, "Wayne Enterprises", null, "active", "2022-01-01"],
        [7, "Oscorp", 3, "active", "2025-01-01"],
        [8, "LexCorp", 2, "decommissioned", "2021-01-01"],
        [9, "Cyberdyne", 1, "active", "2024-09-01"],
        [10, "Tyrell Corp", null, "active", "2023-01-01"],
    ];

    foreach ($customers_data as $c) {
        sqlite_execute(
            "INSERT INTO customers (id, name, discount_group_id, status, contract_start_date) VALUES (?, ?, ?, ?, ?)",
            $c
        );
    }

    // Seed system default pricing tiers
    $default_tiers = [
        ["default", null, 1, 0, 1000, 0.5],
        ["default", null, 1, 1001, 5000, 0.4],
        ["default", null, 1, 5001, null, 0.3],
        ["default", null, 2, 0, 1000, 0.25],
        ["default", null, 2, 1001, 5000, 0.2],
        ["default", null, 2, 5001, null, 0.15],
        ["default", null, 3, 0, 500, 0.75],
        ["default", null, 3, 501, 2000, 0.6],
        ["default", null, 3, 2001, null, 0.45],
        ["default", null, 4, 0, 2000, 0.1],
        ["default", null, 4, 2001, 10000, 0.08],
        ["default", null, 4, 10001, null, 0.05],
        ["default", null, 5, 0, 100, 5.0],
        ["default", null, 5, 101, 500, 4.5],
        ["default", null, 5, 501, null, 4.0],
    ];

    foreach ($default_tiers as $t) {
        sqlite_execute(
            "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date)
             VALUES (?, ?, ?, ?, ?, ?, '2024-01-01')",
            $t
        );
    }

    // Seed group-level overrides
    $group_tiers = [
        ["group", 1, 1, 0, 1000, 0.4],
        ["group", 1, 1, 1001, 5000, 0.32],
        ["group", 1, 1, 5001, null, 0.24],
    ];

    foreach ($group_tiers as $t) {
        sqlite_execute(
            "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date)
             VALUES (?, ?, ?, ?, ?, ?, '2024-01-01')",
            $t
        );
    }

    // Seed customer-level override
    $customer_tiers = [
        ["customer", 6, 5, 0, 100, 4.0],
        ["customer", 6, 5, 101, 500, 3.5],
        ["customer", 6, 5, 501, null, 3.0],
    ];

    foreach ($customer_tiers as $t) {
        sqlite_execute(
            "INSERT INTO pricing_tiers (level, level_id, service_id, volume_start, volume_end, price_per_inquiry, effective_date)
             VALUES (?, ?, ?, ?, ?, ?, '2024-01-01')",
            $t
        );
    }

    // Seed customer settings
    sqlite_execute(
        "INSERT INTO customer_settings (customer_id, monthly_minimum, uses_annualized, look_period_months, effective_date)
         VALUES (1, 500.00, 0, NULL, '2024-01-01')"
    );
    sqlite_execute(
        "INSERT INTO customer_settings (customer_id, monthly_minimum, uses_annualized, annualized_start_date, look_period_months, effective_date)
         VALUES (6, 1000.00, 1, '2022-01-01', 6, '2022-01-01')"
    );

    // Seed escalators
    sqlite_execute(
        "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
         VALUES (1, '2024-01-01', 1, 0, 0, '2024-01-01')"
    );
    sqlite_execute(
        "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
         VALUES (1, '2024-01-01', 2, 5.0, 0, '2024-01-01')"
    );
    sqlite_execute(
        "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
         VALUES (1, '2024-01-01', 3, 10.0, 0, '2024-01-01')"
    );

    // Seed business rules
    sqlite_execute(
        "INSERT INTO business_rules (customer_id, rule_name, rule_description) VALUES (1, 'RULE_SKIP_EMPTY', 'Skip empty records')"
    );
    sqlite_execute(
        "INSERT INTO business_rules (customer_id, rule_name, rule_description) VALUES (1, 'RULE_VALIDATE_SSN', 'Validate SSN format')"
    );
    sqlite_execute(
        "INSERT INTO business_rules (customer_id, rule_name, rule_description) VALUES (1, 'RULE_CHECK_DUPLICATES', 'Check for duplicates')"
    );

    // Mask one rule
    sqlite_execute(
        "INSERT INTO business_rule_masks (customer_id, rule_name, is_masked, effective_date)
         VALUES (1, 'RULE_SKIP_EMPTY', 1, '2024-06-01')"
    );
}
