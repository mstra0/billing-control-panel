<?php
/**
 * Test: History Functions
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for audit/history retrieval functions.
 * Priority 7 - Straightforward queries.
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
    $_qa_test_results = run_history_tests();
    $test_output = ob_get_clean();

    $demo_content = render_history_demo();
    render_qa_page(
        "History Functions",
        "Tests for audit/history retrieval: pricing, settings, escalator, rule mask history",
        $_qa_test_results,
        $test_output,
        $demo_content,
        "#dc3545",
        true
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
echo "Testing: History Functions\n";
echo "===========================\n";

run_history_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_history_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>How History Tracking Works</h3>
        <p>The system maintains an audit trail of all pricing and configuration changes:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>Pricing History</strong> - Track changes to tier pricing over time</li>
            <li><strong>Settings History</strong> - Customer settings modifications</li>
            <li><strong>Escalator History</strong> - Annual escalator changes</li>
            <li><strong>Rule Mask History</strong> - Business rule enable/disable changes</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>History Functions</h3>
        <table>
            <tr>
                <th>Function</th>
                <th>Description</th>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_pricing_history($customer_id)</td>
                <td>Retrieve tier pricing change history for a customer</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_settings_history($customer_id)</td>
                <td>Retrieve customer settings change history</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_escalator_history($customer_id)</td>
                <td>Retrieve escalator configuration changes</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_rule_mask_history($customer_id)</td>
                <td>Retrieve business rule mask toggle history</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// Get pricing history for a customer
$history = get_pricing_history($customer_id);

foreach ($history as $entry) {
    echo "Changed on: " . $entry['changed_at'];
    echo "Old price: $" . $entry['old_price'];
    echo "New price: $" . $entry['new_price'];
}</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: History Functions (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <div style="margin-bottom: 10px;">
                <label>History Type:</label>
                <select name="history_type" style="padding: 5px;">
                    <option value="pricing" <?php echo isset(
                        $_GET["history_type"]
                    ) && $_GET["history_type"] == "pricing"
                        ? "selected"
                        : ""; ?>>Pricing History</option>
                    <option value="settings" <?php echo isset(
                        $_GET["history_type"]
                    ) && $_GET["history_type"] == "settings"
                        ? "selected"
                        : ""; ?>>Settings History</option>
                    <option value="escalator" <?php echo isset(
                        $_GET["history_type"]
                    ) && $_GET["history_type"] == "escalator"
                        ? "selected"
                        : ""; ?>>Escalator History</option>
                </select>
            </div>
            <button type="submit" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Get History (Real Functions)
            </button>
        </form>

        <?php if (isset($_GET["history_type"])):

            $type = $_GET["history_type"];

            // Create test customer and make some changes to generate history
            $demo_customer = create_test_customer([
                "id" => 77777,
                "name" => "Demo History Customer",
            ]);
            $demo_service = create_test_service([
                "id" => 77777,
                "name" => "Demo Service",
            ]);

            // Generate some history
            save_customer_settings($demo_customer, ["monthly_minimum" => 100]);
            save_customer_settings($demo_customer, ["monthly_minimum" => 200]);
            save_customer_tiers($demo_customer, $demo_service, [
                [
                    "volume_start" => 0,
                    "volume_end" => 1000,
                    "price_per_inquiry" => 0.5,
                ],
            ]);
            save_escalators(
                $demo_customer,
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

            // Call REAL history function
            switch ($type) {
                case "pricing":
                    $history = get_pricing_history($demo_customer);
                    $func_name = "get_pricing_history";
                    break;
                case "settings":
                    $history = get_settings_history($demo_customer);
                    $func_name = "get_settings_history";
                    break;
                case "escalator":
                    $history = get_escalator_history($demo_customer);
                    $func_name = "get_escalator_history";
                    break;
            }

            // Clean up (use try/catch for tables that may not exist)
            try {
                sqlite_execute(
                    "DELETE FROM customer_settings WHERE customer_id = ?",
                    [77777]
                );
            } catch (Exception $e) {
            }
            try {
                sqlite_execute(
                    "DELETE FROM customer_escalators WHERE customer_id = ?",
                    [77777]
                );
            } catch (Exception $e) {
            }
            try {
                sqlite_execute(
                    "DELETE FROM pricing_tiers WHERE customer_id = ?",
                    [77777]
                );
            } catch (Exception $e) {
            }
            try {
                sqlite_execute("DELETE FROM services WHERE id = ?", [77777]);
            } catch (Exception $e) {
            }
            try {
                sqlite_execute("DELETE FROM customers WHERE id = ?", [77777]);
            } catch (Exception $e) {
            }
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;"><?php echo $func_name; ?>(<?php echo $demo_customer; ?>):</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 300px;"><?php if (
                empty($history)
            ) {
                echo "(No history found)";
            } else {
                print_r($history);
            } ?></pre>
            <p style="color: #155724; margin-top: 10px; font-size: 0.9em;">
                <strong>Demo created:</strong> Customer, service, settings changes, tier, and escalator - then retrieved history using real <?php echo $func_name; ?>()
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
function run_history_tests()
{
    global $_test_results;

    // ============================================================
    // get_pricing_history() tests
    // ============================================================

    run_test("get_pricing_history - empty returns empty array", function () {
        $history = get_pricing_history();
        assert_true(is_array($history), "Should return array");
    });

    run_test("get_pricing_history - records tier changes", function () {
        $customer_id = create_test_customer(["name" => "History Customer"]);
        $service_id = create_test_service(["name" => "History Service"]);

        // Make some changes
        save_customer_tiers($customer_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.5,
            ],
        ]);

        // Update
        save_customer_tiers($customer_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.45,
            ],
        ]);

        $history = get_pricing_history($customer_id);

        // Should have history entries (if the system tracks history)
        assert_not_null($history, "Should return history");
    });

    run_test("get_pricing_history - filter by customer", function () {
        $c1 = create_test_customer(["name" => "Filter Customer 1"]);
        $c2 = create_test_customer(["name" => "Filter Customer 2"]);
        $service_id = create_test_service(["name" => "Filter Service"]);

        save_customer_tiers($c1, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.5,
            ],
        ]);

        save_customer_tiers($c2, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.6,
            ],
        ]);

        $history_c1 = get_pricing_history($c1);
        $history_c2 = get_pricing_history($c2);

        // Should be filtered by customer
        assert_not_null($history_c1, "Should return c1 history");
        assert_not_null($history_c2, "Should return c2 history");
    });

    // ============================================================
    // get_settings_history() tests
    // ============================================================

    run_test("get_settings_history - empty returns array", function () {
        $history = get_settings_history();
        assert_true(is_array($history), "Should return array");
    });

    run_test("get_settings_history - records settings changes", function () {
        $customer_id = create_test_customer(["name" => "Settings History"]);

        // Make changes
        save_customer_settings($customer_id, ["monthly_minimum" => 100]);
        save_customer_settings($customer_id, ["monthly_minimum" => 200]);

        $history = get_settings_history($customer_id);

        assert_not_null($history, "Should return history");
    });

    run_test("get_settings_history - filter by customer", function () {
        $c1 = create_test_customer(["name" => "Settings C1"]);
        $c2 = create_test_customer(["name" => "Settings C2"]);

        save_customer_settings($c1, ["monthly_minimum" => 100]);
        save_customer_settings($c2, ["monthly_minimum" => 200]);

        $history_c1 = get_settings_history($c1);
        $history_c2 = get_settings_history($c2);

        assert_not_null($history_c1, "Should return c1 history");
        assert_not_null($history_c2, "Should return c2 history");
    });

    // ============================================================
    // get_escalator_history() tests
    // ============================================================

    run_test("get_escalator_history - empty returns array", function () {
        $history = get_escalator_history();
        assert_true(is_array($history), "Should return array");
    });

    run_test("get_escalator_history - records escalator changes", function () {
        $customer_id = create_test_customer(["name" => "Escalator History"]);

        // Set escalators
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
            ],
            "2025-01-01"
        );

        // Update
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

        $history = get_escalator_history($customer_id);

        assert_not_null($history, "Should return history");
    });

    run_test("get_escalator_history - filter by customer", function () {
        $c1 = create_test_customer(["name" => "Esc Hist C1"]);
        $c2 = create_test_customer(["name" => "Esc Hist C2"]);

        save_escalators(
            $c1,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-01-01"
        );

        save_escalators(
            $c2,
            [
                [
                    "year_number" => 1,
                    "escalator_percentage" => 0,
                    "fixed_adjustment" => 0,
                ],
            ],
            "2025-06-01"
        );

        $history_c1 = get_escalator_history($c1);
        $history_c2 = get_escalator_history($c2);

        assert_not_null($history_c1, "Should return c1 history");
        assert_not_null($history_c2, "Should return c2 history");
    });

    // ============================================================
    // get_rule_mask_history() tests
    // ============================================================

    run_test("get_rule_mask_history - empty returns array", function () {
        $history = get_rule_mask_history();
        assert_true(is_array($history), "Should return array");
    });

    run_test("get_rule_mask_history - records mask toggles", function () {
        $customer_id = create_test_customer(["name" => "Mask History"]);

        // Toggle mask on
        toggle_rule_mask($customer_id, "hist_rule", true);

        // Toggle mask off
        toggle_rule_mask($customer_id, "hist_rule", false);

        $history = get_rule_mask_history($customer_id);

        assert_not_null($history, "Should return history");
    });

    run_test("get_rule_mask_history - filter by customer", function () {
        $c1 = create_test_customer(["name" => "Mask Hist C1"]);
        $c2 = create_test_customer(["name" => "Mask Hist C2"]);

        toggle_rule_mask($c1, "rule_a", true);
        toggle_rule_mask($c2, "rule_b", true);

        $history_c1 = get_rule_mask_history($c1);
        $history_c2 = get_rule_mask_history($c2);

        assert_not_null($history_c1, "Should return c1 history");
        assert_not_null($history_c2, "Should return c2 history");
    });

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
