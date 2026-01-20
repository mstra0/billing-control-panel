<?php
/**
 * Test Runner / QA Dashboard
 *
 * CLI: Run all tests or specific test files
 * Browser: Display QA Dashboard with test status and links to individual QA pages
 *
 * Usage (CLI):
 *   php qa_dashboard.php              # Run all tests
 *   php qa_dashboard.php escalators   # Run only escalator tests
 *   php qa_dashboard.php parsing      # Run only parsing tests
 *
 * Usage (Browser):
 *   Navigate to qa_dashboard.php for QA Dashboard
 *   Add ?run=1 to execute tests and show results
 */

// Set timezone to avoid warnings
date_default_timezone_set("UTC");

// Flag to tell test files they're being included
define("TEST_RUNNER_ACTIVE", true);

// Determine if running from CLI or browser
$is_cli = php_sapi_name() === "cli";

// ============================================================
// BROWSER MODE: QA DASHBOARD OR REPORT DOWNLOAD
// ============================================================
if (!$is_cli) {
    // Check if requesting a downloadable report
    if (isset($_GET["report"])) {
        generate_downloadable_report($_GET["report"]);
        exit();
    }
    render_qa_dashboard();
    exit();
}

// ============================================================
// CLI MODE: RUN TESTS
// ============================================================
echo "========================================\n";
echo "CONTROL PANEL TEST RUNNER\n";
echo "========================================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Date: " . date("Y-m-d H:i:s") . "\n";
echo "========================================\n\n";

// Load bootstrap (includes control_panel.php)
require_once __DIR__ . "/bootstrap.php";

// Load fixtures
require_once __DIR__ . "/fixtures.php";

// Determine which tests to run
$test_filter = null;
if (isset($argv[1])) {
    $test_filter = $argv[1];
}

// Find all test files
$test_files = glob(__DIR__ . "/unit/test_*.php");
$integration_files = glob(__DIR__ . "/integration/test_*.php");
$all_test_files = array_merge($test_files, $integration_files);

if (empty($all_test_files)) {
    echo "No test files found!\n";
    echo "Expected test files in:\n";
    echo "  - tests/unit/test_*.php\n";
    echo "  - tests/integration/test_*.php\n";
    exit(1);
}

// Filter if specified
if ($test_filter) {
    $filtered = [];
    foreach ($all_test_files as $file) {
        if (strpos(basename($file), $test_filter) !== false) {
            $filtered[] = $file;
        }
    }
    $all_test_files = $filtered;

    if (empty($all_test_files)) {
        echo "No test files matching '$test_filter' found!\n";
        exit(1);
    }

    echo "Running tests matching: $test_filter\n\n";
} else {
    echo "Running all tests (" . count($all_test_files) . " files)\n\n";
}

// Track overall results
$overall_passed = 0;
$overall_failed = 0;
$files_with_failures = [];

// Run each test file
foreach ($all_test_files as $test_file) {
    $filename = basename($test_file);

    echo "\n";
    echo "########################################\n";
    echo "FILE: $filename\n";
    echo "########################################\n";

    // Reset for this file
    reset_test_results();
    reset_fixture_counters();

    // Setup fresh database
    setup_test_database();

    // Run the test file
    try {
        require $test_file;
    } catch (Exception $e) {
        echo "ERROR loading test file: " . $e->getMessage() . "\n";
        $overall_failed++;
        $files_with_failures[] = $filename;
        continue;
    }

    // Cleanup
    teardown_test_database();

    // Collect results
    global $_test_results;
    $overall_passed += $_test_results["passed"];
    $overall_failed += $_test_results["failed"];

    if ($_test_results["failed"] > 0) {
        $files_with_failures[] = $filename;
    }
}

// Final summary
echo "\n";
echo "========================================\n";
echo "FINAL SUMMARY\n";
echo "========================================\n";
echo "Total Passed:  $overall_passed\n";
echo "Total Failed:  $overall_failed\n";
echo "Test Files:    " . count($all_test_files) . "\n";

if (!empty($files_with_failures)) {
    echo "\nFiles with failures:\n";
    foreach ($files_with_failures as $file) {
        echo "  - $file\n";
    }
}

echo "========================================\n";

