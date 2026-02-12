<?php
/**
 * Test: CRUD Operations
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for save_*, delete_*, and related modification functions.
 * Organized by Configuration entity (matches Configuration menu in Control Panel).
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
    $_test_root = dirname(dirname(__DIR__));
    if (file_exists($_test_root . "/calculator.php")) {
        require_once $_test_root . "/calculator.php";
    } else {
        require_once $_test_root . "/www/billing/calculator.php";
    }

    ob_start();
    $_qa_test_results = run_crud_tests();
    $test_output = ob_get_clean();

    $demo_content = render_crud_demo();
    render_qa_page(
        "CRUD Operations",
        "Entity CRUD tests organized by Configuration menu item",
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
// ENTITY SECTIONS (matches Configuration menu)
// ============================================================
// Each entity maps to a Configuration menu item:
//   Default Pricing  -> save_default_tiers, save_service_cogs, save_transaction_type
//   Groups           -> save_group_tiers
//   Customers        -> save_customer_tiers, save_customer_settings
//   Escalators       -> save_escalators, apply_escalator_delay
//   LMS              -> save_lms, assign_customer_lms
//   Rules            -> toggle_rule_mask, save_billing_flags
//   Billing Reports  -> delete_billing_report

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_crud_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>What is this page?</h3>
        <p>This page tests <strong>Create, Read, Update, Delete</strong> operations for every entity
        in the billing system. Tests are organized to match the
        <strong>Configuration</strong> menu in the Control Panel, so you can quickly verify
        CRUD operations for each specific area.</p>
        <table>
            <tr>
                <th>Configuration Page</th>
                <th>CRUD Functions Tested</th>
                <th>Tests</th>
            </tr>
            <tr>
                <td><a href="../../control_panel.php?action=pricing_defaults" style="color: #28a745; font-weight: bold;">Default Pricing</a></td>
                <td style="font-family: monospace; font-size: 0.85em;">save_default_tiers(), save_service_cogs(), save_transaction_type()</td>
                <td style="text-align: center;">8</td>
            </tr>
            <tr>
                <td><a href="../../control_panel.php?action=pricing_groups" style="color: #28a745; font-weight: bold;">Groups</a></td>
                <td style="font-family: monospace; font-size: 0.85em;">save_group_tiers()</td>
                <td style="text-align: center;">2</td>
            </tr>
            <tr>
                <td><a href="../../control_panel.php?action=pricing_customers" style="color: #28a745; font-weight: bold;">Customers</a></td>
                <td style="font-family: monospace; font-size: 0.85em;">save_customer_tiers(), save_customer_settings()</td>
                <td style="text-align: center;">5</td>
            </tr>
            <tr>
                <td><a href="../../control_panel.php?action=escalators" style="color: #28a745; font-weight: bold;">Escalators</a></td>
                <td style="font-family: monospace; font-size: 0.85em;">save_escalators(), apply_escalator_delay()</td>
                <td style="text-align: center;">4</td>
            </tr>
            <tr>
                <td><a href="../../control_panel.php?action=lms" style="color: #28a745; font-weight: bold;">LMS</a></td>
                <td style="font-family: monospace; font-size: 0.85em;">save_lms(), assign_customer_lms()</td>
                <td style="text-align: center;">5</td>
            </tr>
            <tr>
                <td><a href="../../control_panel.php?action=business_rules" style="color: #28a745; font-weight: bold;">Rules</a></td>
                <td style="font-family: monospace; font-size: 0.85em;">toggle_rule_mask(), save_billing_flags()</td>
                <td style="text-align: center;">5</td>
            </tr>
            <tr>
                <td>Billing Reports</td>
                <td style="font-family: monospace; font-size: 0.85em;">delete_billing_report()</td>
                <td style="text-align: center;">1</td>
            </tr>
            <tr style="background: #f0f0f0; font-weight: bold;">
                <td>Total</td>
                <td></td>
                <td style="text-align: center;"><?php echo 8 +
                    2 +
                    5 +
                    4 +
                    5 +
                    5 +
                    1; ?></td>
            </tr>
        </table>
    </div>

    <?php
    // -------------------------------------------------------
    // Shared demo style
    // -------------------------------------------------------
    $demo_btn =
        "padding: 8px 20px; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;";
    $demo_result_box =
        "background: #1e1e1e; color: #d4d4d4; padding: 12px; border-radius: 5px; overflow-x: auto; font-family: monospace; font-size: 0.85em;";
    $demo_success =
        "background: #d4edda; border: 2px solid #28a745; border-radius: 8px; padding: 15px; margin-top: 12px;";
    $demo_label = "display: inline-block; width: 160px; font-weight: 600;";
    $demo_input =
        "padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; width: 140px;";
    ?>

    <!-- ============ DEFAULT PRICING DEMO ============ -->
    <div class="demo-box" style="border-left: 4px solid #6f42c1;">
        <h3>Default Pricing</h3>
        <p>Append-only tier pricing. Each save creates a new effective set — historical pricing is preserved.</p>
        <form method="get" style="margin: 12px 0;">
            <input type="hidden" name="demo" value="default_tiers">
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Tier 1 price ($):</label>
                <input type="number" name="dt_price1" step="any" value="<?php echo isset(
                    $_GET["dt_price1"]
                )
                    ? htmlspecialchars($_GET["dt_price1"])
                    : "0.50"; ?>" style="<?php echo $demo_input; ?>">
                <span style="color: #888; font-size: 0.85em;">0 – 1,000 volume</span>
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Tier 2 price ($):</label>
                <input type="number" name="dt_price2" step="any" value="<?php echo isset(
                    $_GET["dt_price2"]
                )
                    ? htmlspecialchars($_GET["dt_price2"])
                    : "0.40"; ?>" style="<?php echo $demo_input; ?>">
                <span style="color: #888; font-size: 0.85em;">1,001+ volume</span>
            </div>
            <button type="submit" style="<?php echo $demo_btn; ?> background: #6f42c1;">Save Default Tiers</button>
        </form>
        <?php if (isset($_GET["demo"]) && $_GET["demo"] === "default_tiers"):

            $d_svc = create_test_service(["name" => "Demo Service"]);
            save_default_tiers($d_svc, [
                [
                    "volume_start" => 0,
                    "volume_end" => 1000,
                    "price_per_inquiry" => floatval($_GET["dt_price1"]),
                ],
                [
                    "volume_start" => 1001,
                    "volume_end" => null,
                    "price_per_inquiry" => floatval($_GET["dt_price2"]),
                ],
            ]);
            $d_result = get_current_default_tiers($d_svc);
            sqlite_execute(
                "DELETE FROM pricing_tiers WHERE level = 'default' AND service_id = ?",
                [$d_svc]
            );
            sqlite_execute("DELETE FROM services WHERE id = ?", [$d_svc]);
            ?>
        <div style="<?php echo $demo_success; ?>">
            <strong>save_default_tiers()</strong> &rarr; <strong>get_current_default_tiers()</strong>
            <pre style="<?php echo $demo_result_box; ?>"><?php print_r(
    $d_result
); ?></pre>
        </div>
        <?php
        endif; ?>
    </div>

    <!-- ============ GROUPS DEMO ============ -->
    <div class="demo-box" style="border-left: 4px solid #007bff;">
        <h3>Groups</h3>
        <p>Group tiers override default pricing. Inheritance: Customer &gt; Group &gt; Default.</p>
        <form method="get" style="margin: 12px 0;">
            <input type="hidden" name="demo" value="group_tiers">
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Group price ($):</label>
                <input type="number" name="gt_price" step="any" value="<?php echo isset(
                    $_GET["gt_price"]
                )
                    ? htmlspecialchars($_GET["gt_price"])
                    : "0.80"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <button type="submit" style="<?php echo $demo_btn; ?> background: #007bff;">Save Group Tiers</button>
        </form>
        <?php if (isset($_GET["demo"]) && $_GET["demo"] === "group_tiers"):

            $d_grp = create_test_group(["name" => "Demo Group"]);
            $d_svc = create_test_service(["name" => "Demo Service"]);
            save_default_tiers($d_svc, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 1.0,
                ],
            ]);
            save_group_tiers($d_grp, $d_svc, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => floatval($_GET["gt_price"]),
                ],
            ]);
            $d_result = get_current_group_tiers($d_grp, $d_svc);
            sqlite_execute(
                "DELETE FROM pricing_tiers WHERE level = 'group' AND level_id = ?",
                [$d_grp]
            );
            sqlite_execute(
                "DELETE FROM pricing_tiers WHERE level = 'default' AND service_id = ?",
                [$d_svc]
            );
            sqlite_execute("DELETE FROM discount_groups WHERE id = ?", [
                $d_grp,
            ]);
            sqlite_execute("DELETE FROM services WHERE id = ?", [$d_svc]);
            ?>
        <div style="<?php echo $demo_success; ?>">
            <strong>save_group_tiers()</strong> &rarr; <strong>get_current_group_tiers()</strong>
            <pre style="<?php echo $demo_result_box; ?>"><?php print_r(
    $d_result
); ?></pre>
            <p style="color: #155724; font-size: 0.85em; margin-top: 8px;">Default was $1.00 — group overrides to $<?php echo htmlspecialchars(
                $_GET["gt_price"]
            ); ?></p>
        </div>
        <?php
        endif; ?>
    </div>

    <!-- ============ CUSTOMERS DEMO ============ -->
    <div class="demo-box" style="border-left: 4px solid #28a745;">
        <h3>Customers</h3>
        <p>Customer-level settings: monthly minimums, annualized toggles, billing pause.</p>
        <form method="get" style="margin: 12px 0;">
            <input type="hidden" name="demo" value="customer_settings">
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Monthly Minimum ($):</label>
                <input type="number" name="cs_min" step="any" value="<?php echo isset(
                    $_GET["cs_min"]
                )
                    ? htmlspecialchars($_GET["cs_min"])
                    : "500.00"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Uses Annualized:</label>
                <select name="cs_ann" style="padding: 6px;">
                    <option value="0" <?php echo isset($_GET["cs_ann"]) &&
                    $_GET["cs_ann"] == "1"
                        ? ""
                        : "selected"; ?>>No</option>
                    <option value="1" <?php echo isset($_GET["cs_ann"]) &&
                    $_GET["cs_ann"] == "1"
                        ? "selected"
                        : ""; ?>>Yes</option>
                </select>
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Pause Billing:</label>
                <select name="cs_pause" style="padding: 6px;">
                    <option value="0" <?php echo isset($_GET["cs_pause"]) &&
                    $_GET["cs_pause"] == "1"
                        ? ""
                        : "selected"; ?>>No</option>
                    <option value="1" <?php echo isset($_GET["cs_pause"]) &&
                    $_GET["cs_pause"] == "1"
                        ? "selected"
                        : ""; ?>>Yes</option>
                </select>
            </div>
            <button type="submit" style="<?php echo $demo_btn; ?> background: #28a745;">Save Customer Settings</button>
        </form>
        <?php if (
            isset($_GET["demo"]) &&
            $_GET["demo"] === "customer_settings"
        ):

            $d_cust = create_test_customer(["name" => "Demo Customer"]);
            save_customer_settings($d_cust, [
                "monthly_minimum" => floatval($_GET["cs_min"]),
                "uses_annualized" => intval($_GET["cs_ann"]),
                "billing_paused" => intval($_GET["cs_pause"]),
            ]);
            $d_result = get_current_customer_settings($d_cust);
            sqlite_execute(
                "DELETE FROM customer_settings WHERE customer_id = ?",
                [$d_cust]
            );
            sqlite_execute("DELETE FROM customers WHERE id = ?", [$d_cust]);
            ?>
        <div style="<?php echo $demo_success; ?>">
            <strong>save_customer_settings()</strong> &rarr; <strong>get_current_customer_settings()</strong>
            <pre style="<?php echo $demo_result_box; ?>"><?php print_r(
    $d_result
); ?></pre>
        </div>
        <?php
        endif; ?>
    </div>

    <!-- ============ ESCALATORS DEMO ============ -->
    <div class="demo-box" style="border-left: 4px solid #dc3545;">
        <h3>Escalators</h3>
        <p>Annual price adjustments: percentage and/or fixed dollar. Delays push activation forward.</p>
        <form method="get" style="margin: 12px 0;">
            <input type="hidden" name="demo" value="escalators">
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Base price ($):</label>
                <input type="number" name="esc_base" step="any" value="<?php echo isset(
                    $_GET["esc_base"]
                )
                    ? htmlspecialchars($_GET["esc_base"])
                    : "1.00"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Year 2 percentage (%):</label>
                <input type="number" name="esc_pct" step="any" value="<?php echo isset(
                    $_GET["esc_pct"]
                )
                    ? htmlspecialchars($_GET["esc_pct"])
                    : "5"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Year 2 fixed ($):</label>
                <input type="number" name="esc_fixed" step="any" value="<?php echo isset(
                    $_GET["esc_fixed"]
                )
                    ? htmlspecialchars($_GET["esc_fixed"])
                    : "0"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Delay months:</label>
                <input type="number" name="esc_delay" step="1" min="0" value="<?php echo isset(
                    $_GET["esc_delay"]
                )
                    ? htmlspecialchars($_GET["esc_delay"])
                    : "0"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <button type="submit" style="<?php echo $demo_btn; ?> background: #dc3545;">Save Escalators</button>
        </form>
        <?php if (isset($_GET["demo"]) && $_GET["demo"] === "escalators"):

            $d_cust = create_test_customer([
                "name" => "Demo Escalator Customer",
            ]);
            $esc_start = date("Y-m-d", strtotime("-13 months"));
            save_escalators(
                $d_cust,
                [
                    [
                        "year_number" => 1,
                        "escalator_percentage" => 0,
                        "fixed_adjustment" => 0,
                    ],
                    [
                        "year_number" => 2,
                        "escalator_percentage" => floatval($_GET["esc_pct"]),
                        "fixed_adjustment" => floatval($_GET["esc_fixed"]),
                    ],
                ],
                $esc_start,
                $esc_start
            );
            $delay_months = intval($_GET["esc_delay"]);
            if ($delay_months > 0) {
                apply_escalator_delay($d_cust, 2, $delay_months);
            }
            $d_escalators = get_current_escalators($d_cust);
            $d_price = calculate_escalated_price(
                floatval($_GET["esc_base"]),
                $d_cust,
                date("Y-m-d")
            );
            $base = floatval($_GET["esc_base"]);
            $direction =
                $d_price > $base + 0.001
                    ? "INCREASE"
                    : ($d_price < $base - 0.001
                        ? "DECREASE"
                        : "NO CHANGE");
            $dir_color =
                $direction === "INCREASE"
                    ? "#dc3545"
                    : ($direction === "DECREASE"
                        ? "#28a745"
                        : "#6c757d");
            sqlite_execute(
                "DELETE FROM escalator_delays WHERE customer_id = ?",
                [$d_cust]
            );
            sqlite_execute(
                "DELETE FROM customer_escalators WHERE customer_id = ?",
                [$d_cust]
            );
            sqlite_execute("DELETE FROM customers WHERE id = ?", [$d_cust]);
            ?>
        <div style="<?php echo $demo_success; ?>">
            <strong>save_escalators()</strong> &rarr; <strong>calculate_escalated_price()</strong>
            <div style="margin: 10px 0; padding: 12px; background: white; border-radius: 5px; text-align: center;">
                <span style="font-size: 1.4em;">$<?php echo htmlspecialchars(
                    $_GET["esc_base"]
                ); ?></span>
                <span style="font-size: 1.4em; margin: 0 10px;">&rarr;</span>
                <span style="font-size: 1.6em; font-weight: bold; color: <?php echo $dir_color; ?>;">$<?php echo number_format(
    $d_price,
    4
); ?></span>
                <span style="display: inline-block; padding: 3px 12px; border-radius: 12px; background: <?php echo $dir_color; ?>; color: white; font-size: 0.8em; font-weight: bold; margin-left: 8px;"><?php echo $direction; ?></span>
            </div>
            <pre style="<?php echo $demo_result_box; ?>"><?php print_r(
    $d_escalators
); ?></pre>
            <?php if ($delay_months > 0): ?>
            <p style="color: #856404; font-size: 0.85em; margin-top: 8px;">Delay of <?php echo $delay_months; ?> month(s) applied to year 2.</p>
            <?php endif; ?>
        </div>
        <?php
        endif; ?>
    </div>

    <!-- ============ LMS DEMO ============ -->
    <div class="demo-box" style="border-left: 4px solid #fd7e14;">
        <h3>LMS</h3>
        <p>Lead Management Systems: name, commission rate, and customer assignment.</p>
        <form method="get" style="margin: 12px 0;">
            <input type="hidden" name="demo" value="lms">
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">LMS Name:</label>
                <input type="text" name="lms_name" value="<?php echo isset(
                    $_GET["lms_name"]
                )
                    ? htmlspecialchars($_GET["lms_name"])
                    : "Demo LMS"; ?>" style="<?php echo $demo_input; ?> width: 200px;">
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Commission Rate:</label>
                <input type="number" name="lms_rate" step="any" min="0" max="1" value="<?php echo isset(
                    $_GET["lms_rate"]
                )
                    ? htmlspecialchars($_GET["lms_rate"])
                    : "0.15"; ?>" style="<?php echo $demo_input; ?>">
            </div>
            <button type="submit" style="<?php echo $demo_btn; ?> background: #fd7e14;">Save LMS</button>
        </form>
        <?php if (isset($_GET["demo"]) && $_GET["demo"] === "lms"):

            save_lms(null, $_GET["lms_name"], floatval($_GET["lms_rate"]));
            $d_lms_id = sqlite_last_id();
            $d_cust = create_test_customer(["name" => "Demo LMS Customer"]);
            assign_customer_lms($d_cust, $d_lms_id);
            $d_lms = get_lms($d_lms_id);
            $d_customers = get_customers_by_lms($d_lms_id);
            sqlite_execute("UPDATE customers SET lms_id = NULL WHERE id = ?", [
                $d_cust,
            ]);
            sqlite_execute("DELETE FROM customers WHERE id = ?", [$d_cust]);
            sqlite_execute("DELETE FROM lms WHERE id = ?", [$d_lms_id]);
            ?>
        <div style="<?php echo $demo_success; ?>">
            <strong>save_lms()</strong> &rarr; <strong>assign_customer_lms()</strong> &rarr; <strong>get_lms()</strong>
            <pre style="<?php echo $demo_result_box; ?>"><?php print_r(
    $d_lms
); ?></pre>
            <p style="color: #155724; font-size: 0.85em; margin-top: 8px;">Assigned <?php echo count(
                $d_customers
            ); ?> customer(s) to this LMS.</p>
        </div>
        <?php
        endif; ?>
    </div>

    <!-- ============ RULES DEMO ============ -->
    <div class="demo-box" style="border-left: 4px solid #20c997;">
        <h3>Rules</h3>
        <p>Business rule masking per-customer. Masked rules are excluded from billing calculations.</p>
        <form method="get" style="margin: 12px 0;">
            <input type="hidden" name="demo" value="rules">
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Rule name:</label>
                <input type="text" name="rule_name" value="<?php echo isset(
                    $_GET["rule_name"]
                )
                    ? htmlspecialchars($_GET["rule_name"])
                    : "no_charge_rule"; ?>" style="<?php echo $demo_input; ?> width: 200px;">
            </div>
            <div style="margin-bottom: 8px;">
                <label style="<?php echo $demo_label; ?>">Action:</label>
                <select name="rule_mask" style="padding: 6px;">
                    <option value="1" <?php echo isset($_GET["rule_mask"]) &&
                    $_GET["rule_mask"] == "1"
                        ? "selected"
                        : ""; ?>>Mask (disable rule)</option>
                    <option value="0" <?php echo isset($_GET["rule_mask"]) &&
                    $_GET["rule_mask"] == "0"
                        ? "selected"
                        : ""; ?>>Unmask (enable rule)</option>
                </select>
            </div>
            <button type="submit" style="<?php echo $demo_btn; ?> background: #20c997;">Toggle Rule Mask</button>
        </form>
        <?php if (isset($_GET["demo"]) && $_GET["demo"] === "rules"):

            $d_cust = create_test_customer(["name" => "Demo Rules Customer"]);
            $rule_name = $_GET["rule_name"];
            $mask_on = intval($_GET["rule_mask"]) === 1;
            toggle_rule_mask($d_cust, $rule_name, $mask_on);
            $d_status = get_rule_mask_status($d_cust, $rule_name);
            $status_label = $d_status
                ? "MASKED (disabled)"
                : "ACTIVE (enabled)";
            $status_color = $d_status ? "#dc3545" : "#28a745";
            sqlite_execute(
                "DELETE FROM business_rule_masks WHERE customer_id = ?",
                [$d_cust]
            );
            sqlite_execute("DELETE FROM customers WHERE id = ?", [$d_cust]);
            ?>
        <div style="<?php echo $demo_success; ?>">
            <strong>toggle_rule_mask()</strong> &rarr; <strong>get_rule_mask_status()</strong>
            <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 5px;">
                Rule <code><?php echo htmlspecialchars(
                    $rule_name
                ); ?></code> is:
                <span style="display: inline-block; padding: 4px 14px; border-radius: 12px; background: <?php echo $status_color; ?>; color: white; font-weight: bold; font-size: 0.9em;"><?php echo $status_label; ?></span>
            </div>
        </div>
        <?php
        endif; ?>
    </div>

    <!-- ============ BILLING REPORTS (no interactive demo) ============ -->
    <div class="demo-box" style="border-left: 4px solid #6c757d;">
        <h3>Billing Reports</h3>
        <p>The <code>delete_billing_report()</code> function removes a report and all its line items.
        This is a destructive operation tested automatically above — no interactive demo needed.</p>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// TEST DEFINITIONS (organized by Configuration entity)
// ============================================================
function run_crud_tests()
{
    global $_test_results;

    // ============================================================
    // DEFAULT PRICING
    // Configuration > Default Pricing
    // Functions: save_default_tiers(), save_service_cogs(),
    //            save_transaction_type()
    // ============================================================

    echo "\n--- Default Pricing ---\n";

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
        "save_default_tiers - second save on same day overwrites first",
        function () {
            $service_id = create_test_service(["name" => "ID Verify"]);

            // Initial save: 1 tier
            save_default_tiers($service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => 1000,
                    "price_per_inquiry" => 0.5,
                ],
            ]);

            // Second save on same day: 2 tiers — should overwrite the first
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

            $result = get_current_default_tiers($service_id);
            assert_count(
                2,
                $result,
                "Second save should overwrite first — only 2 tiers"
            );
            assert_float_equals(
                0.6,
                $result[0]["price_per_inquiry"],
                0.01,
                "First tier should be 0.60"
            );
            assert_float_equals(
                0.45,
                $result[1]["price_per_inquiry"],
                0.01,
                "Second tier should be 0.45"
            );
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
        assert_count(1, $result, "Should have 1 current tier");
        assert_float_equals(
            0.5,
            $result[0]["price_per_inquiry"],
            0.01,
            "Current price should still be 0.50, not future 0.75"
        );
    });

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
    // GROUPS
    // Configuration > Groups
    // Functions: save_group_tiers()
    // ============================================================

    echo "\n--- Groups ---\n";

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

    run_test(
        "save_group_tiers - second save on same day overwrites first",
        function () {
            $group_id = create_test_group(["name" => "Temp Group"]);
            $service_id = create_test_service(["name" => "Temp Service"]);

            // First save
            save_group_tiers($group_id, $service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.5,
                ],
            ]);

            // Second save on same day — should overwrite the first
            save_group_tiers($group_id, $service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.35,
                ],
            ]);

            $result = get_current_group_tiers($group_id, $service_id);
            assert_count(
                1,
                $result,
                "Second save should overwrite first — only 1 tier"
            );
            assert_float_equals(
                0.35,
                $result[0]["price_per_inquiry"],
                0.01,
                "Price should be 0.35 from second save"
            );
        }
    );

    // ============================================================
    // CUSTOMERS
    // Configuration > Customers
    // Functions: save_customer_tiers(), save_customer_settings()
    // ============================================================

    echo "\n--- Customers ---\n";

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
        "save_customer_tiers - second save on same day overwrites first",
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

            // Second save on same day — should overwrite the first
            save_customer_tiers($customer_id, $service_id, [
                [
                    "volume_start" => 0,
                    "volume_end" => null,
                    "price_per_inquiry" => 0.35,
                ],
            ]);

            $result = get_current_customer_tiers($customer_id, $service_id);
            assert_count(
                1,
                $result,
                "Second save should overwrite first — only 1 tier"
            );
            assert_float_equals(
                0.35,
                $result[0]["price_per_inquiry"],
                0.01,
                "Price should be 0.35 from second save"
            );
        }
    );

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
    // ESCALATORS
    // Configuration > Escalators
    // Functions: save_escalators(), apply_escalator_delay()
    // ============================================================

    echo "\n--- Escalators ---\n";

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
        assert_count(3, $result, "Should have 3 escalator years");
        assert_float_equals(
            3,
            $result[2]["escalator_percentage"],
            0.01,
            "Year 3 percentage should be 3"
        );
        assert_float_equals(
            5,
            $result[2]["fixed_adjustment"],
            0.01,
            "Year 3 fixed adjustment should be 5"
        );
    });

    run_test(
        "save_escalators - second save on same day overwrites first",
        function () {
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

            // Second save on same day: 2 years — should overwrite the 3-year set
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
            assert_count(
                2,
                $result,
                "Second save should overwrite first — only 2 escalator years"
            );
            assert_float_equals(
                10,
                $result[1]["escalator_percentage"],
                0.01,
                "Year 2 should be 10% from second save"
            );
        }
    );

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
    // LMS
    // Configuration > LMS
    // Functions: save_lms(), assign_customer_lms()
    // ============================================================

    echo "\n--- LMS ---\n";

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
        save_default_commission_rate(0.08);

        save_lms(null, "Default Rate LMS", null);
        $id = sqlite_last_id();

        $lms = get_lms($id);
        assert_null(
            $lms["commission_rate"],
            "LMS record should store null commission"
        );

        $rate = get_effective_commission_rate($id);
        assert_float_equals(
            0.08,
            $rate,
            0.001,
            "Should fall back to default rate 0.08"
        );
    });

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
    // RULES
    // Configuration > Rules
    // Functions: toggle_rule_mask(), save_billing_flags()
    // ============================================================

    echo "\n--- Rules ---\n";

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
    // BILLING REPORTS
    // Functions: delete_billing_report()
    // ============================================================

    echo "\n--- Billing Reports ---\n";

    run_test("delete_billing_report - removes report and lines", function () {
        $customer_id = create_test_customer(["name" => "Delete Test Customer"]);

        $csv =
            "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
        $csv .=
            "2025,1," .
            $customer_id .
            ",Delete Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

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

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
