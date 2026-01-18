<?php
/**
 * Test: Pricing Inheritance
 *
 * Tests for get_effective_customer_tiers(), get_effective_group_tiers(),
 * and get_effective_billing_flags() - the inheritance resolution functions.
 *
 * These are CRITICAL - they determine what prices customers pay!
 */

echo "Testing: Pricing Inheritance\n";
echo "============================\n";

// ============================================================
// get_effective_customer_tiers() tests
// ============================================================

run_test('Customer with no tiers falls back to defaults', function() {
    $service_id = create_test_service();
    $customer_id = create_test_customer();

    // Create default tiers only
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 1.00),
        array('volume_start' => 1001, 'volume_end' => null, 'price_per_inquiry' => 0.80)
    ));

    $tiers = get_effective_customer_tiers($customer_id, $service_id);

    assert_count(2, $tiers, 'Should return 2 tiers');
    assert_float_equals(1.00, (float)$tiers[0]['price_per_inquiry'], 0.01, 'First tier should be $1.00');
    assert_equals('default', $tiers[0]['source'], 'Source should be default');
});

run_test('Customer in group inherits group tiers', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();
    $customer_id = create_test_customer(array('discount_group_id' => $group_id));

    // Create default tiers
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 1.00)
    ));

    // Create group tiers (better pricing)
    create_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.80)
    ));

    $tiers = get_effective_customer_tiers($customer_id, $service_id);

    assert_count(1, $tiers, 'Should return 1 tier');
    assert_float_equals(0.80, (float)$tiers[0]['price_per_inquiry'], 0.01, 'Should use group price $0.80');
    assert_equals('group', $tiers[0]['source'], 'Source should be group');
});

run_test('Customer override takes precedence over group', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();
    $customer_id = create_test_customer(array('discount_group_id' => $group_id));

    // Create default tiers
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 1.00)
    ));

    // Create group tiers
    create_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.80)
    ));

    // Create customer override (best pricing)
    create_customer_tiers($customer_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.60)
    ));

    $tiers = get_effective_customer_tiers($customer_id, $service_id);

    assert_count(1, $tiers, 'Should return 1 tier');
    assert_float_equals(0.60, (float)$tiers[0]['price_per_inquiry'], 0.01, 'Should use customer price $0.60');
    assert_equals('customer', $tiers[0]['source'], 'Source should be customer');
});

run_test('Customer without group still inherits defaults', function() {
    $service_id = create_test_service();
    $customer_id = create_test_customer(array('discount_group_id' => null));

    // Create default tiers
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => 500, 'price_per_inquiry' => 0.50),
        array('volume_start' => 501, 'volume_end' => null, 'price_per_inquiry' => 0.40)
    ));

    $tiers = get_effective_customer_tiers($customer_id, $service_id);

    assert_count(2, $tiers, 'Should return 2 tiers');
    assert_equals('default', $tiers[0]['source'], 'Source should be default');
    assert_equals('default', $tiers[1]['source'], 'Source should be default');
});

run_test('Returns empty array when no tiers exist at any level', function() {
    $service_id = create_test_service();
    $customer_id = create_test_customer();

    // No tiers created at any level

    $tiers = get_effective_customer_tiers($customer_id, $service_id);

    assert_count(0, $tiers, 'Should return empty array');
});

run_test('Multiple tiers preserved in inheritance', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();
    $customer_id = create_test_customer(array('discount_group_id' => $group_id));

    // Create default with 2 tiers
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => 100, 'price_per_inquiry' => 1.00),
        array('volume_start' => 101, 'volume_end' => null, 'price_per_inquiry' => 0.90)
    ));

    // Create group with 3 tiers
    create_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => 100, 'price_per_inquiry' => 0.80),
        array('volume_start' => 101, 'volume_end' => 500, 'price_per_inquiry' => 0.70),
        array('volume_start' => 501, 'volume_end' => null, 'price_per_inquiry' => 0.60)
    ));

    $tiers = get_effective_customer_tiers($customer_id, $service_id);

    assert_count(3, $tiers, 'Should return all 3 group tiers');
    assert_float_equals(0.80, (float)$tiers[0]['price_per_inquiry'], 0.01, 'First tier');
    assert_float_equals(0.70, (float)$tiers[1]['price_per_inquiry'], 0.01, 'Second tier');
    assert_float_equals(0.60, (float)$tiers[2]['price_per_inquiry'], 0.01, 'Third tier');
});

