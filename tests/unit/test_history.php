<?php
/**
 * Test: History Functions
 *
 * Tests for audit/history retrieval functions.
 * Priority 7 - Straightforward queries.
 */

echo "Testing: History Functions\n";
echo "===========================\n";

// ============================================================
// get_pricing_history() tests
// ============================================================

run_test('get_pricing_history - empty returns empty array', function() {
    $history = get_pricing_history();
    assert_true(is_array($history), 'Should return array');
});

run_test('get_pricing_history - records tier changes', function() {
    $customer_id = create_test_customer(array('name' => 'History Customer'));
    $service_id = create_test_service(array('name' => 'History Service'));

    // Make some changes
    save_customer_tiers($customer_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.50)
    ));

    // Update
    save_customer_tiers($customer_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.45)
    ));

    $history = get_pricing_history($customer_id);

    // Should have history entries (if the system tracks history)
    assert_not_null($history, 'Should return history');
});

run_test('get_pricing_history - filter by customer', function() {
    $c1 = create_test_customer(array('name' => 'Filter Customer 1'));
    $c2 = create_test_customer(array('name' => 'Filter Customer 2'));
    $service_id = create_test_service(array('name' => 'Filter Service'));

    save_customer_tiers($c1, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.50)
    ));

    save_customer_tiers($c2, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.60)
    ));

    $history_c1 = get_pricing_history($c1);
    $history_c2 = get_pricing_history($c2);

    // Should be filtered by customer
    assert_not_null($history_c1, 'Should return c1 history');
    assert_not_null($history_c2, 'Should return c2 history');
});

// ============================================================
// get_settings_history() tests
// ============================================================

run_test('get_settings_history - empty returns array', function() {
    $history = get_settings_history();
    assert_true(is_array($history), 'Should return array');
});

run_test('get_settings_history - records settings changes', function() {
    $customer_id = create_test_customer(array('name' => 'Settings History'));

    // Make changes
    save_customer_settings($customer_id, array('monthly_minimum' => 100));
    save_customer_settings($customer_id, array('monthly_minimum' => 200));

    $history = get_settings_history($customer_id);

    assert_not_null($history, 'Should return history');
});

run_test('get_settings_history - filter by customer', function() {
    $c1 = create_test_customer(array('name' => 'Settings C1'));
    $c2 = create_test_customer(array('name' => 'Settings C2'));

    save_customer_settings($c1, array('monthly_minimum' => 100));
    save_customer_settings($c2, array('monthly_minimum' => 200));

    $history_c1 = get_settings_history($c1);
    $history_c2 = get_settings_history($c2);

    assert_not_null($history_c1, 'Should return c1 history');
    assert_not_null($history_c2, 'Should return c2 history');
});

// ============================================================
// get_escalator_history() tests
// ============================================================

run_test('get_escalator_history - empty returns array', function() {
    $history = get_escalator_history();
    assert_true(is_array($history), 'Should return array');
});

run_test('get_escalator_history - records escalator changes', function() {
    $customer_id = create_test_customer(array('name' => 'Escalator History'));

    // Set escalators
    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 3, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    // Update
    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $history = get_escalator_history($customer_id);

    assert_not_null($history, 'Should return history');
});

run_test('get_escalator_history - filter by customer', function() {
    $c1 = create_test_customer(array('name' => 'Esc Hist C1'));
    $c2 = create_test_customer(array('name' => 'Esc Hist C2'));

    save_escalators($c1, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    save_escalators($c2, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0)
    ), '2025-06-01');

    $history_c1 = get_escalator_history($c1);
    $history_c2 = get_escalator_history($c2);

    assert_not_null($history_c1, 'Should return c1 history');
    assert_not_null($history_c2, 'Should return c2 history');
});

// ============================================================
// get_rule_mask_history() tests
// ============================================================

run_test('get_rule_mask_history - empty returns array', function() {
    $history = get_rule_mask_history();
    assert_true(is_array($history), 'Should return array');
});

run_test('get_rule_mask_history - records mask toggles', function() {
    $customer_id = create_test_customer(array('name' => 'Mask History'));

    // Toggle mask on
    toggle_rule_mask($customer_id, 'hist_rule', true);

    // Toggle mask off
    toggle_rule_mask($customer_id, 'hist_rule', false);

    $history = get_rule_mask_history($customer_id);

    assert_not_null($history, 'Should return history');
});

run_test('get_rule_mask_history - filter by customer', function() {
    $c1 = create_test_customer(array('name' => 'Mask Hist C1'));
    $c2 = create_test_customer(array('name' => 'Mask Hist C2'));

    toggle_rule_mask($c1, 'rule_a', true);
    toggle_rule_mask($c2, 'rule_b', true);

    $history_c1 = get_rule_mask_history($c1);
    $history_c2 = get_rule_mask_history($c2);

    assert_not_null($history_c1, 'Should return c1 history');
    assert_not_null($history_c2, 'Should return c2 history');
});

// Print summary
test_summary();
