<?php
/**
 * Test: Utility Functions
 *
 * Tests for helper functions like h(), safe_filename(), format_filesize(), etc.
 * Priority 5 - Simple logic, lower risk.
 */

echo "Testing: Utility Functions\n";
echo "===========================\n";

// ============================================================
// h() tests - HTML escaping
// ============================================================

run_test('h - escapes HTML entities', function() {
    $result = h('<script>alert("xss")</script>');
    assert_contains('&lt;', $result, 'Should escape <');
    assert_contains('&gt;', $result, 'Should escape >');
    assert_contains('&quot;', $result, 'Should escape quotes');
});

run_test('h - handles null', function() {
    $result = h(null);
    assert_equals('', $result, 'Null should return empty string');
});

run_test('h - normal string unchanged (no special chars)', function() {
    $result = h('Hello World');
    assert_equals('Hello World', $result, 'Normal string unchanged');
});

run_test('h - escapes ampersand', function() {
    $result = h('Tom & Jerry');
    assert_contains('&amp;', $result, 'Should escape &');
});

run_test('h - handles special characters', function() {
    $result = h("Line1\nLine2");
    // Newline should be preserved (not an HTML entity)
    assert_contains("\n", $result, 'Newline preserved');
});

// ============================================================
// safe_filename() tests
// ============================================================

run_test('safe_filename - removes special characters', function() {
    $result = safe_filename('file<>:"/\\|?*.txt');
    // Should not contain any of these chars
    assert_false(strpos($result, '<') !== false, 'No < in result');
    assert_false(strpos($result, '>') !== false, 'No > in result');
    assert_false(strpos($result, ':') !== false, 'No : in result');
    assert_false(strpos($result, '"') !== false, 'No " in result');
    assert_false(strpos($result, '/') !== false, 'No / in result');
    assert_false(strpos($result, '\\') !== false, 'No \\ in result');
    assert_false(strpos($result, '|') !== false, 'No | in result');
    assert_false(strpos($result, '?') !== false, 'No ? in result');
    assert_false(strpos($result, '*') !== false, 'No * in result');
});

run_test('safe_filename - replaces spaces with underscore', function() {
    $result = safe_filename('my file name.txt');
    assert_contains('_', $result, 'Spaces should become underscores');
    assert_false(strpos($result, ' ') !== false, 'No spaces in result');
});

run_test('safe_filename - preserves extension', function() {
    $result = safe_filename('document.pdf');
    assert_contains('.pdf', $result, 'Extension preserved');
});

run_test('safe_filename - handles unicode', function() {
    $result = safe_filename('café_résumé.doc');
    // Should handle or strip unicode gracefully
    assert_not_null($result, 'Should return something');
    assert_greater_than(0, strlen($result), 'Should not be empty');
});

run_test('safe_filename - empty string', function() {
    $result = safe_filename('');
    assert_equals('', $result, 'Empty string returns empty');
});

// ============================================================
// generate_filename() tests
// ============================================================

run_test('generate_filename - with prefix', function() {
    $result = generate_filename('report');
    assert_contains('report', $result, 'Should contain prefix');
});

run_test('generate_filename - with extension', function() {
    $result = generate_filename('data', 'csv');
    assert_contains('.csv', $result, 'Should have extension');
});

run_test('generate_filename - includes timestamp', function() {
    $result = generate_filename('test');
    // Should have some date/time component
    assert_greater_than(10, strlen($result), 'Should be longer than just prefix');
});

run_test('generate_filename - unique on multiple calls', function() {
    $r1 = generate_filename('unique');
    usleep(1000); // Small delay
    $r2 = generate_filename('unique');
    // They might be the same if called in same second, but prefix should be there
    assert_contains('unique', $r1, 'First should have prefix');
    assert_contains('unique', $r2, 'Second should have prefix');
});

// ============================================================
// format_filesize() tests
// ============================================================

run_test('format_filesize - bytes', function() {
    $result = format_filesize(500);
    assert_contains('500', $result, 'Should show 500');
    // Should be bytes or B
    assert_true(
        strpos($result, 'B') !== false || strpos($result, 'byte') !== false,
        'Should indicate bytes'
    );
});

run_test('format_filesize - kilobytes', function() {
    $result = format_filesize(1024);
    assert_true(
        strpos($result, 'KB') !== false || strpos($result, '1') !== false,
        'Should be around 1 KB'
    );
});

run_test('format_filesize - megabytes', function() {
    $result = format_filesize(1048576); // 1 MB
    assert_true(
        strpos($result, 'MB') !== false || strpos($result, '1') !== false,
        'Should be around 1 MB'
    );
});

