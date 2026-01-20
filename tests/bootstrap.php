<?php
/**
 * Test Bootstrap
 *
 * Simple PHP 5.6 compatible test framework.
 * No external dependencies required.
 *
 * Usage:
 *   require_once 'bootstrap.php';
 *   run_test('test name', function() { assert_equals(1, 1); });
 *   test_summary();
 */

// Prevent direct browser access showing errors before we're ready
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Test mode flag - used by control_panel.php to use test database
define("TEST_MODE", true);
define("TEST_DB_PATH", "/tmp/test_control_panel.db");

// Track test results
$_test_results = [
    "passed" => 0,
    "failed" => 0,
    "errors" => [],
    "current_test" => "",
];

$_test_suites = [];
$_current_suite = "default";

// ============================================================
// ASSERTION FUNCTIONS
// ============================================================

/**
 * Assert two values are exactly equal (===)
 */
function assert_equals($expected, $actual, $message = "")
{
    global $_test_results;
    if ($expected === $actual) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "equals",
        "message" => $message,
        "expected" => var_export($expected, true),
        "actual" => var_export($actual, true),
    ];
    return false;
}

/**
 * Assert two values are equal with type coercion (==)
 */
function assert_loose_equals($expected, $actual, $message = "")
{
    global $_test_results;
    if ($expected == $actual) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "loose_equals",
        "message" => $message,
        "expected" => var_export($expected, true),
        "actual" => var_export($actual, true),
    ];
    return false;
}

/**
 * Assert value is true
 */
function assert_true($value, $message = "")
{
    global $_test_results;
    if ($value === true) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "true",
        "message" => $message,
        "expected" => "true",
        "actual" => var_export($value, true),
    ];
    return false;
}

/**
 * Assert value is false
 */
function assert_false($value, $message = "")
{
    global $_test_results;
    if ($value === false) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "false",
        "message" => $message,
        "expected" => "false",
        "actual" => var_export($value, true),
    ];
    return false;
}

/**
 * Assert value is null
 */
function assert_null($value, $message = "")
{
    global $_test_results;
    if ($value === null) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "null",
        "message" => $message,
        "expected" => "null",
        "actual" => var_export($value, true),
    ];
    return false;
}

/**
 * Assert value is not null
 */
function assert_not_null($value, $message = "")
{
    global $_test_results;
    if ($value !== null) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "not_null",
        "message" => $message,
        "expected" => "not null",
        "actual" => "null",
    ];
    return false;
}

/**
 * Assert array has expected count
 */
function assert_count($expected, $array, $message = "")
{
    global $_test_results;
    $actual = is_array($array) ? count($array) : -1;
    if ($actual === $expected) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "count",
        "message" => $message,
        "expected" => $expected,
        "actual" => $actual,
    ];
    return false;
}

/**
 * Assert string contains substring
 */
function assert_contains($needle, $haystack, $message = "")
{
    global $_test_results;
    if (is_string($haystack) && strpos($haystack, $needle) !== false) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "contains",
        "message" => $message,
        "expected" => "string containing '$needle'",
        "actual" => is_string($haystack)
            ? substr($haystack, 0, 100) . (strlen($haystack) > 100 ? "..." : "")
            : var_export($haystack, true),
    ];
    return false;
}

/**
 * Assert string does not contain substring
 */
function assert_not_contains($needle, $haystack, $message = "")
{
    global $_test_results;
    if (is_string($haystack) && strpos($haystack, $needle) === false) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "not_contains",
        "message" => $message,
        "expected" => "string NOT containing '$needle'",
        "actual" => substr($haystack, 0, 100),
    ];
    return false;
}

/**
 * Assert value is greater than expected
 */
function assert_greater_than($expected, $actual, $message = "")
{
    global $_test_results;
    if ($actual > $expected) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "greater_than",
        "message" => $message,
        "expected" => "> $expected",
        "actual" => $actual,
    ];
    return false;
}

/**
 * Assert value is less than expected
 */
function assert_less_than($expected, $actual, $message = "")
{
    global $_test_results;
    if ($actual < $expected) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "less_than",
        "message" => $message,
        "expected" => "< $expected",
        "actual" => $actual,
    ];
    return false;
}

/**
 * Assert array has key
 */
function assert_array_has_key($key, $array, $message = "")
{
    global $_test_results;
    if (is_array($array) && array_key_exists($key, $array)) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "array_has_key",
        "message" => $message,
        "expected" => "array with key '$key'",
        "actual" => is_array($array)
            ? "keys: " . implode(", ", array_keys($array))
            : var_export($array, true),
    ];
    return false;
}

/**
 * Assert value is empty (empty string, empty array, null, 0, false)
 */
function assert_empty($value, $message = "")
{
    global $_test_results;
    if (empty($value)) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "empty",
        "message" => $message,
        "expected" => "empty value",
        "actual" => var_export($value, true),
    ];
    return false;
}

