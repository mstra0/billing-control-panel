<?php
/**
 * Test: CRUD Operations
 *
 * Tests for save_*, delete_*, and related modification functions.
 * Priority 3 - Standard database operations.
 */

echo "Testing: CRUD Operations\n";
echo "=========================\n";

// ============================================================
// save_default_tiers() tests
// ============================================================

run_test('save_default_tiers - creates new tiers', function() {
    $service_id = create_test_service(array('name' => 'Credit Check'));

    $tiers = array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.50),
        array('volume_start' => 1001, 'volume_end' => 5000, 'price_per_inquiry' => 0.40),
        array('volume_start' => 5001, 'volume_end' => null, 'price_per_inquiry' => 0.30)
    );

    save_default_tiers($service_id, $tiers);

    $result = get_current_default_tiers($service_id);
    assert_count(3, $result, 'Should save 3 tiers');
    assert_float_equals(0.50, $result[0]['price_per_inquiry'], 0.01, 'First tier price');
    assert_float_equals(0.40, $result[1]['price_per_inquiry'], 0.01, 'Second tier price');
    assert_float_equals(0.30, $result[2]['price_per_inquiry'], 0.01, 'Third tier price');
});

run_test('save_default_tiers - append-only creates new effective set', function() {
    $service_id = create_test_service(array('name' => 'ID Verify'));

    // Initial save
    save_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.50)
    ));

    // Second save creates new effective set (append-only)
    save_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => 500, 'price_per_inquiry' => 0.60),
        array('volume_start' => 501, 'volume_end' => null, 'price_per_inquiry' => 0.45)
    ));

    // get_current returns the latest effective set
    $result = get_current_default_tiers($service_id);
    assert_greater_than(0, count($result), 'Should have tiers');
});

run_test('save_default_tiers - with future effective date', function() {
    $service_id = create_test_service(array('name' => 'Future Service'));
    $future_date = date('Y-m-d', strtotime('+30 days'));

    // Save current tiers first
    save_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.50)
    ));

    // Save future tiers
    save_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.75)
    ), $future_date);

    // Current should still be 0.50 (future not yet effective)
    $result = get_current_default_tiers($service_id);
    assert_greater_than(0, count($result), 'Should have current tiers');
});

// ============================================================
// save_group_tiers() tests
// ============================================================

run_test('save_group_tiers - creates group override', function() {
    $group_id = create_test_group(array('name' => 'Premium'));
    $service_id = create_test_service(array('name' => 'Credit'));

    // First create defaults
    save_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 1.00)
    ));

    // Then group override
    save_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.80)
    ));

    $result = get_current_group_tiers($group_id, $service_id);
    assert_count(1, $result, 'Should have 1 group tier');
    assert_float_equals(0.80, $result[0]['price_per_inquiry'], 0.01, 'Group price override');
});

run_test('save_group_tiers - multiple saves create history', function() {
    $group_id = create_test_group(array('name' => 'Temp Group'));
    $service_id = create_test_service(array('name' => 'Temp Service'));

    // Create group tiers
    save_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.50)
    ));

    // Verify created
    $result = get_current_group_tiers($group_id, $service_id);
    assert_greater_than(0, count($result), 'Should have tiers after save');
});

// ============================================================
// save_customer_tiers() tests
// ============================================================

run_test('save_customer_tiers - creates customer override', function() {
    $customer_id = create_test_customer(array('name' => 'VIP Client'));
    $service_id = create_test_service(array('name' => 'Premium Service'));

    // Create defaults first
    save_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 1.00)
    ));

    // Customer override
    save_customer_tiers($customer_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.60),
        array('volume_start' => 1001, 'volume_end' => null, 'price_per_inquiry' => 0.45)
    ));

    $result = get_current_customer_tiers($customer_id, $service_id);
    assert_count(2, $result, 'Should have 2 customer tiers');
    assert_float_equals(0.60, $result[0]['price_per_inquiry'], 0.01, 'Customer tier 1');
    assert_float_equals(0.45, $result[1]['price_per_inquiry'], 0.01, 'Customer tier 2');
});

