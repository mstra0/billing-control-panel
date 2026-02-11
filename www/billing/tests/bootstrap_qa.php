<?php
/**
 * QA Mode Bootstrap
 *
 * Extends the main bootstrap.php with QA-specific setup.
 * This file includes bootstrap.php to get all test functions,
 * then adds QA mode initialization.
 *
 * Usage:
 *   // In a test file, for QA mode only:
 *   if ($_is_qa_mode) {
 *       require_once __DIR__ . '/../bootstrap_qa.php';
 *   }
 */

// QA Mode flag - distinguishes from CLI test mode
if (!defined("QA_MODE")) {
    define("QA_MODE", true);
}

// Suppress the "Test framework loaded" output from bootstrap.php
ob_start();

// Include the main bootstrap - this gives us ALL test functions
require_once __DIR__ . "/bootstrap.php";

ob_end_clean();

// ============================================================
// QA MODE INITIALIZATION
// ============================================================
// Reset database and fixtures for each QA page load
// This ensures tests always start with a fresh state
setup_test_database();
reset_fixture_counters();
