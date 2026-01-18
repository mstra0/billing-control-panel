<?php
/**
 * Test: CSV Parsing Functions
 *
 * Tests for parse_billing_filename(), parse_billing_csv(),
 * import_billing_report(), csv_escape(), etc.
 *
 * These are HIGH priority - parsing errors corrupt our data!
 */

echo "Testing: CSV Parsing Functions\n";
echo "==============================\n";

// ============================================================
// parse_billing_filename() tests
// ============================================================

run_test('parse_billing_filename - daily pattern basic', function() {
    $result = parse_billing_filename('DataX_2025_01_15_report.csv');

    assert_not_null($result, 'Should parse daily filename');
    assert_equals('daily', $result['type'], 'Type should be daily');
    assert_equals(2025, $result['year'], 'Year should be 2025');
    assert_equals(1, $result['month'], 'Month should be 1');
    assert_equals(15, $result['day'], 'Day should be 15');
});

run_test('parse_billing_filename - daily pattern single digit day', function() {
    $result = parse_billing_filename('DataX_2025_12_5_data.csv');

    assert_not_null($result, 'Should parse daily filename');
    assert_equals('daily', $result['type'], 'Type should be daily');
    assert_equals(2025, $result['year'], 'Year should be 2025');
    assert_equals(12, $result['month'], 'Month should be 12');
    assert_equals(5, $result['day'], 'Day should be 5');
});

run_test('parse_billing_filename - monthly pattern', function() {
    $result = parse_billing_filename('DataX_2025_01_2025_01_final.csv');

    assert_not_null($result, 'Should parse monthly filename');
    assert_equals('monthly', $result['type'], 'Type should be monthly');
    assert_equals(2025, $result['year'], 'Year should be 2025');
    assert_equals(1, $result['month'], 'Month should be 1');
});

run_test('parse_billing_filename - monthly pattern cross-year', function() {
    $result = parse_billing_filename('DataX_2024_12_2025_01_report.csv');

    assert_not_null($result, 'Should parse monthly filename');
    assert_equals('monthly', $result['type'], 'Type should be monthly');
    assert_equals(2024, $result['year'], 'Start year should be 2024');
    assert_equals(12, $result['month'], 'Start month should be 12');
    assert_equals(2025, $result['end_year'], 'End year should be 2025');
    assert_equals(1, $result['end_month'], 'End month should be 1');
});

run_test('parse_billing_filename - invalid pattern returns false', function() {
    $result = parse_billing_filename('random_file.csv');
    assert_false($result, 'Should return false for invalid pattern');

    $result = parse_billing_filename('report_2025_01.csv');
    assert_false($result, 'Should return false for non-DataX pattern');

    $result = parse_billing_filename('DataX_abc_01_15.csv');
    assert_false($result, 'Should return false for non-numeric year');
});

run_test('parse_billing_filename - no extension still works', function() {
    $result = parse_billing_filename('DataX_2025_06_15_test');

    // Should still parse the pattern
    assert_not_null($result, 'Should parse filename without extension');
    assert_equals('daily', $result['type'], 'Type should be daily');
});

// ============================================================
// parse_billing_csv() tests
// ============================================================

run_test('parse_billing_csv - valid content', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,101,Acme Corp,HIT001,Credit Check,0.50,100,50.00,CC001,BIL001\n";
    $csv .= "2025,1,101,Acme Corp,HIT002,ID Verify,0.25,200,50.00,IV001,BIL002\n";

    $result = parse_billing_csv($csv);

    assert_count(0, $result['errors'], 'Should have no errors');
    assert_count(2, $result['rows'], 'Should parse 2 rows');
    assert_equals(2, $result['row_count'], 'Row count should be 2');
});

run_test('parse_billing_csv - parses fields correctly', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,6,999,Test Customer,HIT123,Test Transaction,1.25,500,625.00,TX001,BILL999\n";

    $result = parse_billing_csv($csv);
    $row = $result['rows'][0];

    assert_equals(2025, $row['y'], 'Year should be 2025');
    assert_equals(6, $row['m'], 'Month should be 6');
    assert_equals(999, $row['cust_id'], 'Customer ID should be 999');
    assert_equals('Test Customer', $row['cust_name'], 'Customer name');
    assert_equals('HIT123', $row['hit_code'], 'Hit code');
    assert_equals('Test Transaction', $row['tran_displayname'], 'Display name');
    assert_float_equals(1.25, $row['actual_unit_cost'], 0.01, 'Unit cost');
    assert_equals(500, $row['count'], 'Count');
    assert_float_equals(625.00, $row['revenue'], 0.01, 'Revenue');
    assert_equals('TX001', $row['EFX_code'], 'EFX code');
    assert_equals('BILL999', $row['billing_id'], 'Billing ID');
});