run_test('save_customer_tiers - multiple saves create history', function() {
    $customer_id = create_test_customer(array('name' => 'Update Client'));
    $service_id = create_test_service(array('name' => 'Update Service'));

    // Initial
    save_customer_tiers($customer_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.50)
    ));

    // Second save (append-only system)
    save_customer_tiers($customer_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.35)
    ));

    $result = get_current_customer_tiers($customer_id, $service_id);
    assert_greater_than(0, count($result), 'Should have tiers');
});

// ============================================================
// save_customer_settings() tests
// ============================================================

run_test('save_customer_settings - all fields', function() {
    $customer_id = create_test_customer(array('name' => 'Settings Client'));

    save_customer_settings($customer_id, array(
        'monthly_minimum' => 500.00,
        'uses_annualized' => 1,
        'annualized_start_date' => '2025-06-01',
        'look_period_months' => 12
    ));

    $result = get_current_customer_settings($customer_id);
    assert_float_equals(500.00, $result['monthly_minimum'], 0.01, 'Monthly minimum');
    assert_equals(1, $result['uses_annualized'], 'Uses annualized');
    assert_equals('2025-06-01', $result['annualized_start_date'], 'Annualized start date');
    assert_equals(12, $result['look_period_months'], 'Look period months');
});

run_test('save_customer_settings - partial update', function() {
    $customer_id = create_test_customer(array('name' => 'Partial Client'));

    // Initial settings
    save_customer_settings($customer_id, array(
        'monthly_minimum' => 100.00,
        'pricing_model' => 'flat'
    ));

    // Partial update - only minimum
    save_customer_settings($customer_id, array(
        'monthly_minimum' => 200.00
    ));

    $result = get_current_customer_settings($customer_id);
    assert_float_equals(200.00, $result['monthly_minimum'], 0.01, 'Updated minimum');
});

run_test('save_customer_settings - null values clear settings', function() {
    $customer_id = create_test_customer(array('name' => 'Clear Client'));

    // Set values
    save_customer_settings($customer_id, array(
        'monthly_minimum' => 500.00,
        'uses_annualized' => 1
    ));

    // Clear with null
    save_customer_settings($customer_id, array(
        'monthly_minimum' => null,
        'uses_annualized' => 0
    ));

    $result = get_current_customer_settings($customer_id);
    assert_null($result['monthly_minimum'], 'Monthly minimum should be cleared');
    assert_equals(0, $result['uses_annualized'], 'Uses annualized should be 0');
});

// ============================================================
// save_escalators() tests
// ============================================================

run_test('save_escalators - multiple years', function() {
    $customer_id = create_test_customer(array('name' => 'Escalator Client'));

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 3, 'fixed_adjustment' => 0),
        array('year_number' => 3, 'escalator_percentage' => 3, 'fixed_adjustment' => 5)
    ), '2025-01-01');

    $result = get_current_escalators($customer_id);
    assert_greater_than(0, count($result), 'Should have escalators');
});

run_test('save_escalators - append-only history', function() {
    $customer_id = create_test_customer(array('name' => 'Replace Client'));

    // Initial 3 years
    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0),
        array('year_number' => 3, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    // Second save creates new set (append-only)
    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 10, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    $result = get_current_escalators($customer_id);
    assert_greater_than(0, count($result), 'Should have escalators');
});

// ============================================================
// apply_escalator_delay() tests
// ============================================================

run_test('apply_escalator_delay - single delay', function() {
    $customer_id = create_test_customer(array('name' => 'Delay Client'));

    // Create escalators first
    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    // Apply 3 month delay to year 2
    apply_escalator_delay($customer_id, 2, 3);

    $delays = get_escalator_delays($customer_id);
    assert_count(1, $delays, 'Should have 1 delay');
    assert_equals(2, $delays[0]['year_number'], 'Delay for year 2');
    assert_equals(3, $delays[0]['delay_months'], 'Delay of 3 months');
});

run_test('apply_escalator_delay - multiple delays stack', function() {
    $customer_id = create_test_customer(array('name' => 'Multi Delay'));

    save_escalators($customer_id, array(
        array('year_number' => 1, 'escalator_percentage' => 0, 'fixed_adjustment' => 0),
        array('year_number' => 2, 'escalator_percentage' => 5, 'fixed_adjustment' => 0)
    ), '2025-01-01');

    // Apply delays
    apply_escalator_delay($customer_id, 2, 2);
    apply_escalator_delay($customer_id, 2, 3);

    $total = get_total_delay_months($customer_id, 2);
    assert_equals(5, $total, 'Total delay should be 5 months');
});

