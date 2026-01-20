<?php
/**
 * Integration Test: CSV Ingestion against Real Test Database
 *
 * This test verifies that:
 * 1. CSV files in test_shared/ match the database entities
 * 2. Ingestion completes successfully with no foreign key errors
 * 3. All customer IDs in CSVs exist in the database
 * 4. All EFX_codes in CSVs have matching transaction types
 *
 * Unlike unit tests which use isolated fixtures, this test uses
 * the actual test_shared/control_panel.db and test_shared/archive/*.csv
 */

echo "Integration Test: CSV Ingestion\n";
echo "================================\n\n";

// Use the real test database, not the temp one
define("INTEGRATION_TEST_MODE", true);
define("TEST_MODE", false);

// Set environment to mock_prod which uses test_shared/
putenv("CODE_ENVIRONMENT=mock_prod");
$_ENV["CODE_ENVIRONMENT"] = "mock_prod";
$_SERVER["CODE_ENVIRONMENT"] = "mock_prod";

$base_dir = dirname(dirname(__DIR__));
$test_db_path = $base_dir . "/test_shared/control_panel.db";
$archive_dir = $base_dir . "/test_shared/archive";

// Check prerequisites
if (!file_exists($test_db_path)) {
    die(
        "ERROR: Test database not found at: $test_db_path\n" .
            "Run: php scripts/load_test_data.php --confirm\n"
    );
}

$csv_files = glob($archive_dir . "/DataX_*.csv");
if (empty($csv_files)) {
    die(
        "ERROR: No CSV files found in: $archive_dir\n" .
            "Run: php scripts/generate_billing_files.php\n"
    );
}

echo "Test database: $test_db_path\n";
echo "CSV files found: " . count($csv_files) . "\n\n";

// Connect to test database
$db = new SQLite3($test_db_path);
$db->enableExceptions(true);

// Track results
$tests_passed = 0;
$tests_failed = 0;
$errors = [];

// ============================================================
// Helper functions
// ============================================================

function query_db($sql, $params = [])
{
    global $db;
    $stmt = $db->prepare($sql);
    $i = 1;
    foreach ($params as $param) {
        $stmt->bindValue($i++, $param);
    }
    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function parse_csv_file($filepath)
{
    $content = file_get_contents($filepath);
    $lines = explode("\n", $content);
    $header = null;
    $rows = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        // Parse CSV line (handle quoted fields)
        $fields = str_getcsv($line);

        if ($header === null) {
            $header = $fields;
            continue;
        }

        if (count($fields) >= count($header)) {
            $row = [];
            foreach ($header as $i => $col) {
                $row[$col] = isset($fields[$i]) ? $fields[$i] : "";
            }
            $rows[] = $row;
        }
    }

    return ["header" => $header, "rows" => $rows];
}

function test_pass($name)
{
    global $tests_passed;
    $tests_passed++;
    echo "  [PASS] $name\n";
}

function test_fail($name, $details = "")
{
    global $tests_failed, $errors;
    $tests_failed++;
    $errors[] = ["test" => $name, "details" => $details];
    echo "  [FAIL] $name\n";
    if ($details) {
        echo "         $details\n";
    }
}

// ============================================================
// Test 1: Verify database has data
// ============================================================

echo "Test 1: Database contains required entities\n";

$customers = query_db("SELECT id, name FROM customers WHERE status = 'active'");
if (count($customers) > 0) {
    test_pass("Found " . count($customers) . " active customers");
} else {
    test_fail("No active customers in database");
}

$transaction_types = query_db(
    "SELECT efx_code, display_name FROM transaction_types"
);
if (count($transaction_types) > 0) {
    test_pass("Found " . count($transaction_types) . " transaction types");
} else {
    test_fail("No transaction types in database");
}

// Build lookup maps
$customer_ids = [];
foreach ($customers as $c) {
    $customer_ids[$c["id"]] = $c["name"];
}

$efx_codes = [];
foreach ($transaction_types as $t) {
    $efx_codes[$t["efx_code"]] = $t["display_name"];
}

echo "\n";

// ============================================================
// Test 2: Validate CSV files reference valid entities
// ============================================================

echo "Test 2: CSV files reference valid database entities\n";

$total_rows = 0;
$invalid_customer_ids = [];
$invalid_efx_codes = [];
$files_checked = 0;

// Check a sample of files (first 5 + last 5)
$sample_files = array_slice($csv_files, 0, 5);
if (count($csv_files) > 10) {
    $sample_files = array_merge($sample_files, array_slice($csv_files, -5));
}

foreach ($sample_files as $filepath) {
    $filename = basename($filepath);
    $parsed = parse_csv_file($filepath);
    $files_checked++;
    $total_rows += count($parsed["rows"]);

    foreach ($parsed["rows"] as $row) {
        $cust_id = isset($row["cust_id"]) ? (int) $row["cust_id"] : 0;
        $efx_code = isset($row["EFX_code"]) ? $row["EFX_code"] : "";

        if ($cust_id > 0 && !isset($customer_ids[$cust_id])) {
            $invalid_customer_ids[$cust_id] = isset(
                $invalid_customer_ids[$cust_id]
            )
                ? $invalid_customer_ids[$cust_id] + 1
                : 1;
        }

        if (!empty($efx_code) && !isset($efx_codes[$efx_code])) {
            $invalid_efx_codes[$efx_code] = isset($invalid_efx_codes[$efx_code])
                ? $invalid_efx_codes[$efx_code] + 1
                : 1;
        }
    }
}

