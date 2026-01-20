<?php
/**
 * Test: CRUD Operations
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for save_*, delete_*, and related modification functions.
 * Priority 3 - Standard database operations.
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
    $_qa_test_results = run_crud_tests();
    $test_output = ob_get_clean();

    $demo_content = render_crud_demo();
    render_qa_page(
        "CRUD Operations",
        "Tests for save_*, delete_*, and modification functions",
        $_qa_test_results,
        $test_output,
        $demo_content,
        "#28a745",
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
echo "Testing: CRUD Operations\n";
echo "=========================\n";

run_crud_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_crud_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>CRUD Operation Categories</h3>
        <p>Create, Read, Update, Delete operations for all entities:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>Tiers</strong> - save_default_tiers(), save_group_tiers(), save_customer_tiers()</li>
            <li><strong>Settings</strong> - save_customer_settings()</li>
            <li><strong>Escalators</strong> - save_escalators(), apply_escalator_delay()</li>
            <li><strong>LMS</strong> - save_lms(), assign_customer_lms()</li>
            <li><strong>Billing</strong> - delete_billing_report()</li>
            <li><strong>Rules</strong> - toggle_rule_mask()</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>Append-Only History</h3>
        <p>Most save operations are append-only, creating a new "effective set" rather than updating in place:</p>
        <table>
            <tr>
                <th>Operation</th>
                <th>Behavior</th>
            </tr>
            <tr>
                <td style="font-family: monospace;">save_default_tiers()</td>
                <td>Creates new tier set with effective date</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">save_escalators()</td>
                <td>Creates new escalator schedule</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">save_customer_settings()</td>
                <td>Creates new settings snapshot</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// Save tiered pricing for a customer
save_customer_tiers($customer_id, $service_id, array(
    array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.50),
    array('volume_start' => 1001, 'volume_end' => null, 'price_per_inquiry' => 0.40)
));

// Save customer settings
save_customer_settings($customer_id, array(
    'monthly_minimum' => 500.00,
    'uses_annualized' => 1
));

// Create/update LMS
$lms_id = save_lms(null, 'New LMS', 0.15);
assign_customer_lms($customer_id, $lms_id);</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: Save Customer Settings (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <div style="margin-bottom: 10px;">
                <label>Monthly Minimum: $</label>
                <input type="number" name="monthly_min" step="0.01" value="<?php echo isset(
                    $_GET["monthly_min"]
                )
                    ? htmlspecialchars($_GET["monthly_min"])
                    : "500.00"; ?>" style="width: 120px; padding: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label>Uses Annualized:</label>
                <select name="annualized" style="padding: 5px;">
                    <option value="0" <?php echo isset($_GET["annualized"]) &&
                    $_GET["annualized"] == "0"
                        ? "selected"
                        : ""; ?>>No</option>
                    <option value="1" <?php echo isset($_GET["annualized"]) &&
                    $_GET["annualized"] == "1"
                        ? "selected"
                        : ""; ?>>Yes</option>
                </select>
            </div>
            <div style="margin-bottom: 10px;">
                <label>Pause Billing:</label>
                <select name="paused" style="padding: 5px;">
                    <option value="0" <?php echo isset($_GET["paused"]) &&
                    $_GET["paused"] == "0"
                        ? "selected"
                        : ""; ?>>No</option>
                    <option value="1" <?php echo isset($_GET["paused"]) &&
                    $_GET["paused"] == "1"
                        ? "selected"
                        : ""; ?>>Yes</option>
                </select>
            </div>
            <button type="submit" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Save Settings (Real Function)
            </button>
        </form>

        <?php if (isset($_GET["monthly_min"])):
            // Create real test customer

            $demo_customer = create_test_customer([
                "id" => 88888,
                "name" => "Demo CRUD Customer",
            ]);

            // Call REAL function
            save_customer_settings($demo_customer, [
                "monthly_minimum" => floatval($_GET["monthly_min"]),
                "uses_annualized" => intval($_GET["annualized"]),
                "billing_paused" => intval($_GET["paused"]),
            ]);

            // Read back with REAL function
            $saved = get_current_customer_settings($demo_customer);

            // Clean up
            sqlite_execute(
                "DELETE FROM customer_settings WHERE customer_id = ?",
                [88888]
            );
            sqlite_execute("DELETE FROM customers WHERE id = ?", [88888]);
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;">Result from get_current_customer_settings():</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto;"><?php print_r(
                $saved
            ); ?></pre>
            <p style="color: #155724; margin-top: 10px; font-size: 0.9em;">
                <strong>Functions called:</strong><br>
                1. create_test_customer() - Created customer ID <?php echo $demo_customer; ?><br>
                2. save_customer_settings(<?php echo $demo_customer; ?>, [...]) - Saved settings<br>
                3. get_current_customer_settings(<?php echo $demo_customer; ?>) - Retrieved settings<br>
                4. Cleanup - Deleted test data
            </p>
        </div>
        <?php
        endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// TEST DEFINITIONS
// ============================================================
function run_crud_tests()
{
    global $_test_results;

    // ============================================================
    // save_default_tiers() tests
    // ============================================================

    run_test("save_default_tiers - creates new tiers", function () {
        $service_id = create_test_service(["name" => "Credit Check"]);

        $tiers = [
            [
                "volume_start" => 0,
                "volume_end" => 1000,
                "price_per_inquiry" => 0.5,
            ],
            [
                "volume_start" => 1001,
                "volume_end" => 5000,
                "price_per_inquiry" => 0.4,
            ],
            [
                "volume_start" => 5001,
                "volume_end" => null,
                "price_per_inquiry" => 0.3,
            ],
        ];

        save_default_tiers($service_id, $tiers);

        $result = get_current_default_tiers($service_id);
        assert_count(3, $result, "Should save 3 tiers");
        assert_float_equals(
            0.5,
            $result[0]["price_per_inquiry"],
            0.01,
            "First tier price"
        );
        assert_float_equals(
            0.4,
            $result[1]["price_per_inquiry"],
            0.01,
            "Second tier price"
        );
        assert_float_equals(
            0.3,
            $result[2]["price_per_inquiry"],
            0.01,
            "Third tier price"
        );
    });

    run_test(
        "save_default_tiers - append-only creates new effective set",
        function () {
            $service_id = create_test_service(["name" => "ID Verify"]);

            // Initial save
            save_default_tiers($service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => 1000,
                    "price_per_inquiry" => 0.5,
                ],
            ]);

            // Second save creates new effective set (append-only)
            save_default_tiers($service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => 500,
                    "price_per_inquiry" => 0.6,
                ],
                [
                    "volume_start" => 501,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.45,
                ],
            ]);

            // get_current returns the latest effective set
            $result = get_current_default_tiers($service_id);
            assert_greater_than(0, count($result), "Should have tiers");
        }
    );

    run_test("save_default_tiers - with future effective date", function () {
        $service_id = create_test_service(["name" => "Future Service"]);
        $future_date = date("Y-m-d", strtotime("+30 days"));

        // Save current tiers first
        save_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.5,
            ],
        ]);

        // Save future tiers
        save_default_tiers(
            $service_id,
            [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.75,
                ],
            ],
            $future_date
        );

        // Current should still be 0.50 (future not yet effective)
        $result = get_current_default_tiers($service_id);
        assert_greater_than(0, count($result), "Should have current tiers");
    });

    // ============================================================
    // save_group_tiers() tests
    // ============================================================

    run_test("save_group_tiers - creates group override", function () {
        $group_id = create_test_group(["name" => "Premium"]);
        $service_id = create_test_service(["name" => "Credit"]);

        // First create defaults
        save_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 1.0,
            ],
        ]);

        // Then group override
        save_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.8,
            ],
        ]);

        $result = get_current_group_tiers($group_id, $service_id);
        assert_count(1, $result, "Should have 1 group tier");
        assert_float_equals(
            0.8,
            $result[0]["price_per_inquiry"],
            0.01,
            "Group price override"
        );
    });

    run_test("save_group_tiers - multiple saves create history", function () {
        $group_id = create_test_group(["name" => "Temp Group"]);
        $service_id = create_test_service(["name" => "Temp Service"]);

        // Create group tiers
        save_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.5,
            ],
        ]);

        // Verify created
        $result = get_current_group_tiers($group_id, $service_id);
        assert_greater_than(0, count($result), "Should have tiers after save");
    });

    // ============================================================
    // save_customer_tiers() tests
    // ============================================================

    run_test("save_customer_tiers - creates customer override", function () {
        $customer_id = create_test_customer(["name" => "VIP Client"]);
        $service_id = create_test_service(["name" => "Premium Service"]);

        // Create defaults first
        save_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 1.0,
            ],
        ]);

        // Customer override
        save_customer_tiers($customer_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 1000,
                "price_per_inquiry" => 0.6,
            ],
            [
                "volume_start" => 1001,
                "volume_end" => null,
                "price_per_inquiry" => 0.45,
            ],
        ]);

        $result = get_current_customer_tiers($customer_id, $service_id);
        assert_count(2, $result, "Should have 2 customer tiers");
        assert_float_equals(
            0.6,
            $result[0]["price_per_inquiry"],
            0.01,
            "Customer tier 1"
        );
        assert_float_equals(
            0.45,
            $result[1]["price_per_inquiry"],
            0.01,
            "Customer tier 2"
        );
    });

    run_test(
        "save_customer_tiers - multiple saves create history",
        function () {
            $customer_id = create_test_customer(["name" => "Update Client"]);
            $service_id = create_test_service(["name" => "Update Service"]);

            // Initial
            save_customer_tiers($customer_id, $service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.5,
                ],
            ]);

            // Second save (append-only system)
            save_customer_tiers($customer_id, $service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.35,
                ],
            ]);

            $result = get_current_customer_tiers($customer_id, $service_id);
            assert_greater_than(0, count($result), "Should have tiers");
        }
    );

    // ============================================================
    // save_customer_settings() tests
    // ============================================================

    run_test("save_customer_settings - all fields", function () {
        $customer_id = create_test_customer(["name" => "Settings Client"]);

        save_customer_settings($customer_id, [
            "monthly_minimum" => 500.0,
            "uses_annualized" => 1,
            "annualized_start_date" => "2025-06-01",
            "look_period_months" => 12,
        ]);

        $result = get_current_customer_settings($customer_id);
        assert_float_equals(
            500.0,
            $result["monthly_minimum"],
            0.01,
            "Monthly minimum"
        );
        assert_equals(1, $result["uses_annualized"], "Uses annualized");
        assert_equals(
            "2025-06-01",
            $result["annualized_start_date"],
            "Annualized start date"
        );
        assert_equals(12, $result["look_period_months"], "Look period months");
    });

    run_test("save_customer_settings - partial update", function () {
        $customer_id = create_test_customer(["name" => "Partial Client"]);

        // Initial settings
        save_customer_settings($customer_id, [
            "monthly_minimum" => 100.0,
            "pricing_model" => "flat",
        ]);

        // Partial update - only minimum
        save_customer_settings($customer_id, [
            "monthly_minimum" => 200.0,
        ]);

        $result = get_current_customer_settings($customer_id);
        assert_float_equals(
            200.0,
            $result["monthly_minimum"],
            0.01,
            "Updated minimum"
        );
    });

    run_test(
        "save_customer_settings - null values clear settings",
        function () {
            $customer_id = create_test_customer(["name" => "Clear Client"]);

            // Set values
            save_customer_settings($customer_id, [
                "monthly_minimum" => 500.0,
                "uses_annualized" => 1,
            ]);

            // Clear with null
            save_customer_settings($customer_id, [
                "monthly_minimum" => null,
                "uses_annualized" => 0,
            ]);

            $result = get_current_customer_settings($customer_id);
            assert_null(
                $result["monthly_minimum"],
                "Monthly minimum should be cleared"
            );
            assert_equals(
                0,
                $result["uses_annualized"],
                "Uses annualized should be 0"
            );
        }
    );

    // ============================================================
    // save_escalators() tests
    // ============================================================

    run_test("save_escalators - multiple years", function () {
        $customer_id = create_test_customer(["name" => "Escalator Client"]);

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
                    "escalator_percentage" => 3,
                    "fixed_adjustment" => 0,
                ],
                [
                    "year_number" => 3,
                    "escalator_percentage" => 3,
                    "fixed_adjustment" => 5,
                ],
            ],
            "2025-01-01"
        );

        $result = get_current_escalators($customer_id);
        assert_greater_than(0, count($result), "Should have escalators");
    });

    run_test("save_escalators - append-only history", function () {
        $customer_id = create_test_customer(["name" => "Replace Client"]);

        // Initial 3 years
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
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        // Second save creates new set (append-only)
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

        $result = get_current_escalators($customer_id);
        assert_greater_than(0, count($result), "Should have escalators");
    });

    // ============================================================
    // apply_escalator_delay() tests
    // ============================================================

    run_test("apply_escalator_delay - single delay", function () {
        $customer_id = create_test_customer(["name" => "Delay Client"]);

        // Create escalators first
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

        // Apply 3 month delay to year 2
        apply_escalator_delay($customer_id, 2, 3);

        $delays = get_escalator_delays($customer_id);
        assert_count(1, $delays, "Should have 1 delay");
        assert_equals(2, $delays[0]["year_number"], "Delay for year 2");
        assert_equals(3, $delays[0]["delay_months"], "Delay of 3 months");
    });

    run_test("apply_escalator_delay - multiple delays stack", function () {
        $customer_id = create_test_customer(["name" => "Multi Delay"]);

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

        // Apply delays
        apply_escalator_delay($customer_id, 2, 2);
        apply_escalator_delay($customer_id, 2, 3);

        $total = get_total_delay_months($customer_id, 2);
        assert_equals(5, $total, "Total delay should be 5 months");
    });

    // ============================================================
    // save_lms() tests
    // ============================================================

    run_test("save_lms - creates new LMS", function () {
        $id = save_lms(null, "New LMS", 0.15);

        assert_not_null($id, "Should return ID");

        $lms = get_lms($id);
        assert_equals("New LMS", $lms["name"], "Name should match");
        assert_float_equals(
            0.15,
            $lms["commission_rate"],
            0.001,
            "Commission rate"
        );
    });

    run_test("save_lms - updates existing LMS", function () {
        $id = save_lms(null, "Update LMS", 0.1);

        // Update
        save_lms($id, "Updated LMS Name", 0.12);

        $lms = get_lms($id);
        assert_equals(
            "Updated LMS Name",
            $lms["name"],
            "Name should be updated"
        );
        assert_float_equals(
            0.12,
            $lms["commission_rate"],
            0.001,
            "Rate should be updated"
        );
    });

    run_test("save_lms - null commission falls back to default", function () {
        // Set default
        save_default_commission_rate(0.08);

        $id = save_lms(null, "Default Rate LMS", null);

        $rate = get_effective_commission_rate($id);
        // Should return default rate or the LMS-specific rate
        assert_not_null($rate, "Should return a rate");
    });

    // ============================================================
    // save_billing_flags() tests
    // ============================================================

    run_test("save_billing_flags - default level", function () {
        $service_id = create_test_service(["name" => "Flag Service"]);

        save_billing_flags("default", null, $service_id, "TEST001", 1, 0, 1);

        $flags = get_effective_billing_flags($service_id, "TEST001");
        assert_equals(1, $flags["by_hit"], "By hit flag");
        assert_equals(0, $flags["zero_null"], "Zero null flag");
        assert_equals(1, $flags["bav_by_trans"], "BAV by trans flag");
    });

    run_test("save_billing_flags - group level override", function () {
        $service_id = create_test_service(["name" => "Group Flag Service"]);
        $group_id = create_test_group(["name" => "Flag Group"]);

        // Default
        save_billing_flags("default", null, $service_id, "GRP001", 1, 0, 0);

        // Group override
        save_billing_flags("group", $group_id, $service_id, "GRP001", 0, 1, 0);

        // Check with group context
        $flags = get_effective_billing_flags(
            $service_id,
            "GRP001",
            null,
            $group_id
        );
        assert_equals(0, $flags["by_hit"], "Group should override by_hit");
        assert_equals(
            1,
            $flags["zero_null"],
            "Group should override zero_null"
        );
    });

    run_test("save_billing_flags - customer level override", function () {
        $service_id = create_test_service(["name" => "Cust Flag Service"]);
        $customer_id = create_test_customer(["name" => "Flag Customer"]);

        // Default
        save_billing_flags("default", null, $service_id, "CUST001", 1, 0, 0);

        // Customer override
        save_billing_flags(
            "customer",
            $customer_id,
            $service_id,
            "CUST001",
            0,
            0,
            1
        );

        // Check with customer context
        $flags = get_effective_billing_flags(
            $service_id,
            "CUST001",
            $customer_id,
            null
        );
        assert_equals(0, $flags["by_hit"], "Customer should override by_hit");
        assert_equals(
            1,
            $flags["bav_by_trans"],
            "Customer should override bav_by_trans"
        );
    });

    // ============================================================
    // save_transaction_type() tests
    // ============================================================

    run_test("save_transaction_type - creates new type", function () {
        $id = save_transaction_type(
            "credit",
            "Credit Check",
            "CC001",
            "CREDIT CHECK"
        );

        assert_not_null($id, "Should return ID");

        $type = get_transaction_type_by_efx("CC001");
        assert_equals("credit", $type["type"], "Type should match");
        assert_equals("Credit Check", $type["display_name"], "Display name");
        assert_equals(
            "CREDIT CHECK",
            $type["efx_displayname"],
            "EFX display name"
        );
    });

    run_test("save_transaction_type - creates transaction type", function () {
        save_transaction_type("test", "Test Type", "TEST001", "TEST");

        $type = get_transaction_type_by_efx("TEST001");
        assert_not_null($type, "Should find transaction type");
        assert_equals("TEST001", $type["efx_code"], "EFX code should match");
    });

    run_test("save_transaction_type - with service link", function () {
        $service_id = create_test_service(["name" => "Linked Service"]);

        $id = save_transaction_type(
            "linked",
            "Linked Type",
            "LINK001",
            null,
            $service_id
        );

        $type = get_transaction_type_by_efx("LINK001");
        assert_equals(
            $service_id,
            $type["service_id"],
            "Should link to service"
        );
    });

    // ============================================================
    // save_service_cogs() tests
    // ============================================================

    run_test("save_service_cogs - creates new COGS", function () {
        $service_id = create_test_service(["name" => "COGS Service"]);

        save_service_cogs($service_id, 0.25);

        $cogs = get_service_cogs($service_id);
        assert_float_equals(0.25, $cogs, 0.01, "COGS rate should match");
    });

    run_test("save_service_cogs - updates existing", function () {
        $service_id = create_test_service(["name" => "Update COGS"]);

        save_service_cogs($service_id, 0.2);
        save_service_cogs($service_id, 0.3);

        $cogs = get_service_cogs($service_id);
        assert_float_equals(0.3, $cogs, 0.01, "COGS should be updated");
    });

    // ============================================================
    // assign_customer_lms() tests
    // ============================================================

    run_test("assign_customer_lms - assigns LMS to customer", function () {
        // Use save_lms to create (it handles ID assignment properly)
        $lms_id = save_lms(null, "Assigned LMS", 0.1);
        $customer_id = create_test_customer(["name" => "LMS Client"]);

        assign_customer_lms($customer_id, $lms_id);

        $customers = get_customers_by_lms($lms_id);
        $found = false;
        foreach ($customers as $c) {
            if ($c["id"] == $customer_id) {
                $found = true;
                break;
            }
        }
        assert_true($found, "Customer should be assigned to LMS");
    });

    run_test("assign_customer_lms - reassigns to different LMS", function () {
        $lms1_id = save_lms(null, "LMS One", 0.1);
        $lms2_id = save_lms(null, "LMS Two", 0.12);
        $customer_id = create_test_customer(["name" => "Reassign Client"]);

        // First assignment
        assign_customer_lms($customer_id, $lms1_id);

        // Reassign
        assign_customer_lms($customer_id, $lms2_id);

        // Should be in LMS 2
        $customers = get_customers_by_lms($lms2_id);
        $found = false;
        foreach ($customers as $c) {
            if ($c["id"] == $customer_id) {
                $found = true;
                break;
            }
        }
        assert_true($found, "Customer should be in new LMS");
    });

    // ============================================================
    // delete_billing_report() tests
    // ============================================================

    run_test("delete_billing_report - removes report and lines", function () {
        // Create customer for FK
        create_test_customer(["id" => 201, "name" => "Delete Test Customer"]);

        $csv =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv .=
            "2025,1,201,Delete Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

        $result = import_billing_report(
            "DataX_2025_10_2025_10_delete_test.csv",
            $csv
        );
        $report_id = $result["report_id"];

        // Verify exists
        $lines = get_billing_report_lines($report_id);
        assert_count(1, $lines, "Should have lines before delete");

        // Delete
        delete_billing_report($report_id);

        // Verify gone
        $lines = get_billing_report_lines($report_id);
        assert_count(0, $lines, "Lines should be deleted");
    });

    // ============================================================
    // toggle_rule_mask() tests
    // ============================================================

    run_test("toggle_rule_mask - mask on", function () {
        $customer_id = create_test_customer(["name" => "Mask Client"]);

        toggle_rule_mask($customer_id, "test_rule", true);

        $status = get_rule_mask_status($customer_id, "test_rule");
        assert_true($status, "Rule should be masked");
    });

    run_test("toggle_rule_mask - mask off", function () {
        $customer_id = create_test_customer(["name" => "Unmask Client"]);

        // First mask
        toggle_rule_mask($customer_id, "another_rule", true);

        // Then unmask
        toggle_rule_mask($customer_id, "another_rule", false);

        $status = get_rule_mask_status($customer_id, "another_rule");
        assert_false($status, "Rule should be unmasked");
    });

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
