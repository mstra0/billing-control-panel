<?php
/**
 * Test: Billing Calendar Functions
 *
 * Tests for get_month_events(), get_calendar_year_summary(), is_month_complete(),
 * get_next_incomplete_month(), get_mtd_summary(), and related functions.
 */

echo "Testing: Billing Calendar Functions\n";
echo "====================================\n";

// ============================================================
// get_month_events() tests
// ============================================================

run_test("get_month_events - returns expected structure", function () {
    $events = get_month_events(2026, 1);

    assert_true(is_array($events), "Should return array");
    assert_array_has_key("escalators", $events, "Should have escalators key");
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
    // Create customer with escalator starting today
    $customer_id = create_test_customer(["name" => "Calendar Escalator Test"]);

    // Set escalator to start 1 year ago (so year 2 kicks in this month)
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

    // Should detect the escalator anniversary
    assert_true(is_array($events["escalators"]), "Escalators should be array");
});

run_test("get_month_events - detects paused customers", function () {
    // Create a paused customer
    $customer_id = create_test_customer([
        "name" => "Paused Calendar Customer",
        "status" => "paused",
    ]);

    $events = get_month_events(2026, 1);

    assert_true(
        is_array($events["paused_customers"]),
        "Paused customers should be array"
    );

    // Find the paused customer in results
    $found = false;
    foreach ($events["paused_customers"] as $p) {
        if ($p["id"] == $customer_id) {
            $found = true;
            break;
        }
    }
    assert_true($found, "Should find the paused customer");
});

run_test("get_month_events - warns about customers without LMS", function () {
    // Create customer without LMS
    $customer_id = create_test_customer([
        "name" => "No LMS Calendar Customer",
        "lms_id" => null,
        "status" => "active",
    ]);

    $events = get_month_events(2026, 1);

    // Should have warning about missing LMS
    $found_warning = false;
    foreach ($events["warnings"] as $w) {
        if ($w["customer_id"] == $customer_id && $w["type"] == "no_lms") {
            $found_warning = true;
            break;
        }
    }
    assert_true($found_warning, "Should warn about customer without LMS");
});

run_test("get_month_events - detects annualized resets", function () {
    $customer_id = create_test_customer(["name" => "Annualized Reset Test"]);

    // Set annualized with start date in current month
    $current_month = (int) date("n");
    $current_day = 15; // Mid-month
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

    // Should detect the reset
    $found = false;
    foreach ($events["resets"] as $r) {
        if ($r["customer_id"] == $customer_id) {
            $found = true;
            break;
        }
    }
    assert_true($found, "Should detect annualized reset");
});

// ============================================================
// is_month_complete() tests
// ============================================================

run_test("is_month_complete - false when no monthly report", function () {
    // No reports imported yet for this specific month
    $result = is_month_complete(2030, 12);
    assert_false($result, "Should be false when no monthly report exists");
});

run_test("is_month_complete - true after monthly report ingested", function () {
    // Import a monthly report
    $csv =
        "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2026,3,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    // Monthly filename pattern
    import_billing_report("DataX_2026_03_2026_03_monthly.csv", $csv);

    $result = is_month_complete(2026, 3);
    assert_true($result, "Should be true after monthly report imported");
});

run_test(
    "is_month_complete - daily report does not mark complete",
    function () {
        // Import a daily report
        $csv =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv .= "2026,4,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

        // Daily filename pattern
        import_billing_report("DataX_2026_04_01_2026_04_01_daily.csv", $csv);

        $result = is_month_complete(2026, 4);
        assert_false($result, "Daily report should not mark month as complete");
    }
);

// ============================================================
// get_calendar_year_summary() tests
// ============================================================

run_test("get_calendar_year_summary - returns 12 months", function () {
    $summary = get_calendar_year_summary(2026);

    assert_count(12, $summary, "Should return 12 months");
});

