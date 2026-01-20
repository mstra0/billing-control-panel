# Test Architecture Plan

*Created: January 17, 2026*

## Overview

**Current State:** 0 tests for 169 functions
**Goal:** Comprehensive test coverage for all business logic

## Constraints

- PHP 5.6 compatibility required
- Single-file application architecture
- SQLite database (can use in-memory for tests)
- No external dependencies (no Composer, no PHPUnit framework)

## Test Strategy

### Approach: Simple Built-in Test Runner

Since we can't use PHPUnit (PHP 5.6 + no Composer), we'll build a simple test runner:

```php
// tests.php - standalone test file
// Run with: php tests.php (or via browser)

function assert_equals($expected, $actual, $message = '') { ... }
function assert_true($value, $message = '') { ... }
function assert_false($value, $message = '') { ... }
function assert_null($value, $message = '') { ... }
function assert_not_null($value, $message = '') { ... }
function assert_contains($needle, $haystack, $message = '') { ... }
function assert_count($expected, $array, $message = '') { ... }
function assert_array_has_key($key, $array, $message = '') { ... }
```

### Test Database Strategy

- Use separate SQLite database: `test_control_panel.db`
- Fresh database for each test run (delete and recreate)
- Seed with known test fixtures
- Never touch production/mock database

---

## Function Categories & Test Priority

### PRIORITY 1: Core Business Logic (CRITICAL)
These functions calculate money - must be 100% correct.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `calculate_escalated_price()` | 3480 | Percentage only, fixed only, both, no escalators, with delays, future date, past date |
| `calculate_monthly_minimum_gap()` | 2207 | Below minimum, at minimum, above minimum, no minimum set |
| `get_effective_customer_tiers()` | 2118 | Customer override, group inheritance, default fallback, mixed |
| `get_effective_group_tiers()` | 2046 | Group override, default fallback |
| `get_effective_billing_flags()` | 3394 | Customer override, group override, default, system defaults |
| `get_effective_commission_rate()` | 2796 | LMS override, default rate |
| `generate_tier_pricing_csv()` | 3557 | Full generation, empty customers, with escalators, with flags |

**Estimated: ~50 test cases**

### PRIORITY 2: Data Parsing (HIGH)
These parse external data - errors here corrupt the system.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `parse_billing_filename()` | 3033 | Daily pattern, monthly pattern, invalid patterns, edge cases |
| `parse_billing_csv()` | 3070 | Valid CSV, missing columns, empty file, malformed rows |
| `import_billing_report()` | 3151 | New import, duplicate detection, invalid data |
| `import_transaction_types_csv()` | 3361 | Valid import, partial data, duplicates |
| `csv_read()` | 872 | Valid file, missing file, empty file, large file |
| `csv_write()` | 922 | Normal write, with headers, special characters |
| `csv_escape()` | 3685 | Commas, quotes, newlines, null, normal strings |

**Estimated: ~40 test cases**

### PRIORITY 3: CRUD Operations (MEDIUM)
Standard database operations - less likely to have subtle bugs.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `save_default_tiers()` | 1960 | Create new, update existing, multiple tiers |
| `save_group_tiers()` | 2009 | Create, update, clear |
| `save_customer_tiers()` | 2092 | Create, update |
| `save_customer_settings()` | 2180 | All fields, partial fields |
| `save_escalators()` | 2684 | Multiple years, with adjustments |
| `apply_escalator_delay()` | 2720 | Single delay, multiple delays |
| `save_lms()` | 2807 | New LMS, update existing |
| `save_billing_flags()` | 3458 | Each level, all flags |
| `save_transaction_type()` | 3333 | New, update existing |
| `save_service_cogs()` | 2962 | New, update |
| `toggle_rule_mask()` | 4640 | Mask on, mask off |
| `assign_customer_lms()` | 2938 | Valid assignment, reassignment |

**Estimated: ~35 test cases**

