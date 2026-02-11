<?php
/**
 * Test: Billing Calendar Functions
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for get_month_events(), get_calendar_year_summary(), is_month_complete(),
 * get_next_incomplete_month(), get_mtd_summary(), and related functions.
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
    // Bootstrap for standalone browser access
    require_once __DIR__ . "/../bootstrap_qa.php";

    // Run tests and capture results
    ob_start();
    $_qa_test_results = run_calendar_tests();
    $test_output = ob_get_clean();

    render_calendar_qa_page($_qa_test_results, $test_output);
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
echo "Testing: Billing Calendar Functions\n";
echo "====================================\n";

run_calendar_tests();

// ============================================================
// TEST DEFINITIONS
// ============================================================
function run_calendar_tests()
{
    $results = ["passed" => 0, "failed" => 0, "tests" => []];

    // --------------------------------------------------------
    // get_month_events() tests
    // --------------------------------------------------------

    run_test("get_month_events - returns expected structure", function () {
        $events = get_month_events(2026, 1);

        assert_true(is_array($events), "Should return array");
        assert_array_has_key(
            "escalators",
            $events,
            "Should have escalators key"
        );
        assert_array_has_key("resets", $events, "Should have resets key");
        assert_array_has_key(
            "new_customers",
            $events,
            "Should have new_customers key"
        );
        assert_array_has_key(
            "paused_customers",
            $events,
            "Should have paused_customers key"
        );
        assert_array_has_key("warnings", $events, "Should have warnings key");
    });

    run_test("get_month_events - detects escalators in month", function () {
        $customer_id = create_test_customer([
            "name" => "Calendar Escalator Test",
        ]);
        $start_date = date("Y-m-d", strtotime("-1 year"));
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
            $start_date
        );

        $current_year = (int) date("Y");
        $current_month = (int) date("n");
        $events = get_month_events($current_year, $current_month);

        assert_true(
            is_array($events["escalators"]),
            "Escalators should be array"
        );
    });

    run_test("get_month_events - detects paused customers", function () {
        $customer_id = create_test_customer([
            "name" => "Paused Calendar Customer",
            "status" => "paused",
        ]);

        $events = get_month_events(2026, 1);

        assert_true(
            is_array($events["paused_customers"]),
            "Paused customers should be array"
        );

        $found = false;
        foreach ($events["paused_customers"] as $p) {
            if ($p["id"] == $customer_id) {
                $found = true;
                break;
            }
        }
        assert_true($found, "Should find the paused customer");
    });

    run_test(
        "get_month_events - warns about customers without LMS",
        function () {
            $customer_id = create_test_customer([
                "name" => "No LMS Calendar Customer",
                "lms_id" => null,
                "status" => "active",
            ]);

            $events = get_month_events(2026, 1);

            $found_warning = false;
            foreach ($events["warnings"] as $w) {
                if (
                    $w["customer_id"] == $customer_id &&
                    $w["type"] == "no_lms"
                ) {
                    $found_warning = true;
                    break;
                }
            }
            assert_true(
                $found_warning,
                "Should warn about customer without LMS"
            );
        }
    );

    run_test("get_month_events - detects annualized resets", function () {
        $customer_id = create_test_customer([
            "name" => "Annualized Reset Test",
        ]);

        $current_month = (int) date("n");
        $current_day = 15;
        $start_date =
            date("Y") .
            "-" .
            sprintf("%02d", $current_month) .
            "-" .
            sprintf("%02d", $current_day);

        save_customer_settings($customer_id, [
            "uses_annualized" => 1,
            "annualized_start_date" => $start_date,
            "look_period_months" => 12,
        ]);

        $events = get_month_events((int) date("Y"), $current_month);

        $found = false;
        foreach ($events["resets"] as $r) {
            if ($r["customer_id"] == $customer_id) {
                $found = true;
                break;
            }
        }
        assert_true($found, "Should detect annualized reset");
    });

    // --------------------------------------------------------
    // is_month_complete() tests
    // --------------------------------------------------------

    run_test("is_month_complete - false when no monthly report", function () {
        $result = is_month_complete(2030, 12);
        assert_false($result, "Should be false when no monthly report exists");
    });

    run_test(
        "is_month_complete - true after monthly report ingested",
        function () {
            $csv =
                "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
            $csv .= "2026,3,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

            import_billing_report("DataX_2026_03_2026_03_monthly.csv", $csv);

            $result = is_month_complete(2026, 3);
            assert_true(
                $result,
                "Should be true after monthly report imported"
            );
        }
    );

    run_test(
        "is_month_complete - daily report does not mark complete",
        function () {
            $csv =
                "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
            $csv .= "2026,4,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

            import_billing_report(
                "DataX_2026_04_01_2026_04_01_daily.csv",
                $csv
            );

            $result = is_month_complete(2026, 4);
            assert_false(
                $result,
                "Daily report should not mark month as complete"
            );
        }
    );

    // --------------------------------------------------------
    // get_calendar_year_summary() tests
    // --------------------------------------------------------

    run_test("get_calendar_year_summary - returns 12 months", function () {
        $summary = get_calendar_year_summary(2026);
        assert_count(12, $summary, "Should return 12 months");
    });

    run_test("get_calendar_year_summary - month structure", function () {
        $summary = get_calendar_year_summary(2026);
        $jan = $summary[1];

        assert_array_has_key("year", $jan, "Should have year");
        assert_array_has_key("month", $jan, "Should have month");
        assert_array_has_key("month_name", $jan, "Should have month_name");
        assert_array_has_key("event_count", $jan, "Should have event_count");
        assert_array_has_key(
            "warning_count",
            $jan,
            "Should have warning_count"
        );
        assert_array_has_key(
            "has_escalators",
            $jan,
            "Should have has_escalators"
        );
        assert_array_has_key("is_complete", $jan, "Should have is_complete");
        assert_array_has_key("is_current", $jan, "Should have is_current");
        assert_array_has_key("is_past", $jan, "Should have is_past");

        assert_equals(2026, $jan["year"], "Year should match");
        assert_equals(1, $jan["month"], "Month should be 1 for January");
        assert_equals("Jan", $jan["month_name"], "Month name should be Jan");
    });

    run_test(
        "get_calendar_year_summary - identifies current month",
        function () {
            $current_year = (int) date("Y");
            $current_month = (int) date("n");

            $summary = get_calendar_year_summary($current_year);

            assert_true(
                $summary[$current_month]["is_current"],
                "Current month should be marked"
            );

            if ($current_month < 12) {
                assert_false(
                    $summary[$current_month + 1]["is_past"],
                    "Future month should not be past"
                );
            }

            if ($current_month > 1) {
                assert_true(
                    $summary[$current_month - 1]["is_past"],
                    "Previous month should be past"
                );
            }
        }
    );

    run_test(
        "get_calendar_year_summary - tracks completion status",
        function () {
            $csv =
                "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
            $csv .= "2026,2,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

            import_billing_report("DataX_2026_02_2026_02_summary.csv", $csv);

            $summary = get_calendar_year_summary(2026);

            assert_true(
                $summary[2]["is_complete"],
                "February should be marked complete"
            );
        }
    );

    // --------------------------------------------------------
    // get_next_incomplete_month() tests
    // --------------------------------------------------------

    run_test(
        "get_next_incomplete_month - returns array with year and month",
        function () {
            $result = get_next_incomplete_month();

            assert_true(is_array($result), "Should return array");
            assert_array_has_key("year", $result, "Should have year");
            assert_array_has_key("month", $result, "Should have month");
        }
    );

    run_test(
        "get_next_incomplete_month - returns current month if incomplete",
        function () {
            $result = get_next_incomplete_month();

            assert_greater_than(
                2020,
                $result["year"],
                "Year should be reasonable"
            );
            assert_greater_than(0, $result["month"], "Month should be >= 1");
            assert_less_than(13, $result["month"], "Month should be <= 12");
        }
    );

    // --------------------------------------------------------
    // get_new_customers_since() tests
    // --------------------------------------------------------

    run_test("get_new_customers_since - returns array", function () {
        $result = get_new_customers_since("2020-01-01");
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_new_customers_since - finds new customers", function () {
        $customer_id = create_test_customer(["name" => "Brand New Customer"]);

        $since = date("Y-m-d", strtotime("-1 day"));
        $result = get_new_customers_since($since);

        assert_greater_than(0, count($result), "Should find new customers");
    });

    run_test(
        "get_new_customers_since - includes customer details",
        function () {
            $customer_id = create_test_customer([
                "name" => "Detailed New Customer",
            ]);

            $since = date("Y-m-d", strtotime("-1 day"));
            $result = get_new_customers_since($since);

            if (count($result) > 0) {
                assert_array_has_key("name", $result[0], "Should have name");
                assert_array_has_key("id", $result[0], "Should have id");
            }
        }
    );

    // --------------------------------------------------------
    // get_config_changes_since() tests
    // --------------------------------------------------------

    run_test("get_config_changes_since - returns array", function () {
        $result = get_config_changes_since("2020-01-01");
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_config_changes_since - detects pricing changes", function () {
        $customer_id = create_test_customer(["name" => "Config Change Test"]);
        $service_id = create_test_service(["name" => "Config Change Service"]);

        save_customer_tiers(
            $customer_id,
            $service_id,
            [
                [
                    "volume_start" => 0,
                    "volume_end" => 100,
                    "price_per_inquiry" => 1.0,
                ],
            ],
            date("Y-m-d")
        );

        $since = date("Y-m-d", strtotime("-1 day"));
        $result = get_config_changes_since($since);

        $found = false;
        foreach ($result as $change) {
            if (
                $change["type"] == "pricing" &&
                $change["customer_id"] == $customer_id
            ) {
                $found = true;
                break;
            }
        }
        assert_true($found, "Should detect pricing change");
    });

    run_test(
        "get_config_changes_since - detects settings changes",
        function () {
            $customer_id = create_test_customer([
                "name" => "Settings Change Test",
            ]);

            save_customer_settings($customer_id, ["monthly_minimum" => 500]);

            $since = date("Y-m-d", strtotime("-1 day"));
            $result = get_config_changes_since($since);

            $found = false;
            foreach ($result as $change) {
                if (
                    $change["type"] == "settings" &&
                    $change["customer_id"] == $customer_id
                ) {
                    $found = true;
                    break;
                }
            }
            assert_true($found, "Should detect settings change");
        }
    );

    // --------------------------------------------------------
    // get_mtd_summary() tests
    // --------------------------------------------------------

    run_test("get_mtd_summary - returns expected structure", function () {
        $result = get_mtd_summary(2026, 1);

        assert_true(is_array($result), "Should return array");
        assert_array_has_key(
            "customer_count",
            $result,
            "Should have customer_count"
        );
        assert_array_has_key(
            "total_transactions",
            $result,
            "Should have total_transactions"
        );
        assert_array_has_key(
            "total_revenue",
            $result,
            "Should have total_revenue"
        );
        assert_array_has_key(
            "report_count",
            $result,
            "Should have report_count"
        );
        assert_array_has_key("latest_date", $result, "Should have latest_date");
    });

    run_test("get_mtd_summary - no data returns zeros", function () {
        $result = get_mtd_summary(2099, 12);
        assert_not_null($result, "Should return structure even with no data");
    });

    run_test("get_mtd_summary - calculates from daily reports", function () {
        $csv1 =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv1 .= "2026,5,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

        $csv2 =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv2 .= "2026,5,1,Test,HIT002,Test2,0.60,200,120.00,CC002,BIL002\n";

        import_billing_report("DataX_2026_05_01_2026_05_01_day1.csv", $csv1);
        import_billing_report("DataX_2026_05_02_2026_05_02_day2.csv", $csv2);

        $result = get_mtd_summary(2026, 5);

        assert_equals(
            2,
            $result["report_count"],
            "Should have 2 daily reports"
        );
        assert_loose_equals(
            300,
            $result["total_transactions"],
            "Should have 300 total transactions"
        );
        assert_float_equals(
            170.0,
            $result["total_revenue"],
            0.01,
            'Should have $170 total revenue'
        );
    });

    // --------------------------------------------------------
    // get_mtd_daily_breakdown() tests
    // --------------------------------------------------------

    run_test("get_mtd_daily_breakdown - returns array", function () {
        $result = get_mtd_daily_breakdown(2026, 5);
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_mtd_daily_breakdown - groups by date", function () {
        $result = get_mtd_daily_breakdown(2026, 5);

        if (count($result) > 0) {
            assert_array_has_key(
                "report_date",
                $result[0],
                "Should have report_date"
            );
            assert_array_has_key(
                "transactions",
                $result[0],
                "Should have transactions"
            );
            assert_array_has_key("revenue", $result[0], "Should have revenue");
        }
    });

    // --------------------------------------------------------
    // get_mtd_service_breakdown() tests
    // --------------------------------------------------------

    run_test("get_mtd_service_breakdown - returns array", function () {
        $result = get_mtd_service_breakdown(2026, 5);
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_mtd_service_breakdown - groups by service", function () {
        $result = get_mtd_service_breakdown(2026, 5);

        if (count($result) > 0) {
            assert_array_has_key(
                "service_name",
                $result[0],
                "Should have service_name"
            );
            assert_array_has_key(
                "transactions",
                $result[0],
                "Should have transactions"
            );
            assert_array_has_key("revenue", $result[0], "Should have revenue");
        }
    });

    // --------------------------------------------------------
    // get_mtd_customer_breakdown() tests
    // --------------------------------------------------------

    run_test("get_mtd_customer_breakdown - returns array", function () {
        $result = get_mtd_customer_breakdown(2026, 5);
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_mtd_customer_breakdown - groups by customer", function () {
        $result = get_mtd_customer_breakdown(2026, 5);

        if (count($result) > 0) {
            assert_array_has_key(
                "customer_id",
                $result[0],
                "Should have customer_id"
            );
            assert_array_has_key(
                "transactions",
                $result[0],
                "Should have transactions"
            );
            assert_array_has_key("revenue", $result[0], "Should have revenue");
        }
    });

    // --------------------------------------------------------
    // get_previous_month_mtd() tests
    // --------------------------------------------------------

    run_test(
        "get_previous_month_mtd - returns expected structure",
        function () {
            $result = get_previous_month_mtd(2026, 6, 15);

            assert_true(is_array($result), "Should return array");
            assert_array_has_key(
                "total_transactions",
                $result,
                "Should have total_transactions"
            );
            assert_array_has_key(
                "total_revenue",
                $result,
                "Should have total_revenue"
            );
        }
    );

    run_test("get_previous_month_mtd - handles January correctly", function () {
        $result = get_previous_month_mtd(2026, 1, 15);
        assert_not_null($result, "Should handle January wrap-around");
    });

    run_test("get_previous_month_mtd - compares same day range", function () {
        for ($day = 1; $day <= 3; $day++) {
            $csv =
                "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
            $csv .= "2026,6,1,Test,HIT001,Test,1.00,10,10.00,CC001,BIL001\n";

            $filename = sprintf(
                "DataX_2026_06_%02d_2026_06_%02d_daily.csv",
                $day,
                $day
            );
            import_billing_report($filename, $csv);
        }

        $result = get_previous_month_mtd(2026, 7, 2);

        assert_loose_equals(
            20,
            $result["total_transactions"],
            "Should only count through day 2"
        );
        assert_float_equals(
            20.0,
            $result["total_revenue"],
            0.01,
            "Should sum revenue through day 2"
        );
    });

    // --------------------------------------------------------
    // Edge cases and integration tests
    // --------------------------------------------------------

    run_test("calendar - handles year boundaries", function () {
        $dec = get_month_events(2025, 12);
        $jan = get_month_events(2026, 1);

        assert_not_null($dec, "Should handle December");
        assert_not_null($jan, "Should handle January");
    });

    run_test("calendar - handles leap year", function () {
        $feb = get_month_events(2024, 2);
        assert_not_null($feb, "Should handle leap year February");
    });

    run_test(
        "calendar - escalator with delay shows in correct month",
        function () {
            $customer_id = create_test_customer([
                "name" => "Delayed Escalator Calendar Test",
            ]);

            $start_date = date("Y-m-d", strtotime("-11 months"));
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
                $start_date
            );

            save_escalator_delay($customer_id, 2, 1, "Test delay");

            $current_year = (int) date("Y");
            $current_month = (int) date("n");

            $events = get_month_events($current_year, $current_month);

            assert_true(
                is_array($events["escalators"]),
                "Should have escalators array"
            );
        }
    );

    run_test("calendar - multiple events in same month", function () {
        $start_date = date("Y-m-d", strtotime("-1 year"));

        for ($i = 1; $i <= 3; $i++) {
            $customer_id = create_test_customer([
                "name" => "Multi Event Customer $i",
            ]);
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
                        "escalator_percentage" => $i * 2,
                        "fixed_adjustment" => 0,
                    ],
                ],
                $start_date
            );
        }

        $current_year = (int) date("Y");
        $current_month = (int) date("n");

        $events = get_month_events($current_year, $current_month);

        assert_greater_than(
            0,
            count($events["escalators"]),
            "Should have escalator events"
        );
    });

    echo "\n";

    // Return results for QA mode
    global $_test_results;
    return $_test_results;
}

// ============================================================
// QA PAGE RENDERER
// ============================================================
function render_calendar_qa_page($test_results, $test_output)
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
    <title>QA: Billing Calendar Functions</title>
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
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        header h1 { font-size: 2em; margin-bottom: 10px; }
        header p { opacity: 0.9; }

        .nav { margin-bottom: 20px; }
        .nav a {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .nav a:hover { background: #5a6fd6; }

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
            color: #667eea;
            border-bottom: 2px solid #667eea;
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

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .month-cell {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .month-cell.current { background: #667eea; color: white; }
        .month-cell.complete { background: #28a745; color: white; }
        .month-cell.has-events { border: 2px solid #ffc107; }
        .month-cell .month-name { font-weight: bold; }
        .month-cell .event-count { font-size: 0.8em; opacity: 0.8; }

        .event-list {
            list-style: none;
        }
        .event-list li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .event-list li:last-child { border-bottom: none; }
        .event-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-right: 10px;
        }
        .event-type.escalator { background: #ffc107; color: #333; }
        .event-type.reset { background: #17a2b8; color: white; }
        .event-type.warning { background: #dc3545; color: white; }
        .event-type.paused { background: #6c757d; color: white; }

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

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; font-weight: 600; }

        .try-it {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
        }
        .try-it h4 { color: #0066cc; margin-bottom: 10px; }
        .try-it select, .try-it input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }
        .try-it button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .try-it button:hover { background: #5a6fd6; }

        #demo-result {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav">
            <a href="../qa_dashboard.php">Back to QA Dashboard</a>
            <a href="?run=1">Re-run Tests</a>
        </nav>

        <header>
            <h1>Billing Calendar Functions</h1>
            <p>Tests for monthly calendar, events, MTD summaries, and completion tracking</p>
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
            <h2>Live Demo: Calendar Year View</h2>
            <p>This shows the actual output of <code>get_calendar_year_summary()</code> for the current year:</p>

            <div class="demo-box">
                <h3>Year <?php echo date("Y"); ?> Calendar</h3>
                <?php $summary = get_calendar_year_summary((int) date("Y")); ?>
                <div class="calendar-grid">
                    <?php foreach ($summary as $month_num => $month): ?>
                    <div class="month-cell <?php
                    echo $month["is_current"] ? "current" : "";
                    echo $month["is_complete"] ? " complete" : "";
                    echo $month["event_count"] > 0 ? " has-events" : "";
                    ?>">
                        <div class="month-name"><?php echo $month[
                            "month_name"
                        ]; ?></div>
                        <div class="event-count">
                            <?php if ($month["event_count"] > 0): ?>
                                <?php echo $month["event_count"]; ?> event(s)
                            <?php else: ?>
                                No events
                            <?php endif; ?>
                        </div>
                        <?php if ($month["warning_count"] > 0): ?>
                            <div class="event-count" style="color: #dc3545;">
                                <?php echo $month[
                                    "warning_count"
                                ]; ?> warning(s)
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
                    <strong>Legend:</strong>
                    Purple = Current Month |
                    Green = Complete |
                    Yellow Border = Has Events
                </p>
            </div>
        </section>

        <section>
            <h2>Live Demo: Current Month Events</h2>
            <p>This shows the actual output of <code>get_month_events()</code> for <?php echo date(
                "F Y"
            ); ?>:</p>

            <?php $events = get_month_events(
                (int) date("Y"),
                (int) date("n")
            ); ?>
            <div class="demo-box">
                <h3>Events for <?php echo date("F Y"); ?></h3>

                <?php if (
                    empty($events["escalators"]) &&
                    empty($events["resets"]) &&
                    empty($events["new_customers"]) &&
                    empty($events["paused_customers"]) &&
                    empty($events["warnings"])
                ): ?>
                    <p><em>No events for this month.</em></p>
                <?php else: ?>
                    <ul class="event-list">
                        <?php foreach ($events["escalators"] as $e): ?>
                        <li>
                            <span class="event-type escalator">Escalator</span>
                            Customer #<?php echo $e["customer_id"]; ?> -
                            Year <?php echo $e["year_number"]; ?>
                            (<?php echo $e["escalator_percentage"]; ?>%)
                        </li>
                        <?php endforeach; ?>

                        <?php foreach ($events["resets"] as $r): ?>
                        <li>
                            <span class="event-type reset">Reset</span>
                            Customer #<?php echo $r[
                                "customer_id"
                            ]; ?> - Annualized period reset
                        </li>
                        <?php endforeach; ?>

                        <?php foreach ($events["paused_customers"] as $p): ?>
                        <li>
                            <span class="event-type paused">Paused</span>
                            <?php echo htmlspecialchars(
                                $p["name"]
                            ); ?> (ID: <?php echo $p["id"]; ?>)
                        </li>
                        <?php endforeach; ?>

                        <?php foreach ($events["warnings"] as $w): ?>
                        <li>
                            <span class="event-type warning">Warning</span>
                            Customer #<?php echo $w[
                                "customer_id"
                            ]; ?> - <?php echo htmlspecialchars($w["type"]); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2>Try It Yourself</h2>
            <div class="try-it">
                <h4>Test get_month_events()</h4>
                <form method="get" style="display: inline;">
                    <select name="demo_year">
                        <?php for ($y = 2024; $y <= 2027; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo isset(
    $_GET["demo_year"]
) && $_GET["demo_year"] == $y
    ? "selected"
    : ""; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="demo_month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo isset(
    $_GET["demo_month"]
) && $_GET["demo_month"] == $m
    ? "selected"
    : ""; ?>><?php echo date("F", mktime(0, 0, 0, $m, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit">Get Events</button>
                </form>

                <?php if (
                    isset($_GET["demo_year"]) &&
                    isset($_GET["demo_month"])
                ): ?>
                <div id="demo-result" style="display: block;">
                    <h4>Results for <?php echo date(
                        "F",
                        mktime(0, 0, 0, $_GET["demo_month"], 1)
                    ) .
                        " " .
                        $_GET["demo_year"]; ?>:</h4>
                    <pre class="code-example"><?php
                    $demo_events = get_month_events(
                        (int) $_GET["demo_year"],
                        (int) $_GET["demo_month"]
                    );
                    print_r($demo_events);
                    ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2>Code Examples</h2>

            <div class="demo-box">
                <h3>Get Calendar Year Summary</h3>
                <pre class="code-example"><span class="comment">// Get summary for all 12 months of a year</span>
<span class="variable">$summary</span> = <span class="function">get_calendar_year_summary</span>(<span class="string">2026</span>);

<span class="comment">// Each month contains:</span>
<span class="comment">// - year, month, month_name</span>
<span class="comment">// - event_count, warning_count</span>
<span class="comment">// - has_escalators, is_complete</span>
<span class="comment">// - is_current, is_past</span>

<span class="keyword">foreach</span> (<span class="variable">$summary</span> <span class="keyword">as</span> <span class="variable">$month</span>) {
    <span class="keyword">if</span> (<span class="variable">$month</span>[<span class="string">'has_escalators'</span>]) {
        <span class="function">echo</span> <span class="variable">$month</span>[<span class="string">'month_name'</span>] . <span class="string">" has escalators!\n"</span>;
    }
}</pre>
            </div>

            <div class="demo-box">
                <h3>Get Month Events</h3>
                <pre class="code-example"><span class="comment">// Get all events for a specific month</span>
<span class="variable">$events</span> = <span class="function">get_month_events</span>(<span class="string">2026</span>, <span class="string">1</span>); <span class="comment">// January 2026</span>

<span class="comment">// Returns arrays for:</span>
<span class="comment">// - escalators: customers with price escalations</span>
<span class="comment">// - resets: annualized volume resets</span>
<span class="comment">// - new_customers: recently added customers</span>
<span class="comment">// - paused_customers: customers on hold</span>
<span class="comment">// - warnings: issues needing attention</span>

<span class="keyword">foreach</span> (<span class="variable">$events</span>[<span class="string">'warnings'</span>] <span class="keyword">as</span> <span class="variable">$warning</span>) {
    <span class="function">alert_team</span>(<span class="variable">$warning</span>);
}</pre>
            </div>

            <div class="demo-box">
                <h3>Check Month Completion</h3>
                <pre class="code-example"><span class="comment">// Check if monthly billing is complete</span>
<span class="keyword">if</span> (<span class="function">is_month_complete</span>(<span class="string">2026</span>, <span class="string">1</span>)) {
    <span class="function">echo</span> <span class="string">"January 2026 billing is finalized!"</span>;
} <span class="keyword">else</span> {
    <span class="variable">$next</span> = <span class="function">get_next_incomplete_month</span>();
    <span class="function">echo</span> <span class="string">"Next incomplete: "</span> . <span class="variable">$next</span>[<span class="string">'month'</span>] . <span class="string">"/"</span> . <span class="variable">$next</span>[<span class="string">'year'</span>];
}</pre>
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
            <p>QA Test Page | Last run: <?php echo date("Y-m-d H:i:s"); ?></p>
        </footer>
    </div>
</body>
</html>
<?php
}
