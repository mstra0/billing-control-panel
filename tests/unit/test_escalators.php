<?php
/**
 * Test: Escalator Calculations
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for calculate_escalated_price() and related functions.
 * These are CRITICAL - they calculate money!
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

    ob_start();
    $_qa_test_results = run_escalator_tests();
    $test_output = ob_get_clean();

    render_escalator_qa_page($_qa_test_results, $test_output);
    exit();
}

// ============================================================
// CLI MODE: Include bootstrap if running standalone
// ============================================================
if ($_is_cli && !$_is_included) {
    require_once __DIR__ . "/../bootstrap.php";
}

// ============================================================
// TEST MODE: Run assertions (CLI or included)
// ============================================================
echo "Testing: Escalator Calculations\n";
echo "================================\n";

run_escalator_tests();

// ============================================================
// TEST DEFINITIONS
// ============================================================
function run_escalator_tests()
{
    global $_test_results;

    // --------------------------------------------------------
    // calculate_escalated_price() tests
    // --------------------------------------------------------

    run_test("No escalators returns base price unchanged", function () {
        $customer_id = create_test_customer();

        $result = calculate_escalated_price(100.0, $customer_id, "2026-01-01");

        assert_float_equals(
            100.0,
            $result,
            0.01,
            "Base price should be unchanged"
        );
    });

    run_test("Year 1 with 0% escalator returns base price", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2025-06-01");

        assert_float_equals(
            100.0,
            $result,
            0.01,
            "Year 1 with 0% should return base price"
        );
    });

    run_test("Year 2 with 5% escalator applies percentage", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 5,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            105.0,
            $result,
            0.01,
            '5% escalator should make $100 -> $105'
        );
    });

    run_test("Year 2 with fixed adjustment only", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 10,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            110.0,
            $result,
            0.01,
            '$10 fixed adjustment should make $100 -> $110'
        );
    });

    run_test("Year 2 with both percentage and fixed adjustment", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 5,
                    "fixed_adjustment" => 10,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            115.0,
            $result,
            0.01,
            '5% + $10 should make $100 -> $115'
        );
    });

    run_test("Date before escalator start returns base price", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 10,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-06-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2025-01-01");

        assert_float_equals(
            100.0,
            $result,
            0.01,
            "Date before escalator start should return base price"
        );
    });

    run_test("Year 3 escalator applies correctly", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 5,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 3,
                    "escalator_percentage" => 10,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2024-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-06-01");

        assert_float_equals(
            110.0,
            $result,
            0.01,
            'Year 3 with 10% should make $100 -> $110'
        );
    });

    run_test("Large percentage escalator", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 25,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            125.0,
            $result,
            0.01,
            '25% escalator should make $100 -> $125'
        );
    });

    run_test("Decimal percentage escalator", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 3.5,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            103.5,
            $result,
            0.01,
            '3.5% escalator should make $100 -> $103.50'
        );
    });

    run_test("Negative fixed adjustment (discount)", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => -5,
                ],
            ],
            "2025-01-01"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            95.0,
            $result,
            0.01,
            '-$5 fixed adjustment should make $100 -> $95'
        );
    });

    run_test("Different base prices scale correctly", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 10,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        $date = "2026-02-01";

        assert_float_equals(
            55.0,
            calculate_escalated_price(50.0, $customer_id, $date),
            0.01,
            '$50 + 10% = $55'
        );
        assert_float_equals(
            220.0,
            calculate_escalated_price(200.0, $customer_id, $date),
            0.01,
            '$200 + 10% = $220'
        );
        assert_float_equals(
            1.1,
            calculate_escalated_price(1.0, $customer_id, $date),
            0.01,
            '$1 + 10% = $1.10'
        );
    });

    // --------------------------------------------------------
    // Escalator delay tests
    // --------------------------------------------------------

    run_test("Single delay postpones escalator", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 5,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        apply_escalator_delay($customer_id, 2, 2);

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");
        assert_float_equals(
            100.0,
            $result,
            0.01,
            "During delay period, escalator should not apply"
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-04-01");
        assert_float_equals(
            105.0,
            $result,
            0.01,
            "After delay period, escalator should apply"
        );
    });

    run_test("Multiple delays accumulate", function () {
        $customer_id = create_test_customer();

        apply_escalator_delay($customer_id, 2, 1);
        apply_escalator_delay($customer_id, 2, 1);
        apply_escalator_delay($customer_id, 2, 1);

        $total = get_total_delay_months($customer_id, 2);
        assert_equals(3, $total, "Total delay should be 3 months");
    });

    run_test("get_total_delay_months returns 0 when no delays", function () {
        $customer_id = create_test_customer();

        $total = get_total_delay_months($customer_id, 1);
        assert_equals(0, $total, "No delays should return 0");
    });

    run_test("Delays only affect specified year", function () {
        $customer_id = create_test_customer();

        apply_escalator_delay($customer_id, 2, 3);

        $year1_delay = get_total_delay_months($customer_id, 1);
        $year2_delay = get_total_delay_months($customer_id, 2);
        $year3_delay = get_total_delay_months($customer_id, 3);

        assert_equals(0, $year1_delay, "Year 1 should have no delay");
        assert_equals(3, $year2_delay, "Year 2 should have 3 month delay");
        assert_equals(0, $year3_delay, "Year 3 should have no delay");
    });

    // --------------------------------------------------------
    // get_current_escalators() tests
    // --------------------------------------------------------

    run_test(
        "get_current_escalators returns empty for customer without escalators",
        function () {
            $customer_id = create_test_customer();

            $escalators = get_current_escalators($customer_id);

            assert_count(0, $escalators, "Should return empty array");
        }
    );

    run_test("get_current_escalators returns saved escalators", function () {
        $customer_id = create_test_customer();

        save_escalators(
            $customer_id,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 2,
                    "escalator_percentage" => 5,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 3,
                    "escalator_percentage" => 7,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        $escalators = get_current_escalators($customer_id);

        assert_count(3, $escalators, "Should return 3 escalators");
        assert_equals(
            1,
            (int) $escalators[0]["year_number"],
            "First should be year 1"
        );
        assert_equals(
            2,
            (int) $escalators[1]["year_number"],
            "Second should be year 2"
        );
        assert_equals(
            3,
            (int) $escalators[2]["year_number"],
            "Third should be year 3"
        );
    });

    run_test(
        "get_current_escalators returns latest effective set",
        function () {
            $customer_id = create_test_customer();

            sqlite_execute(
                "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
             VALUES (?, ?, ?, ?, ?, ?)",
                [$customer_id, "2025-01-01", 2, 5, 0, "2025-01-01"]
            );

            sqlite_execute(
                "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
             VALUES (?, ?, ?, ?, ?, ?)",
                [$customer_id, "2025-01-01", 2, 10, 0, "2025-06-01"]
            );

            $escalators = get_current_escalators($customer_id);

            assert_float_equals(
                10.0,
                (float) $escalators[0]["escalator_percentage"],
                0.01,
                "Should return latest escalator percentage"
            );
        }
    );

    // --------------------------------------------------------
    // get_escalator_delays() tests
    // --------------------------------------------------------

    run_test("get_escalator_delays returns empty when no delays", function () {
        $customer_id = create_test_customer();

        $delays = get_escalator_delays($customer_id);

        assert_count(0, $delays, "Should return empty array");
    });

    run_test(
        "get_escalator_delays returns all delays for customer",
        function () {
            $customer_id = create_test_customer();

            apply_escalator_delay($customer_id, 2, 1);
            apply_escalator_delay($customer_id, 3, 2);

            $delays = get_escalator_delays($customer_id);

            assert_count(2, $delays, "Should return 2 delays");
        }
    );

    echo "\n";

    return $_test_results;
}

// ============================================================
// QA PAGE RENDERER
// ============================================================
function render_escalator_qa_page($test_results, $test_output)
{
    $passed = isset($test_results["passed"]) ? $test_results["passed"] : 0;
    $failed = isset($test_results["failed"]) ? $test_results["failed"] : 0;
    $total = $passed + $failed;
    $status = $failed === 0 ? "PASS" : "FAIL";
    $status_color = $failed === 0 ? "#28a745" : "#dc3545";
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QA: Escalator Calculations (CRITICAL)</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        header h1 { font-size: 2em; margin-bottom: 10px; }
        header p { opacity: 0.9; }
        .critical-badge {
            display: inline-block;
            background: #ffc107;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .nav { margin-bottom: 20px; }
        .nav a {
            display: inline-block;
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .nav a:hover { background: #c82333; }

        .status-badge {
            display: inline-block;
            padding: 10px 30px;
            font-size: 1.5em;
            font-weight: bold;
            color: white;
            background: <?php echo $status_color; ?>;
            border-radius: 50px;
            margin: 10px 0;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-box .number { font-size: 2.5em; font-weight: bold; }
        .stat-box.passed .number { color: #28a745; }
        .stat-box.failed .number { color: #dc3545; }
        .stat-box .label { color: #666; text-transform: uppercase; font-size: 0.8em; }

        section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        section h2 {
            color: #dc3545;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .demo-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
        }
        .demo-box h3 { color: #495057; margin-bottom: 15px; }

        .calculator {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
        }
        .calculator h3 { color: #856404; margin-bottom: 15px; }
        .calculator input, .calculator select {
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            margin: 5px;
        }
        .calculator button {
            padding: 10px 25px;
            background: #ffc107;
            color: #333;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .calculator button:hover { background: #e0a800; }

        .result-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            text-align: center;
        }
        .result-box .big-number {
            font-size: 3em;
            font-weight: bold;
            color: #28a745;
        }
        .result-box .calculation {
            color: #666;
            margin-top: 10px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
            margin: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #dc3545;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 12px;
            height: 12px;
            background: #dc3545;
            border-radius: 50%;
        }
        .timeline-item .year { font-weight: bold; color: #dc3545; }
        .timeline-item .details { color: #666; font-size: 0.9em; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; font-weight: 600; }
        .money { font-family: monospace; font-weight: bold; }
        .money.positive { color: #28a745; }
        .money.negative { color: #dc3545; }

        .test-output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.9em;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

        .code-example {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85em;
            overflow-x: auto;
        }
        .code-example .comment { color: #6a9955; }
        .code-example .function { color: #dcdcaa; }
        .code-example .variable { color: #9cdcfe; }
        .code-example .string { color: #ce9178; }
        .code-example .number { color: #b5cea8; }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav">
            <a href="../qa_dashboard.php">Back to QA Dashboard</a>
            <a href="?run=1">Re-run Tests</a>
        </nav>

        <header>
            <div class="critical-badge">CRITICAL - MONEY CALCULATIONS</div>
            <h1>Escalator Calculations</h1>
            <p>Annual price escalation, percentage adjustments, and delay tracking</p>
            <div class="status-badge"><?php echo $status; ?></div>
        </header>

        <div class="stats">
            <div class="stat-box passed">
                <div class="number"><?php echo $passed; ?></div>
                <div class="label">Tests Passed</div>
            </div>
            <div class="stat-box failed">
                <div class="number"><?php echo $failed; ?></div>
                <div class="label">Tests Failed</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $total; ?></div>
                <div class="label">Total Tests</div>
            </div>
        </div>

        <section>
            <h2>What Are Escalators?</h2>
            <div class="demo-box">
                <p><strong>Escalators</strong> are annual price adjustments written into customer contracts. They allow prices to increase each year by either:</p>
                <ul style="margin: 15px 0 15px 20px;">
                    <li><strong>Percentage:</strong> e.g., "5% increase in Year 2"</li>
                    <li><strong>Fixed Amount:</strong> e.g., "$0.10 per transaction increase"</li>
                    <li><strong>Both:</strong> Percentage applied first, then fixed amount added</li>
                </ul>
                <p>Escalators can also have <strong>delays</strong> that postpone when they take effect.</p>
            </div>
        </section>

        <section>
            <h2>Live Demo: Escalator Calculator</h2>
            <div class="calculator">
                <h3>Try It: Calculate Escalated Price (Using Real Function)</h3>
                <form method="get">
                    <div>
                        <label>Base Price: $</label>
                        <input type="number" name="base_price" step="0.01" value="<?php echo isset(
                            $_GET["base_price"]
                        )
                            ? htmlspecialchars($_GET["base_price"])
                            : "1.00"; ?>" style="width: 100px;">
                    </div>
                    <div style="margin-top: 10px;">
                        <label>Year 2 Escalator %:</label>
                        <input type="number" name="yr2_pct" step="0.1" value="<?php echo isset(
                            $_GET["yr2_pct"]
                        )
                            ? htmlspecialchars($_GET["yr2_pct"])
                            : "5"; ?>" style="width: 80px;">
                        <label style="margin-left: 10px;">Fixed: $</label>
                        <input type="number" name="yr2_fixed" step="0.01" value="<?php echo isset(
                            $_GET["yr2_fixed"]
                        )
                            ? htmlspecialchars($_GET["yr2_fixed"])
                            : "0"; ?>" style="width: 80px;">
                    </div>
                    <div style="margin-top: 10px;">
                        <label>Year 3 Escalator %:</label>
                        <input type="number" name="yr3_pct" step="0.1" value="<?php echo isset(
                            $_GET["yr3_pct"]
                        )
                            ? htmlspecialchars($_GET["yr3_pct"])
                            : "8"; ?>" style="width: 80px;">
                        <label style="margin-left: 10px;">Fixed: $</label>
                        <input type="number" name="yr3_fixed" step="0.01" value="<?php echo isset(
                            $_GET["yr3_fixed"]
                        )
                            ? htmlspecialchars($_GET["yr3_fixed"])
                            : "0"; ?>" style="width: 80px;">
                    </div>
                    <div style="margin-top: 10px;">
                        <label>Contract Start:</label>
                        <input type="date" name="start_date" value="<?php echo isset(
                            $_GET["start_date"]
                        )
                            ? htmlspecialchars($_GET["start_date"])
                            : "2025-01-01"; ?>">
                        <label style="margin-left: 10px;">Billing Date:</label>
                        <input type="date" name="billing_date" value="<?php echo isset(
                            $_GET["billing_date"]
                        )
                            ? htmlspecialchars($_GET["billing_date"])
                            : date("Y-m-d"); ?>">
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit">Calculate Using Real Function</button>
                    </div>
                </form>

                <?php if (isset($_GET["base_price"])) {

                    // Create a real test customer and escalators
                    $demo_customer_id = create_test_customer([
                        "id" => 99999,
                        "name" => "Demo Calculator Customer",
                        "contract_start_date" => $_GET["start_date"],
                    ]);

                    $base = floatval($_GET["base_price"]);
                    $start_date = $_GET["start_date"];
                    $billing_date = $_GET["billing_date"];

                    // Save real escalators
                    save_escalators(
                        $demo_customer_id,
                        [
                            [
                                "year_number" => 1,
                                "escalator_percentage" => 0,
                                "fixed_adjustment" => 0,
                            ],
                            [
                                "year_number" => 2,
                                "escalator_percentage" => floatval(
                                    $_GET["yr2_pct"]
                                ),
                                "fixed_adjustment" => floatval(
                                    $_GET["yr2_fixed"]
                                ),
                            ],
                            [
                                "year_number" => 3,
                                "escalator_percentage" => floatval(
                                    $_GET["yr3_pct"]
                                ),
                                "fixed_adjustment" => floatval(
                                    $_GET["yr3_fixed"]
                                ),
                            ],
                        ],
                        $start_date
                    );

                    // Call the REAL function
                    $result = calculate_escalated_price(
                        $base,
                        $demo_customer_id,
                        $billing_date
                    );

                    // Determine which year we're in
                    $start = new DateTime($start_date);
                    $billing = new DateTime($billing_date);
                    $diff = $start->diff($billing);
                    $years_elapsed = $diff->y;
                    $current_year = $years_elapsed + 1;
                    if ($current_year > 3) {
                        $current_year = 3;
                    }
                    if ($billing < $start) {
                        $current_year = 0;
                    }

                    // Clean up (escalators first due to foreign key)
                    sqlite_execute(
                        "DELETE FROM customer_escalators WHERE customer_id = ?",
                        [99999]
                    );
                    sqlite_execute("DELETE FROM customers WHERE id = ?", [
                        99999,
                    ]);
                    ?>
                    <div class="result-box">
                        <div class="big-number">$<?php echo number_format(
                            $result,
                            4
                        ); ?></div>
                        <div class="calculation">
                            <strong>calculate_escalated_price(<?php echo $base; ?>, customer, '<?php echo htmlspecialchars(
    $billing_date
); ?>')</strong><br>
                            Contract started: <?php echo htmlspecialchars(
                                $start_date
                            ); ?><br>
                            Currently in: Year <?php echo $current_year; ?><br>
                            Base $<?php echo number_format(
                                $base,
                                2
                            ); ?> &rarr; Escalated $<?php echo number_format(
     $result,
     4
 ); ?>
                        </div>
                    </div>
                    <?php
                } ?>
                <p style="margin-top: 15px; color: #666; font-size: 0.9em;">
                    This demo creates a real customer, saves real escalators to the database,
                    and calls <code>calculate_escalated_price()</code> - the same function used in production.
                </p>
            </div>
        </section>

        <section>
            <h2>Example: 3-Year Contract</h2>
            <div class="demo-box">
                <h3>Escalator Schedule</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="year">Year 1: $1.00 per inquiry</div>
                        <div class="details">Base pricing, no escalation</div>
                    </div>
                    <div class="timeline-item">
                        <div class="year">Year 2: $1.05 per inquiry (+5%)</div>
                        <div class="details">5% escalator applied: $1.00 x 1.05 = $1.05</div>
                    </div>
                    <div class="timeline-item">
                        <div class="year">Year 3: $1.08 per inquiry (+8%)</div>
                        <div class="details">8% escalator applied: $1.00 x 1.08 = $1.08</div>
                    </div>
                </div>

                <h3 style="margin-top: 20px;">Sample Calculations</h3>
                <table>
                    <tr>
                        <th>Base Price</th>
                        <th>Year 1</th>
                        <th>Year 2 (+5%)</th>
                        <th>Year 3 (+8%)</th>
                    </tr>
                    <tr>
                        <td class="money">$0.50</td>
                        <td class="money">$0.50</td>
                        <td class="money positive">$0.525</td>
                        <td class="money positive">$0.54</td>
                    </tr>
                    <tr>
                        <td class="money">$1.00</td>
                        <td class="money">$1.00</td>
                        <td class="money positive">$1.05</td>
                        <td class="money positive">$1.08</td>
                    </tr>
                    <tr>
                        <td class="money">$2.50</td>
                        <td class="money">$2.50</td>
                        <td class="money positive">$2.625</td>
                        <td class="money positive">$2.70</td>
                    </tr>
                </table>
            </div>
        </section>

        <section>
            <h2>Escalator Delays</h2>
            <div class="demo-box">
                <p><strong>What is a delay?</strong> Sometimes we agree to postpone when an escalator takes effect. For example, a Year 2 escalator normally starts on the contract anniversary, but a 2-month delay means it won't apply until 2 months after the anniversary.</p>

                <h3 style="margin-top: 20px;">Example with Delay</h3>
                <table>
                    <tr>
                        <th>Contract Anniversary</th>
                        <th>Normal Year 2 Start</th>
                        <th>With 2-Month Delay</th>
                    </tr>
                    <tr>
                        <td>January 1, 2026</td>
                        <td>January 1, 2026</td>
                        <td>March 1, 2026</td>
                    </tr>
                </table>
                <p style="margin-top: 10px; color: #666;"><em>During the delay period, transactions are billed at Year 1 rates even though Year 2 has technically begun.</em></p>
            </div>
        </section>

        <section>
            <h2>Code Examples</h2>

            <div class="demo-box">
                <h3>Calculate Escalated Price</h3>
                <pre class="code-example"><span class="comment">// Get the escalated price for a customer on a specific date</span>
<span class="variable">$base_price</span> = <span class="number">1.00</span>;
<span class="variable">$customer_id</span> = <span class="number">123</span>;
<span class="variable">$billing_date</span> = <span class="string">'2026-03-15'</span>;

<span class="variable">$final_price</span> = <span class="function">calculate_escalated_price</span>(
    <span class="variable">$base_price</span>,
    <span class="variable">$customer_id</span>,
    <span class="variable">$billing_date</span>
);

<span class="comment">// If customer has 5% Year 2 escalator and date is in Year 2:</span>
<span class="comment">// $final_price = 1.05</span></pre>
            </div>

            <div class="demo-box">
                <h3>Save Escalators</h3>
                <pre class="code-example"><span class="comment">// Define a 3-year escalator schedule</span>
<span class="function">save_escalators</span>(<span class="variable">$customer_id</span>, <span class="keyword">array</span>(
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">1</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">0</span>,
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    ),
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">2</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">5</span>,
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    ),
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">3</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">8</span>,
        <span class="string">'fixed_adjustment'</span> => <span class="number">0.10</span>  <span class="comment">// 8% + $0.10</span>
    )
), <span class="string">'2025-01-01'</span>);  <span class="comment">// Contract start date</span></pre>
            </div>

            <div class="demo-box">
                <h3>Apply a Delay</h3>
                <pre class="code-example"><span class="comment">// Delay Year 2 escalator by 2 months</span>
<span class="function">apply_escalator_delay</span>(<span class="variable">$customer_id</span>, <span class="number">2</span>, <span class="number">2</span>);

<span class="comment">// Check total delay for a year</span>
<span class="variable">$total_months</span> = <span class="function">get_total_delay_months</span>(<span class="variable">$customer_id</span>, <span class="number">2</span>);
<span class="comment">// Returns: 2</span></pre>
            </div>
        </section>

        <section>
            <h2>Test Output</h2>
            <p>Raw output from running all <?php echo $total; ?> tests:</p>
            <div class="test-output"><?php echo htmlspecialchars(
                $test_output
            ); ?></div>
        </section>

        <?php if (!empty($test_results["errors"])): ?>
        <section>
            <h2>Failed Tests</h2>
            <table>
                <tr>
                    <th>Test</th>
                    <th>Expected</th>
                    <th>Actual</th>
                </tr>
                <?php foreach ($test_results["errors"] as $error): ?>
                <tr>
                    <td><?php echo htmlspecialchars($error["test"]); ?></td>
                    <td><?php echo htmlspecialchars($error["expected"]); ?></td>
                    <td><?php echo htmlspecialchars($error["actual"]); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>
        <?php endif; ?>

        <footer style="text-align: center; padding: 20px; color: #666;">
            <p>QA Test Page - Escalator Calculations | Last run: <?php echo date(
                "Y-m-d H:i:s"
            ); ?></p>
        </footer>
    </div>
</body>
</html>
<?php
}