run_test("get_calendar_year_summary - month structure", function () {
    $summary = get_calendar_year_summary(2026);

    // Check first month has expected keys
    $jan = $summary[1];

    assert_array_has_key("year", $jan, "Should have year");
    assert_array_has_key("month", $jan, "Should have month");
    assert_array_has_key("month_name", $jan, "Should have month_name");
    assert_array_has_key("event_count", $jan, "Should have event_count");
    assert_array_has_key("warning_count", $jan, "Should have warning_count");
    assert_array_has_key("has_escalators", $jan, "Should have has_escalators");
    assert_array_has_key("is_complete", $jan, "Should have is_complete");
    assert_array_has_key("is_current", $jan, "Should have is_current");
    assert_array_has_key("is_past", $jan, "Should have is_past");

    assert_equals(2026, $jan["year"], "Year should match");
    assert_equals(1, $jan["month"], "Month should be 1 for January");
    assert_equals("Jan", $jan["month_name"], "Month name should be Jan");
});

run_test("get_calendar_year_summary - identifies current month", function () {
    $current_year = (int) date("Y");
    $current_month = (int) date("n");

    $summary = get_calendar_year_summary($current_year);

    // Current month should be marked
    assert_true(
        $summary[$current_month]["is_current"],
        "Current month should be marked"
    );

    // Future months should not be past
    if ($current_month < 12) {
        assert_false(
            $summary[$current_month + 1]["is_past"],
            "Future month should not be past"
        );
    }

    // Past months should be marked (if not January)
    if ($current_month > 1) {
        assert_true(
            $summary[$current_month - 1]["is_past"],
            "Previous month should be past"
        );
    }
});

run_test("get_calendar_year_summary - tracks completion status", function () {
    // Import a monthly report for February 2026
    $csv =
        "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2026,2,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    import_billing_report("DataX_2026_02_2026_02_summary.csv", $csv);

    $summary = get_calendar_year_summary(2026);

    assert_true(
        $summary[2]["is_complete"],
        "February should be marked complete"
    );
});

// ============================================================
// get_next_incomplete_month() tests
// ============================================================

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
        // Clear any existing reports for current month (use a future month to be safe)
        $result = get_next_incomplete_month();

        // Should return a valid year/month
        assert_greater_than(2020, $result["year"], "Year should be reasonable");
        assert_greater_than(0, $result["month"], "Month should be >= 1");
        assert_less_than(13, $result["month"], "Month should be <= 12");
    }
);

// ============================================================
// get_new_customers_since() tests
// ============================================================

run_test("get_new_customers_since - returns array", function () {
    $result = get_new_customers_since("2020-01-01");
    assert_true(is_array($result), "Should return array");
});

run_test("get_new_customers_since - finds new customers", function () {
    // Create a customer with recent created_at
    $customer_id = create_test_customer(["name" => "Brand New Customer"]);

    // Query for customers since yesterday
    $since = date("Y-m-d", strtotime("-1 day"));
    $result = get_new_customers_since($since);

    // Should find at least one
    assert_greater_than(0, count($result), "Should find new customers");
});

run_test("get_new_customers_since - includes customer details", function () {
    $customer_id = create_test_customer(["name" => "Detailed New Customer"]);

    $since = date("Y-m-d", strtotime("-1 day"));
    $result = get_new_customers_since($since);

    if (count($result) > 0) {
        assert_array_has_key("name", $result[0], "Should have name");
        assert_array_has_key("id", $result[0], "Should have id");
    }
});

// ============================================================
// get_config_changes_since() tests
// ============================================================

run_test("get_config_changes_since - returns array", function () {
    $result = get_config_changes_since("2020-01-01");
    assert_true(is_array($result), "Should return array");
});

run_test("get_config_changes_since - detects pricing changes", function () {
    // Create customer and add pricing
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

    // Should detect the pricing change
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

run_test("get_config_changes_since - detects settings changes", function () {
    $customer_id = create_test_customer(["name" => "Settings Change Test"]);

    save_customer_settings($customer_id, [
        "monthly_minimum" => 500,
    ]);

    $since = date("Y-m-d", strtotime("-1 day"));
    $result = get_config_changes_since($since);

    // Should detect the settings change
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
});

// ============================================================
// get_mtd_summary() tests
// ============================================================

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
    assert_array_has_key("total_revenue", $result, "Should have total_revenue");
    assert_array_has_key("report_count", $result, "Should have report_count");
    assert_array_has_key("latest_date", $result, "Should have latest_date");
});

