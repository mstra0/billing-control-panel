<?php
/**
 * Test: Escalator Calculations
 *
 * Tests for calculate_escalated_price() and related functions.
 * These are CRITICAL - they calculate money!
 */

echo "Testing: Escalator Calculations\n";
echo "================================\n";

// ============================================================
// calculate_escalated_price() tests
// ============================================================

run_test('No escalators returns base price unchanged', function() {
    $customer_id = create_test_customer();

    $result = calculate_escalated_price(100.00, $customer_id, '2026-01-01');

    assert_float_equals(100.00, $result, 0.01, 'Base price should be unchanged');
});

run_test('Year 1 with 0% escalator returns base price', function() {
    $customer_id = create_test_customer();

    // Year 1 = 0%, starting 2025-01-01
    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $result = calculate_escalated_price(100.00, $customer_id, '2025-06-01');

    assert_float_equals(100.00, $result, 0.01, 'Year 1 with 0% should return base price');
});

run_test('Year 2 with 5% escalator applies percentage', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    // Test in year 2 (after 2026-01-01)
    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');

    assert_float_equals(105.00, $result, 0.01, '5% escalator should make $100 -> $105');
});

run_test('Year 2 with fixed adjustment only', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 0, 'fixed_adjustment' => 10)
    ), '2025-01-01');

    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');

    assert_float_equals(110.00, $result, 0.01, '$10 fixed adjustment should make $100 -> $110');
});

run_test('Year 2 with both percentage and fixed adjustment', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 10)
    ), '2025-01-01');

    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');

    // 5% of 100 = 105, then +10 = 115
    assert_float_equals(115.00, $result, 0.01, '5% + $10 should make $100 -> $115');
});

run_test('Date before escalator start returns base price', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 10, 'fixed_adjustment' => 0)
    ), '2025-06-01');

    // Test BEFORE escalator start date
    $result = calculate_escalated_price(100.00, $customer_id, '2025-01-01');

    assert_float_equals(100.00, $result, 0.01, 'Date before escalator start should return base price');
});

run_test('Year 3 escalator applies correctly', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0),
        array('year_number' => 3, 'escalator_percentage' => 10, 'fixed_adjustment' => 0)
    ), '2024-01-01');

    // Test in year 3 (2026)
    $result = calculate_escalated_price(100.00, $customer_id, '2026-06-01');

    // Year 3 = 10% of base
    assert_float_equals(110.00, $result, 0.01, 'Year 3 with 10% should make $100 -> $110');
});

run_test('Large percentage escalator', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 25, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');

    assert_float_equals(125.00, $result, 0.01, '25% escalator should make $100 -> $125');
});

run_test('Decimal percentage escalator', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 3.5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');

    assert_float_equals(103.50, $result, 0.01, '3.5% escalator should make $100 -> $103.50');
});

run_test('Negative fixed adjustment (discount)', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 0, 'fixed_adjustment' => -5)
    ), '2025-01-01');

    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');

    assert_float_equals(95.00, $result, 0.01, '-$5 fixed adjustment should make $100 -> $95');
});

run_test('Different base prices scale correctly', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 10, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $date = '2026-02-01';

    // Test with different base prices
    assert_float_equals(55.00, calculate_escalated_price(50.00, $customer_id, $date), 0.01, '$50 + 10% = $55');
    assert_float_equals(220.00, calculate_escalated_price(200.00, $customer_id, $date), 0.01, '$200 + 10% = $220');
    assert_float_equals(1.10, calculate_escalated_price(1.00, $customer_id, $date), 0.01, '$1 + 10% = $1.10');
});

// ============================================================
// Escalator delay tests
// ============================================================