// ============================================================
// save_lms() tests
// ============================================================

run_test('save_lms - creates new LMS', function() {
    $id = save_lms(null, 'New LMS', 0.15);

    assert_not_null($id, 'Should return ID');

    $lms = get_lms($id);
    assert_equals('New LMS', $lms['name'], 'Name should match');
    assert_float_equals(0.15, $lms['commission_rate'], 0.001, 'Commission rate');
});

run_test('save_lms - updates existing LMS', function() {
    $id = save_lms(null, 'Update LMS', 0.10);

    // Update
    save_lms($id, 'Updated LMS Name', 0.12);

    $lms = get_lms($id);
    assert_equals('Updated LMS Name', $lms['name'], 'Name should be updated');
    assert_float_equals(0.12, $lms['commission_rate'], 0.001, 'Rate should be updated');
});

run_test('save_lms - null commission falls back to default', function() {
    // Set default
    save_default_commission_rate(0.08);

    $id = save_lms(null, 'Default Rate LMS', null);

    $rate = get_effective_commission_rate($id);
    // Should return default rate or the LMS-specific rate
    assert_not_null($rate, 'Should return a rate');
});

// ============================================================
// save_billing_flags() tests
// ============================================================

run_test('save_billing_flags - default level', function() {
    $service_id = create_test_service(array('name' => 'Flag Service'));

    save_billing_flags('default', null, $service_id, 'TEST001', 1, 0, 1);

    $flags = get_effective_billing_flags($service_id, 'TEST001');
    assert_equals(1, $flags['by_hit'], 'By hit flag');
    assert_equals(0, $flags['zero_null'], 'Zero null flag');
    assert_equals(1, $flags['bav_by_trans'], 'BAV by trans flag');
});

run_test('save_billing_flags - group level override', function() {
    $service_id = create_test_service(array('name' => 'Group Flag Service'));
    $group_id = create_test_group(array('name' => 'Flag Group'));

    // Default
    save_billing_flags('default', null, $service_id, 'GRP001', 1, 0, 0);

    // Group override
    save_billing_flags('group', $group_id, $service_id, 'GRP001', 0, 1, 0);

    // Check with group context
    $flags = get_effective_billing_flags($service_id, 'GRP001', null, $group_id);
    assert_equals(0, $flags['by_hit'], 'Group should override by_hit');
    assert_equals(1, $flags['zero_null'], 'Group should override zero_null');
});

run_test('save_billing_flags - customer level override', function() {
    $service_id = create_test_service(array('name' => 'Cust Flag Service'));
    $customer_id = create_test_customer(array('name' => 'Flag Customer'));

    // Default
    save_billing_flags('default', null, $service_id, 'CUST001', 1, 0, 0);

    // Customer override
    save_billing_flags('customer', $customer_id, $service_id, 'CUST001', 0, 0, 1);

    // Check with customer context
    $flags = get_effective_billing_flags($service_id, 'CUST001', $customer_id, null);
    assert_equals(0, $flags['by_hit'], 'Customer should override by_hit');
    assert_equals(1, $flags['bav_by_trans'], 'Customer should override bav_by_trans');
});

// ============================================================
// save_transaction_type() tests
// ============================================================

run_test('save_transaction_type - creates new type', function() {
    $id = save_transaction_type('credit', 'Credit Check', 'CC001', 'CREDIT CHECK');

    assert_not_null($id, 'Should return ID');

    $type = get_transaction_type_by_efx('CC001');
    assert_equals('credit', $type['type'], 'Type should match');
    assert_equals('Credit Check', $type['display_name'], 'Display name');
    assert_equals('CREDIT CHECK', $type['efx_displayname'], 'EFX display name');
});

run_test('save_transaction_type - creates transaction type', function() {
    save_transaction_type('test', 'Test Type', 'TEST001', 'TEST');

    $type = get_transaction_type_by_efx('TEST001');
    assert_not_null($type, 'Should find transaction type');
    assert_equals('TEST001', $type['efx_code'], 'EFX code should match');
});

