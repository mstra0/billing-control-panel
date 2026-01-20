<?php
/**
 * Test: Pricing Inheritance
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for get_effective_customer_tiers(), get_effective_group_tiers(),
 * and get_effective_billing_flags() - the inheritance resolution functions.
 *
 * These are CRITICAL - they determine what prices customers pay!
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
    $_qa_test_results = run_inheritance_tests();
    $test_output = ob_get_clean();

    $demo_content = render_inheritance_demo();
    render_qa_page(
        "Pricing Inheritance",
        "Tests for tier inheritance: Customer overrides Group overrides Default",
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
echo "Testing: Pricing Inheritance\n";
echo "============================\n";

run_inheritance_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_inheritance_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>How Pricing Inheritance Works</h3>
        <p>Pricing follows a 3-level hierarchy. The most specific level wins:</p>
        <ol style="margin: 15px 0 15px 25px;">
            <li><strong>Customer Level</strong> (highest priority) - Custom pricing for a specific customer</li>
            <li><strong>Group Level</strong> - Pricing for all customers in a discount group</li>
            <li><strong>Default Level</strong> (lowest priority) - Fallback pricing for everyone</li>
        </ol>
    </div>

    <div class="demo-box">
        <h3>Example Scenario</h3>
        <table>
            <tr>
                <th>Level</th>
                <th>Price per Inquiry</th>
                <th>When Used</th>
            </tr>
            <tr>
                <td>Default</td>
                <td style="font-family: monospace;">$1.00</td>
                <td>Any customer without group or custom pricing</td>
            </tr>
            <tr>
                <td>Group: "Premium Partners"</td>
                <td style="font-family: monospace;">$0.80</td>
                <td>Customers in Premium Partners group</td>
            </tr>
            <tr>
                <td>Customer: "Acme Corp"</td>
                <td style="font-family: monospace;">$0.60</td>
                <td>Only Acme Corp (even though they're in Premium Partners)</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// Get effective tiers for a customer (handles inheritance automatically)
$tiers = get_effective_customer_tiers($customer_id, $service_id);

// Result includes source information:
// $tiers[0]['price_per_inquiry'] = 0.60
// $tiers[0]['source'] = 'customer'  // or 'group' or 'default'

// Check where pricing comes from
foreach ($tiers as $tier) {
    echo "Price: $" . $tier['price_per_inquiry'];
    echo " (from " . $tier['source'] . ")";
}</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: Tier Inheritance (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <div style="margin-bottom: 10px;">
                <label>Default Tier 1 Price: $</label>
                <input type="number" name="default_price" step="0.01" value="<?php echo isset(
                    $_GET["default_price"]
                )
                    ? htmlspecialchars($_GET["default_price"])
                    : "1.00"; ?>" style="width: 80px; padding: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label>Group Override Price: $</label>
                <input type="number" name="group_price" step="0.01" value="<?php echo isset(
                    $_GET["group_price"]
                )
                    ? htmlspecialchars($_GET["group_price"])
                    : "0.80"; ?>" style="width: 80px; padding: 5px;">
                <label style="margin-left: 10px;"><input type="checkbox" name="use_group" value="1" <?php echo isset(
                    $_GET["use_group"]
                )
                    ? "checked"
                    : ""; ?>> Enable Group</label>
            </div>
            <div style="margin-bottom: 10px;">
                <label>Customer Override Price: $</label>
                <input type="number" name="customer_price" step="0.01" value="<?php echo isset(
                    $_GET["customer_price"]
                )
                    ? htmlspecialchars($_GET["customer_price"])
                    : "0.60"; ?>" style="width: 80px; padding: 5px;">
                <label style="margin-left: 10px;"><input type="checkbox" name="use_customer" value="1" <?php echo isset(
                    $_GET["use_customer"]
                )
                    ? "checked"
                    : ""; ?>> Enable Customer Override</label>
            </div>
            <button type="submit" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Test Inheritance (Real Functions)
            </button>
        </form>

        <?php if (isset($_GET["default_price"])):
            // Create test entities

            $demo_service = create_test_service([
                "id" => 66666,
                "name" => "Demo Inheritance Service",
            ]);
            $demo_group = create_test_group([
                "id" => 66666,
                "name" => "Demo Group",
            ]);
            $demo_customer = create_test_customer([
                "id" => 66666,
                "name" => "Demo Inheritance Customer",
                "discount_group_id" => isset($_GET["use_group"]) ? 66666 : null,
            ]);

            // Save default tiers
            save_default_tiers($demo_service, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => floatval($_GET["default_price"]),
                ],
            ]);

            // Save group tiers if enabled
            if (isset($_GET["use_group"])) {
                save_group_tiers($demo_group, $demo_service, [
                    [
                        "volume_start" => 0,
                        "volume_end" => null,
                        "price_per_inquiry" => floatval($_GET["group_price"]),
                    ],
                ]);
            }

            // Save customer tiers if enabled
            if (isset($_GET["use_customer"])) {
                save_customer_tiers($demo_customer, $demo_service, [
                    [
                        "volume_start" => 0,
                        "volume_end" => null,
                        "price_per_inquiry" => floatval(
                            $_GET["customer_price"]
                        ),
                    ],
                ]);
            }

            // Call REAL inheritance function
            $effective_tiers = get_effective_customer_tiers(
                $demo_customer,
                $demo_service
            );

            // Clean up
            sqlite_execute("DELETE FROM customer_tiers WHERE customer_id = ?", [
                66666,
            ]);
            sqlite_execute("DELETE FROM group_tiers WHERE group_id = ?", [
                66666,
            ]);
            sqlite_execute("DELETE FROM default_tiers WHERE service_id = ?", [
                66666,
            ]);
            sqlite_execute("DELETE FROM customers WHERE id = ?", [66666]);
            sqlite_execute("DELETE FROM discount_groups WHERE id = ?", [66666]);
            sqlite_execute("DELETE FROM services WHERE id = ?", [66666]);
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;">get_effective_customer_tiers() Result:</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto;"><?php print_r(
                $effective_tiers
            ); ?></pre>
            <p style="color: #155724; margin-top: 10px;">
                <strong>Inheritance chain:</strong> Customer ($<?php echo $_GET[
                    "customer_price"
                ]; ?>) <?php echo isset($_GET["use_customer"])
    ? "&#10004;"
    : "&#10006;"; ?>
                &rarr; Group ($<?php echo $_GET[
                    "group_price"
                ]; ?>) <?php echo isset($_GET["use_group"])
    ? "&#10004;"
    : "&#10006;"; ?>
                &rarr; Default ($<?php echo $_GET["default_price"]; ?>) &#10004;
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
function run_inheritance_tests()
{
    global $_test_results;

    // get_effective_customer_tiers() tests

    run_test("Customer with no tiers falls back to defaults", function () {
        $service_id = create_test_service();
        $customer_id = create_test_customer();

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 1000,
                "price_per_inquiry" => 1.0,
            ],
            [
                "volume_start" => 1001,
                "volume_end" => null,
                "price_per_inquiry" => 0.8,
            ],
        ]);

        $tiers = get_effective_customer_tiers($customer_id, $service_id);

        assert_count(2, $tiers, "Should return 2 tiers");
        assert_float_equals(
            1.0,
            (float) $tiers[0]["price_per_inquiry"],
            0.01,
            'First tier should be $1.00'
        );
        assert_equals(
            "default",
            $tiers[0]["source"],
            "Source should be default"
        );
    });

    run_test("Customer in group inherits group tiers", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();
        $customer_id = create_test_customer(["discount_group_id" => $group_id]);

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 1.0,
            ],
        ]);

        create_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.8,
            ],
        ]);

        $tiers = get_effective_customer_tiers($customer_id, $service_id);

        assert_count(1, $tiers, "Should return 1 tier");
        assert_float_equals(
            0.8,
            (float) $tiers[0]["price_per_inquiry"],
            0.01,
            'Should use group price $0.80'
        );
        assert_equals("group", $tiers[0]["source"], "Source should be group");
    });

    run_test("Customer override takes precedence over group", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();
        $customer_id = create_test_customer(["discount_group_id" => $group_id]);

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 1.0,
            ],
        ]);

        create_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.8,
            ],
        ]);

        create_customer_tiers($customer_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.6,
            ],
        ]);

        $tiers = get_effective_customer_tiers($customer_id, $service_id);

        assert_count(1, $tiers, "Should return 1 tier");
        assert_float_equals(
            0.6,
            (float) $tiers[0]["price_per_inquiry"],
            0.01,
            'Should use customer price $0.60'
        );
        assert_equals(
            "customer",
            $tiers[0]["source"],
            "Source should be customer"
        );
    });

    run_test("Customer without group still inherits defaults", function () {
        $service_id = create_test_service();
        $customer_id = create_test_customer(["discount_group_id" => null]);

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 500,
                "price_per_inquiry" => 0.5,
            ],
            [
                "volume_start" => 501,
                "volume_end" => null,
                "price_per_inquiry" => 0.4,
            ],
        ]);

        $tiers = get_effective_customer_tiers($customer_id, $service_id);

        assert_count(2, $tiers, "Should return 2 tiers");
        assert_equals(
            "default",
            $tiers[0]["source"],
            "Source should be default"
        );
        assert_equals(
            "default",
            $tiers[1]["source"],
            "Source should be default"
        );
    });

    run_test(
        "Returns empty array when no tiers exist at any level",
        function () {
            $service_id = create_test_service();
            $customer_id = create_test_customer();

            $tiers = get_effective_customer_tiers($customer_id, $service_id);

            assert_count(0, $tiers, "Should return empty array");
        }
    );

    run_test("Multiple tiers preserved in inheritance", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();
        $customer_id = create_test_customer(["discount_group_id" => $group_id]);

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 100,
                "price_per_inquiry" => 1.0,
            ],
            [
                "volume_start" => 101,
                "volume_end" => null,
                "price_per_inquiry" => 0.9,
            ],
        ]);

        create_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => 100,
                "price_per_inquiry" => 0.8,
            ],
            [
                "volume_start" => 101,
                "volume_end" => 500,
                "price_per_inquiry" => 0.7,
            ],
            [
                "volume_start" => 501,
                "volume_end" => null,
                "price_per_inquiry" => 0.6,
            ],
        ]);

        $tiers = get_effective_customer_tiers($customer_id, $service_id);

        assert_count(3, $tiers, "Should return all 3 group tiers");
        assert_float_equals(
            0.8,
            (float) $tiers[0]["price_per_inquiry"],
            0.01,
            "First tier"
        );
        assert_float_equals(
            0.7,
            (float) $tiers[1]["price_per_inquiry"],
            0.01,
            "Second tier"
        );
        assert_float_equals(
            0.6,
            (float) $tiers[2]["price_per_inquiry"],
            0.01,
            "Third tier"
        );
    });

    // get_effective_group_tiers() tests

    run_test("Group with no tiers falls back to defaults", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 1.0,
            ],
        ]);

        $tiers = get_effective_group_tiers($group_id, $service_id);

        assert_count(1, $tiers, "Should return 1 tier");
        assert_float_equals(
            1.0,
            (float) $tiers[0]["price_per_inquiry"],
            0.01,
            "Should use default price"
        );
        assert_equals(
            "default",
            $tiers[0]["source"],
            "Source should be default"
        );
    });

    run_test("Group override takes precedence over defaults", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();

        create_default_tiers($service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 1.0,
            ],
        ]);

        create_group_tiers($group_id, $service_id, [
            [
                "volume_start" => 0,
                "volume_end" => null,
                "price_per_inquiry" => 0.75,
            ],
        ]);

        $tiers = get_effective_group_tiers($group_id, $service_id);

        assert_count(1, $tiers, "Should return 1 tier");
        assert_float_equals(
            0.75,
            (float) $tiers[0]["price_per_inquiry"],
            0.01,
            "Should use group price"
        );
        assert_equals("group", $tiers[0]["source"], "Source should be group");
    });

    // get_effective_billing_flags() tests

    run_test("Returns system defaults when no flags configured", function () {
        $service_id = create_test_service();

        $flags = get_effective_billing_flags($service_id, "TEST_CODE");

        assert_equals(1, (int) $flags["by_hit"], "by_hit should default to 1");
        assert_equals(
            0,
            (int) $flags["zero_null"],
            "zero_null should default to 0"
        );
        assert_equals(
            0,
            (int) $flags["bav_by_trans"],
            "bav_by_trans should default to 0"
        );
        assert_equals(
            "system_default",
            $flags["source"],
            "Source should be system_default"
        );
    });

    run_test("Default level flags override system defaults", function () {
        $service_id = create_test_service();

        create_billing_flags(
            "default",
            null,
            $service_id,
            "TEST_CODE",
            0,
            1,
            1
        );

        $flags = get_effective_billing_flags($service_id, "TEST_CODE");

        assert_equals(0, (int) $flags["by_hit"], "by_hit should be 0");
        assert_equals(1, (int) $flags["zero_null"], "zero_null should be 1");
        assert_equals(
            1,
            (int) $flags["bav_by_trans"],
            "bav_by_trans should be 1"
        );
        assert_equals("default", $flags["source"], "Source should be default");
    });

    run_test("Group level flags override default level", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();

        create_billing_flags(
            "default",
            null,
            $service_id,
            "TEST_CODE",
            1,
            0,
            0
        );
        create_billing_flags(
            "group",
            $group_id,
            $service_id,
            "TEST_CODE",
            0,
            1,
            0
        );

        $flags = get_effective_billing_flags(
            $service_id,
            "TEST_CODE",
            null,
            $group_id
        );

        assert_equals(
            0,
            (int) $flags["by_hit"],
            "by_hit should be 0 (group override)"
        );
        assert_equals(
            1,
            (int) $flags["zero_null"],
            "zero_null should be 1 (group override)"
        );
        assert_equals(
            0,
            (int) $flags["bav_by_trans"],
            "bav_by_trans should be 0"
        );
        assert_equals("group", $flags["source"], "Source should be group");
    });

    run_test("Customer level flags override group level", function () {
        $service_id = create_test_service();
        $group_id = create_test_group();
        $customer_id = create_test_customer(["discount_group_id" => $group_id]);

        create_billing_flags(
            "default",
            null,
            $service_id,
            "TEST_CODE",
            1,
            0,
            0
        );
        create_billing_flags(
            "group",
            $group_id,
            $service_id,
            "TEST_CODE",
            0,
            1,
            0
        );
        create_billing_flags(
            "customer",
            $customer_id,
            $service_id,
            "TEST_CODE",
            1,
            1,
            1
        );

        $flags = get_effective_billing_flags(
            $service_id,
            "TEST_CODE",
            $customer_id,
            $group_id
        );

        assert_equals(
            1,
            (int) $flags["by_hit"],
            "by_hit should be 1 (customer override)"
        );
        assert_equals(
            1,
            (int) $flags["zero_null"],
            "zero_null should be 1 (customer override)"
        );
        assert_equals(
            1,
            (int) $flags["bav_by_trans"],
            "bav_by_trans should be 1 (customer override)"
        );
        assert_equals(
            "customer",
            $flags["source"],
            "Source should be customer"
        );
    });

    run_test("Customer without group still gets default flags", function () {
        $service_id = create_test_service();
        $customer_id = create_test_customer();

        create_billing_flags(
            "default",
            null,
            $service_id,
            "TEST_CODE",
            0,
            1,
            1
        );

        $flags = get_effective_billing_flags(
            $service_id,
            "TEST_CODE",
            $customer_id,
            null
        );

        assert_equals(0, (int) $flags["by_hit"], "Should use default flags");
        assert_equals("default", $flags["source"], "Source should be default");
    });

    run_test("Different EFX codes have independent flags", function () {
        $service_id = create_test_service();

        create_billing_flags("default", null, $service_id, "CODE_A", 1, 0, 0);
        create_billing_flags("default", null, $service_id, "CODE_B", 0, 1, 1);

        $flags_a = get_effective_billing_flags($service_id, "CODE_A");
        $flags_b = get_effective_billing_flags($service_id, "CODE_B");

        assert_equals(1, (int) $flags_a["by_hit"], "CODE_A by_hit should be 1");
        assert_equals(0, (int) $flags_b["by_hit"], "CODE_B by_hit should be 0");
    });

    // Commission rate inheritance tests

    run_test("LMS with no rate inherits default commission", function () {
        $lms_id = create_test_lms(["commission_rate" => null]);
        set_default_commission_rate(10.0);

        $rate = get_effective_commission_rate($lms_id);

        assert_float_equals(10.0, $rate, 0.01, "Should inherit default rate");
    });

    run_test("LMS with own rate overrides default", function () {
        $lms_id = create_test_lms(["commission_rate" => 15.0]);
        set_default_commission_rate(10.0);

        $rate = get_effective_commission_rate($lms_id);

        assert_float_equals(15.0, $rate, 0.01, "Should use LMS rate");
    });

    run_test("Default commission rate can be zero", function () {
        $lms_id = create_test_lms(["commission_rate" => null]);
        set_default_commission_rate(0);

        $rate = get_effective_commission_rate($lms_id);

        assert_float_equals(0, $rate, 0.01, "Should return 0");
    });

    echo "\n";

    return $_test_results;
}
