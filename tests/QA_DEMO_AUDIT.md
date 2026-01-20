# QA Demo Audit - Fake vs Real Code

## The Problem

QA test pages have "Live Demo" sections that may contain **fake inline calculations** instead of calling **real production functions**. This is dangerous because:

1. The demo can show correct results while the real code is broken
2. QA thinks they're testing real functionality but they're not
3. Discrepancies between demo and tests go unnoticed

## Example: test_escalators.php (FIXED)

**Before (FAKE):**
```php
// Inline math - NOT calling real function
$after_pct = $base * (1 + $pct / 100);
$result = $after_pct + $fixed;
```

**After (REAL):**
```php
// Creates real customer, saves real escalators, calls real function
$demo_customer_id = create_test_customer(...);
save_escalators($demo_customer_id, ...);
$result = calculate_escalated_price($base, $demo_customer_id, $billing_date);
```

## The Pattern for Real Demos

1. Create test entities using fixture functions
2. Call the REAL production function
3. Display the actual result
4. Clean up test data (delete in correct order for foreign keys)

```php
<?php if (isset($_GET["input"])) {
    // 1. Create real test data
    $customer_id = create_test_customer(array('id' => 99999, ...));
    
    // 2. Set up any required config
    save_some_config($customer_id, ...);
    
    // 3. Call the REAL function
    $result = real_production_function($input, $customer_id);
    
    // 4. Clean up (respect foreign key order)
    sqlite_execute("DELETE FROM child_table WHERE customer_id = ?", array(99999));
    sqlite_execute("DELETE FROM customers WHERE id = ?", array(99999));
    
    // 5. Display result
    ?>
    <div class="result"><?php echo $result; ?></div>
    <?php
} ?>
```

---

## Audit Checklist

| File | Has Demo? | Status | Notes |
|------|-----------|--------|-------|
| test_escalators.php | Yes | REAL | Calls `calculate_escalated_price()` |
| test_calendar.php | Yes | REAL | Calls `get_month_events()` |
| test_crud.php | Yes | REAL | Calls `save_customer_settings()`, `get_current_customer_settings()` |
| test_dashboard.php | Yes | REAL | Calls `get_dashboard_alerts()`, `get_upcoming_escalators()`, `get_sync_status()` |
| test_history.php | Yes | REAL | Calls `get_pricing_history()`, `get_settings_history()`, `get_escalator_history()` |
| test_inheritance.php | Yes | REAL | Calls `get_effective_customer_tiers()` |
| test_mock_mode.php | Yes | REAL | Calls path functions and displays MOCK_MODE constant |
| test_parsing.php | Yes | REAL | Calls `parse_billing_filename()`, `parse_billing_csv()` |
| test_queries.php | Yes | REAL | Calls `get_all_services()`, `get_all_lms()`, `get_all_transaction_types()` |
| test_sync.php | Yes | REAL | Calls `get_sync_status()`, `get_environment_status()`, `get_filesystem_status()` |
| test_utilities.php | Yes | REAL | Calls `h()`, `safe_filename()`, `format_filesize()`, `paginate()`, `generate_filename()` |
| test_ingestion.php | Yes | REAL | Calls `get_ingestion_reports()`, real DB queries, `parse_billing_filename()` |

**Audit completed: 2026-01-20**
**Interactive demos added: 2026-01-19**

All 12 test files now have interactive demos that call REAL production functions.

---

## Files to Audit

```
tests/unit/test_calendar.php
tests/unit/test_crud.php
tests/unit/test_dashboard.php
tests/unit/test_escalators.php      <- FIXED
tests/unit/test_history.php
tests/unit/test_inheritance.php
tests/unit/test_mock_mode.php
tests/unit/test_parsing.php
tests/unit/test_queries.php
tests/unit/test_sync.php
tests/unit/test_utilities.php
tests/integration/test_ingestion.php
```

## How to Audit Each File

1. Open the file and search for `$_GET` in the QA section
2. Look for inline calculations that don't call real functions
3. Check if demo results come from:
   - **BAD:** `$result = $input * 1.05` (fake math)
   - **GOOD:** `$result = calculate_something($input, $customer_id)` (real function)
4. Verify any "calculator" or "try it" sections call production code

## PHP 5.6 Gotcha

If using an LSP/formatter, disable trailing comma insertion. PHP 5.6 doesn't support:
```php
// PHP 7+ only - will break on PHP 5.6
array(
    'key' => 'value',  // <-- trailing comma causes syntax error
)
```
