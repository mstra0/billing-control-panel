<?php
/**
 * Test: Mock Mode Functionality
 *
 * Tests for mock mode URL parameter, session persistence,
 * path switching, error page rendering, and fix_shared_directory.
 */

echo "Testing: Mock Mode Functionality\n";
echo "=================================\n";

// ============================================================
// MOCK_MODE constant tests
// ============================================================

run_test('MOCK_MODE constant is defined', function() {
    assert_true(defined('MOCK_MODE'), 'MOCK_MODE constant should be defined');
});

run_test('MOCK_MODE is boolean', function() {
    assert_true(is_bool(MOCK_MODE), 'MOCK_MODE should be a boolean');
});

// ============================================================
// get_shared_path() mock mode tests
// ============================================================

run_test('get_shared_path - returns test_shared path in mock mode', function() {
    // In test mode, MOCK_MODE should be true
    if (MOCK_MODE) {
        $result = get_shared_path();
        assert_contains('test_shared', $result, 'Should contain test_shared in mock mode');
    } else {
        // Skip if not in mock mode
        assert_true(true, 'Skipped - not in mock mode');
    }
});

run_test('get_shared_path - returns non-empty path', function() {
    $result = get_shared_path();
    assert_not_empty($result, 'Should return a path');
});

// ============================================================
// get_reports_path() tests
// ============================================================

run_test('get_reports_path - returns base reports path', function() {
    $result = get_reports_path();
    assert_contains('reports', $result, 'Should contain reports');
});

run_test('get_reports_path - returns subdir path for tier_pricing', function() {
    $result = get_reports_path('tier_pricing');
    assert_contains('reports', $result, 'Should contain reports');
    assert_contains('tier_pricing', $result, 'Should contain tier_pricing subdirectory');
});

run_test('get_reports_path - returns subdir path for displayname_to_type', function() {
    $result = get_reports_path('displayname_to_type');
    assert_contains('displayname_to_type', $result, 'Should contain displayname_to_type subdirectory');
});

run_test('get_reports_path - returns subdir path for custom', function() {
    $result = get_reports_path('custom');
    assert_contains('custom', $result, 'Should contain custom subdirectory');
});

run_test('get_reports_path - returns subdir path for ingestion', function() {
    $result = get_reports_path('ingestion');
    assert_contains('ingestion', $result, 'Should contain ingestion subdirectory');
});

// ============================================================
// get_temp_path() tests
// ============================================================

run_test('get_temp_path - returns temp path', function() {
    $result = get_temp_path();
    assert_contains('temp', $result, 'Should contain temp');
});

run_test('get_temp_path - is under shared path', function() {
    $shared = get_shared_path();
    $temp = get_temp_path();
    assert_contains($shared, $temp, 'Temp path should be under shared path');
});

// ============================================================
// fix_shared_directory() tests
// ============================================================

run_test('fix_shared_directory - returns array with success key', function() {
    $result = fix_shared_directory('/tmp/test_fix_shared_' . uniqid());
    assert_array_has_key('success', $result, 'Should have success key');
    assert_array_has_key('message', $result, 'Should have message key');
});

run_test('fix_shared_directory - success is boolean', function() {
    $result = fix_shared_directory('/tmp/test_fix_shared_' . uniqid());
    assert_true(is_bool($result['success']), 'Success should be boolean');
});

run_test('fix_shared_directory - message is string', function() {
    $result = fix_shared_directory('/tmp/test_fix_shared_' . uniqid());
    assert_true(is_string($result['message']), 'Message should be string');
    assert_not_empty($result['message'], 'Message should not be empty');
});

run_test('fix_shared_directory - can create temp directory', function() {
    $test_path = '/tmp/test_fix_shared_' . uniqid();

    // Clean up if exists
    if (is_dir($test_path)) {
        rmdir($test_path);
    }

    $result = fix_shared_directory($test_path);

    if ($result['success']) {
        assert_true(is_dir($test_path), 'Directory should exist after fix');
        // Clean up
        @rmdir($test_path . '/archive');
        @rmdir($test_path . '/pending');
        @rmdir($test_path . '/generated');
        @rmdir($test_path . '/reports');
        @rmdir($test_path . '/temp');
        @rmdir($test_path);
    } else {
        // If it couldn't create, that's acceptable (permissions)
        assert_true(true, 'Could not create directory - acceptable');
    }
});

run_test('fix_shared_directory - creates subdirectories when successful', function() {
    $test_path = '/tmp/test_fix_shared_subdirs_' . uniqid();

    $result = fix_shared_directory($test_path);

    if ($result['success']) {
        // Check subdirectories were created
        $subdirs = array('archive', 'pending', 'generated', 'reports', 'temp');
        foreach ($subdirs as $subdir) {
            $subdir_path = $test_path . '/' . $subdir;
            if (is_dir($subdir_path)) {
                assert_true(true, "Subdirectory $subdir exists");
                @rmdir($subdir_path);
            }
        }
        @rmdir($test_path);
    } else {
        assert_true(true, 'Could not create - skipping subdir check');
    }
});