run_test('parse_billing_csv - missing required column', function() {
    // Missing 'revenue' column
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,EFX_code,billing_id\n";
    $csv .= "2025,1,101,Acme Corp,HIT001,Credit Check,0.50,100,CC001,BIL001\n";

    $result = parse_billing_csv($csv);

    assert_greater_than(0, count($result['errors']), 'Should have errors for missing column');
    assert_contains('revenue', $result['errors'][0], 'Error should mention missing column');
});

run_test('parse_billing_csv - empty file', function() {
    $result = parse_billing_csv('');

    assert_greater_than(0, count($result['errors']), 'Should error on empty file');
});

run_test('parse_billing_csv - header only, no data', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";

    $result = parse_billing_csv($csv);

    assert_count(0, $result['errors'], 'Should have no errors');
    assert_count(0, $result['rows'], 'Should have no rows');
    assert_equals(0, $result['row_count'], 'Row count should be 0');
});

run_test('parse_billing_csv - skips empty lines', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,101,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";
    $csv .= "\n";  // Empty line
    $csv .= "2025,1,102,Beta,HIT002,Test2,0.60,200,120.00,CC002,BIL002\n";
    $csv .= "\n";  // Another empty line

    $result = parse_billing_csv($csv);

    assert_count(0, $result['errors'], 'Should have no errors');
    assert_count(2, $result['rows'], 'Should parse 2 rows (skipping empty lines)');
});

run_test('parse_billing_csv - handles quoted fields', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,101,\"Acme, Inc.\",HIT001,\"Credit Check, Standard\",0.50,100,50.00,CC001,BIL001\n";

    $result = parse_billing_csv($csv);

    assert_count(0, $result['errors'], 'Should have no errors');
    assert_equals('Acme, Inc.', $result['rows'][0]['cust_name'], 'Should handle quoted field with comma');
    assert_equals('Credit Check, Standard', $result['rows'][0]['tran_displayname'], 'Should handle quoted field');
});

run_test('parse_billing_csv - row with missing cust_id errors', function() {
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,,Acme Corp,HIT001,Credit Check,0.50,100,50.00,CC001,BIL001\n";

    $result = parse_billing_csv($csv);

    assert_greater_than(0, count($result['errors']), 'Should error on missing cust_id');
    assert_count(0, $result['rows'], 'Should not include invalid row');
});

// ============================================================
// csv_escape() tests
// ============================================================

run_test('csv_escape - normal string unchanged', function() {
    assert_equals('hello', csv_escape('hello'), 'Normal string unchanged');
    assert_equals('test123', csv_escape('test123'), 'Alphanumeric unchanged');
});

run_test('csv_escape - string with comma gets quoted', function() {
    assert_equals('"hello,world"', csv_escape('hello,world'), 'Should quote string with comma');
});

run_test('csv_escape - string with quote gets escaped', function() {
    assert_equals('"say ""hello"""', csv_escape('say "hello"'), 'Should escape quotes');
});

run_test('csv_escape - string with newline gets quoted', function() {
    $result = csv_escape("line1\nline2");
    assert_contains('"', $result, 'Should quote string with newline');
});

run_test('csv_escape - null returns empty string', function() {
    assert_equals('', csv_escape(null), 'Null should return empty string');
});

run_test('csv_escape - empty string unchanged', function() {
    assert_equals('', csv_escape(''), 'Empty string unchanged');
});

run_test('csv_escape - number as string unchanged', function() {
    assert_equals('123.45', csv_escape('123.45'), 'Number string unchanged');
});

// ============================================================
// import_billing_report() tests
// ============================================================

run_test('import_billing_report - successful import', function() {
    // Create customers first (FK constraint)
    create_test_customer(array('id' => 101, 'name' => 'Acme'));
    create_test_customer(array('id' => 102, 'name' => 'Beta'));

    $filename = 'DataX_2025_03_2025_03_test.csv';
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,3,101,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";
    $csv .= "2025,3,102,Beta,HIT002,Test2,0.60,200,120.00,CC002,BIL002\n";

    $result = import_billing_report($filename, $csv);

    assert_true($result['success'], 'Import should succeed');
    assert_equals(2, $result['rows_imported'], 'Should import 2 rows');
    assert_not_null($result['report_id'], 'Should return report ID');
    assert_count(0, $result['errors'], 'Should have no errors');
});

run_test('import_billing_report - detects monthly from filename', function() {
    // Create customer first (FK constraint) - use auto ID from fixture
    $cust_id = create_test_customer(array('name' => 'Monthly Acme'));

    $filename = 'DataX_2025_06_2025_06_monthly.csv';
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,6,$cust_id,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    $result = import_billing_report($filename, $csv);

    assert_true($result['success'], 'Import should succeed');

    // Verify report type
    $reports = get_billing_reports('monthly');
    assert_greater_than(0, count($reports), 'Should have monthly report');
});

