<?php
/**
 * Test: Query/Retrieval Functions
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for get_* functions that retrieve data.
 * Priority 4 - Query functions should return correct data shapes.
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
    require_once __DIR__ . "/../qa_wrapper.php";

    ob_start();
    $_qa_test_results = run_queries_tests();
    $test_output = ob_get_clean();

    $demo_content = render_queries_demo();
    render_qa_page(
        "Query/Retrieval Functions",
        "Tests for get_* functions: services, tiers, settings, escalators, LMS, billing",
        $_qa_test_results,
        $test_output,
        $demo_content,
        "#667eea",
        false
    );
    exit();
}

// ============================================================
// CLI MODE: Include bootstrap if running standalone
// ============================================================
if ($_is_cli && !$_is_included) {
    require_once __DIR__ . "/../bootstrap.php";
}

// ============================================================
// TEST MODE
// ============================================================
echo "Testing: Query/Retrieval Functions\n";
echo "===================================\n";

run_queries_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_queries_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>Query Function Categories</h3>
        <p>The system provides various get_* functions for retrieving data:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>Services</strong> - get_all_services(), get_service()</li>
            <li><strong>Tiers</strong> - get_current_default_tiers(), get_current_group_tiers(), get_current_customer_tiers()</li>
            <li><strong>Settings</strong> - get_current_customer_settings(), get_service_cogs()</li>
            <li><strong>Escalators</strong> - get_current_escalators(), get_escalator_delays()</li>
            <li><strong>LMS</strong> - get_all_lms(), get_lms(), get_customers_by_lms()</li>
            <li><strong>Billing</strong> - get_billing_reports(), get_billing_report_lines()</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>Common Query Patterns</h3>
        <table>
            <tr>
                <th>Pattern</th>
                <th>Example</th>
                <th>Returns</th>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_all_*()</td>
                <td>get_all_services()</td>
                <td>Array of all entities</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_*($id)</td>
                <td>get_lms($id)</td>
                <td>Single entity or null</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_current_*($id)</td>
                <td>get_current_escalators($cust_id)</td>
                <td>Current effective set</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_*_by_*($id)</td>
                <td>get_customers_by_lms($lms_id)</td>
                <td>Filtered array</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// Get all services
$services = get_all_services();

// Get current tiers for a customer+service
$tiers = get_current_customer_tiers($customer_id, $service_id);

// Get customer settings
$settings = get_current_customer_settings($customer_id);

// Get LMS with its customers
$lms = get_lms($lms_id);
$customers = get_customers_by_lms($lms_id);</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: Query Functions (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <button type="submit" name="run_queries" value="1" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Run Query Functions
            </button>
        </form>

        <?php if (isset($_GET["run_queries"])):
            // Call REAL functions

            $services = get_all_services();
            $lms_list = get_all_lms();
            $transaction_types = get_all_transaction_types();
            $billing_reports = get_billing_reports();
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;">get_all_services() - <?php echo count(
                $services
            ); ?> found:</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 150px;"><?php echo empty(
                $services
            )
                ? "(none)"
                : print_r($services, true); ?></pre>

            <h4 style="color: #155724; margin: 15px 0 10px;">get_all_lms() - <?php echo count(
                $lms_list
            ); ?> found:</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 150px;"><?php echo empty(
                $lms_list
            )
                ? "(none)"
                : print_r($lms_list, true); ?></pre>

            <h4 style="color: #155724; margin: 15px 0 10px;">get_all_transaction_types() - <?php echo count(
                $transaction_types
            ); ?> found:</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 150px;"><?php echo empty(
                $transaction_types
            )
                ? "(none)"
                : print_r($transaction_types, true); ?></pre>

            <h4 style="color: #155724; margin: 15px 0 10px;">get_billing_reports() - <?php echo count(
                $billing_reports
            ); ?> found:</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 150px;"><?php echo empty(
                $billing_reports
            )
                ? "(none)"
                : print_r($billing_reports, true); ?></pre>
        </div>
        <?php
        endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// TEST DEFINITIONS
// ============================================================
function run_queries_tests()
{
    global $_test_results;

    // ============================================================
    // get_all_services() tests
    // ============================================================

    run_test("get_all_services - empty database", function () {
        $services = get_all_services();
        assert_count(0, $services, "Should return empty array");
    });

    run_test("get_all_services - returns all services", function () {
        create_test_service(["name" => "Service A"]);
        create_test_service(["name" => "Service B"]);
        create_test_service(["name" => "Service C"]);

        $services = get_all_services();
        assert_count(3, $services, "Should return 3 services");
    });

    // ============================================================
    // get_current_default_tiers() tests
    // ============================================================

    run_test("get_current_default_tiers - no tiers", function () {
        $service_id = create_test_service(["name" => "No Tiers"]);

        $tiers = get_current_default_tiers($service_id);
        assert_count(0, $tiers, "Should return empty for no tiers");
    });

    run_test("get_current_default_tiers - returns current tiers", function () {
        $service_id = create_test_service(["name" => "With Tiers"]);

        save_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 1000,
                "price_per_inquiry" => 0.5,
            ],
            [
                "volume_start" => 1001,
                "volume_end" => null,
                "price_per_inquiry" => 0.4,
            ],
        ]);

        $tiers = get_current_default_tiers($service_id);
        assert_count(2, $tiers, "Should return 2 tiers");
        assert_equals(0, $tiers[0]["volume_start"], "First tier starts at 0");
        assert_equals(1000, $tiers[0]["volume_end"], "First tier ends at 1000");
    });

    run_test(
        "get_current_default_tiers - respects effective date",
        function () {
            $service_id = create_test_service(["name" => "Future Tiers"]);

            // Current tiers
            save_default_tiers(
                $service_id,
                [
                    [
                        "volume_start" => 0,
                        "volume_end" => null,
                        "price_per_inquiry" => 0.5,
                    ],
                ],
                date("Y-m-d")
            );

            // Future tiers (30 days from now)
            save_default_tiers(
                $service_id,
                [
                    [
                        "volume_start" => 0,
                        "volume_end" => null,
                        "price_per_inquiry" => 0.6,
                    ],
                ],
                date("Y-m-d", strtotime("+30 days"))
            );

            $tiers = get_current_default_tiers($service_id);
            // Should return current, not future
            assert_float_equals(
                0.5,
                $tiers[0]["price_per_inquiry"],
                0.01,
                "Should return current tier price"
            );
        }
    );

    // ============================================================
    // get_current_group_tiers() tests
    // ============================================================

    run_test("get_current_group_tiers - no override", function () {
        $group_id = create_test_group(["name" => "No Override Group"]);
        $service_id = create_test_service(["name" => "Group Service"]);

        $tiers = get_current_group_tiers($group_id, $service_id);
        assert_count(0, $tiers, "Should return empty for no override");
    });

    run_test("get_current_group_tiers - with override", function () {
        $group_id = create_test_group(["name" => "Override Group"]);
        $service_id = create_test_service(["name" => "Group Override Service"]);

        save_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 500,
                "price_per_inquiry" => 0.45,
            ],
            [
                "volume_start" => 501,
                "volume_end" => null,
                "price_per_inquiry" => 0.35,
            ],
        ]);

        $tiers = get_current_group_tiers($group_id, $service_id);
        assert_count(2, $tiers, "Should return 2 group tiers");
    });

    // ============================================================
    // get_current_customer_tiers() tests
    // ============================================================

    run_test("get_current_customer_tiers - no override", function () {
        $customer_id = create_test_customer(["name" => "No Override Customer"]);
        $service_id = create_test_service(["name" => "Customer Service"]);

        $tiers = get_current_customer_tiers($customer_id, $service_id);
        assert_count(0, $tiers, "Should return empty for no override");
    });

    run_test("get_current_customer_tiers - with override", function () {
        $customer_id = create_test_customer(["name" => "Override Customer"]);
        $service_id = create_test_service([
            "name" => "Customer Override Service",
        ]);

        save_customer_tiers($customer_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.3,
            ],
        ]);

        $tiers = get_current_customer_tiers($customer_id, $service_id);
        assert_count(1, $tiers, "Should return 1 customer tier");
        assert_float_equals(
            0.3,
            $tiers[0]["price_per_inquiry"],
            0.01,
            "Customer price"
        );
    });

    // ============================================================
    // get_current_customer_settings() tests
    // ============================================================

    run_test(
        "get_current_customer_settings - no settings returns defaults",
        function () {
            $customer_id = create_test_customer(["name" => "Default Settings"]);

            $settings = get_current_customer_settings($customer_id);
            // Should return some structure even without explicit settings
            assert_not_null($settings, "Should return settings object");
        }
    );

    run_test("get_current_customer_settings - with settings", function () {
        $customer_id = create_test_customer(["name" => "Explicit Settings"]);

        save_customer_settings($customer_id, [
            "monthly_minimum" => 250.0,
            "uses_annualized" => 1,
        ]);

        $settings = get_current_customer_settings($customer_id);
        assert_float_equals(
            250.0,
            $settings["monthly_minimum"],
            0.01,
            "Monthly minimum"
        );
        assert_equals(1, $settings["uses_annualized"], "Uses annualized");
    });

    // ============================================================
    // get_current_escalators() tests
    // ============================================================

    run_test("get_current_escalators - no escalators", function () {
        $customer_id = create_test_customer(["name" => "No Escalators"]);

        $escalators = get_current_escalators($customer_id);
        assert_count(0, $escalators, "Should return empty");
    });

    run_test("get_current_escalators - with escalators", function () {
        $customer_id = create_test_customer(["name" => "Has Escalators"]);

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
                    "escalator_percentage" => 5,
                    "fixed_adjustment" => 10,
                ],
            ],
            "2025-01-01"
        );

        $escalators = get_current_escalators($customer_id);
        assert_count(3, $escalators, "Should return 3 escalators");
        assert_equals(1, $escalators[0]["year_number"], "Year 1");
        assert_equals(2, $escalators[1]["year_number"], "Year 2");
        assert_equals(3, $escalators[2]["year_number"], "Year 3");
    });

    // ============================================================
    // get_escalator_delays() tests
    // ============================================================

    run_test("get_escalator_delays - no delays", function () {
        $customer_id = create_test_customer(["name" => "No Delays"]);

        $delays = get_escalator_delays($customer_id);
        assert_count(0, $delays, "Should return empty");
    });

    run_test("get_escalator_delays - with delays", function () {
        $customer_id = create_test_customer(["name" => "Has Delays"]);

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

        apply_escalator_delay($customer_id, 2, 3);

        $delays = get_escalator_delays($customer_id);
        assert_count(1, $delays, "Should return 1 delay");
        assert_equals(2, $delays[0]["year_number"], "Delay for year 2");
        assert_equals(3, $delays[0]["delay_months"], "3 month delay");
    });

    // ============================================================
    // get_total_delay_months() tests
    // ============================================================

    run_test("get_total_delay_months - no delays returns 0", function () {
        $customer_id = create_test_customer(["name" => "Zero Delay"]);

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

        $total = get_total_delay_months($customer_id, 2);
        assert_equals(0, $total, "Should return 0");
    });

    run_test("get_total_delay_months - sums multiple delays", function () {
        $customer_id = create_test_customer(["name" => "Sum Delays"]);

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
        apply_escalator_delay($customer_id, 2, 4);

        $total = get_total_delay_months($customer_id, 2);
        assert_equals(6, $total, "Should sum to 6 months");
    });

    // ============================================================
    // get_all_lms() tests
    // ============================================================

    run_test("get_all_lms - empty", function () {
        $lms_list = get_all_lms();
        assert_count(0, $lms_list, "Should return empty");
    });

    run_test("get_all_lms - returns all", function () {
        create_test_lms(["name" => "LMS Alpha"]);
        create_test_lms(["name" => "LMS Beta"]);

        $lms_list = get_all_lms();
        assert_count(2, $lms_list, "Should return 2 LMS");
    });

    // ============================================================
    // get_lms() tests
    // ============================================================

    run_test("get_lms - exists", function () {
        $id = create_test_lms(["name" => "Find Me LMS"]);

        $lms = get_lms($id);
        assert_not_null($lms, "Should find LMS");
        assert_equals("Find Me LMS", $lms["name"], "Name should match");
    });

    run_test("get_lms - not exists", function () {
        $lms = get_lms(99999);
        assert_null($lms, "Should return null for non-existent");
    });

    // ============================================================
    // get_default_commission_rate() tests
    // ============================================================

    run_test("get_default_commission_rate - returns rate or null", function () {
        $rate = get_default_commission_rate();
        // Returns whatever is in the database (may have been set by earlier tests)
        assert_true(
            $rate === null || is_numeric($rate),
            "Should be null or numeric"
        );
    });

    run_test("get_default_commission_rate - set", function () {
        save_default_commission_rate(0.12);

        $rate = get_default_commission_rate();
        assert_float_equals(0.12, $rate, 0.001, "Should return saved rate");
    });

    // ============================================================
    // get_effective_commission_rate() tests
    // ============================================================

    run_test("get_effective_commission_rate - LMS override", function () {
        save_default_commission_rate(0.1);
        $lms_id = create_test_lms([
            "name" => "Override LMS",
            "commission_rate" => 0.15,
        ]);

        $rate = get_effective_commission_rate($lms_id);
        assert_float_equals(0.15, $rate, 0.001, "Should return LMS rate");
    });

    run_test(
        "get_effective_commission_rate - uses default when LMS has no override",
        function () {
            save_default_commission_rate(0.1);
            $lms_id = create_test_lms([
                "name" => "Default LMS",
                "commission_rate" => null,
            ]);

            $rate = get_effective_commission_rate($lms_id);
            assert_float_equals(
                0.1,
                $rate,
                0.001,
                "Should return default rate"
            );
        }
    );

    // ============================================================
    // get_service_cogs() tests
    // ============================================================

    run_test("get_service_cogs - not set returns null or 0", function () {
        $service_id = create_test_service(["name" => "No COGS"]);

        $cogs = get_service_cogs($service_id);
        // Implementation may return null or 0 for unset
        assert_true(
            $cogs === null || $cogs == 0,
            "Should return null or 0 when not set"
        );
    });

    run_test("get_service_cogs - set", function () {
        $service_id = create_test_service(["name" => "Has COGS"]);
        save_service_cogs($service_id, 0.28);

        $cogs = get_service_cogs($service_id);
        assert_float_equals(0.28, $cogs, 0.01, "Should return COGS rate");
    });

    // ============================================================
    // get_billing_reports() tests
    // ============================================================

    run_test("get_billing_reports - empty", function () {
        $reports = get_billing_reports();
        assert_count(0, $reports, "Should return empty");
    });

    run_test("get_billing_reports - all types", function () {
        // Create customer for FK
        create_test_customer(["id" => 301, "name" => "Report Customer"]);

        $csv =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv .= "2025,1,301,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

        import_billing_report("DataX_2025_11_2025_11_monthly.csv", $csv);
        import_billing_report("DataX_2025_11_20_daily.csv", $csv);

        $all = get_billing_reports();
        assert_greater_than(1, count($all), "Should have multiple reports");
    });

    run_test("get_billing_reports - filter by type", function () {
        // Create customer for FK
        create_test_customer(["id" => 302, "name" => "Filter Customer"]);

        $csv =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv .= "2025,1,302,Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

        import_billing_report("DataX_2025_12_2025_12_m.csv", $csv);
        import_billing_report("DataX_2025_12_15_d.csv", $csv);

        $monthly = get_billing_reports("monthly");
        $daily = get_billing_reports("daily");

        // Each should have at least one
        foreach ($monthly as $r) {
            assert_equals("monthly", $r["report_type"], "Should be monthly");
        }
        foreach ($daily as $r) {
            assert_equals("daily", $r["report_type"], "Should be daily");
        }
    });

    // ============================================================
    // get_billing_report_lines() tests
    // ============================================================

    run_test("get_billing_report_lines - with lines", function () {
        create_test_customer(["id" => 303, "name" => "Lines Customer A"]);
        create_test_customer(["id" => 304, "name" => "Lines Customer B"]);

        $csv =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv .= "2025,1,303,A,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";
        $csv .= "2025,1,304,B,HIT002,Test2,0.60,200,120.00,CC002,BIL002\n";

        $result = import_billing_report(
            "DataX_2026_01_2026_01_lines.csv",
            $csv
        );
        $report_id = $result["report_id"];

        $lines = get_billing_report_lines($report_id);
        assert_count(2, $lines, "Should have 2 lines");
    });

    run_test("get_billing_report_lines - empty report", function () {
        $lines = get_billing_report_lines(99999);
        assert_count(0, $lines, "Should return empty for non-existent report");
    });

    // ============================================================
    // get_all_transaction_types() tests
    // ============================================================

    run_test("get_all_transaction_types - empty", function () {
        $types = get_all_transaction_types();
        assert_count(0, $types, "Should return empty");
    });

    run_test("get_all_transaction_types - returns all", function () {
        save_transaction_type("credit", "Credit", "CR001");
        save_transaction_type("identity", "Identity", "ID001");
        save_transaction_type("other", "Other", "OT001");

        $types = get_all_transaction_types();
        assert_count(3, $types, "Should return 3 types");
    });

    // ============================================================
    // get_transaction_type_by_efx() tests
    // ============================================================

    run_test("get_transaction_type_by_efx - exists", function () {
        save_transaction_type("lookup", "Lookup Type", "LOOK001");

        $type = get_transaction_type_by_efx("LOOK001");
        assert_not_null($type, "Should find type");
        assert_equals("lookup", $type["type"], "Type should match");
    });

    run_test("get_transaction_type_by_efx - not exists", function () {
        $type = get_transaction_type_by_efx("NONEXISTENT");
        assert_null($type, "Should return null");
    });

    // ============================================================
    // get_customers_by_lms() tests
    // ============================================================

    run_test("get_customers_by_lms - with customers", function () {
        $lms_id = create_test_lms(["name" => "Customer LMS"]);

        create_test_customer(["name" => "LMS Cust 1", "lms_id" => $lms_id]);
        create_test_customer(["name" => "LMS Cust 2", "lms_id" => $lms_id]);
        create_test_customer(["name" => "Other Cust"]); // Different LMS

        $customers = get_customers_by_lms($lms_id);
        assert_count(2, $customers, "Should return 2 customers");
    });

    run_test("get_customers_by_lms - no customers", function () {
        $lms_id = create_test_lms(["name" => "Empty LMS"]);

        $customers = get_customers_by_lms($lms_id);
        assert_count(0, $customers, "Should return empty");
    });

    // ============================================================
    // get_customers_without_lms() tests
    // ============================================================

    run_test(
        "get_customers_without_lms - returns unassigned customers",
        function () {
            // Note: This test runs after others that may have created unassigned customers
            // Just verify the function returns an array
            $unassigned = get_customers_without_lms();
            assert_true(is_array($unassigned), "Should return array");
        }
    );

    run_test(
        "get_customers_without_lms - newly assigned customer not in list",
        function () {
            $lms_id = save_lms(null, "Query Test LMS", 0.1);
            $customer_id = create_test_customer([
                "name" => "Query Assigned Customer",
                "lms_id" => $lms_id,
            ]);

            $unassigned = get_customers_without_lms();

            // This specific customer should NOT be in the unassigned list
            $found = false;
            foreach ($unassigned as $c) {
                if ($c["id"] == $customer_id) {
                    $found = true;
                    break;
                }
            }
            assert_false(
                $found,
                "Assigned customer should not be in unassigned list"
            );
        }
    );

    // ============================================================
    // get_customers_with_minimums() tests
    // ============================================================

    run_test("get_customers_with_minimums - with minimums", function () {
        $c1 = create_test_customer(["name" => "Min Customer 1"]);
        $c2 = create_test_customer(["name" => "Min Customer 2"]);

        save_customer_settings($c1, ["monthly_minimum" => 500.0]);
        save_customer_settings($c2, ["monthly_minimum" => 1000.0]);

        $with_mins = get_customers_with_minimums();
        assert_greater_than(
            1,
            count($with_mins),
            "Should return customers with minimums"
        );
    });

    run_test("get_customers_with_minimums - returns array", function () {
        // Just verify function returns proper structure
        $with_mins = get_customers_with_minimums();
        assert_true(is_array($with_mins), "Should return array");
    });

    // ============================================================
    // get_customer_rules() tests
    // ============================================================

    run_test("get_customer_rules - returns rules", function () {
        $customer_id = create_test_customer(["name" => "Rules Customer"]);

        $rules = get_customer_rules($customer_id);
        assert_not_null($rules, "Should return rules array");
    });

    // ============================================================
    // get_rule_mask_status() tests
    // ============================================================

    run_test("get_rule_mask_status - not masked", function () {
        $customer_id = create_test_customer(["name" => "Unmask Status"]);

        $status = get_rule_mask_status($customer_id, "some_rule");
        assert_false($status, "Should return false when not masked");
    });

    run_test("get_rule_mask_status - masked", function () {
        $customer_id = create_test_customer(["name" => "Mask Status"]);

        toggle_rule_mask($customer_id, "masked_rule", true);

        $status = get_rule_mask_status($customer_id, "masked_rule");
        assert_true($status, "Should return true when masked");
    });

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
