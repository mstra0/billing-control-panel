<?php
/**
 * Test Fixtures
 *
 * Factory functions to create test data.
 * All functions return the ID of the created entity.
 */

// Track auto-increment IDs for factories
$_fixture_counters = array(
    'customer' => 0,
    'group' => 0,
    'service' => 0,
    'lms' => 0
);

/**
 * Create a test customer
 */
function create_test_customer($overrides = array()) {
    global $_fixture_counters;
    $_fixture_counters['customer']++;

    $defaults = array(
        'id' => $_fixture_counters['customer'],
        'name' => 'Test Customer ' . $_fixture_counters['customer'],
        'status' => 'active',
        'discount_group_id' => null,
        'contract_start_date' => date('Y-m-d'),
        'lms_id' => null
    );

    $data = array_merge($defaults, $overrides);

    sqlite_execute(
        "INSERT INTO customers (id, name, status, discount_group_id, contract_start_date, lms_id)
         VALUES (?, ?, ?, ?, ?, ?)",
        array($data['id'], $data['name'], $data['status'], $data['discount_group_id'], $data['contract_start_date'], $data['lms_id'])
    );

    return $data['id'];
}

/**
 * Create a test discount group
 */
function create_test_group($overrides = array()) {
    global $_fixture_counters;
    $_fixture_counters['group']++;

    $defaults = array(
        'id' => $_fixture_counters['group'],
        'name' => 'Test Group ' . $_fixture_counters['group']
    );

    $data = array_merge($defaults, $overrides);

    sqlite_execute(
        "INSERT INTO discount_groups (id, name) VALUES (?, ?)",
        array($data['id'], $data['name'])
    );

    return $data['id'];
}

/**
 * Create a test service
 */
function create_test_service($overrides = array()) {
    global $_fixture_counters;
    $_fixture_counters['service']++;

    $defaults = array(
        'id' => $_fixture_counters['service'],
        'name' => 'Test Service ' . $_fixture_counters['service']
    );

    $data = array_merge($defaults, $overrides);

    sqlite_execute(
        "INSERT INTO services (id, name) VALUES (?, ?)",
        array($data['id'], $data['name'])
    );

    return $data['id'];
}

/**
 * Create a test LMS
 */
function create_test_lms($overrides = array()) {
    global $_fixture_counters;
    $_fixture_counters['lms']++;

    $defaults = array(
        'id' => $_fixture_counters['lms'],
        'name' => 'Test LMS ' . $_fixture_counters['lms'],
        'commission_rate' => null,
        'status' => 'active'
    );

    $data = array_merge($defaults, $overrides);

    sqlite_execute(
        "INSERT INTO lms (id, name, commission_rate, status) VALUES (?, ?, ?, ?)",
        array($data['id'], $data['name'], $data['commission_rate'], $data['status'])
    );

    return $data['id'];
}

/**
 * Create default pricing tiers for a service
 */
function create_default_tiers($service_id, $tiers = null) {
    if ($tiers === null) {
        // Default: 3-tier pricing
        $tiers = array(
            array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.50),
            array('volume_start' => 1001, 'volume_end' => 5000, 'price_per_inquiry' => 0.40),
            array('volume_start' => 5001, 'volume_end' => null, 'price_per_inquiry' => 0.30)
        );
    }

    save_default_tiers($service_id, $tiers);
}

/**
 * Create group pricing tiers for a service
 */
function create_group_tiers($group_id, $service_id, $tiers) {
    save_group_tiers($group_id, $service_id, $tiers);
}

/**
 * Create customer pricing tiers for a service
 */
function create_customer_tiers($customer_id, $service_id, $tiers) {
    save_customer_tiers($customer_id, $service_id, $tiers);
}

/**
 * Create escalators for a customer
 */
function create_escalators($customer_id, $start_date, $escalators) {
    save_escalators($customer_id, $escalators, $start_date);
}

/**
 * Create customer settings
 */
function create_customer_settings($customer_id, $settings) {
    save_customer_settings($customer_id, $settings);
}

/**
 * Create a transaction type
 */
function create_transaction_type($type, $display_name, $efx_code, $service_id = null) {
    return save_transaction_type($type, $display_name, $efx_code, null, $service_id);
}

/**
 * Create billing flags
 */
function create_billing_flags($level, $level_id, $service_id, $efx_code, $by_hit = 1, $zero_null = 0, $bav_by_trans = 0) {
    save_billing_flags($level, $level_id, $service_id, $efx_code, $by_hit, $zero_null, $bav_by_trans);
}

/**
 * Create a complete test scenario with customer, group, service, and tiers
 * Returns array of created IDs
 */
function create_standard_test_scenario() {
    // Create service
    $service_id = create_test_service(array('name' => 'Credit Check'));

    // Create default tiers
    create_default_tiers($service_id, array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 1.00),
        array('volume_start' => 1001, 'volume_end' => 5000, 'price_per_inquiry' => 0.80),
        array('volume_start' => 5001, 'volume_end' => null, 'price_per_inquiry' => 0.60)
    ));

    // Create group with override
    $group_id = create_test_group(array('name' => 'Premium Partners'));
    create_group_tiers($group_id, $service_id, array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.90),
        array('volume_start' => 1001, 'volume_end' => 5000, 'price_per_inquiry' => 0.70),
        array('volume_start' => 5001, 'volume_end' => null, 'price_per_inquiry' => 0.50)
    ));

    // Create LMS
    $lms_id = create_test_lms(array('name' => 'Main LMS'));

    // Create customers
    $customer_no_group = create_test_customer(array(
        'name' => 'Customer No Group',
        'lms_id' => $lms_id
    ));

    $customer_in_group = create_test_customer(array(
        'name' => 'Customer In Group',
        'discount_group_id' => $group_id,
        'lms_id' => $lms_id
    ));

    $customer_with_override = create_test_customer(array(
        'name' => 'Customer With Override',
        'discount_group_id' => $group_id,
        'lms_id' => $lms_id
    ));

    // Customer override - even better pricing
    create_customer_tiers($customer_with_override, $service_id, array(
        array('volume_start' => 0, 'volume_end' => 1000, 'price_per_inquiry' => 0.80),
        array('volume_start' => 1001, 'volume_end' => null, 'price_per_inquiry' => 0.40)
    ));

    return array(
        'service_id' => $service_id,
        'group_id' => $group_id,
        'lms_id' => $lms_id,
        'customer_no_group' => $customer_no_group,
        'customer_in_group' => $customer_in_group,
        'customer_with_override' => $customer_with_override
    );
}

/**
 * Reset fixture counters (call after setup_test_database)
 */
function reset_fixture_counters() {
    global $_fixture_counters;
    $_fixture_counters = array(
        'customer' => 0,
        'group' => 0,
        'service' => 0,
        'lms' => 0
    );
}

/**
 * Set the default commission rate
 */
function set_default_commission_rate($rate) {
    save_default_commission_rate($rate);
}