if ($overall_failed === 0) {
    echo "ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}

// ============================================================
// QA DASHBOARD RENDERER
// ============================================================
function render_qa_dashboard()
{
    // Check if we should run tests
    $run_tests = isset($_GET["run"]) && $_GET["run"] == "1";
    $test_results = [];

    if ($run_tests) {
        $test_results = run_all_tests_for_dashboard();
    }

    // Get list of test files
    $unit_tests = get_unit_test_info();
    $integration_tests = get_integration_test_info();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QA Dashboard - Billing Control Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        header h1 { font-size: 2.5em; margin-bottom: 10px; }
        header p { opacity: 0.9; font-size: 1.1em; }

        .run-button {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            font-size: 1.2em;
            font-weight: bold;
            text-decoration: none;
            border-radius: 50px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .run-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .download-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .download-btn {
            display: inline-block;
            padding: 10px 25px;
            background: rgba(255,255,255,0.2);
            color: white;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            border-radius: 25px;
            border: 2px solid rgba(255,255,255,0.5);
            transition: background 0.2s;
        }
        .download-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .download-btn .icon { margin-right: 5px; }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .summary-card .number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-card .label {
            color: #666;
            text-transform: uppercase;
            font-size: 0.85em;
        }
        .summary-card.passed .number { color: #28a745; }
        .summary-card.failed .number { color: #dc3545; }
        .summary-card.total .number { color: #667eea; }

        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .info-box h3 { color: #0066cc; margin-bottom: 15px; }
        .info-box p { margin-bottom: 10px; }
        .info-box ul { margin-left: 25px; }

        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .test-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .test-card:hover { transform: translateY(-3px); }

        .test-card-header {
            padding: 20px;
            color: white;
        }
        .test-card-header h3 { margin-bottom: 5px; }
        .test-card-header .priority {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
        }

        .test-card-body {
            padding: 20px;
        }
        .test-card-body p { color: #666; margin-bottom: 15px; }
        .test-card-body .functions {
            font-family: monospace;
            font-size: 0.85em;
            color: #495057;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .test-card-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .test-card-footer a {
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
        }
        .view-link { background: #667eea; color: white; }
        .view-link:hover { background: #5a6fd6; }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .status-pass { background: #d4edda; color: #155724; }
        .status-fail { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }

        .theme-critical { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .theme-parsing { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        .theme-queries { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .theme-utilities { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); }
        .theme-crud { background: linear-gradient(135deg, #28a745 0%, #218838 100%); }
        .theme-dashboard { background: linear-gradient(135deg, #fd7e14 0%, #e96b02 100%); }
        .theme-sync { background: linear-gradient(135deg, #20c997 0%, #1aa179 100%); }

        .help-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .help-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .help-section h3 { color: #495057; margin: 20px 0 10px; }
        .help-section ol, .help-section ul { margin-left: 25px; margin-bottom: 15px; }
        .help-section li { margin-bottom: 8px; }

        footer {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>QA Dashboard</h1>
            <p>Billing Control Panel Test Suite</p>
            <a href="?run=1" class="run-button">Run All Tests</a>
            <div class="download-buttons">
                <a href="?report=txt" class="download-btn"><span class="icon">&#128196;</span> Download Report (TXT)</a>
                <a href="?report=csv" class="download-btn"><span class="icon">&#128202;</span> Download Report (CSV)</a>
                <a href="?report=json" class="download-btn"><span class="icon">&#128203;</span> Download Report (JSON)</a>
            </div>
        </header>

        <?php if ($run_tests): ?>
        <div class="summary-cards">
            <div class="summary-card passed">
                <div class="number"><?php echo $test_results["passed"]; ?></div>
                <div class="label">Tests Passed</div>
            </div>
            <div class="summary-card failed">
                <div class="number"><?php echo $test_results["failed"]; ?></div>
                <div class="label">Tests Failed</div>
            </div>
            <div class="summary-card total">
                <div class="number"><?php echo $test_results["passed"] +
                    $test_results["failed"]; ?></div>
                <div class="label">Total Tests</div>
            </div>
            <div class="summary-card">
                <div class="number"><?php echo $test_results["files"]; ?></div>
                <div class="label">Test Files</div>
            </div>
        </div>

        <?php if ($test_results["failed"] > 0): ?>
        <div class="info-box" style="background: #f8d7da; border-color: #f5c6cb;">
            <h3 style="color: #721c24;">Some Tests Failed</h3>
            <p>The following test files had failures:</p>
            <ul>
                <?php foreach ($test_results["failures"] as $file): ?>
                <li><?php echo htmlspecialchars($file); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Click on individual test pages below to see detailed error information.</p>
        </div>
        <?php else: ?>
        <div class="info-box" style="background: #d4edda; border-color: #c3e6cb;">
            <h3 style="color: #155724;">All Tests Passed!</h3>
            <p>Great job! All <?php echo $test_results[
                "passed"
            ]; ?> tests passed successfully.</p>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="info-box">
            <h3>Welcome, QA Team!</h3>
            <p>This dashboard helps you verify the billing control panel is working correctly.</p>
            <ul>
                <li><strong>Run All Tests</strong> - Click the button above to execute all automated tests</li>
                <li><strong>Individual Test Pages</strong> - Click any card below to see detailed demos and test results</li>
                <li><strong>Live Demos</strong> - Each test page includes interactive examples you can try</li>
            </ul>
        </div>
        <?php endif; ?>

        <h2 style="margin-bottom: 20px; color: #333;">Unit Tests</h2>

        <div class="test-grid">
            <?php foreach ($unit_tests as $test): ?>
            <div class="test-card">
                <div class="test-card-header <?php echo $test["theme"]; ?>">
                    <span class="priority"><?php echo $test[
                        "priority"
                    ]; ?></span>
                    <h3><?php echo htmlspecialchars($test["title"]); ?></h3>
                </div>
                <div class="test-card-body">
                    <p><?php echo htmlspecialchars($test["description"]); ?></p>
                    <div class="functions">
                        <?php echo htmlspecialchars($test["functions"]); ?>
                    </div>
                </div>
                <div class="test-card-footer">
                    <?php if (
                        $run_tests &&
                        isset($test_results["by_file"][$test["file"]])
                    ): ?>
                        <?php $file_result =
                            $test_results["by_file"][$test["file"]]; ?>
                        <span class="status-badge <?php echo $file_result[
                            "failed"
                        ] > 0
                            ? "status-fail"
                            : "status-pass"; ?>">
                            <?php echo $file_result["failed"] > 0
                                ? "FAIL"
                                : "PASS"; ?>
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-pending">Not Run</span>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars(
                        $test["url"]
                    ); ?>" class="view-link">View QA Page</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2 style="margin: 40px 0 20px; color: #333;">Integration Tests</h2>

        <div class="test-grid">
            <?php foreach ($integration_tests as $test): ?>
            <div class="test-card">
                <div class="test-card-header <?php echo $test["theme"]; ?>">
                    <span class="priority"><?php echo $test[
                        "priority"
                    ]; ?></span>
                    <h3><?php echo htmlspecialchars($test["title"]); ?></h3>
                </div>
                <div class="test-card-body">
                    <p><?php echo htmlspecialchars($test["description"]); ?></p>
                    <div class="functions">
                        <?php echo htmlspecialchars($test["functions"]); ?>
                    </div>
                </div>
                <div class="test-card-footer">
                    <?php if (
                        $run_tests &&
                        isset($test_results["by_file"][$test["file"]])
                    ): ?>
                        <?php $file_result =
                            $test_results["by_file"][$test["file"]]; ?>
                        <span class="status-badge <?php echo $file_result[
                            "failed"
                        ] > 0
                            ? "status-fail"
                            : "status-pass"; ?>">
                            <?php echo $file_result["failed"] > 0
                                ? "FAIL"
                                : "PASS"; ?>
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-pending">Not Run</span>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars(
                        $test["url"]
                    ); ?>" class="view-link">View QA Page</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="help-section">
            <h2>How to Use This Dashboard</h2>

            <h3>For Quick Verification:</h3>
            <ol>
                <li>Click <strong>"Run All Tests"</strong> at the top of the page</li>
                <li>Wait for tests to complete (usually takes 10-30 seconds)</li>
                <li>Check the summary cards show all tests passed (green)</li>
                <li>If any tests fail, click the specific test card to see details</li>
            </ol>

            <h3>For Detailed Review:</h3>
            <ol>
                <li>Click on any test card to open its individual QA page</li>
                <li>Each page shows:
                    <ul>
                        <li><strong>Live demos</strong> - Interactive examples of the feature</li>
                        <li><strong>Test results</strong> - Pass/fail status for each test case</li>
                        <li><strong>Code examples</strong> - How to use the functions</li>
                    </ul>
                </li>
                <li>Try the interactive demos to verify behavior</li>
            </ol>

            <h3>Understanding Test Priorities:</h3>
            <ul>
                <li><strong>CRITICAL</strong> - Money calculations, billing accuracy (must pass!)</li>
                <li><strong>HIGH</strong> - Core business logic and data integrity</li>
                <li><strong>MEDIUM</strong> - Standard functionality</li>
                <li><strong>LOW</strong> - Utilities and helpers</li>
            </ul>

            <h3>Running Tests from Command Line:</h3>
            <p>For automated testing or CI/CD:</p>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto;">
# Run all tests
php5.6 tests/qa_dashboard.php

# Run specific tests
php5.6 tests/qa_dashboard.php escalators
php5.6 tests/qa_dashboard.php parsing</pre>
        </div>

        <footer>
            <p>QA Dashboard | Billing Control Panel | PHP <?php echo PHP_VERSION; ?></p>
            <p>Last updated: <?php echo date("Y-m-d H:i:s"); ?></p>
        </footer>
    </div>
</body>
</html>
<?php
}

/**
 * Get information about unit test files
 */
function get_unit_test_info()
{
    return [
        [
            "file" => "test_escalators.php",
            "title" => "Escalator Calculations",
            "description" =>
                "Annual price escalation, percentage adjustments, and delay tracking",
            "functions" =>
                "calculate_escalated_price(), save_escalators(), apply_escalator_delay()",
            "priority" => "CRITICAL",
            "theme" => "theme-critical",
            "url" => "unit/test_escalators.php",
        ],
        [
            "file" => "test_inheritance.php",
            "title" => "Pricing Inheritance",
            "description" =>
                "Tier inheritance: Customer > Group > Default pricing resolution",
            "functions" =>
                "get_effective_customer_tiers(), get_effective_group_tiers()",
            "priority" => "CRITICAL",
            "theme" => "theme-critical",
            "url" => "unit/test_inheritance.php",
        ],
        [
            "file" => "test_calendar.php",
            "title" => "Billing Calendar",
            "description" =>
                "Monthly calendar, events, MTD summaries, and completion tracking",
            "functions" =>
                "get_month_events(), get_calendar_year_summary(), is_month_complete()",
            "priority" => "HIGH",
            "theme" => "theme-dashboard",
            "url" => "unit/test_calendar.php",
        ],
        [
            "file" => "test_parsing.php",
            "title" => "CSV Parsing",
            "description" =>
                "Billing file parsing, validation, and import functions",
            "functions" =>
                "parse_billing_filename(), parse_billing_csv(), import_billing_report()",
            "priority" => "HIGH",
            "theme" => "theme-parsing",
            "url" => "unit/test_parsing.php",
        ],
        [
            "file" => "test_crud.php",
            "title" => "CRUD Operations",
            "description" =>
                "Create, read, update, delete operations for all entities",
            "functions" =>
                "save_default_tiers(), save_customer_settings(), save_escalators()",
            "priority" => "HIGH",
            "theme" => "theme-crud",
            "url" => "unit/test_crud.php",
        ],
        [
            "file" => "test_queries.php",
            "title" => "Query Functions",
            "description" => "Data retrieval and query functions",
            "functions" =>
                "get_all_services(), get_current_default_tiers(), get_all_lms()",
            "priority" => "MEDIUM",
            "theme" => "theme-queries",
            "url" => "unit/test_queries.php",
        ],
        [
            "file" => "test_history.php",
            "title" => "History Functions",
            "description" => "Audit trail and history retrieval",
            "functions" =>
                "get_pricing_history(), get_settings_history(), get_escalator_history()",
            "priority" => "MEDIUM",
            "theme" => "theme-queries",
            "url" => "unit/test_history.php",
        ],
        [
            "file" => "test_dashboard.php",
            "title" => "Dashboard & Alerts",
            "description" => "Dashboard alerts, upcoming events, and summaries",
            "functions" => "get_dashboard_alerts(), get_upcoming_escalators()",
            "priority" => "MEDIUM",
            "theme" => "theme-dashboard",
            "url" => "unit/test_dashboard.php",
        ],
        [
            "file" => "test_utilities.php",
            "title" => "Utility Functions",
            "description" =>
                "Helper functions for escaping, formatting, and validation",
            "functions" =>
                "h(), safe_filename(), format_filesize(), paginate()",
            "priority" => "LOW",
            "theme" => "theme-utilities",
            "url" => "unit/test_utilities.php",
        ],
        [
            "file" => "test_mock_mode.php",
            "title" => "Mock Mode",
            "description" =>
                "Mock mode functionality, path switching, directory management",
            "functions" =>
                "get_shared_path(), fix_shared_directory(), ensure_directories()",
            "priority" => "MEDIUM",
            "theme" => "theme-sync",
            "url" => "unit/test_mock_mode.php",
        ],
        [
            "file" => "test_sync.php",
            "title" => "Data Sync",
            "description" =>
                "Remote data synchronization and environment status",
            "functions" =>
                "sync_customers_from_remote(), get_sync_status(), get_filesystem_status()",
            "priority" => "MEDIUM",
            "theme" => "theme-sync",
            "url" => "unit/test_sync.php",
        ],
    ];
}

/**
 * Get information about integration test files
 */
function get_integration_test_info()
{
    return [
        [
            "file" => "test_ingestion.php",
            "title" => "CSV Ingestion",
            "description" =>
                "End-to-end CSV ingestion against real test database with entity validation",
            "functions" =>
                "get_ingestion_reports(), parse_billing_filename(), entity validation",
            "priority" => "HIGH",
            "theme" => "theme-parsing",
            "url" => "integration/test_ingestion.php",
        ],
    ];
}

/**
 * Generate downloadable test report
 */
function generate_downloadable_report($format)
{
    // Suppress bootstrap output
    ob_start();
    require_once __DIR__ . "/bootstrap.php";
    require_once __DIR__ . "/fixtures.php";
    ob_end_clean();

    $test_files = glob(__DIR__ . "/unit/test_*.php");
    $integration_files = glob(__DIR__ . "/integration/test_*.php");
    $all_test_files = array_merge($test_files, $integration_files);

    $results = [
        "passed" => 0,
        "failed" => 0,
        "files" => count($all_test_files),
        "details" => [],
        "timestamp" => date("Y-m-d H:i:s"),
        "php_version" => PHP_VERSION,
    ];

    foreach ($all_test_files as $test_file) {
        $filename = basename($test_file);

        reset_test_results();
        reset_fixture_counters();
        setup_test_database();

        ob_start();
        try {
            require $test_file;
        } catch (Exception $e) {
            $results["details"][] = [
                "file" => $filename,
                "passed" => 0,
                "failed" => 1,
                "status" => "ERROR",
                "error" => $e->getMessage(),
            ];
            ob_end_clean();
            continue;
        }
        ob_end_clean();

        teardown_test_database();

        global $_test_results;
        $file_passed = $_test_results["passed"];
        $file_failed = $_test_results["failed"];

        $results["passed"] += $file_passed;
        $results["failed"] += $file_failed;
        $results["details"][] = [
            "file" => $filename,
            "passed" => $file_passed,
            "failed" => $file_failed,
            "status" => $file_failed > 0 ? "FAIL" : "PASS",
            "errors" => $_test_results["errors"],
        ];
    }

    $results["overall_status"] = $results["failed"] > 0 ? "FAIL" : "PASS";

    // Generate output based on format
    switch ($format) {
        case "csv":
            output_csv_report($results);
            break;
        case "txt":
            output_text_report($results);
            break;
        case "json":
            output_json_report($results);
            break;
        default:
            output_text_report($results);
    }
}

/**
 * Output CSV report
 */
function output_csv_report($results)
{
    $filename = "qa_test_report_" . date("Ymd_His") . ".csv";
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen("php://output", "w");

    // Header info
    fputcsv($output, ["QA Test Report - Billing Control Panel"]);
    fputcsv($output, ["Generated", $results["timestamp"]]);
    fputcsv($output, ["PHP Version", $results["php_version"]]);
    fputcsv($output, ["Overall Status", $results["overall_status"]]);
    fputcsv($output, ["Total Passed", $results["passed"]]);
    fputcsv($output, ["Total Failed", $results["failed"]]);
    fputcsv($output, [""]);

    // Summary by file
    fputcsv($output, ["Test File", "Status", "Passed", "Failed"]);
    foreach ($results["details"] as $detail) {
        fputcsv($output, [
            $detail["file"],
            $detail["status"],
            $detail["passed"],
            $detail["failed"],
        ]);
    }

    // Failed test details
    $has_failures = false;
    foreach ($results["details"] as $detail) {
        if (!empty($detail["errors"])) {
            if (!$has_failures) {
                fputcsv($output, [""]);
                fputcsv($output, ["FAILURE DETAILS"]);
                fputcsv($output, [
                    "File",
                    "Test",
                    "Type",
                    "Expected",
                    "Actual",
                ]);
                $has_failures = true;
            }
            foreach ($detail["errors"] as $error) {
                fputcsv($output, [
                    $detail["file"],
                    $error["test"],
                    $error["type"],
                    $error["expected"],
                    $error["actual"],
                ]);
            }
        }
    }

    fclose($output);
}

/**
 * Output plain text report
 */
function output_text_report($results)
{
    $filename = "qa_test_report_" . date("Ymd_His") . ".txt";
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo "================================================================================\n";
    echo "QA TEST REPORT - BILLING CONTROL PANEL\n";
    echo "================================================================================\n";
    echo "\n";
    echo "Generated:    " . $results["timestamp"] . "\n";
    echo "PHP Version:  " . $results["php_version"] . "\n";
    echo "\n";
    echo "================================================================================\n";
    echo "SUMMARY\n";
    echo "================================================================================\n";
    echo "\n";
    echo "Overall Status:  " . $results["overall_status"] . "\n";
    echo "Total Passed:    " . $results["passed"] . "\n";
    echo "Total Failed:    " . $results["failed"] . "\n";
    echo "Total Tests:     " . ($results["passed"] + $results["failed"]) . "\n";
    echo "Test Files:      " . $results["files"] . "\n";
    echo "\n";
    echo "================================================================================\n";
    echo "RESULTS BY FILE\n";
    echo "================================================================================\n";
    echo "\n";
    echo str_pad("File", 35) .
        str_pad("Status", 10) .
        str_pad("Passed", 10) .
        "Failed\n";
    echo str_repeat("-", 65) . "\n";

    foreach ($results["details"] as $detail) {
        echo str_pad($detail["file"], 35);
        echo str_pad($detail["status"], 10);
        echo str_pad($detail["passed"], 10);
        echo $detail["failed"] . "\n";
    }

    // Failed test details
    $has_failures = false;
    foreach ($results["details"] as $detail) {
        if (!empty($detail["errors"])) {
            if (!$has_failures) {
                echo "\n";
                echo "================================================================================\n";
                echo "FAILURE DETAILS\n";
                echo "================================================================================\n";
                $has_failures = true;
            }
            echo "\n";
            echo "FILE: " . $detail["file"] . "\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($detail["errors"] as $error) {
                echo "  Test:     " . $error["test"] . "\n";
                echo "  Type:     " . $error["type"] . "\n";
                echo "  Expected: " . $error["expected"] . "\n";
                echo "  Actual:   " . $error["actual"] . "\n";
                echo "\n";
            }
        }
    }

    echo "\n";
    echo "================================================================================\n";
    echo "END OF REPORT\n";
    echo "================================================================================\n";
}

/**
 * Output JSON report
 */
function output_json_report($results)
{
    $filename = "qa_test_report_" . date("Ymd_His") . ".json";
    header("Content-Type: application/json");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo json_encode($results, JSON_PRETTY_PRINT);
}

/**
 * Run all tests and collect results for dashboard display
 */
function run_all_tests_for_dashboard()
{
    // Load bootstrap
    require_once __DIR__ . "/bootstrap.php";
    require_once __DIR__ . "/fixtures.php";

    $test_files = glob(__DIR__ . "/unit/test_*.php");
    $integration_files = glob(__DIR__ . "/integration/test_*.php");
    $all_test_files = array_merge($test_files, $integration_files);

    $results = [
        "passed" => 0,
        "failed" => 0,
        "files" => count($all_test_files),
        "failures" => [],
        "by_file" => [],
    ];

    foreach ($all_test_files as $test_file) {
        $filename = basename($test_file);

        // Reset for this file
        reset_test_results();
        reset_fixture_counters();

        // Setup fresh database
        setup_test_database();

        // Capture output
        ob_start();

        try {
            require $test_file;
        } catch (Exception $e) {
            $results["failed"]++;
            $results["failures"][] = $filename;
            ob_end_clean();
            continue;
        }

        ob_end_clean();

        // Cleanup
        teardown_test_database();

        // Collect results
        global $_test_results;
        $file_passed = $_test_results["passed"];
        $file_failed = $_test_results["failed"];

        $results["passed"] += $file_passed;
        $results["failed"] += $file_failed;
        $results["by_file"][$filename] = [
            "passed" => $file_passed,
            "failed" => $file_failed,
        ];

        if ($file_failed > 0) {
            $results["failures"][] = $filename;
        }
    }

    return $results;
}