run_test('Single delay postpones escalator', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    // Add 2-month delay to year 2
    apply_escalator_delay($customer_id, 2, 2);

    // Test during normal year 2 but within delay period - should NOT apply
    $result = calculate_escalated_price(100.00, $customer_id, '2026-02-01');
    assert_float_equals(100.00, $result, 0.01, 'During delay period, escalator should not apply');

    // Test after delay period - should apply
    $result = calculate_escalated_price(100.00, $customer_id, '2026-04-01');
    assert_float_equals(105.00, $result, 0.01, 'After delay period, escalator should apply');
});

run_test('Multiple delays accumulate', function() {
    $customer_id = create_test_customer();

    // Add multiple delays
    apply_escalator_delay($customer_id, 2, 1);
    apply_escalator_delay($customer_id, 2, 1);
    apply_escalator_delay($customer_id, 2, 1);

    // Total 3 months delay
    $total = get_total_delay_months($customer_id, 2);
    assert_equals(3, $total, 'Total delay should be 3 months');
});

run_test('get_total_delay_months returns 0 when no delays', function() {
    $customer_id = create_test_customer();

    $total = get_total_delay_months($customer_id, 1);
    assert_equals(0, $total, 'No delays should return 0');
});

run_test('Delays only affect specified year', function() {
    $customer_id = create_test_customer();

    apply_escalator_delay($customer_id, 2, 3);

    $year1_delay = get_total_delay_months($customer_id, 1);
    $year2_delay = get_total_delay_months($customer_id, 2);
    $year3_delay = get_total_delay_months($customer_id, 3);

    assert_equals(0, $year1_delay, 'Year 1 should have no delay');
    assert_equals(3, $year2_delay, 'Year 2 should have 3 month delay');
    assert_equals(0, $year3_delay, 'Year 3 should have no delay');
});

// ============================================================
// get_current_escalators() tests
// ============================================================

run_test('get_current_escalators returns empty for customer without escalators', function() {
    $customer_id = create_test_customer();

    $escalators = get_current_escalators($customer_id);

    assert_count(0, $escalators, 'Should return empty array');
});

run_test('get_current_escalators returns saved escalators', function() {
    $customer_id = create_test_customer();

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0),
        array('year_number' => 3, 'escalator_percentage' => 7, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $escalators = get_current_escalators($customer_id);

    assert_count(3, $escalators, 'Should return 3 escalators');
    assert_equals(1, (int)$escalators[0]['year_number'], 'First should be year 1');
    assert_equals(2, (int)$escalators[1]['year_number'], 'Second should be year 2');
    assert_equals(3, (int)$escalators[2]['year_number'], 'Third should be year 3');
});

run_test('get_current_escalators returns latest effective set', function() {
    $customer_id = create_test_customer();

    // Save initial escalators with effective date in the past
    sqlite_execute(
        "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
         VALUES (?, ?, ?, ?, ?, ?)",
        array($customer_id, '2025-01-01', 2, 5, 0, '2025-01-01')
    );

    // Save updated escalators with later effective date
    sqlite_execute(
        "INSERT INTO customer_escalators (customer_id, escalator_start_date, year_number, escalator_percentage, fixed_adjustment, effective_date)
         VALUES (?, ?, ?, ?, ?, ?)",
        array($customer_id, '2025-01-01', 2, 10, 0, '2025-06-01')
    );

    $escalators = get_current_escalators($customer_id);

    // Should get the latest (10%)
    assert_float_equals(10.0, (float)$escalators[0]['escalator_percentage'], 0.01, 'Should return latest escalator percentage');
});

// ============================================================
// get_escalator_delays() tests
// ============================================================

run_test('get_escalator_delays returns empty when no delays', function() {
    $customer_id = create_test_customer();

    $delays = get_escalator_delays($customer_id);

    assert_count(0, $delays, 'Should return empty array');
});

run_test('get_escalator_delays returns all delays for customer', function() {
    $customer_id = create_test_customer();

    apply_escalator_delay($customer_id, 2, 1);
    apply_escalator_delay($customer_id, 3, 2);

    $delays = get_escalator_delays($customer_id);

    assert_count(2, $delays, 'Should return 2 delays');
});

// Print summary
test_summary();
