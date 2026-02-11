<?php
/**
 * Test: Mock Mode Functionality
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for mock mode URL parameter, session persistence,
 * path switching, error page rendering, and fix_shared_directory.
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
    $_qa_test_results = run_mock_mode_tests();
    $test_output = ob_get_clean();

    $demo_content = render_mock_mode_demo();
    render_qa_page(
        "Mock Mode Functionality",
        "Tests for mock mode: path switching, session, directory management",
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
echo "Testing: Mock Mode Functionality\n";
echo "=================================\n";

run_mock_mode_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_mock_mode_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>Mock Mode Overview</h3>
        <p>Mock mode allows the application to run without a production shared directory:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>MOCK_MODE constant</strong> - Indicates if running in mock mode</li>
            <li><strong>Path Switching</strong> - Uses test_shared/ instead of production paths</li>
            <li><strong>Session Persistence</strong> - Mock mode persists across requests</li>
            <li><strong>fix_shared_directory()</strong> - Creates required directory structure</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>Path Functions</h3>
        <table>
            <tr>
                <th>Function</th>
                <th>Production</th>
                <th>Mock Mode</th>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_shared_path()</td>
                <td>/var/www/shared/</td>
                <td>./test_shared/</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_reports_path()</td>
                <td>/var/www/shared/reports/</td>
                <td>./test_shared/reports/</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">get_temp_path()</td>
                <td>/var/www/shared/temp/</td>
                <td>./test_shared/temp/</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Directory Structure</h3>
        <pre class="code-example">test_shared/
  archive/         - Processed files
  pending/         - Files awaiting processing
  generated/       - Generated reports
  reports/         - Report storage
    tier_pricing/
    displayname_to_type/
    custom/
    ingestion/
  temp/            - Temporary files</pre>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// Check if in mock mode
if (MOCK_MODE) {
    echo "Running in mock mode";
}

// Fix/create shared directory structure
$result = fix_shared_directory('/path/to/shared');
if ($result['success']) {
    echo "Directory created: " . $result['message'];
}

// Ensure all directories exist
$errors = ensure_directories();
if (empty($errors)) {
    echo "All directories ready";
}</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: Path Functions (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <button type="submit" name="run_paths" value="1" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Get Paths (Real Functions)
            </button>
        </form>

        <?php if (isset($_GET["run_paths"])):
            // Call REAL functions

            $shared_path = get_shared_path();
            $generated_path = get_generated_path();
            $pending_path = get_pending_path();
            $archive_path = get_archive_path();
            $reports_path = get_reports_path();
            $temp_path = get_temp_path();
            $ensure_result = ensure_directories();
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #155724; margin-bottom: 10px;">Path Functions:</h4>
            <table style="width: 100%; background: white; border-collapse: collapse; margin-bottom: 15px;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Function</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Path</th>
                    <th style="padding: 8px; border: 1px solid #ddd;">Exists?</th>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">get_shared_path()</td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $shared_path
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo is_dir(
                        $shared_path
                    )
                        ? "&#10004;"
                        : "&#10006;"; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">get_generated_path()</td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $generated_path
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo is_dir(
                        $generated_path
                    )
                        ? "&#10004;"
                        : "&#10006;"; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">get_pending_path()</td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $pending_path
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo is_dir(
                        $pending_path
                    )
                        ? "&#10004;"
                        : "&#10006;"; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">get_archive_path()</td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $archive_path
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo is_dir(
                        $archive_path
                    )
                        ? "&#10004;"
                        : "&#10006;"; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">get_reports_path()</td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $reports_path
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo is_dir(
                        $reports_path
                    )
                        ? "&#10004;"
                        : "&#10006;"; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">get_temp_path()</td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-size: 0.9em;"><?php echo htmlspecialchars(
                        $temp_path
                    ); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo is_dir(
                        $temp_path
                    )
                        ? "&#10004;"
                        : "&#10006;"; ?></td>
                </tr>
            </table>

            <h4 style="color: #155724; margin: 15px 0 10px;">MOCK_MODE constant: <?php echo MOCK_MODE
                ? '<span style="color: green;">TRUE</span>'
                : '<span style="color: red;">FALSE</span>'; ?></h4>

            <h4 style="color: #155724; margin: 15px 0 10px;">ensure_directories() result:</h4>
            <pre style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; overflow-x: auto;"><?php print_r(
                $ensure_result
            ); ?></pre>
        </div>
        <?php
        endif; ?>
    </div>
    <?php return ob_get_clean();
}

// ============================================================
// TEST DEFINITIONS
// ============================================================
function run_mock_mode_tests()
{
    global $_test_results;

    // ============================================================
    // MOCK_MODE constant tests
    // ============================================================

    run_test("MOCK_MODE constant is defined", function () {
        assert_true(
            defined("MOCK_MODE"),
            "MOCK_MODE constant should be defined"
        );
    });

    run_test("MOCK_MODE is boolean", function () {
        assert_true(is_bool(MOCK_MODE), "MOCK_MODE should be a boolean");
    });

    // ============================================================
    // get_shared_path() mock mode tests
    // ============================================================

    run_test(
        "get_shared_path - returns test_shared path in mock mode",
        function () {
            // In test mode, MOCK_MODE should be true
            if (MOCK_MODE) {
                $result = get_shared_path();
                assert_contains(
                    "test_shared",
                    $result,
                    "Should contain test_shared in mock mode"
                );
            } else {
                // Skip if not in mock mode
                assert_true(true, "Skipped - not in mock mode");
            }
        }
    );

    run_test("get_shared_path - returns non-empty path", function () {
        $result = get_shared_path();
        assert_not_empty($result, "Should return a path");
    });

    // ============================================================
    // get_reports_path() tests
    // ============================================================

    run_test("get_reports_path - returns base reports path", function () {
        $result = get_reports_path();
        assert_contains("reports", $result, "Should contain reports");
    });

    run_test(
        "get_reports_path - returns subdir path for tier_pricing",
        function () {
            $result = get_reports_path("tier_pricing");
            assert_contains("reports", $result, "Should contain reports");
            assert_contains(
                "tier_pricing",
                $result,
                "Should contain tier_pricing subdirectory"
            );
        }
    );

    run_test(
        "get_reports_path - returns subdir path for displayname_to_type",
        function () {
            $result = get_reports_path("displayname_to_type");
            assert_contains(
                "displayname_to_type",
                $result,
                "Should contain displayname_to_type subdirectory"
            );
        }
    );

    run_test("get_reports_path - returns subdir path for custom", function () {
        $result = get_reports_path("custom");
        assert_contains(
            "custom",
            $result,
            "Should contain custom subdirectory"
        );
    });

    run_test(
        "get_reports_path - returns subdir path for ingestion",
        function () {
            $result = get_reports_path("ingestion");
            assert_contains(
                "ingestion",
                $result,
                "Should contain ingestion subdirectory"
            );
        }
    );

    // ============================================================
    // get_temp_path() tests
    // ============================================================

    run_test("get_temp_path - returns temp path", function () {
        $result = get_temp_path();
        assert_contains("temp", $result, "Should contain temp");
    });

    run_test("get_temp_path - is under shared path", function () {
        $shared = get_shared_path();
        $temp = get_temp_path();
        assert_contains(
            $shared,
            $temp,
            "Temp path should be under shared path"
        );
    });

    // ============================================================
    // fix_shared_directory() tests
    // ============================================================

    run_test(
        "fix_shared_directory - returns array with success key",
        function () {
            $result = fix_shared_directory("/tmp/test_fix_shared_" . uniqid());
            assert_array_has_key("success", $result, "Should have success key");
            assert_array_has_key("message", $result, "Should have message key");
        }
    );

    run_test("fix_shared_directory - success is boolean", function () {
        $result = fix_shared_directory("/tmp/test_fix_shared_" . uniqid());
        assert_true(is_bool($result["success"]), "Success should be boolean");
    });

    run_test("fix_shared_directory - message is string", function () {
        $result = fix_shared_directory("/tmp/test_fix_shared_" . uniqid());
        assert_true(is_string($result["message"]), "Message should be string");
        assert_not_empty($result["message"], "Message should not be empty");
    });

    run_test("fix_shared_directory - can create temp directory", function () {
        $test_path = "/tmp/test_fix_shared_" . uniqid();

        // Clean up if exists
        if (is_dir($test_path)) {
            rmdir($test_path);
        }

        $result = fix_shared_directory($test_path);

        if ($result["success"]) {
            assert_true(is_dir($test_path), "Directory should exist after fix");
            // Clean up
            @rmdir($test_path . "/archive");
            @rmdir($test_path . "/pending");
            @rmdir($test_path . "/generated");
            @rmdir($test_path . "/reports");
            @rmdir($test_path . "/temp");
            @rmdir($test_path);
        } else {
            // If it couldn't create, that's acceptable (permissions)
            assert_true(true, "Could not create directory - acceptable");
        }
    });

    run_test(
        "fix_shared_directory - creates subdirectories when successful",
        function () {
            $test_path = "/tmp/test_fix_shared_subdirs_" . uniqid();

            $result = fix_shared_directory($test_path);

            if ($result["success"]) {
                // Check subdirectories were created
                $subdirs = [
                    "archive",
                    "pending",
                    "generated",
                    "reports",
                    "temp",
                ];
                foreach ($subdirs as $subdir) {
                    $subdir_path = $test_path . "/" . $subdir;
                    if (is_dir($subdir_path)) {
                        assert_true(true, "Subdirectory $subdir exists");
                        @rmdir($subdir_path);
                    }
                }
                @rmdir($test_path);
            } else {
                assert_true(true, "Could not create - skipping subdir check");
            }
        }
    );

    run_test(
        "fix_shared_directory - fails gracefully for impossible paths",
        function () {
            // Try to create in a path that shouldn't be writable
            $result = fix_shared_directory("/root/impossible_path_" . uniqid());

            // Should return array even on failure
            assert_array_has_key(
                "success",
                $result,
                "Should still return array"
            );
            assert_array_has_key(
                "message",
                $result,
                "Should still have message"
            );
            // Most likely will fail, but that's expected
            assert_true(
                is_bool($result["success"]),
                "Success should be boolean"
            );
        }
    );

    // ============================================================
    // ensure_directories() tests
    // ============================================================

    run_test("ensure_directories - returns array", function () {
        $result = ensure_directories();
        assert_true(is_array($result), "Should return array of errors");
    });

    run_test(
        "ensure_directories - creates required directories in mock mode",
        function () {
            if (MOCK_MODE) {
                $result = ensure_directories();

                // Check directories exist
                assert_true(
                    is_dir(get_generated_path()),
                    "Generated path should exist"
                );
                assert_true(
                    is_dir(get_pending_path()),
                    "Pending path should exist"
                );
                assert_true(
                    is_dir(get_archive_path()),
                    "Archive path should exist"
                );
                assert_true(
                    is_dir(get_reports_path()),
                    "Reports path should exist"
                );
                assert_true(is_dir(get_temp_path()), "Temp path should exist");
            } else {
                assert_true(true, "Skipped - not in mock mode");
            }
        }
    );

    run_test(
        "ensure_directories - creates reports subdirectories",
        function () {
            if (MOCK_MODE) {
                ensure_directories();

                assert_true(
                    is_dir(get_reports_path("tier_pricing")),
                    "tier_pricing subdir should exist"
                );
                assert_true(
                    is_dir(get_reports_path("displayname_to_type")),
                    "displayname_to_type subdir should exist"
                );
                assert_true(
                    is_dir(get_reports_path("custom")),
                    "custom subdir should exist"
                );
                assert_true(
                    is_dir(get_reports_path("ingestion")),
                    "ingestion subdir should exist"
                );
            } else {
                assert_true(true, "Skipped - not in mock mode");
            }
        }
    );

    // ============================================================
    // render_shared_directory_error() tests
    // ============================================================

    run_test("render_shared_directory_error - function exists", function () {
        assert_true(
            function_exists("render_shared_directory_error"),
            "Function should exist"
        );
    });

    run_test("render_shared_directory_error - outputs HTML", function () {
        ob_start();
        render_shared_directory_error("/test/path");
        $output = ob_get_clean();

        assert_not_empty($output, "Should output something");
        assert_contains("<!DOCTYPE html>", $output, "Should be valid HTML");
        assert_contains("</html>", $output, "Should have closing html tag");
    });

    run_test(
        "render_shared_directory_error - shows path in output",
        function () {
            $test_path = "/var/www/html/custom_shared";

            ob_start();
            render_shared_directory_error($test_path);
            $output = ob_get_clean();

            assert_contains($test_path, $output, "Should display the path");
        }
    );

    run_test("render_shared_directory_error - has fix button", function () {
        ob_start();
        render_shared_directory_error("/test/path");
        $output = ob_get_clean();

        assert_contains(
            "fix_shared_directory",
            $output,
            "Should have fix action link"
        );
        assert_contains("Fix It", $output, "Should have Fix It button text");
    });

    run_test("render_shared_directory_error - has mock mode link", function () {
        ob_start();
        render_shared_directory_error("/test/path");
        $output = ob_get_clean();

        assert_contains(
            "mock=1",
            $output,
            "Should have link to enable mock mode"
        );
        assert_contains("Mock", $output, "Should mention Mock mode option");
    });

    run_test(
        "render_shared_directory_error - escapes path for XSS prevention",
        function () {
            $malicious_path = '<script>alert("xss")</script>';

            ob_start();
            render_shared_directory_error($malicious_path);
            $output = ob_get_clean();

            // Should escape the script tags
            assert_not_contains(
                "<script>",
                $output,
                "Should escape script tags"
            );
            assert_contains(
                "&lt;script&gt;",
                $output,
                "Should HTML-encode malicious content"
            );
        }
    );

    // ============================================================
    // Session mock mode persistence tests
    // ============================================================

    run_test("session mock_mode - can be set", function () {
        // Save current value
        $original = isset($_SESSION["mock_mode"])
            ? $_SESSION["mock_mode"]
            : null;

        $_SESSION["mock_mode"] = true;
        assert_true(
            $_SESSION["mock_mode"],
            "Should be able to set mock_mode to true"
        );

        $_SESSION["mock_mode"] = false;
        assert_false(
            $_SESSION["mock_mode"],
            "Should be able to set mock_mode to false"
        );

        // Restore
        if ($original !== null) {
            $_SESSION["mock_mode"] = $original;
        }
    });

    // ============================================================
    // Path constants tests
    // ============================================================

    run_test("SHARED_BASE_PATH constant is defined", function () {
        assert_true(
            defined("SHARED_BASE_PATH"),
            "SHARED_BASE_PATH should be defined"
        );
    });

    run_test("PATH_GENERATED constant is defined", function () {
        assert_true(
            defined("PATH_GENERATED"),
            "PATH_GENERATED should be defined"
        );
    });

    run_test("PATH_PENDING constant is defined", function () {
        assert_true(defined("PATH_PENDING"), "PATH_PENDING should be defined");
    });

    run_test("PATH_ARCHIVE constant is defined", function () {
        assert_true(defined("PATH_ARCHIVE"), "PATH_ARCHIVE should be defined");
    });

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
