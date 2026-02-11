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

// Include calculator for cross-engine agreement tests
require_once dirname(dirname(__DIR__)) . "/calculator.php";

// Backdated effective_date for test data — must be far enough in the past
// that point-in-time queries with any reasonable as_of_date will see the data.
define("TEST_EFFECTIVE_DATE", "2020-01-01");

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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-06-01",
            TEST_EFFECTIVE_DATE
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
            "2024-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
        );

        apply_escalator_delay($customer_id, 2, 2, TEST_EFFECTIVE_DATE);

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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
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

    // --------------------------------------------------------
    // Negative escalator (de-escalation) tests
    // --------------------------------------------------------

    run_test("Negative percentage escalator reduces price", function () {
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
                    "escalator_percentage" => -10,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01",
            TEST_EFFECTIVE_DATE
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            90.0,
            $result,
            0.01,
            '-10% escalator should make $100 -> $90'
        );
    });

    run_test("Negative percentage with positive fixed adjustment", function () {
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
                    "escalator_percentage" => -5,
                    "fixed_adjustment" => 10,
                ],
            ],
            "2025-01-01",
            TEST_EFFECTIVE_DATE
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            105.0,
            $result,
            0.01,
            '-5% + $10 fixed should make $100 -> $95 + $10 = $105'
        );
    });

    run_test("Positive percentage with negative fixed adjustment", function () {
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
                    "fixed_adjustment" => -5,
                ],
            ],
            "2025-01-01",
            TEST_EFFECTIVE_DATE
        );

        $result = calculate_escalated_price(100.0, $customer_id, "2026-02-01");

        assert_float_equals(
            105.0,
            $result,
            0.01,
            '+10% - $5 fixed should make $100 -> $110 - $5 = $105'
        );
    });

    run_test(
        "Multi-year with mixed positive and negative escalators",
        function () {
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
                    [
                        "year_number" => 3,
                        "escalator_percentage" => -5,
                        "fixed_adjustment" => 0,
                    ],
                ],
                "2024-01-01",
                TEST_EFFECTIVE_DATE
            );

            // Year 2: +10% -> $110
            $result_yr2 = calculate_escalated_price(
                100.0,
                $customer_id,
                "2025-06-01"
            );
            assert_float_equals(
                110.0,
                $result_yr2,
                0.01,
                'Year 2 (+10%) should make $100 -> $110'
            );

            // Year 3: -5% from base -> $95
            $result_yr3 = calculate_escalated_price(
                100.0,
                $customer_id,
                "2026-06-01"
            );
            assert_float_equals(
                95.0,
                $result_yr3,
                0.01,
                'Year 3 (-5%) should make $100 -> $95'
            );
        }
    );

    run_test(
        "Negative escalator with delay still applies after delay",
        function () {
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
                        "escalator_percentage" => -8,
                        "fixed_adjustment" => 0,
                    ],
                ],
                "2025-01-01",
                TEST_EFFECTIVE_DATE
            );

            apply_escalator_delay($customer_id, 2, 3, TEST_EFFECTIVE_DATE);

            // During delay: should still be base price
            $result_during = calculate_escalated_price(
                100.0,
                $customer_id,
                "2026-02-01"
            );
            assert_float_equals(
                100.0,
                $result_during,
                0.01,
                "During delay, negative escalator should not apply yet"
            );

            // After delay: -8% should apply
            $result_after = calculate_escalated_price(
                100.0,
                $customer_id,
                "2026-05-01"
            );
            assert_float_equals(
                92.0,
                $result_after,
                0.01,
                'After delay, -8% escalator should make $100 -> $92'
            );
        }
    );

    // --------------------------------------------------------
    // Cross-engine agreement tests (audit vs CSV must match)
    // --------------------------------------------------------

    run_test("Audit and CSV escalator calculations agree", function () {
        $customer_id = create_test_customer();
        $service_id = create_test_service();

        // Create EFX code mapping
        sqlite_execute(
            "INSERT INTO transaction_types (type, display_name, efx_code, service_id)
                 VALUES ('test', 'Test Type', 'TEST_CROSS_ENGINE', ?)",
            [$service_id]
        );

        // Create default tiers (backdated so point-in-time query finds them)
        save_default_tiers(
            $service_id,
            [
                [
                    "volume_start" => 1,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.5,
                ],
            ],
            TEST_EFFECTIVE_DATE
        );

        // Create escalators: Year 1 (0%), Year 2 (5%), Year 3 (10%)
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
                    "fixed_adjustment" => 2,
                ],
            ],
            "2024-01-01",
            TEST_EFFECTIVE_DATE
        );

        // Test in Year 2 (after 1+ year from start)
        $as_of = "2025-03-01";

        $csv_price = calculate_escalated_price(0.5, $customer_id, $as_of);

        $audit = calculate_price_audit(
            $as_of,
            $customer_id,
            "TEST_CROSS_ENGINE",
            100
        );

        assert_float_equals(
            $csv_price,
            $audit["expected_unit_price"],
            0.000001,
            "Audit and CSV escalator calculations must agree"
        );
    });

    // --------------------------------------------------------
    // Point-in-time escalator tests
    // --------------------------------------------------------

    run_test("Escalator uses point-in-time data", function () {
        $customer_id = create_test_customer();

        // Insert escalators with effective_date = 2025-01-01
        sqlite_execute(
            "INSERT INTO customer_escalators (customer_id, effective_date, escalator_start_date, year_number, escalator_percentage, fixed_adjustment)
                 VALUES (?, '2025-01-01', '2024-01-01', 1, 0, 0)",
            [$customer_id]
        );
        sqlite_execute(
            "INSERT INTO customer_escalators (customer_id, effective_date, escalator_start_date, year_number, escalator_percentage, fixed_adjustment)
                 VALUES (?, '2025-01-01', '2024-01-01', 2, 5, 0)",
            [$customer_id]
        );

        // Insert DIFFERENT escalators with effective_date = 2025-07-01
        sqlite_execute(
            "INSERT INTO customer_escalators (customer_id, effective_date, escalator_start_date, year_number, escalator_percentage, fixed_adjustment)
                 VALUES (?, '2025-07-01', '2024-01-01', 1, 0, 0)",
            [$customer_id]
        );
        sqlite_execute(
            "INSERT INTO customer_escalators (customer_id, effective_date, escalator_start_date, year_number, escalator_percentage, fixed_adjustment)
                 VALUES (?, '2025-07-01', '2024-01-01', 2, 20, 0)",
            [$customer_id]
        );

        // As of 2025-06-01 should see 5% (first set)
        $price_before = calculate_escalated_price(
            100.0,
            $customer_id,
            "2025-06-01"
        );
        assert_float_equals(
            105.0,
            $price_before,
            0.01,
            "Before second effective_date, should use first escalator set (5%)"
        );

        // As of 2025-08-01 should see 20% (second set)
        $price_after = calculate_escalated_price(
            100.0,
            $customer_id,
            "2025-08-01"
        );
        assert_float_equals(
            120.0,
            $price_after,
            0.01,
            "After second effective_date, should use second escalator set (20%)"
        );
    });

    // --------------------------------------------------------
    // Delay actually works test
    // --------------------------------------------------------

    run_test("Delay pushes escalator activation correctly", function () {
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
            "2025-01-01",
            TEST_EFFECTIVE_DATE
        );

        // Add 3-month delay to year 2
        apply_escalator_delay($customer_id, 2, 3, TEST_EFFECTIVE_DATE);

        // Without delay, year 2 would activate ~2026-01-01
        // With 3-month (90-day) delay, should activate ~2026-04-01

        // Just after normal activation: should NOT have escalator yet
        $price_during_delay = calculate_escalated_price(
            100.0,
            $customer_id,
            "2026-02-01"
        );
        assert_float_equals(
            100.0,
            $price_during_delay,
            0.01,
            "During delay period, year 2 escalator should not apply"
        );

        // After delay period: should have escalator
        $price_after_delay = calculate_escalated_price(
            100.0,
            $customer_id,
            "2026-05-01"
        );
        assert_float_equals(
            110.0,
            $price_after_delay,
            0.01,
            "After delay period, year 2 escalator (10%) should apply"
        );

        // Verify get_escalator_year_on_date also reflects the delay
        $info_during = get_escalator_year_on_date($customer_id, "2026-02-01");
        assert_equals(
            1,
            $info_during["current_year"],
            "During delay, year_on_date should report year 1"
        );

        $info_after = get_escalator_year_on_date($customer_id, "2026-05-01");
        assert_equals(
            2,
            $info_after["current_year"],
            "After delay, year_on_date should report year 2"
        );
    });

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
            <p>Annual price adjustments (increase or decrease), percentage and fixed adjustments, and delay tracking</p>
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
                <p><strong>Escalators</strong> are annual price adjustments written into customer contracts. They can <strong>increase or decrease</strong> prices each year by:</p>
                <ul style="margin: 15px 0 15px 20px;">
                    <li><strong>Percentage:</strong> e.g., "+5% increase" or "-3% decrease" in Year 2</li>
                    <li><strong>Fixed Amount:</strong> e.g., "+$0.10" or "-$0.05" per transaction</li>
                    <li><strong>Both:</strong> Percentage applied first, then fixed amount added (either can be positive or negative)</li>
                </ul>
                <p>Escalators can also have <strong>delays</strong> that postpone when they take effect.</p>
                <p style="margin-top: 10px;"><strong>Positive values</strong> = price increase (escalation). <strong>Negative values</strong> = price decrease (de-escalation).</p>
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
                    <?php
                    $direction = "unchanged";
                    $direction_color = "#6c757d";
                    $direction_label = "No Change";
                    if ($result > $base) {
                        $direction = "increase";
                        $direction_color = "#dc3545";
                        $direction_label = "INCREASE";
                    } elseif ($result < $base) {
                        $direction = "decrease";
                        $direction_color = "#28a745";
                        $direction_label = "DECREASE";
                    }
                    $pct_change =
                        $base > 0 ? (($result - $base) / $base) * 100 : 0;
                    ?>
                    <div class="result-box" style="border-color: <?php echo $direction_color; ?>;">
                        <div class="big-number" style="color: <?php echo $direction_color; ?>;">$<?php echo number_format(
    $result,
    4
); ?></div>
                        <div style="display: inline-block; padding: 4px 12px; background: <?php echo $direction_color; ?>; color: white; border-radius: 15px; font-weight: bold; font-size: 0.85em; margin: 8px 0;">
                            <?php echo $direction_label; ?> (<?php echo ($pct_change >=
 0
     ? "+"
     : "") . number_format($pct_change, 2); ?>%)
                        </div>
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
                            ); ?> &rarr; Adjusted $<?php echo number_format(
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
                <h3>Escalator Schedule (Increase Example)</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="year">Year 1: $1.00 per inquiry</div>
                        <div class="details">Base pricing, no escalation</div>
                    </div>
                    <div class="timeline-item">
                        <div class="year">Year 2: $1.05 per inquiry (+5%)</div>
                        <div class="details">Positive escalator: $1.00 &times; 1.05 = $1.05</div>
                    </div>
                    <div class="timeline-item">
                        <div class="year">Year 3: $1.08 per inquiry (+8%)</div>
                        <div class="details">Positive escalator: $1.00 &times; 1.08 = $1.08</div>
                    </div>
                </div>

                <h3 style="margin-top: 20px;">Escalator Schedule (Decrease Example)</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="year">Year 1: $1.00 per inquiry</div>
                        <div class="details">Base pricing, no escalation</div>
                    </div>
                    <div class="timeline-item">
                        <div class="year">Year 2: $0.95 per inquiry (-5%)</div>
                        <div class="details">Negative escalator: $1.00 &times; 0.95 = $0.95</div>
                    </div>
                    <div class="timeline-item">
                        <div class="year">Year 3: $0.90 per inquiry (-10%)</div>
                        <div class="details">Negative escalator: $1.00 &times; 0.90 = $0.90</div>
                    </div>
                </div>

                <h3 style="margin-top: 20px;">Sample Calculations</h3>
                <table>
                    <tr>
                        <th>Base Price</th>
                        <th>Year 1</th>
                        <th>Year 2 (+5%)</th>
                        <th>Year 2 (-5%)</th>
                        <th>Year 3 (+8%)</th>
                        <th>Year 3 (-3%)</th>
                    </tr>
                    <tr>
                        <td class="money">$0.50</td>
                        <td class="money">$0.50</td>
                        <td class="money positive">$0.525</td>
                        <td class="money negative">$0.475</td>
                        <td class="money positive">$0.54</td>
                        <td class="money negative">$0.485</td>
                    </tr>
                    <tr>
                        <td class="money">$1.00</td>
                        <td class="money">$1.00</td>
                        <td class="money positive">$1.05</td>
                        <td class="money negative">$0.95</td>
                        <td class="money positive">$1.08</td>
                        <td class="money negative">$0.97</td>
                    </tr>
                    <tr>
                        <td class="money">$2.50</td>
                        <td class="money">$2.50</td>
                        <td class="money positive">$2.625</td>
                        <td class="money negative">$2.375</td>
                        <td class="money positive">$2.70</td>
                        <td class="money negative">$2.425</td>
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

<span class="comment">// If customer has +5% Year 2 escalator: $final_price = 1.05</span>
<span class="comment">// If customer has -5% Year 2 escalator: $final_price = 0.95</span></pre>
            </div>

            <div class="demo-box">
                <h3>Save Escalators (Increase)</h3>
                <pre class="code-example"><span class="comment">// Define a 3-year escalator schedule with increases</span>
<span class="function">save_escalators</span>(<span class="variable">$customer_id</span>, <span class="keyword">array</span>(
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">1</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">0</span>,
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    ),
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">2</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">5</span>,    <span class="comment">// +5% increase</span>
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    ),
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">3</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">8</span>,    <span class="comment">// +8% increase + $0.10</span>
        <span class="string">'fixed_adjustment'</span> => <span class="number">0.10</span>
    )
), <span class="string">'2025-01-01'</span>);  <span class="comment">// Contract start date</span></pre>
            </div>

            <div class="demo-box">
                <h3>Save Escalators (Decrease / De-escalation)</h3>
                <pre class="code-example"><span class="comment">// Define a schedule with price decreases (negative values)</span>
<span class="function">save_escalators</span>(<span class="variable">$customer_id</span>, <span class="keyword">array</span>(
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">1</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">0</span>,
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    ),
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">2</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">-5</span>,   <span class="comment">// -5% decrease</span>
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    ),
    <span class="keyword">array</span>(
        <span class="string">'year_number'</span> => <span class="number">3</span>,
        <span class="string">'escalator_percentage'</span> => <span class="number">-10</span>,  <span class="comment">// -10% decrease</span>
        <span class="string">'fixed_adjustment'</span> => <span class="number">0</span>
    )
), <span class="string">'2025-01-01'</span>);
<span class="comment">// Year 2: $1.00 x 0.95 = $0.95</span>
<span class="comment">// Year 3: $1.00 x 0.90 = $0.90</span></pre>
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