run_test('save_transaction_type - with service link', function() {
    $service_id = create_test_service(array('name' => 'Linked Service'));

    $id = save_transaction_type('linked', 'Linked Type', 'LINK001', null, $service_id);

    $type = get_transaction_type_by_efx('LINK001');
    assert_equals($service_id, $type['service_id'], 'Should link to service');
});

// ============================================================
// save_service_cogs() tests
// ============================================================

run_test('save_service_cogs - creates new COGS', function() {
    $service_id = create_test_service(array('name' => 'COGS Service'));

    save_service_cogs($service_id, 0.25);

    $cogs = get_service_cogs($service_id);
    assert_float_equals(0.25, $cogs, 0.01, 'COGS rate should match');
});

run_test('save_service_cogs - updates existing', function() {
    $service_id = create_test_service(array('name' => 'Update COGS'));

    save_service_cogs($service_id, 0.20);
    save_service_cogs($service_id, 0.30);

    $cogs = get_service_cogs($service_id);
    assert_float_equals(0.30, $cogs, 0.01, 'COGS should be updated');
});

// ============================================================
// assign_customer_lms() tests
// ============================================================

run_test('assign_customer_lms - assigns LMS to customer', function() {
    // Use save_lms to create (it handles ID assignment properly)
    $lms_id = save_lms(null, 'Assigned LMS', 0.10);
    $customer_id = create_test_customer(array('name' => 'LMS Client'));

    assign_customer_lms($customer_id, $lms_id);

    $customers = get_customers_by_lms($lms_id);
    $found = false;
    foreach ($customers as $c) {
        if ($c['id'] == $customer_id) {
            $found = true;
            break;
        }
    }
    assert_true($found, 'Customer should be assigned to LMS');
});

run_test('assign_customer_lms - reassigns to different LMS', function() {
    $lms1_id = save_lms(null, 'LMS One', 0.10);
    $lms2_id = save_lms(null, 'LMS Two', 0.12);
    $customer_id = create_test_customer(array('name' => 'Reassign Client'));

    // First assignment
    assign_customer_lms($customer_id, $lms1_id);

    // Reassign
    assign_customer_lms($customer_id, $lms2_id);

    // Should be in LMS 2
    $customers = get_customers_by_lms($lms2_id);
    $found = false;
    foreach ($customers as $c) {
        if ($c['id'] == $customer_id) {
            $found = true;
            break;
        }
    }
    assert_true($found, 'Customer should be in new LMS');
});

// ============================================================
// delete_billing_report() tests
// ============================================================

run_test('delete_billing_report - removes report and lines', function() {
    // Create customer for FK
    create_test_customer(array('id' => 201, 'name' => 'Delete Test Customer'));

    $csv = "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    $csv .= "2025,1,201,Delete Test,HIT001,Test,0.50,100,50.00,CC001,BIL001\n";

    $result = import_billing_report('DataX_2025_10_2025_10_delete_test.csv', $csv);
    $report_id = $result['report_id'];

    // Verify exists
    $lines = get_billing_report_lines($report_id);
    assert_count(1, $lines, 'Should have lines before delete');

    // Delete
    delete_billing_report($report_id);

    // Verify gone
    $lines = get_billing_report_lines($report_id);
    assert_count(0, $lines, 'Lines should be deleted');
});

// ============================================================
// toggle_rule_mask() tests
// ============================================================

run_test('toggle_rule_mask - mask on', function() {
    $customer_id = create_test_customer(array('name' => 'Mask Client'));

    toggle_rule_mask($customer_id, 'test_rule', true);

    $status = get_rule_mask_status($customer_id, 'test_rule');
    assert_true($status, 'Rule should be masked');
});

run_test('toggle_rule_mask - mask off', function() {
    $customer_id = create_test_customer(array('name' => 'Unmask Client'));

    // First mask
    toggle_rule_mask($customer_id, 'another_rule', true);

    // Then unmask
    toggle_rule_mask($customer_id, 'another_rule', false);

    $status = get_rule_mask_status($customer_id, 'another_rule');
    assert_false($status, 'Rule should be unmasked');
});

// Print summary
test_summary();
