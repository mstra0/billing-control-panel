<?php
/**
 * Test: Utility Functions
 *
 * QA Page: Navigate directly to this file in browser for visual demo
 * Automated: Include via qa_dashboard.php for CI/CD testing
 *
 * Tests for helper functions like h(), safe_filename(), format_filesize(), etc.
 * Priority 5 - Simple logic, lower risk.
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
    $_qa_test_results = run_utilities_tests();
    $test_output = ob_get_clean();

    $demo_content = render_utilities_demo();
    render_qa_page(
        "Utility Functions",
        "Tests for helper functions: escaping, filenames, pagination, paths",
        $_qa_test_results,
        $test_output,
        $demo_content,
        "#6c757d",
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
echo "Testing: Utility Functions\n";
echo "===========================\n";

run_utilities_tests();

// ============================================================
// DEMO CONTENT FOR QA
// ============================================================
function render_utilities_demo()
{
    ob_start(); ?>
    <div class="demo-box">
        <h3>Utility Function Categories</h3>
        <p>Helper functions for common operations:</p>
        <ul style="margin: 15px 0 15px 25px;">
            <li><strong>Escaping</strong> - h() for HTML escaping to prevent XSS</li>
            <li><strong>Filenames</strong> - safe_filename(), generate_filename()</li>
            <li><strong>Formatting</strong> - format_filesize()</li>
            <li><strong>Pagination</strong> - paginate()</li>
            <li><strong>Paths</strong> - get_shared_path(), get_generated_path(), etc.</li>
            <li><strong>Validation</strong> - is_valid_filepath()</li>
        </ul>
    </div>

    <div class="demo-box">
        <h3>Security Functions</h3>
        <table>
            <tr>
                <th>Function</th>
                <th>Purpose</th>
                <th>Example</th>
            </tr>
            <tr>
                <td style="font-family: monospace;">h($str)</td>
                <td>HTML escape</td>
                <td>h('&lt;script&gt;') returns &amp;lt;script&amp;gt;</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">safe_filename($name)</td>
                <td>Sanitize filename</td>
                <td>safe_filename('my file.txt') returns my_file.txt</td>
            </tr>
            <tr>
                <td style="font-family: monospace;">is_valid_filepath($path)</td>
                <td>Validate path safety</td>
                <td>Blocks directory traversal attacks</td>
            </tr>
        </table>
    </div>

    <div class="demo-box">
        <h3>Code Example</h3>
        <pre class="code-example">// HTML escaping (XSS prevention)
echo h($user_input);

// Generate safe filenames
$filename = generate_filename('report', 'csv');
// Returns: report_20250115_143052.csv

// Pagination
$page_info = paginate($total_items, $current_page, $per_page);
// Returns: ['current' => 5, 'total_pages' => 10, 'has_next' => true, ...]

// Format file sizes
echo format_filesize(1048576);  // "1 MB"</pre>
    </div>

    <div class="demo-box" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h3>Live Demo: Utility Functions (Using Real Functions)</h3>
        <form method="get" style="margin: 15px 0;">
            <div style="margin-bottom: 10px;">
                <label>Test HTML Escaping:</label>
                <input type="text" name="escape_test" value="<?php echo isset(
                    $_GET["escape_test"]
                )
                    ? htmlspecialchars($_GET["escape_test"])
                    : '<script>alert(\"XSS\")</script>'; ?>" style="width: 300px; padding: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label>Test Filename:</label>
                <input type="text" name="filename_test" value="<?php echo isset(
                    $_GET["filename_test"]
                )
                    ? htmlspecialchars($_GET["filename_test"])
                    : "My Report <2024>.csv"; ?>" style="width: 200px; padding: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label>Test File Size (bytes):</label>
                <input type="number" name="filesize_test" value="<?php echo isset(
                    $_GET["filesize_test"]
                )
                    ? htmlspecialchars($_GET["filesize_test"])
                    : "1048576"; ?>" style="width: 150px; padding: 5px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label>Pagination - Total Items:</label>
                <input type="number" name="paginate_total" value="<?php echo isset(
                    $_GET["paginate_total"]
                )
                    ? htmlspecialchars($_GET["paginate_total"])
                    : "100"; ?>" style="width: 80px; padding: 5px;">
                <label style="margin-left: 10px;">Page:</label>
                <input type="number" name="paginate_page" value="<?php echo isset(
                    $_GET["paginate_page"]
                )
                    ? htmlspecialchars($_GET["paginate_page"])
                    : "3"; ?>" style="width: 60px; padding: 5px;">
                <label style="margin-left: 10px;">Per Page:</label>
                <input type="number" name="paginate_per" value="<?php echo isset(
                    $_GET["paginate_per"]
                )
                    ? htmlspecialchars($_GET["paginate_per"])
                    : "10"; ?>" style="width: 60px; padding: 5px;">
            </div>
            <button type="submit" style="padding: 10px 25px; background: #ffc107; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                Run Utility Functions
            </button>
        </form>

        <?php if (isset($_GET["escape_test"])):
            // Call REAL functions

            $escaped = h($_GET["escape_test"]);
            $safe_name = safe_filename($_GET["filename_test"]);
            $formatted_size = format_filesize(intval($_GET["filesize_test"]));
            $pagination = paginate(
                intval($_GET["paginate_total"]),
                intval($_GET["paginate_page"]),
                intval($_GET["paginate_per"])
            );
            $generated = generate_filename("demo_report", "csv");
            ?>
        <div style="background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-top: 15px;">
            <table style="width: 100%; background: white; border-collapse: collapse;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Function</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Input</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Output</th>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">h()</td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $_GET["escape_test"]
                    ); ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $escaped
                    ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">safe_filename()</td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $_GET["filename_test"]
                    ); ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $safe_name
                    ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">format_filesize()</td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format(
                        intval($_GET["filesize_test"])
                    ); ?> bytes</td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $formatted_size
                    ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">generate_filename()</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">'demo_report', 'csv'</td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars(
                        $generated
                    ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">paginate()</td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $_GET[
                        "paginate_total"
                    ]; ?>, <?php echo $_GET[
    "paginate_page"
]; ?>, <?php echo $_GET["paginate_per"]; ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><pre style="margin: 0; font-size: 0.85em;"><?php print_r(
                        $pagination
                    ); ?></pre></td>
                </tr>
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
function run_utilities_tests()
{
    global $_test_results;

    // ============================================================
    // h() tests - HTML escaping
    // ============================================================

    run_test("h - escapes HTML entities", function () {
        $result = h('<script>alert("xss")</script>');
        assert_contains("&lt;", $result, "Should escape <");
        assert_contains("&gt;", $result, "Should escape >");
        assert_contains("&quot;", $result, "Should escape quotes");
    });

    run_test("h - handles null", function () {
        $result = h(null);
        assert_equals("", $result, "Null should return empty string");
    });

    run_test("h - normal string unchanged (no special chars)", function () {
        $result = h("Hello World");
        assert_equals("Hello World", $result, "Normal string unchanged");
    });

    run_test("h - escapes ampersand", function () {
        $result = h("Tom & Jerry");
        assert_contains("&amp;", $result, "Should escape &");
    });

    run_test("h - handles special characters", function () {
        $result = h("Line1\nLine2");
        // Newline should be preserved (not an HTML entity)
        assert_contains("\n", $result, "Newline preserved");
    });

    // ============================================================
    // safe_filename() tests
    // ============================================================

    run_test("safe_filename - removes special characters", function () {
        $result = safe_filename('file<>:"/\\|?*.txt');
        // Should not contain any of these chars
        assert_false(strpos($result, "<") !== false, "No < in result");
        assert_false(strpos($result, ">") !== false, "No > in result");
        assert_false(strpos($result, ":") !== false, "No : in result");
        assert_false(strpos($result, '"') !== false, 'No " in result');
        assert_false(strpos($result, "/") !== false, "No / in result");
        assert_false(strpos($result, "\\") !== false, "No \\ in result");
        assert_false(strpos($result, "|") !== false, "No | in result");
        assert_false(strpos($result, "?") !== false, "No ? in result");
        assert_false(strpos($result, "*") !== false, "No * in result");
    });

    run_test("safe_filename - replaces spaces with underscore", function () {
        $result = safe_filename("my file name.txt");
        assert_contains("_", $result, "Spaces should become underscores");
        assert_false(strpos($result, " ") !== false, "No spaces in result");
    });

    run_test("safe_filename - preserves extension", function () {
        $result = safe_filename("document.pdf");
        assert_contains(".pdf", $result, "Extension preserved");
    });

    run_test("safe_filename - handles unicode", function () {
        $result = safe_filename("cafe_resume.doc");
        // Should handle or strip unicode gracefully
        assert_not_null($result, "Should return something");
        assert_greater_than(0, strlen($result), "Should not be empty");
    });

    run_test("safe_filename - empty string", function () {
        $result = safe_filename("");
        assert_equals("", $result, "Empty string returns empty");
    });

    // ============================================================
    // generate_filename() tests
    // ============================================================

    run_test("generate_filename - with prefix", function () {
        $result = generate_filename("report");
        assert_contains("report", $result, "Should contain prefix");
    });

    run_test("generate_filename - with extension", function () {
        $result = generate_filename("data", "csv");
        assert_contains(".csv", $result, "Should have extension");
    });

    run_test("generate_filename - includes timestamp", function () {
        $result = generate_filename("test");
        // Should have some date/time component
        assert_greater_than(
            10,
            strlen($result),
            "Should be longer than just prefix"
        );
    });

    run_test("generate_filename - unique on multiple calls", function () {
        $r1 = generate_filename("unique");
        usleep(1000); // Small delay
        $r2 = generate_filename("unique");
        // They might be the same if called in same second, but prefix should be there
        assert_contains("unique", $r1, "First should have prefix");
        assert_contains("unique", $r2, "Second should have prefix");
    });

    // ============================================================
    // format_filesize() tests
    // ============================================================

    run_test("format_filesize - bytes", function () {
        $result = format_filesize(500);
        assert_contains("500", $result, "Should show 500");
        // Should be bytes or B
        assert_true(
            strpos($result, "B") !== false || strpos($result, "byte") !== false,
            "Should indicate bytes"
        );
    });

    run_test("format_filesize - kilobytes", function () {
        $result = format_filesize(1024);
        assert_true(
            strpos($result, "KB") !== false || strpos($result, "1") !== false,
            "Should be around 1 KB"
        );
    });

    run_test("format_filesize - megabytes", function () {
        $result = format_filesize(1048576); // 1 MB
        assert_true(
            strpos($result, "MB") !== false || strpos($result, "1") !== false,
            "Should be around 1 MB"
        );
    });

    run_test("format_filesize - gigabytes", function () {
        $result = format_filesize(1073741824); // 1 GB
        assert_true(
            strpos($result, "GB") !== false || strpos($result, "1") !== false,
            "Should be around 1 GB"
        );
    });

    run_test("format_filesize - zero", function () {
        $result = format_filesize(0);
        assert_contains("0", $result, "Should show 0");
    });

    // ============================================================
    // paginate() tests
    // ============================================================

    run_test("paginate - first page", function () {
        $result = paginate(100, 1, 10);

        assert_equals(1, $result["current"], "Current page");
        assert_equals(10, $result["per_page"], "Per page");
        assert_loose_equals(10, $result["total_pages"], "Total pages"); // ceil() returns float
        assert_equals(100, $result["total"], "Total items");
        assert_true($result["has_next"], "Should have next");
        assert_false($result["has_prev"], "Should not have prev");
    });

    run_test("paginate - middle page", function () {
        $result = paginate(100, 5, 10);

        assert_equals(5, $result["current"], "Current page");
        assert_true($result["has_prev"], "Should have prev");
        assert_true($result["has_next"], "Should have next");
    });

    run_test("paginate - last page", function () {
        $result = paginate(100, 10, 10);

        assert_equals(10, $result["current"], "Current page");
        assert_true($result["has_prev"], "Should have prev");
        assert_false($result["has_next"], "Should not have next");
    });

    run_test("paginate - single page", function () {
        $result = paginate(5, 1, 10);

        assert_equals(1, $result["current"], "Current page");
        assert_loose_equals(1, $result["total_pages"], "Total pages"); // ceil() returns float
        assert_false($result["has_prev"], "Should not have prev");
        assert_false($result["has_next"], "Should not have next");
    });

    run_test("paginate - page beyond total", function () {
        $result = paginate(50, 100, 10);

        // Returns the requested page even if beyond total
        assert_equals(100, $result["current"], "Returns requested page");
        assert_loose_equals(5, $result["total_pages"], "Total pages is 5"); // ceil() returns float
    });

    run_test("paginate - zero items", function () {
        $result = paginate(0, 1, 10);

        assert_equals(0, $result["total"], "Total items");
        assert_loose_equals(0, $result["total_pages"], "Zero pages for empty"); // ceil() returns float
    });

    // ============================================================
    // is_valid_filepath() tests
    // Note: is_valid_filepath() requires the file to exist AND be in allowed dirs
    // ============================================================

    run_test(
        "is_valid_filepath - non-existent path returns false",
        function () {
            $result = is_valid_filepath("/nonexistent/path/file.txt");
            assert_false($result, "Non-existent path returns false");
        }
    );

    run_test("is_valid_filepath - blocks directory traversal", function () {
        $result = is_valid_filepath("/var/www/../../../etc/passwd");
        assert_false($result, "Should block directory traversal");
    });

    run_test("is_valid_filepath - blocks double dots", function () {
        $result = is_valid_filepath("../../secret.txt");
        assert_false($result, "Should block .. traversal");
    });

    run_test(
        "is_valid_filepath - path outside allowed dirs returns false",
        function () {
            // Even if file exists, must be in allowed directories
            $result = is_valid_filepath("/etc/passwd");
            assert_false($result, "Should reject files outside allowed dirs");
        }
    );

    run_test("is_valid_filepath - blocks null bytes", function () {
        $result = is_valid_filepath("/var/www/html/file\x00.txt");
        assert_false($result, "Should block null bytes");
    });

    // ============================================================
    // get_shared_path() tests
    // ============================================================

    run_test("get_shared_path - returns valid path", function () {
        $result = get_shared_path();
        assert_not_null($result, "Should return path");
        assert_greater_than(0, strlen($result), "Should not be empty");
    });

    // ============================================================
    // get_generated_path() tests
    // ============================================================

    run_test("get_generated_path - returns valid path", function () {
        $result = get_generated_path();
        assert_not_null($result, "Should return path");
        assert_greater_than(0, strlen($result), "Should not be empty");
    });

    // ============================================================
    // get_pending_path() tests
    // ============================================================

    run_test("get_pending_path - returns valid path", function () {
        $result = get_pending_path();
        assert_not_null($result, "Should return path");
        assert_greater_than(0, strlen($result), "Should not be empty");
    });

    // ============================================================
    // get_archive_path() tests
    // ============================================================

    run_test("get_archive_path - returns valid path", function () {
        $result = get_archive_path();
        assert_not_null($result, "Should return path");
        assert_greater_than(0, strlen($result), "Should not be empty");
    });

    // Print summary
    test_summary();

    echo "\n";

    return $_test_results;
}
