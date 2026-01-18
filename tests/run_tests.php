<?php
/**
 * Test Runner
 *
 * Run all tests or specific test files.
 *
 * Usage:
 *   php run_tests.php              # Run all tests
 *   php run_tests.php escalators   # Run only escalator tests
 *   php run_tests.php parsing      # Run only parsing tests
 */

// Set timezone to avoid warnings
date_default_timezone_set('UTC');

// Determine if running from CLI or browser
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "========================================\n";
echo "CONTROL PANEL TEST RUNNER\n";
echo "========================================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Load bootstrap (includes control_panel.php)
require_once __DIR__ . '/bootstrap.php';

// Load fixtures
require_once __DIR__ . '/fixtures.php';

// Determine which tests to run
$test_filter = null;
if ($is_cli && isset($argv[1])) {
    $test_filter = $argv[1];
} elseif (isset($_GET['test'])) {
    $test_filter = $_GET['test'];
}

// Find all test files
$test_files = glob(__DIR__ . '/unit/test_*.php');
$integration_files = glob(__DIR__ . '/integration/test_*.php');
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
    $all_test_files = array_filter($all_test_files, function($file) use ($test_filter) {
        return strpos(basename($file), $test_filter) !== false;
    });

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
$files_with_failures = array();

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
    $overall_passed += $_test_results['passed'];
    $overall_failed += $_test_results['failed'];

    if ($_test_results['failed'] > 0) {
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