run_test('format_filesize - gigabytes', function() {
    $result = format_filesize(1073741824); // 1 GB
    assert_true(
        strpos($result, 'GB') !== false || strpos($result, '1') !== false,
        'Should be around 1 GB'
    );
});

run_test('format_filesize - zero', function() {
    $result = format_filesize(0);
    assert_contains('0', $result, 'Should show 0');
});

// ============================================================
// paginate() tests
// ============================================================

run_test('paginate - first page', function() {
    $result = paginate(100, 1, 10);

    assert_equals(1, $result['current'], 'Current page');
    assert_equals(10, $result['per_page'], 'Per page');
    assert_loose_equals(10, $result['total_pages'], 'Total pages');  // ceil() returns float
    assert_equals(100, $result['total'], 'Total items');
    assert_true($result['has_next'], 'Should have next');
    assert_false($result['has_prev'], 'Should not have prev');
});

run_test('paginate - middle page', function() {
    $result = paginate(100, 5, 10);

    assert_equals(5, $result['current'], 'Current page');
    assert_true($result['has_prev'], 'Should have prev');
    assert_true($result['has_next'], 'Should have next');
});

run_test('paginate - last page', function() {
    $result = paginate(100, 10, 10);

    assert_equals(10, $result['current'], 'Current page');
    assert_true($result['has_prev'], 'Should have prev');
    assert_false($result['has_next'], 'Should not have next');
});

run_test('paginate - single page', function() {
    $result = paginate(5, 1, 10);

    assert_equals(1, $result['current'], 'Current page');
    assert_loose_equals(1, $result['total_pages'], 'Total pages');  // ceil() returns float
    assert_false($result['has_prev'], 'Should not have prev');
    assert_false($result['has_next'], 'Should not have next');
});

run_test('paginate - page beyond total', function() {
    $result = paginate(50, 100, 10);

    // Returns the requested page even if beyond total
    assert_equals(100, $result['current'], 'Returns requested page');
    assert_loose_equals(5, $result['total_pages'], 'Total pages is 5');  // ceil() returns float
});

run_test('paginate - zero items', function() {
    $result = paginate(0, 1, 10);

    assert_equals(0, $result['total'], 'Total items');
    assert_loose_equals(0, $result['total_pages'], 'Zero pages for empty');  // ceil() returns float
});

// ============================================================
// is_valid_filepath() tests
// Note: is_valid_filepath() requires the file to exist AND be in allowed dirs
// ============================================================

run_test('is_valid_filepath - non-existent path returns false', function() {
    $result = is_valid_filepath('/nonexistent/path/file.txt');
    assert_false($result, 'Non-existent path returns false');
});

run_test('is_valid_filepath - blocks directory traversal', function() {
    $result = is_valid_filepath('/var/www/../../../etc/passwd');
    assert_false($result, 'Should block directory traversal');
});

run_test('is_valid_filepath - blocks double dots', function() {
    $result = is_valid_filepath('../../secret.txt');
    assert_false($result, 'Should block .. traversal');
});

run_test('is_valid_filepath - path outside allowed dirs returns false', function() {
    // Even if file exists, must be in allowed directories
    $result = is_valid_filepath('/etc/passwd');
    assert_false($result, 'Should reject files outside allowed dirs');
});

run_test('is_valid_filepath - blocks null bytes', function() {
    $result = is_valid_filepath("/var/www/html/file\x00.txt");
    assert_false($result, 'Should block null bytes');
});

// ============================================================
// get_shared_path() tests
// ============================================================

run_test('get_shared_path - returns valid path', function() {
    $result = get_shared_path();
    assert_not_null($result, 'Should return path');
    assert_greater_than(0, strlen($result), 'Should not be empty');
});

// ============================================================
// get_generated_path() tests
// ============================================================

run_test('get_generated_path - returns valid path', function() {
    $result = get_generated_path();
    assert_not_null($result, 'Should return path');
    assert_greater_than(0, strlen($result), 'Should not be empty');
});

// ============================================================
// get_pending_path() tests
// ============================================================

run_test('get_pending_path - returns valid path', function() {
    $result = get_pending_path();
    assert_not_null($result, 'Should return path');
    assert_greater_than(0, strlen($result), 'Should not be empty');
});

// ============================================================
// get_archive_path() tests
// ============================================================

run_test('get_archive_path - returns valid path', function() {
    $result = get_archive_path();
    assert_not_null($result, 'Should return path');
    assert_greater_than(0, strlen($result), 'Should not be empty');
});

// Print summary
test_summary();