### PRIORITY 4: Query/Retrieval (MEDIUM)
Get functions - should return correct data shapes.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `get_all_services()` | 1915 | Empty, with data |
| `get_current_default_tiers()` | 1935 | With data, no data, effective date logic |
| `get_current_group_tiers()` | 1985 | With data, no data |
| `get_current_customer_tiers()` | 2068 | With data, no data |
| `get_current_customer_settings()` | 2155 | With settings, defaults |
| `get_current_escalators()` | 2661 | With escalators, none |
| `get_escalator_delays()` | 2708 | With delays, none |
| `get_total_delay_months()` | 2732 | Multiple delays sum |
| `get_all_lms()` | 2749 | Empty, with data |
| `get_lms()` | 2756 | Exists, not exists |
| `get_default_commission_rate()` | 2764 | Set, not set |
| `get_service_cogs()` | 2949 | Set, not set |
| `get_billing_reports()` | 3252 | By type, all, limit |
| `get_billing_report_lines()` | 3270 | With lines, empty |
| `get_all_transaction_types()` | 3313 | Empty, with data |
| `get_customers_by_lms()` | 2911 | With customers, none |
| `get_customers_without_lms()` | 2925 | Some unassigned, all assigned |
| `get_customers_with_minimums()` | 2232 | With minimums, none |

**Estimated: ~45 test cases**

### PRIORITY 5: Utility Functions (LOW)
Helper functions - simple logic, less risk.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `h()` | 848 | HTML entities, null, special chars |
| `safe_filename()` | 811 | Special chars, spaces, unicode |
| `generate_filename()` | 822 | Prefix, extension |
| `format_filesize()` | 832 | Bytes, KB, MB, GB |
| `paginate()` | 574 | First page, middle, last, single page |
| `get_shared_path()` | 743 | Mock mode, production mode |
| `is_valid_filepath()` | 1220 | Valid paths, traversal attacks |

**Estimated: ~20 test cases**

### PRIORITY 6: Dashboard/Alert Functions (LOW)
Read-only aggregations - less critical.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `get_dashboard_alerts()` | 1355 | Various alert conditions |
| `get_upcoming_escalators()` | 1408 | Within range, none |
| `get_customers_with_masked_rules()` | 1460 | With masks, none |
| `get_upcoming_annualized_resets()` | 1494 | Within range, none |
| `get_billing_summary_by_customer()` | 3289 | With data, empty |

**Estimated: ~15 test cases**

### PRIORITY 7: History Functions (LOW)
Audit queries - straightforward.

| Function | Lines | Test Cases Needed |
|----------|-------|-------------------|
| `get_pricing_history()` | 4841 | With history, filtered |
| `get_settings_history()` | 4887 | With history, filtered |
| `get_escalator_history()` | 4930 | With history, filtered |
| `get_rule_mask_history()` | 4987 | With history, filtered |

**Estimated: ~10 test cases**

### NOT TESTED: Action & Render Functions (~70 functions)
These are controller/view functions that:
- Call other functions we DO test
- Generate HTML output
- Handle HTTP request/response

Integration testing these would require mocking HTTP context. Focus on unit testing the logic they call instead.

---

## Test File Structure

```
/home/user/dev/PHP/
├── control_panel.php          # Main application
├── tests/
│   ├── bootstrap.php          # Test framework, helpers, DB setup
│   ├── fixtures.php           # Test data factories
│   ├── qa_dashboard.php          # Test runner (CLI + browser)
│   │
│   ├── unit/
│   │   ├── test_escalators.php       # calculate_escalated_price, delays
│   │   ├── test_inheritance.php      # get_effective_* functions
│   │   ├── test_parsing.php          # CSV parsing, filename parsing
│   │   ├── test_generation.php       # generate_tier_pricing_csv
│   │   ├── test_crud.php             # save_* functions
│   │   ├── test_queries.php          # get_* functions
│   │   ├── test_utilities.php        # Helper functions
│   │   ├── test_lms.php              # LMS/commission functions
│   │   └── test_billing_flags.php    # Billing flag inheritance
│   │
│   └── integration/
│       ├── test_full_generation.php  # End-to-end CSV generation
│       └── test_full_ingestion.php   # End-to-end CSV import
```

