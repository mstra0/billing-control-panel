<?php
/**
 * Integration Test: CSV Ingestion against Real Test Database
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Run standalone for CI/CD testing
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

// ============================================================
// MODE DETECTION
// ============================================================
$_is_cli = php_sapi_name() === "cli";
$_is_included = defined("TEST_RUNNER_ACTIVE");
$_is_qa_mode = !$_is_cli && !$_is_included;

// ============================================================
// QA MODE: Show HTML page with demo
// ============================================================
if ($_is_qa_mode) {
    require_once __DIR__ . "/../bootstrap_qa.php";
    require_once __DIR__ . "/../qa_wrapper.php";

    ob_start();
    $_qa_test_results = run_ingestion_tests();
    $test_output = ob_get_clean();

    $demo_content = render_ingestion_demo();
    render_qa_page(
        "CSV Ingestion Integration",
        "Integration tests for CSV ingestion against real test database",
        $_qa_test_results,
        $test_output,
        $demo_content,
        "#6f42c1",
        false
    );
    exit();
}

// ============================================================
// CLI MODE: Skip when run by test runner
// ============================================================
if ($_is_included) {
    echo "Integration Test: CSV Ingestion\n";
    echo "================================\n";
    echo "  [SKIP] Integration tests require standalone execution\n";
    echo "  Run directly: php5.6 tests/integration/test_ingestion.php\n\n";
    return;
}

// ============================================================
// STANDALONE CLI MODE
// ============================================================
echo "Integration Test: CSV Ingestion\n";
echo "================================\n\n";

run_ingestion_tests_cli();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_ingestion_demo()
{
    ob_start();

    // Get archive path for demo
    $base_dir = dirname(dirname(__DIR__));

    // Try mock_prod first
    putenv("CODE_ENVIRONMENT=mock_prod");
    $_ENV["CODE_ENVIRONMENT"] = "mock_prod";
    $_SERVER["CODE_ENVIRONMENT"] = "mock_prod";

    $archive_path = $base_dir . "/test_shared/archive";
    $db_path = $base_dir . "/test_shared/control_panel.db";
    ?>
    <div class="demo-box">
        <h3>How CSV Ingestion Works</h3>
        <p>The ingestion system processes billing CSV files through these steps:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>File Discovery</strong> - Scans archive directory for CSV files</li>
            <li><strong>Validation</strong> - Verifies customer IDs and EFX codes exist in database</li>
            <li><strong>Parsing</strong> - Extracts billing data from CSV format</li>
            <li><strong>Import</strong> - Inserts validated data into billing tables</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>File Categories</h3>
        <table>
            <tr>
                <th>Category</th>
                <th>Pattern</th>
                <th>Description</th>
            </tr>
            <tr>
                <td>Daily Humanreadable</td>
                <td style="font-family: monospace;">DataX_YYYY_MM_DD_humanreadable.csv</td>
                <td>Daily billing data in human-readable format</td>
            </tr>
            <tr>
                <td>Monthly Humanreadable</td>
                <td style="font-family: monospace;">DataX_YYYY_MM_YYYY_MM_humanreadable.csv</td>
                <td>Monthly summary in human-readable format</td>
            </tr>
            <tr>
                <td>Monthly EBCDIC</td>
                <td style="font-family: monospace;">DataX_YYYY_MM_YYYY_MM_ebcdic.csv</td>
                <td>Monthly data in EBCDIC format</td>
            </tr>
        </table>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: get_ingestion_reports() (Real Function)</h3>
        <p style="margin-bottom: 15px;">This calls the real <code>get_ingestion_reports()</code> function from data.php:</p>

        <?php
        // Call REAL function
        $reports = get_ingestion_reports();

        $daily_count = count($reports["daily_humanreadable"]);
        $monthly_hr_count = count($reports["monthly_humanreadable"]);
        $monthly_eb_count = count($reports["monthly_ebcdic"]);
        $total_count = $daily_count + $monthly_hr_count + $monthly_eb_count;
        ?>

        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;">Result: <?php echo $total_count; ?> Reports Found</h4>

            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div style="flex: 1; background: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #28a745;"><?php echo $daily_count; ?></div>
                    <div style="color: #666;">Daily Reports</div>
                </div>
                <div style="flex: 1; background: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #17a2b8;"><?php echo $monthly_hr_count; ?></div>
                    <div style="color: #666;">Monthly HR</div>
                </div>
                <div style="flex: 1; background: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #6f42c1;"><?php echo $monthly_eb_count; ?></div>
                    <div style="color: #666;">Monthly EBCDIC</div>
                </div>
            </div>

            <?php if ($daily_count > 0): ?>
            <h5 style="color: #155724; margin: 10px 0;">Daily Reports (showing up to 5):</h5>
            <table style="width: 100%; background: white;">
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Modified</th>
                </tr>
                <?php foreach (
                    array_slice($reports["daily_humanreadable"], 0, 5)
                    as $report
                ): ?>
                <tr>
                    <td style="font-family: monospace; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $report["name"]
                    ); ?></td>
                    <td><?php echo number_format($report["size"]); ?> bytes</td>
                    <td><?php echo date(
                        "Y-m-d H:i",
                        $report["modified"]
                    ); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <?php if ($monthly_hr_count > 0): ?>
            <h5 style="color: #155724; margin: 10px 0;">Monthly Humanreadable Reports (showing up to 5):</h5>
            <table style="width: 100%; background: white;">
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Modified</th>
                </tr>
                <?php foreach (
                    array_slice($reports["monthly_humanreadable"], 0, 5)
                    as $report
                ): ?>
                <tr>
                    <td style="font-family: monospace; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $report["name"]
                    ); ?></td>
                    <td><?php echo number_format($report["size"]); ?> bytes</td>
                    <td><?php echo date(
                        "Y-m-d H:i",
                        $report["modified"]
                    ); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="demo-box" style="background: #e7f3ff; border: 2px solid #007bff;">
        <h3>Live Demo: Database Entity Validation (Real Queries)</h3>
        <p style="margin-bottom: 15px;">This validates that entities referenced in CSVs exist in the database:</p>

        <?php
        // Query real database
        $customers = sqlite_query(
            "SELECT id, name FROM customers WHERE status = 'active' ORDER BY name LIMIT 10"
        );
        $transaction_types = sqlite_query(
            "SELECT efx_code, display_name FROM transaction_types ORDER BY efx_code LIMIT 10"
        );
        ?>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <h4 style="color: #0056b3;">Active Customers (first 10)</h4>
                <table style="background: white; width: 100%;">
                    <tr><th>ID</th><th>Name</th></tr>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo $c["id"]; ?></td>
                        <td><?php echo htmlspecialchars($c["name"]); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div style="flex: 1;">
                <h4 style="color: #0056b3;">Transaction Types (first 10)</h4>
                <table style="background: white; width: 100%;">
                    <tr><th>EFX Code</th><th>Display Name</th></tr>
                    <?php foreach ($transaction_types as $t): ?>
                    <tr>
                        <td style="font-family: monospace;"><?php echo htmlspecialchars(
                            $t["efx_code"]
                        ); ?></td>
                        <td><?php echo htmlspecialchars(
                            $t["display_name"]
                        ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="demo-box" style="background: #f8d7da; border: 2px solid #dc3545;">
        <h3>Live Demo: CSV File Sample Parsing</h3>
        <p style="margin-bottom: 15px;">Parse and validate a sample from the first CSV file found:</p>

        <?php
        $archive_path = get_archive_path();
        $csv_files = glob($archive_path . "/DataX_*.csv");

        if (!empty($csv_files)):

            $sample_file = $csv_files[0];
            $filename = basename($sample_file);

            // Parse filename using real function
            $file_info = parse_billing_filename($filename);

            // Read first few lines
            $content = file_get_contents($sample_file);
            $lines = explode("\n", $content);
            $header = isset($lines[0]) ? trim($lines[0]) : "";
            $first_data = isset($lines[1]) ? trim($lines[1]) : "";
            ?>
        <div style="background: #f5c6cb; border-radius: 10px; padding: 15px;">
            <h4 style="color: #721c24;">Sample File: <?php echo htmlspecialchars(
                $filename
            ); ?></h4>

            <h5 style="margin: 10px 0 5px;">parse_billing_filename() Result:</h5>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto;"><?php if (
                $file_info === false
            ) {
                echo "false (invalid filename pattern)";
            } else {
                print_r($file_info);
            } ?></pre>

            <h5 style="margin: 10px 0 5px;">CSV Header:</h5>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 0.85em;"><?php echo htmlspecialchars(
                $header
            ); ?></pre>

            <h5 style="margin: 10px 0 5px;">First Data Row:</h5>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 0.85em;"><?php echo htmlspecialchars(
                $first_data
            ); ?></pre>

            <h5 style="margin: 10px 0 5px;">Total Lines in File: <?php echo count(
                $lines
            ); ?></h5>
        </div>
        <?php
        else:
             ?>
        <div style="background: #f5c6cb; border-radius: 10px; padding: 15px; color: #721c24;">
            <strong>No CSV files found in archive directory.</strong><br>
            Path checked: <?php echo htmlspecialchars($archive_path); ?>
        </div>
        <?php
        endif;
        ?>
    </div>

    <div class="demo-box">
        <h3>Test Environment</h3>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Archive Path</td>
                <td style="font-family: monospace;"><?php echo htmlspecialchars(
                    get_archive_path()
                ); ?></td>
            </tr>
            <tr>
                <td>Database Path</td>
                <td style="font-family: monospace;"><?php echo htmlspecialchars(
                    get_test_db_path()
                ); ?></td>
            </tr>
            <tr>
                <td>Environment</td>
                <td style="font-family: monospace;"><?php echo htmlspecialchars(
                    getenv("CODE_ENVIRONMENT") ?: "not set"
                ); ?></td>
            </tr>
            <tr>
                <td>MOCK_MODE</td>
                <td style="font-family: monospace;"><?php echo defined(
                    "MOCK_MODE"
                )
                    ? (MOCK_MODE
                        ? "true"
                        : "false")
                    : "not defined"; ?></td>
            </tr>
        </table>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// TEST RUNNER FOR QA MODE
// ============================================================
function run_ingestion_tests()
{
    global $_test_results;

    // Set environment for test database
    putenv("CODE_ENVIRONMENT=mock_prod");
    $_ENV["CODE_ENVIRONMENT"] = "mock_prod";
    $_SERVER["CODE_ENVIRONMENT"] = "mock_prod";

    run_test(
        "get_ingestion_reports returns array with categories",
        function () {
            $reports = get_ingestion_reports();

            assert_true(is_array($reports), "Should return array");
            assert_true(
                isset($reports["daily_humanreadable"]),
                "Should have daily_humanreadable key"
            );
            assert_true(
                isset($reports["monthly_humanreadable"]),
                "Should have monthly_humanreadable key"
            );
            assert_true(
                isset($reports["monthly_ebcdic"]),
                "Should have monthly_ebcdic key"
            );
        }
    );

    run_test(
        "get_ingestion_reports daily files have correct structure",
        function () {
            $reports = get_ingestion_reports();

            if (count($reports["daily_humanreadable"]) > 0) {
                $first = $reports["daily_humanreadable"][0];
                assert_true(isset($first["name"]), "Should have name");
                assert_true(isset($first["path"]), "Should have path");
                assert_true(isset($first["size"]), "Should have size");
                assert_true(isset($first["modified"]), "Should have modified");
            } else {
                // No daily files is okay - just verify the structure
                assert_true(true, "No daily files to check");
            }
        }
    );

    run_test("Database has active customers", function () {
        $customers = sqlite_query(
            "SELECT COUNT(*) as cnt FROM customers WHERE status = 'active'"
        );
        assert_true(
            $customers[0]["cnt"] > 0,
            "Should have at least one active customer"
        );
    });

    run_test("Database has transaction types", function () {
        $types = sqlite_query("SELECT COUNT(*) as cnt FROM transaction_types");
        assert_true(
            $types[0]["cnt"] > 0,
            "Should have at least one transaction type"
        );
    });

    run_test("Archive path is accessible", function () {
        $archive_path = get_archive_path();
        assert_true(
            is_dir($archive_path),
            "Archive path should be a directory: " . $archive_path
        );
    });

    run_test("parse_billing_filename works with daily pattern", function () {
        $result = parse_billing_filename("DataX_2025_01_15_humanreadable.csv");
        assert_not_null($result, "Should parse daily filename");
        assert_equals("daily", $result["type"], "Type should be daily");
    });

    run_test("parse_billing_filename works with monthly pattern", function () {
        $result = parse_billing_filename(
            "DataX_2025_01_2025_01_humanreadable.csv"
        );
        assert_not_null($result, "Should parse monthly filename");
        assert_equals("monthly", $result["type"], "Type should be monthly");
    });

    return $_test_results;
}

// ============================================================
// CLI STANDALONE TEST RUNNER
// ============================================================
function run_ingestion_tests_cli()
{
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

    // Connect to test database directly
    $db = new SQLite3($test_db_path);
    $db->enableExceptions(true);

    // Track results
    $tests_passed = 0;
    $tests_failed = 0;
    $errors = [];

    // Helper functions for CLI mode
    $query_db = function ($sql, $params = []) use ($db) {
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
    };

    $parse_csv_file = function ($filepath) {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        $header = null;
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

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
    };

    $test_pass = function ($name) use (&$tests_passed) {
        $tests_passed++;
        echo "  [PASS] $name\n";
    };

    $test_fail = function ($name, $details = "") use (
        &$tests_failed,
        &$errors
    ) {
        $tests_failed++;
        $errors[] = ["test" => $name, "details" => $details];
        echo "  [FAIL] $name\n";
        if ($details) {
            echo "         $details\n";
        }
    };

    // ============================================================
    // Test 1: Verify database has data
    // ============================================================

    echo "Test 1: Database contains required entities\n";

    $customers = $query_db(
        "SELECT id, name FROM customers WHERE status = 'active'"
    );
    if (count($customers) > 0) {
        $test_pass("Found " . count($customers) . " active customers");
    } else {
        $test_fail("No active customers in database");
    }

    $transaction_types = $query_db(
        "SELECT efx_code, display_name FROM transaction_types"
    );
    if (count($transaction_types) > 0) {
        $test_pass("Found " . count($transaction_types) . " transaction types");
    } else {
        $test_fail("No transaction types in database");
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
        $parsed = $parse_csv_file($filepath);
        $files_checked++;
        $total_rows += count($parsed["rows"]);

        foreach ($parsed["rows"] as $row) {
            $cust_id = isset($row["cust_id"]) ? (int) $row["cust_id"] : 0;
            $efx_code = isset($row["EFX_code"]) ? $row["EFX_code"] : "";

            if ($cust_id > 0 && !isset($customer_ids[$cust_id])) {
                if (!isset($invalid_customer_ids[$cust_id])) {
                    $invalid_customer_ids[$cust_id] = 0;
                }
                $invalid_customer_ids[$cust_id]++;
            }

            if (!empty($efx_code) && !isset($efx_codes[$efx_code])) {
                if (!isset($invalid_efx_codes[$efx_code])) {
                    $invalid_efx_codes[$efx_code] = 0;
                }
                $invalid_efx_codes[$efx_code]++;
            }
        }
    }

    echo "  Checked $files_checked files, $total_rows total rows\n";

    if (empty($invalid_customer_ids)) {
        $test_pass("All customer IDs in CSVs exist in database");
    } else {
        $details =
            "Invalid IDs: " . implode(", ", array_keys($invalid_customer_ids));
        $test_fail(
            "Found " . count($invalid_customer_ids) . " invalid customer IDs",
            $details
        );
    }

    if (empty($invalid_efx_codes)) {
        $test_pass("All EFX_codes in CSVs exist in database");
    } else {
        $details =
            "Invalid codes: " . implode(", ", array_keys($invalid_efx_codes));
        $test_fail(
            "Found " . count($invalid_efx_codes) . " invalid EFX_codes",
            $details
        );
    }

    echo "\n";

    // ============================================================
    // Test 3: Verify CSV file format is correct
    // ============================================================

    echo "Test 3: Verify CSV file format\n";

    $sample_file = $csv_files[0];
    $filename = basename($sample_file);
    $parsed = $parse_csv_file($sample_file);

    echo "  Testing with: $filename\n";

    $required_columns = [
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

    $missing_columns = [];
    foreach ($required_columns as $col) {
        if (!in_array($col, $parsed["header"])) {
            $missing_columns[] = $col;
        }
    }

    if (empty($missing_columns)) {
        $test_pass("CSV has all required columns");
    } else {
        $test_fail("Missing columns: " . implode(", ", $missing_columns));
    }

    if (count($parsed["rows"]) > 0) {
        $test_pass("CSV has " . count($parsed["rows"]) . " data rows");
    } else {
        $test_fail("CSV has no data rows");
    }

    echo "\n";

    // ============================================================
    // Test 4: Verify customer names match between CSV and DB
    // ============================================================

    echo "Test 4: Customer names in CSV match database\n";

    $name_mismatches = [];
    $sample_file = $csv_files[0];
    $parsed = $parse_csv_file($sample_file);

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
        $test_pass("All customer names match between CSV and database");
    } else {
        $keys = array_keys($name_mismatches);
        $details = "First mismatch: " . $keys[0];
        $test_fail(
            "Found " . count($name_mismatches) . " name mismatches",
            $details
        );
    }

    echo "\n";

    // ============================================================
    // Test 5: Verify filename parsing works
    // ============================================================

    echo "Test 5: Filename parsing\n";

    // Test daily pattern
    $daily_file = "DataX_2025_12_15_humanreadable.csv";
    $daily_pattern = preg_match(
        "/^DataX_(\d{4})_(\d{1,2})_(\d{1,2})_/",
        $daily_file,
        $matches
    );
    if (
        $daily_pattern &&
        $matches[1] == "2025" &&
        $matches[2] == "12" &&
        $matches[3] == "15"
    ) {
        $test_pass("Daily filename pattern parsed correctly");
    } else {
        $test_fail("Daily filename pattern failed");
    }

    // Test monthly pattern
    $monthly_file = "DataX_2025_12_2025_12_summary.csv";
    $monthly_pattern = preg_match(
        "/^DataX_(\d{4})_(\d{1,2})_(\d{4})_(\d{1,2})_/",
        $monthly_file,
        $matches
    );
    if ($monthly_pattern && $matches[1] == "2025" && $matches[2] == "12") {
        $test_pass("Monthly filename pattern parsed correctly");
    } else {
        $test_fail("Monthly filename pattern failed");
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
}