run_test('import_billing_report - detects daily from filename', function() {
    // Create customer first (FK constraint)
    $cust_id = create_test_customer(array('name' => 'Daily Acme'));

    $filename = 'DataX_2025_07_15_daily.csv';
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,7,$cust_id,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    $result = import_billing_report($filename, $csv);

    assert_true($result['success'], 'Import should succeed');

    // Verify report type
    $reports = get_billing_reports('daily');
    assert_greater_than(0, count($reports), 'Should have daily report');
});

run_test('import_billing_report - duplicate detection', function() {
    // Create customer first (FK constraint)
    $cust_id = create_test_customer(array('name' => 'Dup Acme'));

    $filename = 'DataX_2025_08_2025_08_unique.csv';
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,8,$cust_id,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    // First import
    $result1 = import_billing_report($filename, $csv);
    assert_true($result1['success'], 'First import should succeed');

    // Second import of same file
    $result2 = import_billing_report($filename, $csv);
    assert_false($result2['success'], 'Duplicate import should fail');
    assert_greater_than(0, count($result2['errors']), 'Should have error message');
});

run_test('import_billing_report - invalid CSV content', function() {
    $filename = 'DataX_2025_09_2025_09_bad.csv';
    $csv = "invalid,header,format\n";
    $csv .= "some,bad,data\n";

    $result = import_billing_report($filename, $csv);

    assert_false($result['success'], 'Import should fail');
    assert_greater_than(0, count($result['errors']), 'Should have errors');
});

run_test('import_billing_report - invalid filename pattern', function() {
    $filename = 'random_file.csv';
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,101,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    $result = import_billing_report($filename, $csv);

    // Should fail because we can't determine report type
    assert_false($result['success'], 'Import should fail for invalid filename');
});

// ============================================================
// import_transaction_types_csv() tests
// ============================================================

run_test('import_transaction_types_csv - valid import', function() {
    $csv = "type,display_name,EFX_code,EFX_displayname\n";
    $csv .= "credit,Credit Check,CC001,CREDIT CHK\n";
    $csv .= "credit,Credit Plus,CC002,CREDIT PLS\n";
    $csv .= "identity,ID Verify,ID001,ID VERIFY\n";

    $result = import_transaction_types_csv($csv);

    assert_equals(3, $result['imported'], 'Should import 3 types');
    assert_count(0, $result['errors'], 'Should have no errors');

    // Verify they exist
    $types = get_all_transaction_types();
    assert_greater_than(0, count($types), 'Should have transaction types');
});

run_test('import_transaction_types_csv - handles missing optional field', function() {
    $csv = "type,display_name,EFX_code\n";  // No EFX_displayname
    $csv .= "test,Test Type,TEST001\n";

    $result = import_transaction_types_csv($csv);

    assert_equals(1, $result['imported'], 'Should import 1 type');
    assert_count(0, $result['errors'], 'Should have no errors');
});

run_test('import_transaction_types_csv - insufficient fields error', function() {
    $csv = "type,display_name,EFX_code\n";
    $csv .= "only_two_fields,missing\n";  // Only 2 fields

    $result = import_transaction_types_csv($csv);

    assert_greater_than(0, count($result['errors']), 'Should have errors');
});

// ============================================================
// get_billing_reports() tests
// ============================================================

run_test('get_billing_reports - returns all reports', function() {
    // Create customer first (FK constraint)
    $cust_id = create_test_customer(array('name' => 'Reports Acme'));

    // Import a couple reports
    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,$cust_id,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    import_billing_report('DataX_2025_01_2025_01_a.csv', $csv);
    import_billing_report('DataX_2025_02_2025_02_b.csv', $csv);

    $reports = get_billing_reports();

    assert_greater_than(1, count($reports), 'Should return multiple reports');
});

run_test('get_billing_reports - filter by type', function() {
    // Create customer first (FK constraint)
    $cust_id = create_test_customer(array('name' => 'Filter Acme'));

    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,$cust_id,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    // Import monthly and daily
    import_billing_report('DataX_2025_04_2025_04_monthly.csv', $csv);
    import_billing_report('DataX_2025_04_15_daily.csv', $csv);

    $monthly = get_billing_reports('monthly');
    $daily = get_billing_reports('daily');

    // Each should have at least one
    assert_greater_than(0, count($monthly), 'Should have monthly reports');
    assert_greater_than(0, count($daily), 'Should have daily reports');
});

// ============================================================
// delete_billing_report() tests
// ============================================================

run_test('delete_billing_report - removes report and lines', function() {
    // Create customer first (FK constraint)
    $cust_id = create_test_customer(array('name' => 'Delete Acme'));

    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,5,$cust_id,Acme,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    $result = import_billing_report('DataX_2025_05_2025_05_delete.csv', $csv);
    $report_id = $result['report_id'];

    // Verify lines exist
    $lines = get_billing_report_lines($report_id);
    assert_count(1, $lines, 'Should have 1 line before delete');

    // Delete
    delete_billing_report($report_id);

    // Verify lines gone
    $lines = get_billing_report_lines($report_id);
    assert_count(0, $lines, 'Should have 0 lines after delete');
});

// Print summary
test_summary();