---

## Test Framework (bootstrap.php)

```php
<?php
// tests/bootstrap.php

// Include main application (for function definitions)
// But we'll use a separate test database

define('TEST_MODE', true);
define('TEST_DB_PATH', __DIR__ . '/test_control_panel.db');

// Test assertion functions
$_test_results = array('passed' => 0, 'failed' => 0, 'errors' => array());

function assert_equals($expected, $actual, $message = '') {
    global $_test_results;
    if ($expected === $actual) {
        $_test_results['passed']++;
        return true;
    }
    $_test_results['failed']++;
    $_test_results['errors'][] = array(
        'type' => 'equals',
        'message' => $message,
        'expected' => var_export($expected, true),
        'actual' => var_export($actual, true)
    );
    return false;
}

function assert_true($value, $message = '') {
    return assert_equals(true, $value, $message);
}

function assert_false($value, $message = '') {
    return assert_equals(false, $value, $message);
}

function assert_null($value, $message = '') {
    return assert_equals(null, $value, $message);
}

function assert_not_null($value, $message = '') {
    global $_test_results;
    if ($value !== null) {
        $_test_results['passed']++;
        return true;
    }
    $_test_results['failed']++;
    $_test_results['errors'][] = array(
        'type' => 'not_null',
        'message' => $message,
        'actual' => 'null'
    );
    return false;
}

function assert_count($expected, $array, $message = '') {
    return assert_equals($expected, count($array), $message);
}

function assert_contains($needle, $haystack, $message = '') {
    global $_test_results;
    if (strpos($haystack, $needle) !== false) {
        $_test_results['passed']++;
        return true;
    }
    $_test_results['failed']++;
    $_test_results['errors'][] = array(
        'type' => 'contains',
        'message' => $message,
        'needle' => $needle,
        'haystack' => substr($haystack, 0, 100) . '...'
    );
    return false;
}

function assert_greater_than($expected, $actual, $message = '') {
    global $_test_results;
    if ($actual > $expected) {
        $_test_results['passed']++;
        return true;
    }
    $_test_results['failed']++;
    $_test_results['errors'][] = array(
        'type' => 'greater_than',
        'message' => $message,
        'expected' => "> $expected",
        'actual' => $actual
    );
    return false;
}

function assert_array_has_key($key, $array, $message = '') {
    global $_test_results;
    if (array_key_exists($key, $array)) {
        $_test_results['passed']++;
        return true;
    }
    $_test_results['failed']++;
    $_test_results['errors'][] = array(
        'type' => 'array_has_key',
        'message' => $message,
        'key' => $key,
        'keys' => implode(', ', array_keys($array))
    );
    return false;
}

// Test runner
function run_test($name, $callback) {
    global $_test_results;
    echo "  - $name ... ";
    try {
        $callback();
        echo "PASS\n";
    } catch (Exception $e) {
        $_test_results['failed']++;
        $_test_results['errors'][] = array(
            'type' => 'exception',
            'message' => $name,
            'error' => $e->getMessage()
        );
        echo "FAIL (exception: {$e->getMessage()})\n";
    }
}

function test_summary() {
    global $_test_results;
    echo "\n========================================\n";
    echo "RESULTS: {$_test_results['passed']} passed, {$_test_results['failed']} failed\n";
    
    if (!empty($_test_results['errors'])) {
        echo "\nFAILURES:\n";
        foreach ($_test_results['errors'] as $error) {
            echo "  [{$error['type']}] {$error['message']}\n";
            if (isset($error['expected'])) {
                echo "    Expected: {$error['expected']}\n";
                echo "    Actual:   {$error['actual']}\n";
            }
        }
    }
    
    echo "========================================\n";
    return $_test_results['failed'] === 0;
}

// Database setup for tests
function setup_test_database() {
    global $_sqlite_db;
    
    // Delete existing test database
    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }
    
    // Force new connection to test database
    $_sqlite_db = null;
    
    // Initialize schema (will create fresh)
    sqlite_db();
}

function teardown_test_database() {
    global $_sqlite_db;
    $_sqlite_db = null;
    
    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }
}
```

