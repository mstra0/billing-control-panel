# QA Test Template Pattern

## Overview

Each test file serves THREE purposes:

1. **Automated Test** - When included by `qa_dashboard.php`, runs assertions
2. **QA HTML Page** - When navigated directly in browser, shows friendly UI
3. **Live Demo** - Visual examples proving the functionality works

## Detection Logic

```php
// Detect how we're being accessed
$is_cli = php_sapi_name() === 'cli';
$is_included = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== basename(__FILE__));
$is_direct_browser = !$is_cli && !$is_included;
```

## File Structure

```php
<?php
/**
 * Test: [Feature Name]
 * 
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 */

// ============================================================
// MODE DETECTION
// ============================================================
$is_cli = php_sapi_name() === 'cli';
$is_included = defined('TEST_RUNNER_ACTIVE');
$is_qa_mode = !$is_cli && !$is_included;

// ============================================================
// QA MODE: Show HTML page with demo
// ============================================================
if ($is_qa_mode) {
    // Bootstrap for standalone access
    require_once __DIR__ . '/../bootstrap.php';
    require_once __DIR__ . '/../fixtures.php';
    setup_test_database();
    
    render_qa_page();
    exit;
}

// ============================================================
// TEST MODE: Run assertions (CLI or included)
// ============================================================
echo "Testing: [Feature Name]\n";
echo "========================\n";

run_test('test name', function() {
    // assertions...
});

// ... more tests ...

test_summary();

// ============================================================
// QA PAGE RENDERER
// ============================================================
function render_qa_page() {
?>
<!DOCTYPE html>
<html>
<head>
    <title>QA: [Feature Name]</title>
    <style>
        /* Include standard QA styles */
    </style>
</head>
<body>
    <h1>[Feature Name] - QA Test Page</h1>
    
    <section class="demo">
        <h2>Live Demo</h2>
        <!-- Interactive demo showing the feature working -->
    </section>
    
    <section class="tests">
        <h2>Automated Tests</h2>
        <!-- Run and display test results visually -->
    </section>
    
    <section class="examples">
        <h2>Examples</h2>
        <!-- Code examples and expected outputs -->
    </section>
</body>
</html>
<?php
}
```

## QA Page Requirements

1. **Header** - Clear title, what's being tested
2. **Status Badge** - PASS/FAIL indicator
3. **Live Demo** - Interactive example (input -> output)
4. **Test Results** - Visual list of all tests with pass/fail
5. **Examples** - Show actual data being tested
6. **Back Link** - Link to QA Dashboard

## Standard Styles

All QA pages should use consistent styling:
- Green = Pass
- Red = Fail  
- Yellow = Warning/Pending
- Clean, readable fonts
- Mobile-friendly

## Integration with QA Dashboard

The QA Dashboard (`qa_dashboard.php`) will:
1. List all test files as clickable links
2. Show pass/fail counts
3. Allow running individual tests
4. Provide "Run All" button
5. Show last run timestamp