/**
 * Assert value is not empty
 */
function assert_not_empty($value, $message = "")
{
    global $_test_results;
    if (!empty($value)) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "not_empty",
        "message" => $message,
        "expected" => "non-empty value",
        "actual" => var_export($value, true),
    ];
    return false;
}

/**
 * Assert two floats are equal within a delta
 */
function assert_float_equals($expected, $actual, $delta = 0.0001, $message = "")
{
    global $_test_results;
    if (abs($expected - $actual) < $delta) {
        $_test_results["passed"]++;
        return true;
    }
    $_test_results["failed"]++;
    $_test_results["errors"][] = [
        "test" => $_test_results["current_test"],
        "type" => "float_equals",
        "message" => $message,
        "expected" => "$expected (±$delta)",
        "actual" => $actual,
    ];
    return false;
}

// ============================================================
// TEST RUNNER FUNCTIONS
// ============================================================

/**
 * Run a single test
 */
function run_test($name, $callback)
{
    global $_test_results;
    $_test_results["current_test"] = $name;

    $start_passed = $_test_results["passed"];
    $start_failed = $_test_results["failed"];

    echo "  - $name ... ";

    try {
        $callback();

        // Check if any assertions failed during this test
        if ($_test_results["failed"] > $start_failed) {
            echo "FAIL\n";
        } elseif ($_test_results["passed"] > $start_passed) {
            echo "PASS\n";
        } else {
            echo "NO ASSERTIONS\n";
        }
    } catch (Exception $e) {
        $_test_results["failed"]++;
        $_test_results["errors"][] = [
            "test" => $name,
            "type" => "exception",
            "message" => $e->getMessage(),
            "expected" => "no exception",
            "actual" => get_class($e) . ": " . $e->getMessage(),
        ];
        echo "ERROR ({$e->getMessage()})\n";
    }

    $_test_results["current_test"] = "";
}

/**
 * Define a test suite
 */
function test_suite($name, $callback)
{
    global $_current_suite;
    $_current_suite = $name;

    echo "\n";
    echo "========================================\n";
    echo "TEST SUITE: $name\n";
    echo "========================================\n";

    $callback();

    $_current_suite = "default";
}

/**
 * Print test summary and return success/failure
 */
function test_summary()
{
    global $_test_results;

    echo "\n";
    echo "========================================\n";
    echo "TEST RESULTS\n";
    echo "========================================\n";
    echo "Passed: {$_test_results["passed"]}\n";
    echo "Failed: {$_test_results["failed"]}\n";

    if (!empty($_test_results["errors"])) {
        echo "\n";
        echo "FAILURES:\n";
        echo "----------------------------------------\n";
        foreach ($_test_results["errors"] as $error) {
            echo "[{$error["type"]}] {$error["test"]}\n";
            if (!empty($error["message"])) {
                echo "  Message:  {$error["message"]}\n";
            }
            echo "  Expected: {$error["expected"]}\n";
            echo "  Actual:   {$error["actual"]}\n";
            echo "\n";
        }
    }

    echo "========================================\n";

    $success = $_test_results["failed"] === 0;
    echo $success ? "ALL TESTS PASSED!\n" : "SOME TESTS FAILED!\n";
    echo "========================================\n";

    return $success;
}

/**
 * Reset test results (for running multiple test files)
 */
function reset_test_results()
{
    global $_test_results;
    $_test_results = [
        "passed" => 0,
        "failed" => 0,
        "errors" => [],
        "current_test" => "",
    ];
}

// ============================================================
// DATABASE SETUP FUNCTIONS
// ============================================================

/**
 * Setup fresh test database
 */
function setup_test_database()
{
    global $_sqlite_db;

    // Close existing connection
    $_sqlite_db = null;

    // Delete existing test database
    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }

    // The control_panel.php will create fresh schema on sqlite_db() call
}

/**
 * Teardown test database
 */
function teardown_test_database()
{
    global $_sqlite_db;
    $_sqlite_db = null;

    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }
}

/**
 * Get the test database path (used by control_panel.php when TEST_MODE is true)
 */
function get_test_db_path()
{
    return TEST_DB_PATH;
}

// ============================================================
// INCLUDE CONTROL PANEL (for function definitions)
// ============================================================

// We need to modify how control_panel.php gets its database path
// It should check for TEST_MODE and use TEST_DB_PATH

// For now, we'll include it and override the database path
$_original_cwd = getcwd();
chdir(dirname(__DIR__)); // Change to PHP directory

// Capture and discard any output from control_panel.php
ob_start();

// Include the main application to get all function definitions
require_once dirname(__DIR__) . "/control_panel.php";

ob_end_clean();

chdir($_original_cwd);

// Include test fixtures (factory functions)
require_once __DIR__ . "/fixtures.php";

echo "Test framework loaded.\n";
echo "Test database: " . TEST_DB_PATH . "\n";