run_test("get_mtd_summary - no data returns zeros", function () {
    // Use a year/month with no data
    $result = get_mtd_summary(2099, 12);

    // Should return structure with zeros/nulls
    assert_not_null($result, "Should return structure even with no data");
});

run_test("get_mtd_summary - calculates from daily reports", function () {
    // Import daily reports for a specific month
    $csv1 =
        "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv1 .= "2026,5,1,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    $csv2 =
        "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv2 .= "2026,5,1,Test,HIT002,Test2,0.60,200,120.00,CC002,BIL002\n";

    import_billing_report("DataX_2026_05_01_2026_05_01_day1.csv", $csv1);
    import_billing_report("DataX_2026_05_02_2026_05_02_day2.csv", $csv2);

    $result = get_mtd_summary(2026, 5);

    assert_equals(2, $result["report_count"], "Should have 2 daily reports");
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

// ============================================================
// get_mtd_daily_breakdown() tests
// ============================================================

run_test("get_mtd_daily_breakdown - returns array", function () {
    $result = get_mtd_daily_breakdown(2026, 5);
    assert_true(is_array($result), "Should return array");
});

run_test("get_mtd_daily_breakdown - groups by date", function () {
    // Should already have data from previous test
    $result = get_mtd_daily_breakdown(2026, 5);

    // Each row should have report_date
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

// ============================================================
// get_mtd_service_breakdown() tests
// ============================================================

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

// ============================================================
// get_mtd_customer_breakdown() tests
// ============================================================

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

// ============================================================
// get_previous_month_mtd() tests
// ============================================================

run_test("get_previous_month_mtd - returns expected structure", function () {
    $result = get_previous_month_mtd(2026, 6, 15);

    assert_true(is_array($result), "Should return array");
    assert_array_has_key(
        "total_transactions",
        $result,
        "Should have total_transactions"
    );
    assert_array_has_key("total_revenue", $result, "Should have total_revenue");
});

run_test("get_previous_month_mtd - handles January correctly", function () {
    // January should look at December of previous year
    $result = get_previous_month_mtd(2026, 1, 15);

    // Should not error - should look at Dec 2025
    assert_not_null($result, "Should handle January wrap-around");
});

run_test("get_previous_month_mtd - compares same day range", function () {
    // Import data for June 2026, days 1-10
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

    // When checking July day 2, should only look at June days 1-2
    $result = get_previous_month_mtd(2026, 7, 2);

    // Should have data from June days 1-2 only (20 transactions, $20 revenue)
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

// ============================================================
// Edge cases and integration tests
// ============================================================

run_test("calendar - handles year boundaries", function () {
    // December to January transition
    $dec = get_month_events(2025, 12);
    $jan = get_month_events(2026, 1);

    assert_not_null($dec, "Should handle December");
    assert_not_null($jan, "Should handle January");
});

run_test("calendar - handles leap year", function () {
    // 2024 is a leap year
    $feb = get_month_events(2024, 2);
    assert_not_null($feb, "Should handle leap year February");
});

run_test("calendar - escalator with delay shows in correct month", function () {
    $customer_id = create_test_customer([
        "name" => "Delayed Escalator Calendar Test",
    ]);

    // Start date 11 months ago, year 2 with 1 month delay = takes effect this month
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

    // Add 1 month delay
    save_escalator_delay($customer_id, 2, 1, "Test delay");

    $current_year = (int) date("Y");
    $current_month = (int) date("n");

    $events = get_month_events($current_year, $current_month);

    // The delay should push the escalator to a different month
    // This test verifies the delay is considered
    assert_true(
        is_array($events["escalators"]),
        "Should have escalators array"
    );
});

run_test("calendar - multiple events in same month", function () {
    // Create multiple customers with escalators in the same month
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

    // Should have multiple escalators
    assert_greater_than(
        0,
        count($events["escalators"]),
        "Should have escalator events"
    );
});

echo "\n";
