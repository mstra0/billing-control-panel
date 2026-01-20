<?php
/**
 * Test: Sync Functions
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for data sync functionality including sync_*_from_remote functions,
 * sync status tracking, filesystem status, and environment status.
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
    $_qa_test_results = run_sync_tests();
    $test_output = ob_get_clean();

    $demo_content = render_sync_demo();
    render_qa_page(
        "Sync Functions",
        "Tests for data synchronization: remote sync, status tracking, environment info",
        $_qa_test_results,
        $test_output,
        $demo_content,
        "#20c997",
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
echo "Testing: Sync Functions\n";
echo "========================\n";

run_sync_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_sync_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>Sync Function Categories</h3>
        <p>Functions for synchronizing data from remote sources:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>Entity Sync</strong> - sync_customers_from_remote(), sync_services_from_remote(), etc.</li>
            <li><strong>Bulk Sync</strong> - sync_all_from_remote() for complete refresh</li>
            <li><strong>Status Tracking</strong> - get_sync_status(), get_sync_log()</li>
            <li><strong>Environment</strong> - get_filesystem_status(), get_environment_status()</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>Sync Entities</h3>
        <table>
            <tr>
                <th>Entity</th>
                <th>Function</th>
            </tr>
            <tr>
                <td>Customers</td>
                <td style="font-family: monospace;">sync_customers_from_remote()</td>
            </tr>
            <tr>
                <td>Services</td>
                <td style="font-family: monospace;">sync_services_from_remote()</td>
            </tr>
            <tr>
                <td>Discount Groups</td>
                <td style="font-family: monospace;">sync_discount_groups_from_remote()</td>
            </tr>
            <tr>
                <td>Business Rules</td>
                <td style="font-family: monospace;">sync_business_rules_from_remote()</td>
            </tr>
            <tr>
                <td>All Entities</td>
                <td style="font-family: monospace;">sync_all_from_remote()</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// Sync all entities from remote
$results = sync_all_from_remote();
foreach ($results as $entity => $result) {
    echo "$entity: {$result['total']} records\n";
}

// Check sync status
$status = get_sync_status();
foreach ($status as $entity => $info) {
    echo "{$info['display_name']}: ";
    echo "Last sync: {$info['last_sync']}, ";
    echo "Records: {$info['current_count']}\n";
}

// Get environment info
$env = get_environment_status();
echo "PHP: {$env['php_version']}\n";
echo "SQLite: {$env['sqlite_version']}\n";
echo "Mock Mode: " . ($env['mock_mode'] ? 'Yes' : 'No');</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: Sync & Environment Status (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <button type="submit" name="run_status" value="1" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Get Status (Real Functions)
            </button>
        </form>

        <?php if (isset($_GET["run_status"])):
            // Call REAL functions

            $sync_status = get_sync_status();
            $env_status = get_environment_status();
            $fs_status = get_filesystem_status();
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;">get_environment_status():</h4>
            <table style="width: 100%; background: white; border-collapse: collapse; margin-bottom: 15px;">
                <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">PHP Version</td><td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                    $env_status["php_version"]
                ); ?></td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">SQLite Version</td><td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                    $env_status["sqlite_version"]
                ); ?></td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Mock Mode</td><td style="padding: 8px; border: 1px solid #ddd;"><?php echo $env_status[
                    "mock_mode"
                ]
                    ? "Yes"
                    : "No"; ?></td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Memory Limit</td><td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                    $env_status["memory_limit"]
                ); ?></td></tr>
                <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Remote DB Configured</td><td style="padding: 8px; border: 1px solid #ddd;"><?php echo $env_status[
                    "remote_db_configured"
                ]
                    ? "Yes"
                    : "No"; ?></td></tr>
            </table>

            <h4 style="color: #155724; margin: 15px 0 10px;">get_sync_status():</h4>
            <table style="width: 100%; background: white; border-collapse: collapse; margin-bottom: 15px;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 8px; border: 1px solid #ddd;">Entity</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Count</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Last Sync</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                </tr>
                <?php foreach ($sync_status as $entity => $info): ?>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $info["display_name"]
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $info[
                        "current_count"
                    ]; ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $info[
                        "last_sync"
                    ]
                        ? $info["last_sync"]
                        : "Never"; ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $info[
                        "last_status"
                    ]
                        ? $info["last_status"]
                        : "-"; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h4 style="color: #155724; margin: 15px 0 10px;">get_filesystem_status():</h4>
            <table style="width: 100%; background: white; border-collapse: collapse;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 8px; border: 1px solid #ddd;">Path</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Files</th>
                </tr>
                <?php foreach ($fs_status as $key => $info): ?>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $info["description"]
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; color: <?php echo $info[
                        "status"
                    ] == "ok"
                        ? "#155724"
                        : "#721c24"; ?>;"><?php echo strtoupper(
    $info["status"]
); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $info[
                        "file_count"
                    ]; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
        endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// TEST DEFINITIONS
// ============================================================
function run_sync_tests()
{
    global $_test_results;

    // ============================================================
    // sync_customers_from_remote() tests
    // ============================================================

    run_test("sync_customers_from_remote - returns array", function () {
        $result = sync_customers_from_remote();
        assert_true(is_array($result), "Should return array");
    });

    run_test(
        "sync_customers_from_remote - has required keys in mock mode",
        function () {
            $result = sync_customers_from_remote();
            assert_array_has_key("total", $result, "Should have total key");
            assert_array_has_key(
                "message",
                $result,
                "Should have message key in mock mode"
            );
        }
    );

    run_test("sync_customers_from_remote - logs to sync_log", function () {
        // Clear any existing log entries for customers
        sqlite_execute("DELETE FROM sync_log WHERE entity_type = 'customers'");

        sync_customers_from_remote();

        $log = sqlite_query(
            "SELECT * FROM sync_log WHERE entity_type = 'customers' ORDER BY synced_at DESC LIMIT 1"
        );
        assert_not_empty($log, "Should have log entry");
        assert_equals(
            "customers",
            $log[0]["entity_type"],
            "Entity type should be customers"
        );
        assert_equals("success", $log[0]["status"], "Status should be success");
    });

    // ============================================================
    // sync_services_from_remote() tests
    // ============================================================

    run_test("sync_services_from_remote - returns array", function () {
        $result = sync_services_from_remote();
        assert_true(is_array($result), "Should return array");
    });

    run_test("sync_services_from_remote - logs to sync_log", function () {
        sqlite_execute("DELETE FROM sync_log WHERE entity_type = 'services'");

        sync_services_from_remote();

        $log = sqlite_query(
            "SELECT * FROM sync_log WHERE entity_type = 'services' ORDER BY synced_at DESC LIMIT 1"
        );
        assert_not_empty($log, "Should have log entry");
        assert_equals(
            "services",
            $log[0]["entity_type"],
            "Entity type should be services"
        );
    });

    // ============================================================
    // sync_discount_groups_from_remote() tests
    // ============================================================

    run_test("sync_discount_groups_from_remote - returns array", function () {
        $result = sync_discount_groups_from_remote();
        assert_true(is_array($result), "Should return array");
    });

    run_test(
        "sync_discount_groups_from_remote - logs to sync_log",
        function () {
            sqlite_execute(
                "DELETE FROM sync_log WHERE entity_type = 'discount_groups'"
            );

            sync_discount_groups_from_remote();

            $log = sqlite_query(
                "SELECT * FROM sync_log WHERE entity_type = 'discount_groups' ORDER BY synced_at DESC LIMIT 1"
            );
            assert_not_empty($log, "Should have log entry");
        }
    );

    // ============================================================
    // sync_business_rules_from_remote() tests
    // ============================================================

    run_test("sync_business_rules_from_remote - returns array", function () {
        $result = sync_business_rules_from_remote();
        assert_true(is_array($result), "Should return array");
    });

    run_test("sync_business_rules_from_remote - logs to sync_log", function () {
        sqlite_execute(
            "DELETE FROM sync_log WHERE entity_type = 'business_rules'"
        );

        sync_business_rules_from_remote();

        $log = sqlite_query(
            "SELECT * FROM sync_log WHERE entity_type = 'business_rules' ORDER BY synced_at DESC LIMIT 1"
        );
        assert_not_empty($log, "Should have log entry");
    });

    // ============================================================
    // sync_all_from_remote() tests
    // ============================================================

    run_test(
        "sync_all_from_remote - returns array with all entities",
        function () {
            $result = sync_all_from_remote();
            assert_true(is_array($result), "Should return array");
            assert_array_has_key("customers", $result, "Should have customers");
            assert_array_has_key("services", $result, "Should have services");
            assert_array_has_key(
                "discount_groups",
                $result,
                "Should have discount_groups"
            );
            assert_array_has_key("lms", $result, "Should have lms");
            assert_array_has_key("cogs", $result, "Should have cogs");
            assert_array_has_key(
                "business_rules",
                $result,
                "Should have business_rules"
            );
        }
    );

    // ============================================================
    // get_sync_status() tests
    // ============================================================

    run_test("get_sync_status - returns array", function () {
        $result = get_sync_status();
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_sync_status - has all entities", function () {
        $result = get_sync_status();
        assert_array_has_key("customers", $result, "Should have customers");
        assert_array_has_key("services", $result, "Should have services");
        assert_array_has_key(
            "discount_groups",
            $result,
            "Should have discount_groups"
        );
        assert_array_has_key("lms", $result, "Should have lms");
        assert_array_has_key("cogs", $result, "Should have cogs");
        assert_array_has_key(
            "business_rules",
            $result,
            "Should have business_rules"
        );
    });

    run_test("get_sync_status - entity has required fields", function () {
        $result = get_sync_status();
        $customers = $result["customers"];

        assert_array_has_key("entity", $customers, "Should have entity");
        assert_array_has_key(
            "display_name",
            $customers,
            "Should have display_name"
        );
        assert_array_has_key(
            "current_count",
            $customers,
            "Should have current_count"
        );
        assert_array_has_key("last_sync", $customers, "Should have last_sync");
        assert_array_has_key(
            "last_sync_count",
            $customers,
            "Should have last_sync_count"
        );
        assert_array_has_key(
            "last_status",
            $customers,
            "Should have last_status"
        );
    });

    run_test("get_sync_status - display_name is human readable", function () {
        $result = get_sync_status();

        assert_equals(
            "Customers",
            $result["customers"]["display_name"],
            "Customers display name"
        );
        assert_equals(
            "Services",
            $result["services"]["display_name"],
            "Services display name"
        );
        assert_equals(
            "Discount Groups",
            $result["discount_groups"]["display_name"],
            "Discount groups display name"
        );
    });

    // ============================================================
    // get_sync_log() tests
    // ============================================================

    run_test("get_sync_log - returns array", function () {
        $result = get_sync_log();
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_sync_log - respects limit", function () {
        // Run several syncs to create log entries
        sync_customers_from_remote();
        sync_services_from_remote();
        sync_discount_groups_from_remote();

        $result = get_sync_log(2);
        assert_true(count($result) <= 2, "Should return at most 2 entries");
    });

    run_test("get_sync_log - entries have required fields", function () {
        sync_customers_from_remote();

        $result = get_sync_log(1);
        if (!empty($result)) {
            $entry = $result[0];
            assert_array_has_key("id", $entry, "Should have id");
            assert_array_has_key(
                "entity_type",
                $entry,
                "Should have entity_type"
            );
            assert_array_has_key("synced_at", $entry, "Should have synced_at");
            assert_array_has_key(
                "record_count",
                $entry,
                "Should have record_count"
            );
            assert_array_has_key("status", $entry, "Should have status");
        }
    });

    // ============================================================
    // check_sync_needed() tests
    // ============================================================

    run_test("check_sync_needed - returns array in mock mode", function () {
        $result = check_sync_needed();
        assert_true(is_array($result), "Should return array");
    });

    run_test("check_sync_needed - has status in mock mode", function () {
        if (MOCK_MODE) {
            $result = check_sync_needed();
            assert_array_has_key("status", $result, "Should have status");
            assert_equals(
                "mock",
                $result["status"],
                "Status should be mock in mock mode"
            );
        } else {
            assert_true(true, "Skipped - not in mock mode");
        }
    });

    // ============================================================
    // clear_entity_data() tests
    // ============================================================

    run_test("clear_entity_data - rejects invalid entity", function () {
        $result = clear_entity_data("invalid_table");
        assert_false($result["success"], "Should fail for invalid entity");
        assert_contains(
            "Invalid entity",
            $result["message"],
            "Should have error message"
        );
    });

    run_test(
        "clear_entity_data - returns success for valid entity",
        function () {
            // Insert a test record
            sqlite_execute(
                "INSERT OR IGNORE INTO lms (id, name, status) VALUES (9999, 'Test LMS', 'active')"
            );

            $result = clear_entity_data("lms");
            assert_true($result["success"], "Should succeed for valid entity");
            assert_contains(
                "Cleared",
                $result["message"],
                "Should have cleared message"
            );
        }
    );

    run_test("clear_entity_data - actually clears data", function () {
        // Insert a test record
        sqlite_execute(
            "INSERT INTO lms (id, name, status) VALUES (8888, 'Test LMS 2', 'active')"
        );

        $before = sqlite_query("SELECT COUNT(*) as cnt FROM lms");
        assert_greater_than(
            0,
            $before[0]["cnt"],
            "Should have records before clear"
        );

        clear_entity_data("lms");

        $after = sqlite_query("SELECT COUNT(*) as cnt FROM lms");
        assert_equals(
            0,
            $after[0]["cnt"],
            "Should have no records after clear"
        );
    });

    // ============================================================
    // get_filesystem_status() tests
    // ============================================================

    run_test("get_filesystem_status - returns array", function () {
        $result = get_filesystem_status();
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_filesystem_status - has all path types", function () {
        $result = get_filesystem_status();
        assert_array_has_key("shared", $result, "Should have shared");
        assert_array_has_key("archive", $result, "Should have archive");
        assert_array_has_key("pending", $result, "Should have pending");
        assert_array_has_key("generated", $result, "Should have generated");
        assert_array_has_key("reports", $result, "Should have reports");
        assert_array_has_key("temp", $result, "Should have temp");
    });

    run_test(
        "get_filesystem_status - path entry has required fields",
        function () {
            $result = get_filesystem_status();
            $shared = $result["shared"];

            assert_array_has_key("path", $shared, "Should have path");
            assert_array_has_key(
                "description",
                $shared,
                "Should have description"
            );
            assert_array_has_key("exists", $shared, "Should have exists");
            assert_array_has_key("readable", $shared, "Should have readable");
            assert_array_has_key("writable", $shared, "Should have writable");
            assert_array_has_key(
                "file_count",
                $shared,
                "Should have file_count"
            );
            assert_array_has_key("status", $shared, "Should have status");
        }
    );

    run_test(
        "get_filesystem_status - status is ok/partial/missing",
        function () {
            $result = get_filesystem_status();
            $valid_statuses = ["ok", "partial", "missing"];

            foreach ($result as $key => $fs) {
                assert_true(
                    in_array($fs["status"], $valid_statuses),
                    "Status for $key should be ok/partial/missing, got: " .
                        $fs["status"]
                );
            }
        }
    );

    // ============================================================
    // get_environment_status() tests
    // ============================================================

    run_test("get_environment_status - returns array", function () {
        $result = get_environment_status();
        assert_true(is_array($result), "Should return array");
    });

    run_test("get_environment_status - has required keys", function () {
        $result = get_environment_status();
        assert_array_has_key("mock_mode", $result, "Should have mock_mode");
        assert_array_has_key("php_version", $result, "Should have php_version");
        assert_array_has_key(
            "sqlite_version",
            $result,
            "Should have sqlite_version"
        );
        assert_array_has_key(
            "shared_base_path",
            $result,
            "Should have shared_base_path"
        );
        assert_array_has_key(
            "memory_limit",
            $result,
            "Should have memory_limit"
        );
        assert_array_has_key(
            "remote_db_configured",
            $result,
            "Should have remote_db_configured"
        );
    });

    run_test(
        "get_environment_status - mock_mode matches constant",
        function () {
            $result = get_environment_status();
            assert_equals(
                MOCK_MODE,
                $result["mock_mode"],
                "mock_mode should match MOCK_MODE constant"
            );
        }
    );

    run_test("get_environment_status - php_version is valid", function () {
        $result = get_environment_status();
        assert_equals(
            PHP_VERSION,
            $result["php_version"],
            "php_version should match PHP_VERSION"
        );
    });

    run_test(
        "get_environment_status - sqlite_version is non-empty",
        function () {
            $result = get_environment_status();
            assert_not_empty(
                $result["sqlite_version"],
                "sqlite_version should not be empty"
            );
        }
    );

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