// ============================================================
// get_effective_group_tiers() tests
// ============================================================

run_test('Group with no tiers falls back to defaults', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();

    // Create default tiers only
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 1.00)
    ));

    $tiers = get_effective_group_tiers($group_id, $service_id);

    assert_count(1, $tiers, 'Should return 1 tier');
    assert_float_equals(1.00, (float)$tiers[0]['price_per_inquiry'], 0.01, 'Should use default price');
    assert_equals('default', $tiers[0]['source'], 'Source should be default');
});

run_test('Group override takes precedence over defaults', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();

    // Create default tiers
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 1.00)
    ));

    // Create group override
    create_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => null, 'price_per_inquiry' => 0.75)
    ));

    $tiers = get_effective_group_tiers($group_id, $service_id);

    assert_count(1, $tiers, 'Should return 1 tier');
    assert_float_equals(0.75, (float)$tiers[0]['price_per_inquiry'], 0.01, 'Should use group price');
    assert_equals('group', $tiers[0]['source'], 'Source should be group');
});

// ============================================================
// get_effective_billing_flags() tests
// ============================================================

run_test('Returns system defaults when no flags configured', function() {
    $service_id = create_test_service();

    $flags = get_effective_billing_flags($service_id, 'TEST_CODE');

    // System defaults: by_hit=1, zero_null=0, bav_by_trans=0
    assert_equals(1, (int)$flags['by_hit'], 'by_hit should default to 1');
    assert_equals(0, (int)$flags['zero_null'], 'zero_null should default to 0');
    assert_equals(0, (int)$flags['bav_by_trans'], 'bav_by_trans should default to 0');
    assert_equals('system_default', $flags['source'], 'Source should be system_default');
});

run_test('Default level flags override system defaults', function() {
    $service_id = create_test_service();

    // Set default level flags
    create_billing_flags('default', null, $service_id, 'TEST_CODE', 0, 1, 1);

    $flags = get_effective_billing_flags($service_id, 'TEST_CODE');

    assert_equals(0, (int)$flags['by_hit'], 'by_hit should be 0');
    assert_equals(1, (int)$flags['zero_null'], 'zero_null should be 1');
    assert_equals(1, (int)$flags['bav_by_trans'], 'bav_by_trans should be 1');
    assert_equals('default', $flags['source'], 'Source should be default');
});

run_test('Group level flags override default level', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();

    // Set default level flags
    create_billing_flags('default', null, $service_id, 'TEST_CODE', 1, 0, 0);

    // Set group level flags (different)
    create_billing_flags('group', $group_id, $service_id, 'TEST_CODE', 0, 1, 0);

    $flags = get_effective_billing_flags($service_id, 'TEST_CODE', null, $group_id);

    assert_equals(0, (int)$flags['by_hit'], 'by_hit should be 0 (group override)');
    assert_equals(1, (int)$flags['zero_null'], 'zero_null should be 1 (group override)');
    assert_equals(0, (int)$flags['bav_by_trans'], 'bav_by_trans should be 0');
    assert_equals('group', $flags['source'], 'Source should be group');
});