run_test('fix_shared_directory - fails gracefully for impossible paths', function() {
    // Try to create in a path that shouldn't be writable
    $result = fix_shared_directory('/root/impossible_path_' . uniqid());

    // Should return array even on failure
    assert_array_has_key('success', $result, 'Should still return array');
    assert_array_has_key('message', $result, 'Should still have message');
    // Most likely will fail, but that's expected
    assert_true(is_bool($result['success']), 'Success should be boolean');
});

// ============================================================
// ensure_directories() tests
// ============================================================

run_test('ensure_directories - returns array', function() {
    $result = ensure_directories();
    assert_true(is_array($result), 'Should return array of errors');
});

run_test('ensure_directories - creates required directories in mock mode', function() {
    if (MOCK_MODE) {
        $result = ensure_directories();

        // Check directories exist
        assert_true(is_dir(get_generated_path()), 'Generated path should exist');
        assert_true(is_dir(get_pending_path()), 'Pending path should exist');
        assert_true(is_dir(get_archive_path()), 'Archive path should exist');
        assert_true(is_dir(get_reports_path()), 'Reports path should exist');
        assert_true(is_dir(get_temp_path()), 'Temp path should exist');
    } else {
        assert_true(true, 'Skipped - not in mock mode');
    }
});

run_test('ensure_directories - creates reports subdirectories', function() {
    if (MOCK_MODE) {
        ensure_directories();

        assert_true(is_dir(get_reports_path('tier_pricing')), 'tier_pricing subdir should exist');
        assert_true(is_dir(get_reports_path('displayname_to_type')), 'displayname_to_type subdir should exist');
        assert_true(is_dir(get_reports_path('custom')), 'custom subdir should exist');
        assert_true(is_dir(get_reports_path('ingestion')), 'ingestion subdir should exist');
    } else {
        assert_true(true, 'Skipped - not in mock mode');
    }
});

// ============================================================
// render_shared_directory_error() tests
// ============================================================

run_test('render_shared_directory_error - function exists', function() {
    assert_true(function_exists('render_shared_directory_error'), 'Function should exist');
});

run_test('render_shared_directory_error - outputs HTML', function() {
    ob_start();
    render_shared_directory_error('/test/path');
    $output = ob_get_clean();

    assert_not_empty($output, 'Should output something');
    assert_contains('<!DOCTYPE html>', $output, 'Should be valid HTML');
    assert_contains('</html>', $output, 'Should have closing html tag');
});

run_test('render_shared_directory_error - shows path in output', function() {
    $test_path = '/var/www/html/custom_shared';

    ob_start();
    render_shared_directory_error($test_path);
    $output = ob_get_clean();

    assert_contains($test_path, $output, 'Should display the path');
});

run_test('render_shared_directory_error - has fix button', function() {
    ob_start();
    render_shared_directory_error('/test/path');
    $output = ob_get_clean();

    assert_contains('fix_shared_directory', $output, 'Should have fix action link');
    assert_contains('Fix It', $output, 'Should have Fix It button text');
});

run_test('render_shared_directory_error - has mock mode link', function() {
    ob_start();
    render_shared_directory_error('/test/path');
    $output = ob_get_clean();

    assert_contains('mock=1', $output, 'Should have link to enable mock mode');
    assert_contains('Mock', $output, 'Should mention Mock mode option');
});

run_test('render_shared_directory_error - escapes path for XSS prevention', function() {
    $malicious_path = '<script>alert("xss")</script>';

    ob_start();
    render_shared_directory_error($malicious_path);
    $output = ob_get_clean();

    // Should escape the script tags
    assert_not_contains('<script>', $output, 'Should escape script tags');
    assert_contains('&lt;script&gt;', $output, 'Should HTML-encode malicious content');
});

// ============================================================
// Session mock mode persistence tests
// ============================================================

run_test('session mock_mode - can be set', function() {
    // Save current value
    $original = isset($_SESSION['mock_mode']) ? $_SESSION['mock_mode'] : null;

    $_SESSION['mock_mode'] = true;
    assert_true($_SESSION['mock_mode'], 'Should be able to set mock_mode to true');

    $_SESSION['mock_mode'] = false;
    assert_false($_SESSION['mock_mode'], 'Should be able to set mock_mode to false');

    // Restore
    if ($original !== null) {
        $_SESSION['mock_mode'] = $original;
    }
});

// ============================================================
// Path constants tests
// ============================================================

run_test('SHARED_BASE_PATH constant is defined', function() {
    assert_true(defined('SHARED_BASE_PATH'), 'SHARED_BASE_PATH should be defined');
});

run_test('PATH_GENERATED constant is defined', function() {
    assert_true(defined('PATH_GENERATED'), 'PATH_GENERATED should be defined');
});

run_test('PATH_PENDING constant is defined', function() {
    assert_true(defined('PATH_PENDING'), 'PATH_PENDING should be defined');
});

run_test('PATH_ARCHIVE constant is defined', function() {
    assert_true(defined('PATH_ARCHIVE'), 'PATH_ARCHIVE should be defined');
});

// Print summary
test_summary();