---

## Sample Test File (test_escalators.php)

```php
<?php
// tests/unit/test_escalators.php

require_once __DIR__ . '/../bootstrap.php';

echo "Testing: Escalator Calculations\n";
echo "================================\n";

// Setup: Create test customer with escalators
function setup_escalator_test_data() {
    // Create a customer
    sqlite_execute(
        "INSERT INTO customers (id, name, status) VALUES (?, ?, ?)",
        array(1, 'Test Customer', 'active')
    );
    
    // Create a service
    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (?, ?)",
        array(1, 'Test Service')
    );
}

run_test('No escalators returns base price', function() {
    setup_test_database();
    setup_escalator_test_data();
    
    $result = calculate_escalated_price(100.00, 1, '2026-01-01');
    assert_equals(100.00, $result, 'Should return base price when no escalators');
});

run_test('Percentage escalator in year 2', function() {
    setup_test_database();
    setup_escalator_test_data();
    
    // Add 5% escalator for year 2
    save_escalators(1, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');
    
    // Test in year 2 (after 2026-01-01)
    $result = calculate_escalated_price(100.00, 1, '2026-02-01');
    assert_equals(105.00, $result, 'Should apply 5% escalation');
});

run_test('Fixed adjustment escalator', function() {
    setup_test_database();
    setup_escalator_test_data();
    
    // Add $10 fixed adjustment for year 2
    save_escalators(1, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 0, 'fixed_adjustment' => 10)
    ), '2025-01-01');
    
    $result = calculate_escalated_price(100.00, 1, '2026-02-01');
    assert_equals(110.00, $result, 'Should apply $10 fixed adjustment');
});

run_test('Combined percentage and fixed adjustment', function() {
    setup_test_database();
    setup_escalator_test_data();
    
    // Add 5% + $10 for year 2
    save_escalators(1, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 10)
    ), '2025-01-01');
    
    $result = calculate_escalated_price(100.00, 1, '2026-02-01');
    assert_equals(115.00, $result, 'Should apply 5% then $10');
});

run_test('Escalator with delay', function() {
    setup_test_database();
    setup_escalator_test_data();
    
    // Add escalator for year 2
    save_escalators(1, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');
    
    // Add 2-month delay
    apply_escalator_delay(1, 2, 2);
    
    // Test just after normal year 2 start - should NOT apply yet
    $result = calculate_escalated_price(100.00, 1, '2026-01-15');
    assert_equals(100.00, $result, 'Should not apply escalator during delay period');
    
    // Test after delay period
    $result = calculate_escalated_price(100.00, 1, '2026-03-15');
    assert_equals(105.00, $result, 'Should apply escalator after delay');
});

run_test('Date before escalator start returns base price', function() {
    setup_test_database();
    setup_escalator_test_data();
    
    save_escalators(1, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-06-01');
    
    // Test before escalator start date
    $result = calculate_escalated_price(100.00, 1, '2025-01-01');
    assert_equals(100.00, $result, 'Should return base price before escalator starts');
});

test_summary();
```

---

## Sample Test File (test_parsing.php)