run_test('Customer level flags override group level', function() {
    $service_id = create_test_service();
    $group_id = create_test_group();
    $customer_id = create_test_customer(array('discount_group_id' => $group_id));

    // Set default level flags
    create_billing_flags('default', null, $service_id, 'TEST_CODE', 1, 0, 0);

    // Set group level flags
    create_billing_flags('group', $group_id, $service_id, 'TEST_CODE', 0, 1, 0);

    // Set customer level flags (different again)
    create_billing_flags('customer', $customer_id, $service_id, 'TEST_CODE', 1, 1, 1);

    $flags = get_effective_billing_flags($service_id, 'TEST_CODE', $customer_id, $group_id);

    assert_equals(1, (int)$flags['by_hit'], 'by_hit should be 1 (customer override)');
    assert_equals(1, (int)$flags['zero_null'], 'zero_null should be 1 (customer override)');
    assert_equals(1, (int)$flags['bav_by_trans'], 'bav_by_trans should be 1 (customer override)');
    assert_equals('customer', $flags['source'], 'Source should be customer');
});

run_test('Customer without group still gets default flags', function() {
    $service_id = create_test_service();
    $customer_id = create_test_customer();

    // Set default level flags
    create_billing_flags('default', null, $service_id, 'TEST_CODE', 0, 1, 1);

    // No group, no customer override
    $flags = get_effective_billing_flags($service_id, 'TEST_CODE', $customer_id, null);

    assert_equals(0, (int)$flags['by_hit'], 'Should use default flags');
    assert_equals('default', $flags['source'], 'Source should be default');
});

run_test('Different EFX codes have independent flags', function() {
    $service_id = create_test_service();

    // Set flags for CODE_A
    create_billing_flags('default', null, $service_id, 'CODE_A', 1, 0, 0);

    // Set different flags for CODE_B
    create_billing_flags('default', null, $service_id, 'CODE_B', 0, 1, 1);

    $flags_a = get_effective_billing_flags($service_id, 'CODE_A');
    $flags_b = get_effective_billing_flags($service_id, 'CODE_B');

    assert_equals(1, (int)$flags_a['by_hit'], 'CODE_A by_hit should be 1');
    assert_equals(0, (int)$flags_b['by_hit'], 'CODE_B by_hit should be 0');
});

// ============================================================
// Full inheritance scenario tests
// ============================================================

run_test('Full inheritance scenario - standard test data', function() {
    // Use the standard test scenario
    $ids = create_standard_test_scenario();

    $service_id = $ids['service_id'];

    // Customer with no group should get defaults
    $tiers = get_effective_customer_tiers($ids['customer_no_group'], $service_id);
    assert_float_equals(1.00, (float)$tiers[0]['price_per_inquiry'], 0.01, 'No group customer gets default price');
    assert_equals('default', $tiers[0]['source'], 'Source is default');

    // Customer in group should get group pricing
    $tiers = get_effective_customer_tiers($ids['customer_in_group'], $service_id);
    assert_float_equals(0.90, (float)$tiers[0]['price_per_inquiry'], 0.01, 'Group customer gets group price');
    assert_equals('group', $tiers[0]['source'], 'Source is group');

    // Customer with override should get their own pricing
    $tiers = get_effective_customer_tiers($ids['customer_with_override'], $service_id);
    assert_float_equals(0.80, (float)$tiers[0]['price_per_inquiry'], 0.01, 'Override customer gets their price');
    assert_equals('customer', $tiers[0]['source'], 'Source is customer');
});

// ============================================================
// Commission rate inheritance tests
// ============================================================

run_test('LMS with no rate inherits default commission', function() {
    $lms_id = create_test_lms(array('commission_rate' => null));
    set_default_commission_rate(10.0);

    $rate = get_effective_commission_rate($lms_id);

    assert_float_equals(10.0, $rate, 0.01, 'Should inherit default rate');
});

run_test('LMS with own rate overrides default', function() {
    $lms_id = create_test_lms(array('commission_rate' => 15.0));
    set_default_commission_rate(10.0);

    $rate = get_effective_commission_rate($lms_id);

    assert_float_equals(15.0, $rate, 0.01, 'Should use LMS rate');
});

run_test('Default commission rate can be zero', function() {
    $lms_id = create_test_lms(array('commission_rate' => null));
    set_default_commission_rate(0);

    $rate = get_effective_commission_rate($lms_id);

    assert_float_equals(0, $rate, 0.01, 'Should return 0');
});

// Print summary
test_summary();
