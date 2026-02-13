<?php
/**
 * phpliteadmin wrapper — uses app config for DB path and password.
 * This file lives in www/billing/ so the existing nav link works.
 */
define("API_MODE", true);
require_once __DIR__ . "/control_panel.php";

// Override phpliteadmin defaults with app-aware config
$password = PHPLITEADMIN_PASSWORD;
$directory = false;
$databases = array(
    array(
        'path' => SQLITE_DB_PATH,
        'name' => 'Billing Control Panel (' . CODE_ENVIRONMENT . ')'
    )
);

// Load the actual phpliteadmin from tools directory
require __DIR__ . "/../../tools/phpliteadmin.php";