```php
<?php
// tests/unit/test_parsing.php

require_once __DIR__ . '/../bootstrap.php';

echo "Testing: CSV Parsing Functions\n";
echo "==============================\n";

run_test('parse_billing_filename - daily pattern', function() {
    $result = parse_billing_filename('DataX_2025_01_15_report.csv');
    
    assert_not_null($result, 'Should parse daily filename');
    assert_equals('daily', $result['type'], 'Should detect daily type');
    assert_equals(2025, $result['year'], 'Should extract year');
    assert_equals(1, $result['month'], 'Should extract month');
    assert_equals(15, $result['day'], 'Should extract day');
});

run_test('parse_billing_filename - monthly pattern', function() {
    $result = parse_billing_filename('DataX_2025_01_2025_01_final.csv');
    
    assert_not_null($result, 'Should parse monthly filename');
    assert_equals('monthly', $result['type'], 'Should detect monthly type');
    assert_equals(2025, $result['year'], 'Should extract start year');
    assert_equals(1, $result['month'], 'Should extract start month');
});

run_test('parse_billing_filename - invalid pattern', function() {
    $result = parse_billing_filename('random_file.csv');
    assert_false($result, 'Should return false for invalid pattern');
});

run_test('parse_billing_csv - valid content', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,101,Acme Corp,HIT001,Credit Check,0.50,100,50.00,CC001,BIL001\n";
    $csv .= "2025,1,101,Acme Corp,HIT002,ID Verify,0.25,200,50.00,IV001,BIL002\n";
    
    $result = parse_billing_csv($csv);
    
    assert_count(0, $result['errors'], 'Should have no errors');
    assert_count(2, $result['rows'], 'Should parse 2 rows');
    assert_equals(101, $result['rows'][0]['cust_id'], 'Should parse customer ID');
    assert_equals(0.50, $result['rows'][0]['actual_unit_cost'], 'Should parse cost as float');
});

run_test('parse_billing_csv - missing required column', function() {
    $csv = "y,m,cust_id,cust_name\n";
    $csv .= "2025,1,101,Acme Corp\n";
    
    $result = parse_billing_csv($csv);
    
    assert_greater_than(0, count($result['errors']), 'Should have errors for missing columns');
});

run_test('parse_billing_csv - empty file', function() {
    $result = parse_billing_csv('');
    
    assert_greater_than(0, count($result['errors']), 'Should error on empty file');
});

run_test('csv_escape - normal string', function() {
    assert_equals('hello', csv_escape('hello'), 'Normal string unchanged');
});

run_test('csv_escape - string with comma', function() {
    assert_equals('"hello,world"', csv_escape('hello,world'), 'Should quote string with comma');
});

run_test('csv_escape - string with quote', function() {
    assert_equals('"say ""hello"""', csv_escape('say "hello"'), 'Should escape quotes');
});

run_test('csv_escape - null value', function() {
    assert_equals('', csv_escape(null), 'Should return empty string for null');
});

test_summary();
```

---

## Test Execution

### CLI Execution

```bash
# Run all tests
cd /home/user/dev/PHP
podman exec -i kind_dijkstra php /var/www/html/tests/qa_dashboard.php

# Run specific test file
podman exec -i kind_dijkstra php /var/www/html/tests/unit/test_escalators.php
```

### Browser Execution

Navigate to: `http://localhost:8080/tests/qa_dashboard.php`

---

## Test Coverage Summary

| Category | Functions | Test Cases | Priority |
|----------|-----------|------------|----------|
| Core Business Logic | 7 | ~50 | CRITICAL |
| Data Parsing | 7 | ~40 | HIGH |
| CRUD Operations | 12 | ~35 | MEDIUM |
| Query/Retrieval | 18 | ~45 | MEDIUM |
| Utility Functions | 7 | ~20 | LOW |
| Dashboard/Alerts | 5 | ~15 | LOW |
| History Functions | 4 | ~10 | LOW |
| **TOTAL** | **60** | **~215** | - |

**Not Tested:** 109 action/render functions (controller/view layer)

---

## Implementation Order

1. **Week 1:** Bootstrap + test_escalators.php + test_inheritance.php
2. **Week 2:** test_parsing.php + test_generation.php
3. **Week 3:** test_crud.php + test_queries.php
4. **Week 4:** test_utilities.php + test_lms.php + test_billing_flags.php
5. **Week 5:** Integration tests + cleanup

---

## Next Steps

1. Create `/tests/` directory structure
2. Implement `bootstrap.php` with test framework
3. Implement `fixtures.php` with test data factories
4. Start with PRIORITY 1: `test_escalators.php`
5. Run tests after each function implementation
6. Add tests to CI (if/when we have CI)

---

*Ready to implement tests on your command.*