echo "  Checked $files_checked files, $total_rows total rows\n";

if (empty($invalid_customer_ids)) {
    test_pass("All customer IDs in CSVs exist in database");
} else {
    $details =
        "Invalid IDs: " . implode(", ", array_keys($invalid_customer_ids));
    test_fail(
        "Found " . count($invalid_customer_ids) . " invalid customer IDs",
        $details
    );
}

if (empty($invalid_efx_codes)) {
    test_pass("All EFX_codes in CSVs exist in database");
} else {
    $details =
        "Invalid codes: " . implode(", ", array_keys($invalid_efx_codes));
    test_fail(
        "Found " . count($invalid_efx_codes) . " invalid EFX_codes",
        $details
    );
}

echo "\n";

// ============================================================
// Test 3: Simulate ingestion of one file
// ============================================================

echo "Test 3: Simulate ingestion of a sample CSV file\n";

// Include the main application for import functions
// We need to set up environment first
$_SERVER["REQUEST_METHOD"] = "CLI";
$_GET["action"] = "cli_test";

// Temporarily override database path
$_ENV["TEST_DB_OVERRIDE"] = $test_db_path;

ob_start();
require_once $base_dir . "/control_panel.php";
ob_end_clean();

// Pick a sample file
$sample_file = $csv_files[0];
$filename = basename($sample_file);
$csv_content = file_get_contents($sample_file);

echo "  Testing with: $filename\n";

// Check if import function exists
if (function_exists("import_billing_report")) {
    // First, check if this report was already imported
    $file_info = parse_billing_filename($filename);
    if ($file_info) {
        // Clear any existing import of this file for clean test
        $existing = sqlite_query(
            "SELECT id FROM billing_reports WHERE report_type = ? AND report_year = ? AND report_month = ? AND report_date = ?",
            [
                $file_info["type"],
                $file_info["year"],
                $file_info["month"],
                sprintf(
                    "%04d-%02d-%02d",
                    $file_info["year"],
                    $file_info["month"],
                    isset($file_info["day"]) ? $file_info["day"] : 1
                ),
            ]
        );

        if (!empty($existing)) {
            // Delete existing for clean test
            sqlite_execute(
                "DELETE FROM billing_report_lines WHERE report_id = ?",
                [$existing[0]["id"]]
            );
            sqlite_execute("DELETE FROM billing_reports WHERE id = ?", [
                $existing[0]["id"],
            ]);
            echo "  (Cleared previous import for clean test)\n";
        }
    }

    $result = import_billing_report($filename, $csv_content);

    if ($result["success"]) {
        test_pass("Import succeeded: {$result["rows_imported"]} rows imported");

        // Verify data was actually inserted
        if ($result["report_id"]) {
            $lines = sqlite_query(
                "SELECT COUNT(*) as cnt FROM billing_report_lines WHERE report_id = ?",
                [$result["report_id"]]
            );
            if ($lines[0]["cnt"] > 0) {
                test_pass("Verified {$lines[0]["cnt"]} lines in database");
            } else {
                test_fail("No lines found in database after import");
            }

            // Clean up test import
            sqlite_execute(
                "DELETE FROM billing_report_lines WHERE report_id = ?",
                [$result["report_id"]]
            );
            sqlite_execute("DELETE FROM billing_reports WHERE id = ?", [
                $result["report_id"],
            ]);
            echo "  (Cleaned up test import)\n";
        }
    } else {
        $details = implode("; ", $result["errors"]);
        test_fail("Import failed", $details);
    }
} else {
    test_fail("import_billing_report function not available");
}

echo "\n";

// ============================================================
// Test 4: Verify customer names match between CSV and DB
// ============================================================

echo "Test 4: Customer names in CSV match database\n";

$name_mismatches = [];
$sample_file = $csv_files[0];
$parsed = parse_csv_file($sample_file);

foreach ($parsed["rows"] as $row) {
    $cust_id = isset($row["cust_id"]) ? (int) $row["cust_id"] : 0;
    $csv_name = isset($row["cust_name"]) ? trim($row["cust_name"]) : "";

    if ($cust_id > 0 && isset($customer_ids[$cust_id])) {
        $db_name = $customer_ids[$cust_id];
        if ($csv_name !== $db_name) {
            $key = "$cust_id: CSV='$csv_name' DB='$db_name'";
            $name_mismatches[$key] = true;
        }
    }
}

if (empty($name_mismatches)) {
    test_pass("All customer names match between CSV and database");
} else {
    $details = "First mismatch: " . array_keys($name_mismatches)[0];
    test_fail(
        "Found " . count($name_mismatches) . " name mismatches",
        $details
    );
}

echo "\n";

// ============================================================
// Summary
// ============================================================

echo "========================================\n";
echo "INTEGRATION TEST RESULTS\n";
echo "========================================\n";
echo "Passed: $tests_passed\n";
echo "Failed: $tests_failed\n";

if (!empty($errors)) {
    echo "\nFAILURES:\n";
    foreach ($errors as $error) {
        echo "  - {$error["test"]}\n";
        if ($error["details"]) {
            echo "    {$error["details"]}\n";
        }
    }
}

echo "========================================\n";

$db->close();

exit($tests_failed > 0 ? 1 : 0);
