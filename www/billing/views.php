<?php
// ============================================================
// VIEW FUNCTIONS (Templates)
// All render_* functions that output HTML
// ============================================================

// ============================================================
// SPECIAL ERROR PAGES
// ============================================================

/**
 * Render friendly error page when shared directory is not available
 * Shows in production mode when SHARED_BASE_PATH doesn't exist
 */
function render_shared_directory_error($path)
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Required - Control Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .error-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .error-header .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        .error-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .error-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .error-body {
            padding: 40px;
        }
        .error-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            font-family: monospace;
            font-size: 13px;
        }
        .error-details .label {
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .error-details .path {
            color: #e74c3c;
            word-break: break-all;
        }
        .error-explanation {
            margin-bottom: 30px;
        }
        .error-explanation h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .error-explanation p {
            color: #666;
            margin-bottom: 15px;
        }
        .error-explanation ul {
            color: #666;
            margin-left: 20px;
        }
        .error-explanation li {
            margin-bottom: 8px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .btn .icon {
            margin-right: 8px;
            font-size: 18px;
        }
        .mode-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .or-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
        }
        .or-divider::before,
        .or-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #ddd;
        }
        .or-divider span {
            padding: 0 15px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-header">
            <div class="icon">&#128193;</div>
            <h1>Shared Directory Not Found</h1>
            <p>The production shared drive is not accessible</p>
        </div>
        <div class="error-body">
            <div class="mode-badge">&#9888; Production Mode Active</div>

            <div class="error-details">
                <div class="label">Expected Path</div>
                <div class="path"><?php echo htmlspecialchars($path); ?></div>
            </div>

            <div class="error-explanation">
                <h3>What's happening?</h3>
                <p>The Control Panel is running in <strong>production mode</strong> but can't find the shared directory where billing CSVs are stored.</p>

                <h3>Possible causes:</h3>
                <ul>
                    <li>The shared drive is not mounted</li>
                    <li>The symlink hasn't been created yet</li>
                    <li>Network connectivity issues</li>
                    <li>Permission problems</li>
                </ul>
            </div>

            <div class="btn-group">
                <form method="post" action="?action=fix_shared_directory" style="flex: 1; display: flex;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <span class="icon">&#128295;</span>
                        Fix It Automatically
                    </button>
                </form>
            </div>

            <div class="or-divider"><span>or</span></div>

            <div class="btn-group">
                <a href="?mock=1" class="btn btn-secondary" style="flex: 1;">
                    <span class="icon">&#128202;</span>
                    Use Mock Data Instead
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}

// ============================================================
// STANDARD PAGE RENDERING
// ============================================================

function render_header($title = "Control Panel")
{
    $flash = get_flash(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($title); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background: #f5f5f5;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        /* Header */
        .header {
            background: #2c3e50;
            color: #fff;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 20px; font-weight: 500; }

        /* Navigation */
        .nav {
            background: #34495e;
            padding: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .nav > a {
            display: inline-block;
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px 20px;
            transition: background 0.2s;
        }
        .nav > a:hover, .nav > a.active {
            background: #2c3e50;
        }
        .nav-group {
            position: relative;
        }
        .nav-group-label {
            display: inline-block;
            color: #ecf0f1;
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.2s;
            user-select: none;
        }
        .nav-group-label:after {
            content: " ▾";
            font-size: 10px;
            opacity: 0.7;
        }
        .nav-group:hover .nav-group-label,
        .nav-group.has-active .nav-group-label {
            background: #2c3e50;
        }
        .nav-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #2c3e50;
            min-width: 180px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        .nav-group:hover .nav-dropdown {
            display: block;
        }
        .nav-dropdown a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            padding: 10px 20px;
            transition: background 0.2s;
            border-left: 3px solid transparent;
        }
        .nav-dropdown a:hover {
            background: #34495e;
            border-left-color: #3498db;
        }
        .nav-dropdown a.active {
            background: #34495e;
            border-left-color: #3498db;
        }
        .nav-spacer {
            flex-grow: 1;
        }
        .nav-external {
            opacity: 0.7;
            font-size: 13px;
        }
        .nav-external:hover {
            opacity: 1;
        }
        .mode-indicator {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 15px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .mode-indicator.mock {
            background: #f39c12;
            color: #fff;
        }
        .mode-indicator.mock:hover {
            background: #e67e22;
        }
        .mode-indicator.production {
            background: #27ae60;
            color: #fff;
        }
        .mode-indicator.production:hover {
            background: #219a52;
        }
        .mode-indicator .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }
        .mode-indicator.mock .dot {
            background: #fff;
        }
        .mode-indicator.production .dot {
            background: #fff;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Breadcrumb */
        .breadcrumb {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb span {
            margin: 0 8px;
            color: #999;
        }

        /* Cards */
        .card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .card h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        tr:hover { background: #f8f9fa; }

        /* Buttons & Links */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .btn-info { background: #3498db; }
        .btn-info:hover { background: #2980b9; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        /* Badges */
        .badge { display: inline-block; padding: 3px 8px; font-size: 11px; font-weight: 600; border-radius: 3px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-error, .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-default { background: #e9ecef; color: #495057; }

        /* Flash Messages */
        .flash {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .flash-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .flash-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* AJAX Loading Skeletons */
        .ajax-content { min-height: 200px; position: relative; }
        .loading-skeleton { padding: 40px; text-align: center; color: #999; }
        .skeleton-bar { height: 16px; background: #e8e8e8; border-radius: 4px; margin: 12px auto;
            animation: skeleton-pulse 1.5s ease-in-out infinite; }
        .skeleton-bar.w75 { width: 75%; }
        .skeleton-bar.w50 { width: 50%; }
        .skeleton-bar.w90 { width: 90%; }
        @keyframes skeleton-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .ajax-error { padding: 20px; }

        /* Forms */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus { border-color: #3498db; outline: none; }

        /* Stats */
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-card {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card .number { font-size: 32px; font-weight: 700; color: #2c3e50; }
        .stat-card .label { color: #666; font-size: 13px; }

        /* Data Preview */
        .data-preview { overflow-x: auto; }
        .data-preview table { font-size: 13px; }
        .data-preview td { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* Utility */
        .text-muted { color: #999; }
        .text-right { text-align: right; }
        .mb-20 { margin-bottom: 20px; }

        /* Search & Filter Bar */
        .search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .search-bar input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-width: 250px;
        }
        .search-bar input[type="text"]:focus {
            border-color: #3498db;
            outline: none;
        }
        .search-bar select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            font-size: 13px;
        }
        .pagination a:hover {
            background: #f0f0f0;
            border-color: #ccc;
        }
        .pagination .active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
        }
        .pagination .ellipsis {
            border: none;
            padding: 6px 8px;
        }
        .pagination-info {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 10px;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed; top: 20px; right: 20px; z-index: 5000;
            display: flex; flex-direction: column; gap: 10px; pointer-events: none;
        }
        .toast {
            pointer-events: auto; min-width: 300px; max-width: 450px;
            padding: 14px 40px 14px 16px; border-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15); font-size: 14px; line-height: 1.4;
            position: relative; opacity: 0; transform: translateX(80px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .toast.toast-visible { opacity: 1; transform: translateX(0); }
        .toast.toast-fade-out { opacity: 0; transform: translateX(80px); }
        .toast-success { background: #d4edda; color: #155724; border-left: 4px solid #27ae60; }
        .toast-error   { background: #f8d7da; color: #721c24; border-left: 4px solid #e74c3c; }
        .toast-info    { background: #d1ecf1; color: #0c5460; border-left: 4px solid #3498db; }
        .toast-close {
            position: absolute; top: 8px; right: 10px; background: none; border: none;
            font-size: 18px; cursor: pointer; color: inherit; opacity: 0.6; padding: 0 4px;
        }
        .toast-close:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Control Panel</h1>
    </div>

    <?php
    $action = get_action();
    $config_active =
        strpos($action, "pricing_") === 0 ||
        strpos($action, "escalator") === 0 ||
        strpos($action, "business_rule") === 0 ||
        strpos($action, "lms") === 0 ||
        strpos($action, "minimums") === 0 ||
        strpos($action, "annualized") === 0;
    $data_active =
        strpos($action, "ingestion") === 0 ||
        strpos($action, "generation") === 0 ||
        $action === "billing_reports" ||
        $action === "view_billing_report" ||
        strpos($action, "export") === 0 ||
        $action === "history";
    ?>
    <div class="nav">
        <a href="?action=dashboard" <?php echo $action === "dashboard"
            ? 'class="active"'
            : ""; ?>>Dashboard</a>
        <a href="?action=calendar" <?php echo strpos($action, "calendar") === 0
            ? 'class="active"'
            : ""; ?>>Calendar</a>

        <div class="nav-group<?php echo $config_active
            ? " has-active"
            : ""; ?>">
            <span class="nav-group-label">Configuration</span>
            <div class="nav-dropdown">
                <a href="?action=pricing_customers" <?php echo strpos(
                    $action,
                    "pricing_customer",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Customers</a>
                <a href="?action=pricing_groups" <?php echo strpos(
                    $action,
                    "pricing_group",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Groups</a>
                <a href="?action=lms" <?php echo strpos($action, "lms") === 0
                    ? 'class="active"'
                    : ""; ?>>LMS</a>
                <a href="?action=escalators" <?php echo strpos(
                    $action,
                    "escalator",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Escalators</a>
                <a href="?action=business_rules" <?php echo strpos(
                    $action,
                    "business_rule",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Rules</a>
                <a href="?action=minimums" <?php echo strpos(
                    $action,
                    "minimums",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Monthly Minimums</a>
                <a href="?action=annualized" <?php echo strpos(
                    $action,
                    "annualized",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Annualized Tiers</a>
                <a href="?action=pricing_defaults" <?php echo strpos(
                    $action,
                    "pricing_defaults",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Default Pricing</a>
            </div>
        </div>

        <div class="nav-group<?php echo $data_active ? " has-active" : ""; ?>">
            <span class="nav-group-label">Data</span>
            <div class="nav-dropdown">
                <a href="?action=ingestion" <?php echo strpos(
                    $action,
                    "ingestion",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Ingestion</a>
                <a href="?action=generation" <?php echo strpos(
                    $action,
                    "generation",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Generation</a>
                <a href="?action=billing_reports" <?php echo $action ===
                    "billing_reports" || $action === "view_billing_report"
                    ? 'class="active"'
                    : ""; ?>>Billing Reports</a>
                <a href="?action=export" <?php echo strpos(
                    $action,
                    "export",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Export</a>
                <a href="?action=history" <?php echo $action === "history"
                    ? 'class="active"'
                    : ""; ?>>History</a>
                <a href="?action=billing_intelligence" <?php echo strpos(
                    $action,
                    "billing_",
                ) === 0
                    ? 'class="active"'
                    : ""; ?> style="color: #27ae60; font-weight: 600;">Billing Dashboard</a>
                <a href="?action=admin" <?php echo strpos($action, "admin") ===
                0
                    ? 'class="active"'
                    : ""; ?> style="color: #ff9800;">Admin</a>
            </div>
        </div>

        <div class="nav-spacer"></div>

        <?php if (MOCK_MODE): ?>
            <a href="?mock=0" class="mode-indicator mock" title="Click to switch to Production Mode">
                <span class="dot"></span>
                Mock Data
            </a>
        <?php else: ?>
            <a href="?mock=1" class="mode-indicator production" title="Click to switch to Mock Mode">
                <span class="dot"></span>
                Production
            </a>
        <?php endif; ?>

        <a href="tests/qa_dashboard.php" class="nav-external">QA Dashboard</a>
        <a href="phpliteadmin.php" target="_blank" class="nav-external">DB Explorer</a>
    </div>

    <div class="container">
        <?php if ($flash): ?>
            <div class="flash flash-<?php echo h($flash["type"]); ?>">
                <?php echo h($flash["message"]); ?>
            </div>
        <?php endif; ?>
<?php
}
/**
 * Render page footer/layout end
 */ function render_footer()
{
    ?>
    </div>

    <div id="toast-container" class="toast-container"></div>

    <!-- Background Job Status Modal -->
    <div id="job-modal" style="display:none;">
        <div class="job-modal-backdrop"></div>
        <div class="job-modal-content">
            <h3 id="job-modal-title">Processing...</h3>
            <div class="progress-bar" style="margin: 20px 0; height: 24px;">
                <div id="job-modal-fill" class="fill green" style="width: 0%; min-width: 0;">0%</div>
            </div>
            <div id="job-modal-step" style="font-weight: 600; margin-bottom: 10px;">Starting...</div>
            <div id="job-modal-log" style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #f8f8f8; padding: 10px; border-radius: 4px; border: 1px solid #eee;"></div>
            <div id="job-modal-result" style="display:none; margin-top: 15px;"></div>
        </div>
    </div>
    <style>
        .job-modal-backdrop {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 9998;
        }
        .job-modal-content {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #fff; border-radius: 12px; padding: 30px; width: 500px;
            max-width: 90vw; z-index: 9999; box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .job-modal-content h3 { margin: 0 0 10px 0; font-size: 18px; }
    </style>
    <script src="js/billing.js"></script>
</body>
</html>
<?php
} // ------------------------------------------------------------
// VIEW RENDERERS
// ------------------------------------------------------------
/**
 * Render dashboard view
 */
function render_dashboard($data)
{
    render_header("Dashboard - Control Panel"); ?>
    <div id="dashboard-stats" class="stats ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading dashboard...</p>
        </div>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>
        <a href="?action=pricing_defaults" class="btn">Manage Defaults</a>
        <a href="?action=pricing_groups" class="btn">Manage Groups</a>
        <a href="?action=pricing_customers" class="btn">Manage Customers</a>
        <a href="?action=upload_config" class="btn btn-success">Upload Config CSV</a>
    </div>

    <div id="dashboard-alerts" class="ajax-content"></div>
    <div id="dashboard-pending" class="ajax-content"></div>
    <div id="dashboard-reports" class="ajax-content"></div>

    <noscript>
    <div class="stats">
        <div class="stat-card"><div class="number"><?php echo $data[
            "service_count"
        ]; ?></div><div class="label">Services</div></div>
        <div class="stat-card"><div class="number"><?php echo $data[
            "group_count"
        ]; ?></div><div class="label">Discount Groups</div></div>
        <div class="stat-card"><div class="number"><?php echo $data[
            "customer_active"
        ]; ?></div><div class="label">Active Customers</div></div>
        <div class="stat-card"><div class="number"><?php echo $data[
            "customer_total"
        ]; ?></div><div class="label">Total Customers</div></div>
    </div>
    </noscript>

    <script>
    apiGet('dashboard', null, function(err, data) {
        if (err) {
            showAjaxError('dashboard-stats', err);
            return;
        }

        // Stat cards
        var html = '<div class="stat-card"><div class="number">' + escapeHtml(String(data.service_count)) + '</div><div class="label">Services</div></div>';
        html += '<div class="stat-card"><div class="number">' + escapeHtml(String(data.group_count)) + '</div><div class="label">Discount Groups</div></div>';
        html += '<div class="stat-card"><div class="number">' + escapeHtml(String(data.customer_active)) + '</div><div class="label">Active Customers</div></div>';
        html += '<div class="stat-card"><div class="number">' + escapeHtml(String(data.customer_total)) + '</div><div class="label">Total Customers</div></div>';
        var statsEl = document.getElementById('dashboard-stats');
        statsEl.className = 'stats';
        statsEl.innerHTML = html;

        // Alerts
        if (data.alerts && data.alerts.length > 0) {
            html = '<div class="card"><h2>Alerts &amp; Notifications</h2>';
            html += '<div style="display: flex; flex-direction: column; gap: 10px;">';
            for (var i = 0; i < data.alerts.length; i++) {
                var a = data.alerts[i];
                var bg = a.type === 'warning'
                    ? 'background: #fff3cd; border: 1px solid #ffc107;'
                    : 'background: #d1ecf1; border: 1px solid #bee5eb;';
                html += '<div style="padding: 12px 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; ' + bg + '">';
                html += '<span>' + a.message + '</span>';
                html += '<a href="' + escapeHtml(a.link) + '" class="btn btn-sm">View</a>';
                html += '</div>';
            }
            html += '</div></div>';
            document.getElementById('dashboard-alerts').innerHTML = html;
        }

        // Pending configs
        if (data.pending_configs && data.pending_configs.length > 0) {
            var configs = data.pending_configs.slice(0, 5);
            html = '<div class="card"><h2>Pending Configs</h2>';
            html += '<p class="text-muted mb-20">' + data.pending_configs.length + ' files awaiting processing.</p>';
            html += '<table><thead><tr><th>Filename</th><th>Size</th><th>Uploaded</th></tr></thead><tbody>';
            for (var i = 0; i < configs.length; i++) {
                html += '<tr>';
                html += '<td>' + escapeHtml(configs[i].name) + '</td>';
                html += '<td>' + formatFilesize(configs[i].size) + '</td>';
                html += '<td>' + formatDate(configs[i].modified) + '</td>';
                html += '</tr>';
            }
            html += '</tbody></table></div>';
            document.getElementById('dashboard-pending').innerHTML = html;
        }

        // Recent reports
        if (data.reports && data.reports.length > 0) {
            var reports = data.reports.slice(0, 5);
            html = '<div class="card"><h2>Recent Reports</h2>';
            html += '<table><thead><tr><th>Filename</th><th>Size</th><th>Generated</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < reports.length; i++) {
                var r = reports[i];
                html += '<tr>';
                html += '<td>' + escapeHtml(r.name) + '</td>';
                html += '<td>' + formatFilesize(r.size) + '</td>';
                html += '<td>' + formatDate(r.modified) + '</td>';
                html += '<td class="text-right">';
                html += '<a href="?action=view_report&file=' + encodeURIComponent(r.name) + '" class="btn btn-sm">View</a> ';
                html += '<a href="?action=download_report&file=' + encodeURIComponent(r.name) + '" class="btn btn-sm btn-success">Download</a>';
                html += '</td></tr>';
            }
            html += '</tbody></table>';
            if (data.reports.length > 5) {
                html += '<p style="margin-top: 15px;"><a href="?action=list_reports">View all reports &rarr;</a></p>';
            }
            html += '</div>';
            document.getElementById('dashboard-reports').innerHTML = html;
        }
    });
    </script>
<?php
} /**
 * Render list reports view
 * @deprecated Use render_billing_reports() instead
 */
function render_list_reports($data)
{
    header("Location: ?action=billing_reports");
    exit();
} /**
 * @deprecated Use render_view_billing_report() instead
 */
function render_view_report($data)
{
    header("Location: ?action=billing_reports");
    exit();
} /**
 * Render upload config form
 */
function render_upload_config($data)
{
    render_header("Upload Config - Control Panel"); ?>
    <div class="card">
        <h2>Upload Configuration CSV</h2>

        <?php if ($data["error"]): ?>
            <div class="flash flash-error"><?php echo h(
                $data["error"],
            ); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="config_file">Select CSV File</label>
                <input type="file" name="config_file" id="config_file" class="form-control" accept=".csv" required>
            </div>

            <button type="submit" class="btn btn-success">Upload Config</button>
        </form>
    </div>

    <div class="card">
        <h2>Instructions</h2>
        <p>Upload a configuration CSV file to submit for processing.</p>
        <ul style="margin: 10px 0 0 20px;">
            <li>File must be in CSV format</li>
            <li>First row must contain column headers</li>
            <li>File will be placed in pending queue for processing</li>
            <li>A copy will be archived for historical reference</li>
        </ul>
    </div>
<?php
} /**
 * Render ingestion page - upload and manage billing reports
 */
function render_ingestion($data)
{
    render_header("Ingestion - Control Panel");
    $tab = isset($data["tab"]) ? $data["tab"] : "reports";
    ?>

    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading ingestion data...</p>
        </div>
    </div>

    <script>
    (function() {
        var currentTab = <?php echo json_encode($tab); ?>;
        apiGet('ingestion', {tab: currentTab}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var el = document.getElementById('page-data');
            var html = '';

            var reports = data.reports || [];
            var driveFiles = data.drive_files || [];
            var stats = data.stats || {};
            var tab = data.tab || currentTab;

            // Count pending (not imported) drive files
            var pendingCount = 0;
            for (var p = 0; p < driveFiles.length; p++) {
                if (!driveFiles[p].imported) { pendingCount++; }
            }

            // Outer card
            html += '<div class="card">';
            html += '<h2>Billing Report Ingestion</h2>';

            // Stats row
            html += '<div style="display: flex; gap: 20px; margin-bottom: 20px;">';

            html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold;">' + escapeHtml(String(stats.total_reports || 0)) + '</div>';
            html += '<div style="color: #666; font-size: 13px;">Imported Reports</div>';
            html += '</div>';

            html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold;">' + numberFormat(stats.total_rows || 0, 0) + '</div>';
            html += '<div style="color: #666; font-size: 13px;">Total Rows</div>';
            html += '</div>';

            var pendingBg = pendingCount > 0 ? '#fff3cd' : '#d4edda';
            html += '<div style="flex: 1; background: ' + pendingBg + '; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold;">' + escapeHtml(String(pendingCount)) + '</div>';
            html += '<div style="color: #666; font-size: 13px;">Pending on Drive</div>';
            html += '</div>';

            html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 13px; color: #666;">Date Range</div>';
            if (stats.earliest && stats.earliest) {
                html += '<div>' + escapeHtml(stats.earliest) + ' to ' + escapeHtml(stats.latest) + '</div>';
            } else {
                html += '<div>No data</div>';
            }
            html += '</div>';

            html += '</div>'; // end stats row

            // Tab navigation
            html += '<div style="margin-bottom: 20px; border-bottom: 2px solid #eee;">';

            var tabs = [
                {key: 'reports', label: 'Imported Reports'},
                {key: 'upload', label: 'Upload File'},
                {key: 'drive', label: 'Import from Drive'}
            ];
            for (var t = 0; t < tabs.length; t++) {
                var isActive = (tab === tabs[t].key);
                var color = isActive ? '#3498db' : '#666';
                var border = isActive ? '#3498db' : 'transparent';
                var weight = isActive ? '600' : 'normal';
                html += '<a href="?action=ingestion&tab=' + escapeHtml(tabs[t].key) + '" style="display: inline-block; padding: 10px 20px; text-decoration: none; color: ' + color + '; border-bottom: 2px solid ' + border + '; margin-bottom: -2px; font-weight: ' + weight + ';">';
                html += escapeHtml(tabs[t].label);
                if (tabs[t].key === 'drive' && pendingCount > 0) {
                    html += ' <span class="badge badge-warning">' + escapeHtml(String(pendingCount)) + '</span>';
                }
                html += '</a>';
            }

            html += '</div>'; // end tab bar

            // Tab content
            if (tab === 'upload') {
                // ---- Upload Tab ----
                html += '<h3>Upload Billing CSV</h3>';
                html += '<form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">';
                html += '<div style="display: flex; gap: 10px; align-items: center;">';
                html += '<input type="file" name="billing_csv" accept=".csv" required style="flex: 1;">';
                html += '<button type="submit" class="btn btn-success">Upload &amp; Import</button>';
                html += '</div>';
                html += '<p class="text-muted" style="margin-top: 8px; font-size: 12px;">';
                html += 'Expected format: <code>DataX_YYYY_MM_DD_humanreadable.csv</code><br>';
                html += 'Columns: <code>y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id</code>';
                html += '</p>';
                html += '</form>';

            } else if (tab === 'drive') {
                // ---- Drive Tab ----
                html += '<h3>Import from Drive</h3>';
                html += '<p class="text-muted" style="margin-bottom: 15px;">Files available in the archive directory for import.</p>';

                if (driveFiles.length === 0) {
                    html += '<p class="text-muted">No billing files found in archive directory.</p>';
                } else {
                    html += '<form method="POST">';
                    html += '<input type="hidden" name="bulk_import" value="1">';
                    html += '<div style="margin-bottom: 15px;">';
                    html += '<button type="button" class="btn btn-sm" onclick="selectAllPending()">Select All Pending</button>';
                    html += '<button type="button" class="btn btn-sm" onclick="deselectAll()">Deselect All</button>';
                    html += '<button type="submit" class="btn btn-success" style="margin-left: 20px;">Import Selected</button>';
                    html += '</div>';

                    html += '<table>';
                    html += '<thead><tr>';
                    html += '<th style="width: 40px;"><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th>';
                    html += '<th>Filename</th>';
                    html += '<th>Size</th>';
                    html += '<th>Modified</th>';
                    html += '<th>Status</th>';
                    html += '<th class="text-right">Actions</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';

                    for (var i = 0; i < driveFiles.length; i++) {
                        var file = driveFiles[i];
                        var rowStyle = file.imported ? 'opacity: 0.6;' : '';
                        html += '<tr style="' + rowStyle + '">';

                        // Checkbox
                        html += '<td>';
                        html += '<input type="checkbox" name="selected_files[]" value="' + escapeHtml(file.filename) + '"';
                        html += ' class="file-checkbox"';
                        if (file.imported) { html += ' disabled'; }
                        html += ' data-pending="' + (file.imported ? '0' : '1') + '">';
                        html += '</td>';

                        // Filename
                        html += '<td><code>' + escapeHtml(file.filename) + '</code></td>';

                        // Size
                        var sizeKB = (file.size / 1024).toFixed(1);
                        html += '<td>' + sizeKB + ' KB</td>';

                        // Modified
                        html += '<td>' + formatDate(file.modified) + '</td>';

                        // Status
                        html += '<td>';
                        if (file.imported) {
                            html += '<span class="badge badge-success">Imported</span>';
                        } else {
                            html += '<span class="badge badge-warning">Pending</span>';
                        }
                        html += '</td>';

                        // Actions
                        html += '<td class="text-right">';
                        if (!file.imported) {
                            html += '<a href="?action=ingestion&import_file=' + encodeURIComponent(file.filename) + '" class="btn btn-sm btn-success">Import</a>';
                        } else {
                            html += '<span class="text-muted">-</span>';
                        }
                        html += '</td>';

                        html += '</tr>';
                    }

                    html += '</tbody></table>';
                    html += '</form>';
                }

            } else {
                // ---- Reports Tab (default) ----
                html += '<h3>Imported Reports</h3>';

                if (reports.length === 0) {
                    html += '<p class="text-muted">No reports imported yet. ';
                    html += '<a href="?action=ingestion&tab=drive">Import from drive</a> or ';
                    html += '<a href="?action=ingestion&tab=upload">upload a file</a>.';
                    html += '</p>';
                } else {
                    html += '<table>';
                    html += '<thead><tr>';
                    html += '<th>Type</th>';
                    html += '<th>Date</th>';
                    html += '<th>File</th>';
                    html += '<th>Rows</th>';
                    html += '<th>Imported At</th>';
                    html += '<th class="text-right">Actions</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';

                    for (var r = 0; r < reports.length; r++) {
                        var report = reports[r];
                        html += '<tr>';

                        // Type badge
                        var badgeClass = (report.report_type === 'monthly') ? 'badge-success' : 'badge-info';
                        html += '<td><span class="badge ' + badgeClass + '">' + escapeHtml(report.report_type) + '</span></td>';

                        // Date
                        html += '<td>' + escapeHtml(report.report_date) + '</td>';

                        // File
                        html += '<td><code style="font-size: 11px;">' + escapeHtml(report.file_path) + '</code></td>';

                        // Rows
                        html += '<td>' + numberFormat(report.record_count, 0) + '</td>';

                        // Imported At
                        html += '<td>' + escapeHtml(report.imported_at) + '</td>';

                        // Actions
                        html += '<td class="text-right">';
                        html += '<a href="?action=ingestion_view&id=' + escapeHtml(String(report.id)) + '" class="btn btn-sm">View</a>';
                        html += '<a href="?action=ingestion&delete=' + escapeHtml(String(report.id)) + '" class="btn btn-sm" style="background: #e74c3c;" onclick="return confirm(\'Delete this report?\');">Delete</a>';
                        html += '</td>';

                        html += '</tr>';
                    }

                    html += '</tbody></table>';
                }
            }

            html += '</div>'; // close .card

            el.innerHTML = html;
        });

        // Drive tab helper functions (defined globally so onclick attributes can find them)
        window.toggleAll = function(checkbox) {
            var checkboxes = document.querySelectorAll('.file-checkbox:not([disabled])');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = checkbox.checked;
            }
        };

        window.selectAllPending = function() {
            var checkboxes = document.querySelectorAll('.file-checkbox[data-pending="1"]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = true;
            }
        };

        window.deselectAll = function() {
            var checkboxes = document.querySelectorAll('.file-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = false;
            }
            var selectAllEl = document.getElementById('select-all');
            if (selectAllEl) { selectAllEl.checked = false; }
        };
    })();
    </script>
<?php render_footer();
} /**
 * Render single line audit view - "Show Your Work" page
 */
function render_line_audit($data)
{
    render_header("Price Audit - Control Panel"); ?>
    <!-- MathJax for LaTeX rendering -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <style>
        .audit-container { max-width: 900px; margin: 0 auto; }
        .audit-header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .audit-header h2 { margin: 0 0 10px 0; }
        .audit-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .audit-meta-item { }
        .audit-meta-item .label { font-size: 11px; color: #666; text-transform: uppercase; }
        .audit-meta-item .value { font-size: 16px; font-weight: 500; }

        .audit-step { background: white; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 15px; overflow: hidden; }
        .audit-step-header { padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .audit-step-header .step-num { background: #2196f3; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-right: 10px; }
        .audit-step-header.success .step-num { background: #4caf50; }
        .audit-step-header.error .step-num { background: #f44336; }
        .audit-step-body { padding: 16px; }

        .tier-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .tier-table th, .tier-table td { padding: 8px 12px; text-align: left; border: 1px solid #e0e0e0; }
        .tier-table th { background: #f8f9fa; font-size: 12px; text-transform: uppercase; }
        .tier-table tr.matched { background: #e8f5e9; }
        .tier-table tr.matched td { font-weight: 600; }

        .inheritance-chain { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 10px 0; }
        .inheritance-item { padding: 6px 12px; border-radius: 4px; font-size: 13px; }
        .inheritance-item.applied { background: #e8f5e9; color: #2e7d32; }
        .inheritance-item.skipped { background: #f5f5f5; color: #999; text-decoration: line-through; }
        .inheritance-arrow { color: #999; }

        .calculation-box { background: #fafafa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin: 15px 0; font-size: 16px; }
        .calculation-box .MathJax { font-size: 18px !important; }

        .comparison-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; margin: 20px 0; }
        .comparison-cell { padding: 20px; text-align: center; }
        .comparison-cell.header { background: #f8f9fa; font-weight: 600; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e0e0e0; padding: 12px; }
        .comparison-cell .amount { font-size: 24px; font-weight: bold; font-family: monospace; }
        .comparison-cell.expected { background: #e3f2fd; }
        .comparison-cell.actual { background: #fff3e0; }
        .comparison-cell.variance { background: #f5f5f5; }
        .comparison-cell.match { background: #e8f5e9; }
        .comparison-cell.mismatch { background: #ffebee; }

        .status-badge { display: inline-block; padding: 8px 20px; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .status-badge.match { background: #4caf50; color: white; }
        .status-badge.variance { background: #ff9800; color: white; }
        .status-badge.error { background: #f44336; color: white; }

        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .detail-item { background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .detail-item .label { font-size: 11px; color: #666; }
        .detail-item .value { font-weight: 500; }

        .error-box { background: #ffebee; border: 1px solid #ffcdd2; border-radius: 4px; padding: 15px; color: #c62828; }
    </style>

    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <p>Loading price audit...</p>
        </div>
    </div>

    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var lineId = params.get('id');

        apiGet('line_audit', {id: lineId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var audit = data.audit;
            var latex = data.latex;
            var variance = audit.variance || null;
            var el = document.getElementById('page-data');
            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb">';
            html += '<a href="?action=ingestion">Ingestion</a>';
            html += '<span>/</span>';
            html += '<a href="?action=ingestion_view&id=' + escapeHtml(audit.report_id + '') + '">Report ' + escapeHtml(audit.report_date) + '</a>';
            html += '<span>/</span>';
            html += 'Line Audit #' + escapeHtml(audit.line_id + '');
            html += '</div>';

            html += '<div class="audit-container">';

            // Audit Header
            html += '<div class="audit-header">';
            html += '<h2>Price Calculation Audit</h2>';
            html += '<div class="audit-meta">';

            html += '<div class="audit-meta-item">';
            html += '<div class="label">Report Date</div>';
            html += '<div class="value">' + escapeHtml(audit.report_date) + ' (' + escapeHtml(audit.report_type) + ')</div>';
            html += '</div>';

            html += '<div class="audit-meta-item">';
            html += '<div class="label">Customer</div>';
            html += '<div class="value">' + escapeHtml(audit.customer_name ? audit.customer_name : 'ID: ' + audit.customer_id) + '</div>';
            html += '</div>';

            html += '<div class="audit-meta-item">';
            html += '<div class="label">Service</div>';
            html += '<div class="value">' + escapeHtml(audit.service_name ? audit.service_name : audit.efx_code) + '</div>';
            html += '</div>';

            html += '<div class="audit-meta-item">';
            html += '<div class="label">Transaction Count</div>';
            html += '<div class="value">' + numberFormat(audit.count, 0) + '</div>';
            html += '</div>';

            html += '</div>'; // audit-meta
            html += '</div>'; // audit-header

            // Error box
            if (audit.errors && audit.errors.length > 0) {
                html += '<div class="error-box">';
                html += '<strong>Calculation Errors:</strong>';
                html += '<ul style="margin: 10px 0 0 0; padding-left: 20px;">';
                for (var e = 0; e < audit.errors.length; e++) {
                    html += '<li>' + escapeHtml(audit.errors[e]) + '</li>';
                }
                html += '</ul>';
                html += '</div>';
            }

            // Calculation Steps
            html += '<h3 style="margin-top: 30px;">Calculation Steps</h3>';

            var steps = audit.steps || [];
            for (var s = 0; s < steps.length; s++) {
                var step = steps[s];
                var headerClass = step.success ? 'success' : 'error';
                var stepLabel = step.name.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });

                html += '<div class="audit-step">';
                html += '<div class="audit-step-header ' + headerClass + '">';
                html += '<div>';
                html += '<span class="step-num">' + escapeHtml(step.step + '') + '</span>';
                html += '<strong>' + escapeHtml(stepLabel) + '</strong>';
                html += '<span style="color: #666; margin-left: 10px;">' + escapeHtml(step.description) + '</span>';
                html += '</div>';
                if (step.success) {
                    html += '<span style="color: #4caf50;">&#10003;</span>';
                } else {
                    html += '<span style="color: #f44336;">&#10007;</span>';
                }
                html += '</div>'; // audit-step-header

                html += '<div class="audit-step-body">';

                // Step body varies by step name
                if (step.name === 'customer_lookup' && step.success) {
                    html += '<div class="detail-grid">';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Customer ID</div>';
                    html += '<div class="value">' + escapeHtml(step.result.customer_id + '') + '</div>';
                    html += '</div>';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Customer Name</div>';
                    html += '<div class="value">' + escapeHtml(step.result.customer_name) + '</div>';
                    html += '</div>';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Discount Group</div>';
                    html += '<div class="value">' + (step.result.group_name ? escapeHtml(step.result.group_name) : '<em>None</em>') + '</div>';
                    html += '</div>';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Status</div>';
                    html += '<div class="value">' + escapeHtml(step.result.status) + '</div>';
                    html += '</div>';
                    html += '</div>'; // detail-grid

                } else if (step.name === 'service_mapping' && step.success) {
                    html += '<div class="detail-grid">';
                    html += '<div class="detail-item">';
                    html += '<div class="label">EFX Code</div>';
                    html += '<div class="value"><code>' + escapeHtml(step.efx_code) + '</code></div>';
                    html += '</div>';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Mapped Service</div>';
                    html += '<div class="value">' + escapeHtml(step.result.service_name) + ' (ID: ' + escapeHtml(step.result.service_id + '') + ')</div>';
                    html += '</div>';
                    html += '</div>'; // detail-grid

                } else if (step.name === 'tier_resolution' && step.success) {
                    // Inheritance chain
                    html += '<p><strong>Inheritance Chain:</strong></p>';
                    html += '<div class="inheritance-chain">';
                    var chain = step.inheritance_chain || [];
                    for (var ci = 0; ci < chain.length; ci++) {
                        if (ci > 0) {
                            html += '<span class="inheritance-arrow">&rarr;</span>';
                        }
                        var item = chain[ci];
                        var itemClass = item.applied ? 'applied' : 'skipped';
                        html += '<span class="inheritance-item ' + itemClass + '">';
                        html += escapeHtml(item.level.charAt(0).toUpperCase() + item.level.slice(1));
                        if (item.group_name) {
                            html += ' (' + escapeHtml(item.group_name) + ')';
                        }
                        if (item.applied && item.effective_date) {
                            html += '<br><small>as of ' + escapeHtml(item.effective_date) + '</small>';
                        }
                        html += '</span>';
                    }
                    html += '</div>'; // inheritance-chain

                    // Tier table
                    html += '<p style="margin-top: 15px;"><strong>Resolved Tiers (source: ' + escapeHtml(step.source) + '):</strong></p>';
                    html += '<table class="tier-table">';
                    html += '<thead><tr>';
                    html += '<th>Volume Start</th>';
                    html += '<th>Volume End</th>';
                    html += '<th>Price per Inquiry</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';
                    var tiers = step.tiers || [];
                    for (var ti = 0; ti < tiers.length; ti++) {
                        var tier = tiers[ti];
                        html += '<tr>';
                        html += '<td>' + numberFormat(tier.volume_start, 0) + '</td>';
                        html += '<td>' + (tier.volume_end ? numberFormat(tier.volume_end, 0) : 'Unlimited') + '</td>';
                        html += '<td>$' + numberFormat(tier.price, 4) + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';

                } else if (step.name === 'tier_matching' && step.success) {
                    html += '<div class="detail-grid">';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Transaction Volume</div>';
                    html += '<div class="value">' + numberFormat(step.volume, 0) + '</div>';
                    html += '</div>';
                    html += '<div class="detail-item">';
                    html += '<div class="label">Matched Tier</div>';
                    var matchedEnd = (step.matched_tier.volume_end !== null && step.matched_tier.volume_end !== undefined && !isNaN(Number(step.matched_tier.volume_end)))
                        ? numberFormat(step.matched_tier.volume_end, 0)
                        : 'Unlimited';
                    html += '<div class="value">' + numberFormat(step.matched_tier.volume_start, 0) + ' - ' + matchedEnd + '</div>';
                    html += '</div>';
                    html += '</div>'; // detail-grid

                    // Base price box (green)
                    html += '<div style="margin-top: 15px; padding: 15px; background: #e8f5e9; border-radius: 4px; text-align: center;">';
                    html += '<div style="font-size: 12px; color: #666; text-transform: uppercase;">Base Price</div>';
                    html += '<div style="font-size: 28px; font-weight: bold; color: #2e7d32;">$' + numberFormat(step.base_price, 4) + '</div>';
                    html += '</div>';

                } else if (step.name === 'escalator_calculation' && step.success) {
                    if (step.has_escalator) {
                        html += '<div class="detail-grid">';
                        html += '<div class="detail-item">';
                        html += '<div class="label">Contract Start</div>';
                        html += '<div class="value">' + escapeHtml(step.contract_start) + '</div>';
                        html += '</div>';
                        html += '<div class="detail-item">';
                        html += '<div class="label">Current Year</div>';
                        html += '<div class="value">Year ' + escapeHtml(step.current_year + '');
                        if (step.delay_months > 0) {
                            html += ' <small>(+' + escapeHtml(step.delay_months + '') + ' mo delay)</small>';
                        }
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="detail-item">';
                        html += '<div class="label">Escalator %</div>';
                        html += '<div class="value">' + (step.escalator_percentage > 0 ? '+' + escapeHtml(step.escalator_percentage + '') + '%' : '0%') + '</div>';
                        html += '</div>';
                        html += '<div class="detail-item">';
                        html += '<div class="label">Fixed Adjustment</div>';
                        var fixedAdj = step.fixed_adjustment;
                        var fixedAdjStr;
                        if (fixedAdj != 0) {
                            fixedAdjStr = (fixedAdj > 0 ? '+' : '') + '$' + numberFormat(fixedAdj, 4);
                        } else {
                            fixedAdjStr = 'None';
                        }
                        html += '<div class="value">' + fixedAdjStr + '</div>';
                        html += '</div>';
                        html += '</div>'; // detail-grid
                    } else {
                        html += '<p style="color: #666;"><em>No escalator configured for this customer</em></p>';
                    }

                    // Adjusted price box (orange)
                    html += '<div style="margin-top: 15px; padding: 15px; background: #fff3e0; border-radius: 4px; text-align: center;">';
                    html += '<div style="font-size: 12px; color: #666; text-transform: uppercase;">Adjusted Price</div>';
                    html += '<div style="font-size: 28px; font-weight: bold; color: #e65100;">$' + numberFormat(step.adjusted_price, 4) + '</div>';
                    html += '<div style="font-size: 12px; color: #666; margin-top: 5px;">' + escapeHtml(step.calculation) + '</div>';
                    html += '</div>';

                } else if (step.name === 'revenue_calculation' && step.success) {
                    // Expected revenue box (blue)
                    html += '<div style="padding: 20px; background: #e3f2fd; border-radius: 4px; text-align: center;">';
                    html += '<div style="font-size: 12px; color: #666; text-transform: uppercase;">Expected Revenue</div>';
                    html += '<div style="font-size: 32px; font-weight: bold; color: #1565c0;">$' + numberFormat(step.expected_revenue, 2) + '</div>';
                    html += '<div style="font-size: 14px; color: #666; margin-top: 5px;">';
                    html += '$' + numberFormat(step.unit_price, 4) + ' &times; ' + numberFormat(step.count, 0) + ' = $' + numberFormat(step.expected_revenue, 2);
                    html += '</div>';
                    html += '</div>';

                } else if (!step.success) {
                    // Error step
                    html += '<div class="error-box">';
                    html += escapeHtml(step.error ? step.error : 'Unknown error');
                    html += '</div>';

                } else {
                    // Default: JSON dump
                    html += '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">';
                    html += escapeHtml(JSON.stringify(step, null, 2));
                    html += '</pre>';
                }

                html += '</div>'; // audit-step-body
                html += '</div>'; // audit-step
            }

            // LaTeX calculation box
            if (!audit.errors || audit.errors.length === 0) {
                html += '<h3 style="margin-top: 30px;">Mathematical Representation</h3>';
                html += '<div class="calculation-box">';
                html += '\\[';
                html += latex;
                html += '\\]';
                html += '</div>';
            }

            // Variance comparison grid
            if (variance) {
                html += '<h3 style="margin-top: 30px;">Verification: Expected vs Actual</h3>';

                html += '<div class="comparison-grid">';

                // Header row
                html += '<div class="comparison-cell header">Expected (Our Calculation)</div>';
                html += '<div class="comparison-cell header">Actual (Their CSV)</div>';
                html += '<div class="comparison-cell header">Variance</div>';

                // Unit Price row
                html += '<div class="comparison-cell expected">';
                html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Unit Price</div>';
                html += '<div class="amount">$' + numberFormat(audit.expected_unit_price, 4) + '</div>';
                html += '</div>';
                html += '<div class="comparison-cell actual">';
                html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Unit Price</div>';
                html += '<div class="amount">$' + numberFormat(audit.actual_unit_price, 4) + '</div>';
                html += '</div>';
                var varUnitClass = variance.is_match ? 'match' : 'mismatch';
                html += '<div class="comparison-cell ' + varUnitClass + '">';
                html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Difference</div>';
                html += '<div class="amount">' + (variance.unit_price >= 0 ? '+' : '') + '$' + numberFormat(variance.unit_price, 4) + '</div>';
                html += '</div>';

                // Revenue row
                html += '<div class="comparison-cell expected">';
                html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Revenue</div>';
                html += '<div class="amount">$' + numberFormat(audit.expected_revenue, 2) + '</div>';
                html += '</div>';
                html += '<div class="comparison-cell actual">';
                html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Revenue</div>';
                html += '<div class="amount">$' + numberFormat(audit.actual_revenue, 2) + '</div>';
                html += '</div>';
                html += '<div class="comparison-cell ' + varUnitClass + '">';
                html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Difference</div>';
                html += '<div class="amount">' + (variance.revenue >= 0 ? '+' : '') + '$' + numberFormat(variance.revenue, 2) + '</div>';
                html += '</div>';

                html += '</div>'; // comparison-grid

                // Status badge
                html += '<div style="text-align: center; margin: 30px 0;">';
                if (variance.status === 'MATCH') {
                    html += '<span class="status-badge match">VERIFIED - Prices Match</span>';
                } else if (variance.status === 'VARIANCE') {
                    html += '<span class="status-badge variance">VARIANCE DETECTED - ' + numberFormat(Math.abs(variance.unit_price_pct), 2) + '% difference</span>';
                } else {
                    html += '<span class="status-badge error">CALCULATION ERROR</span>';
                }
                html += '</div>';
            }

            // Navigation
            html += '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">';
            html += '<a href="?action=ingestion_view&id=' + escapeHtml(audit.report_id + '') + '" class="btn">Back to Report</a>';
            html += ' <a href="?action=report_audit&id=' + escapeHtml(audit.report_id + '') + '" class="btn">Audit All Lines</a>';
            html += '</div>';

            html += '</div>'; // audit-container

            el.innerHTML = html;

            // Trigger MathJax typesetting after DOM update
            if (window.MathJax && MathJax.typesetPromise) {
                MathJax.typesetPromise();
            }
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render report-level audit summary
 */
function render_report_audit($data)
{
    render_header("Report Audit - Control Panel"); ?>
    <style>
        .audit-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .audit-summary-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .audit-summary-card .number { font-size: 32px; font-weight: bold; }
        .audit-summary-card .label { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .audit-summary-card.matches { background: #e8f5e9; }
        .audit-summary-card.matches .number { color: #2e7d32; }
        .audit-summary-card.variances { background: #fff3e0; }
        .audit-summary-card.variances .number { color: #e65100; }
        .audit-summary-card.errors { background: #ffebee; }
        .audit-summary-card.errors .number { color: #c62828; }

        .audit-line-row { cursor: pointer; }
        .audit-line-row:hover { background: #f5f5f5; }
        .audit-line-row.match td { background: #e8f5e9; }
        .audit-line-row.variance td { background: #fff3e0; }
        .audit-line-row.error td { background: #ffebee; }
    </style>

    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading audit...</p>
        </div>
    </div>

    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var reportId = params.get('id');
        if (!reportId) {
            showAjaxError('page-data', 'No report ID specified');
            return;
        }
        apiGet('report_audit', {id: reportId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var audit = data.audit;
            var lines = audit.lines || [];
            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb">';
            html += '<a href="?action=ingestion">Ingestion</a>';
            html += '<span>/</span>';
            html += '<a href="?action=ingestion_view&id=' + audit.report_id + '">Report ' + escapeHtml(audit.report_date) + '</a>';
            html += '<span>/</span>';
            html += 'Full Audit';
            html += '</div>';

            // Card wrapper
            html += '<div class="card">';
            html += '<h2>Report Audit: ' + escapeHtml(audit.report_date) + ' (' + escapeHtml(audit.report_type) + ')</h2>';

            // Summary cards (4-grid)
            html += '<div class="audit-summary">';
            html += '<div class="audit-summary-card">';
            html += '<div class="number">' + audit.total_lines + '</div>';
            html += '<div class="label">Total Lines</div>';
            html += '</div>';
            html += '<div class="audit-summary-card matches">';
            html += '<div class="number">' + audit.matches + '</div>';
            html += '<div class="label">Matches</div>';
            html += '</div>';
            html += '<div class="audit-summary-card variances">';
            html += '<div class="number">' + audit.variances + '</div>';
            html += '<div class="label">Variances</div>';
            html += '</div>';
            html += '<div class="audit-summary-card errors">';
            html += '<div class="number">' + audit.errors + '</div>';
            html += '<div class="label">Errors</div>';
            html += '</div>';
            html += '</div>';

            // Revenue comparison (3-grid)
            var varianceBg = Math.abs(audit.total_variance) < 0.01 ? '#e8f5e9' : '#ffebee';
            var varianceSign = audit.total_variance >= 0 ? '+' : '';

            html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">';

            html += '<div style="background: #e3f2fd; padding: 15px; border-radius: 4px; text-align: center;">';
            html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Expected Revenue</div>';
            html += '<div style="font-size: 24px; font-weight: bold;">$' + numberFormat(audit.total_expected_revenue, 2) + '</div>';
            html += '</div>';

            html += '<div style="background: #fff3e0; padding: 15px; border-radius: 4px; text-align: center;">';
            html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Actual Revenue</div>';
            html += '<div style="font-size: 24px; font-weight: bold;">$' + numberFormat(audit.total_actual_revenue, 2) + '</div>';
            html += '</div>';

            html += '<div style="background: ' + varianceBg + '; padding: 15px; border-radius: 4px; text-align: center;">';
            html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Total Variance</div>';
            html += '<div style="font-size: 24px; font-weight: bold;">' + varianceSign + '$' + numberFormat(audit.total_variance, 2) + '</div>';
            html += '</div>';

            html += '</div>';

            // Line Details
            html += '<h3>Line Details</h3>';
            html += '<table>';
            html += '<thead><tr>';
            html += '<th>Line</th>';
            html += '<th>Customer</th>';
            html += '<th>Service</th>';
            html += '<th>Count</th>';
            html += '<th>Expected</th>';
            html += '<th>Actual</th>';
            html += '<th>Variance</th>';
            html += '<th>Status</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                var rowClass = 'match';
                if (line.errors && line.errors.length > 0) {
                    rowClass = 'error';
                } else if (!line.variance.is_match) {
                    rowClass = 'variance';
                }

                html += '<tr class="audit-line-row ' + rowClass + '" onclick="window.location=\'?action=line_audit&id=' + line.line_id + '\'">';
                html += '<td>#' + line.line_id + '</td>';
                html += '<td>' + escapeHtml(line.customer_name ? line.customer_name : line.customer_id) + '</td>';
                html += '<td>' + escapeHtml(line.service_name ? line.service_name : line.efx_code) + '</td>';
                html += '<td class="text-right">' + numberFormat(line.count, 0) + '</td>';

                // Expected unit price
                if (line.expected_unit_price !== null && line.expected_unit_price !== undefined) {
                    html += '<td class="text-right">$' + numberFormat(line.expected_unit_price, 4) + '</td>';
                } else {
                    html += '<td class="text-right">-</td>';
                }

                // Actual unit price
                html += '<td class="text-right">$' + numberFormat(line.actual_unit_price, 4) + '</td>';

                // Variance
                if (line.variance.unit_price !== null && line.variance.unit_price !== undefined) {
                    var vpSign = line.variance.unit_price >= 0 ? '+' : '';
                    html += '<td class="text-right">' + vpSign + '$' + numberFormat(line.variance.unit_price, 4) + '</td>';
                } else {
                    html += '<td class="text-right">-</td>';
                }

                // Status badge
                if (line.errors && line.errors.length > 0) {
                    html += '<td><span class="badge badge-error">Error</span></td>';
                } else if (line.variance.is_match) {
                    html += '<td><span class="badge badge-success">Match</span></td>';
                } else {
                    html += '<td><span class="badge badge-warning">Variance</span></td>';
                }

                html += '</tr>';
            }

            html += '</tbody></table>';
            html += '</div>'; // close card

            // Back button
            html += '<div style="margin-top: 20px;">';
            html += '<a href="?action=ingestion_view&id=' + audit.report_id + '" class="btn">Back to Report</a>';
            html += '</div>';

            document.getElementById('page-data').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render generation page - generate tier_pricing.csv
 */
function render_generation($data)
{
    render_header("Generation - Control Panel"); ?>

    <div class="card">
        <h2>Generate Tier Pricing CSV</h2>

        <div id="page-data" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading generation data...</p>
            </div>
        </div>

        <?php if (!empty($data["preview"])): ?>
        <h3>Preview (<?php echo $data["preview"]["row_count"]; ?> rows)</h3>
        <?php if (!empty($data["preview"]["errors"])): ?>
            <div class="flash flash-error"><?php echo h(
                implode(", ", $data["preview"]["errors"]),
            ); ?></div>
        <?php endif; ?>

        <?php if (!empty($data["preview"]["rows"])): ?>
            <div style="max-height: 400px; overflow: auto;">
                <table>
                    <thead>
                        <tr>
                            <?php foreach (
                                array_keys($data["preview"]["rows"][0])
                                as $col
                            ): ?>
                                <th><?php echo h($col); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (
                            array_slice($data["preview"]["rows"], 0, 50)
                            as $row
                        ): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?php echo h($val); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($data["preview"]["row_count"] > 50): ?>
                <p class="text-muted">Showing first 50 of <?php echo $data[
                    "preview"
                ]["row_count"]; ?> rows.</p>
            <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Transaction Types</h2>
        <p class="text-muted">Manage EFX code to service mappings.</p>
        <a href="?action=generation_types" class="btn">Manage Transaction Types</a>
    </div>

    <script>
    (function() {
        apiGet('generation', null, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var el = document.getElementById('page-data');
            var html = '';

            // Stats row
            html += '<div style="display: flex; gap: 20px; margin-bottom: 20px;">';
            html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold;">' + numberFormat(data.active_customers, 0) + '</div>';
            html += '<div style="color: #666; font-size: 13px;">Active Customers</div>';
            html += '</div>';
            html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold;">' + numberFormat(data.services_count, 0) + '</div>';
            html += '<div style="color: #666; font-size: 13px;">Services</div>';
            html += '</div>';
            html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">';
            html += '<div style="font-size: 24px; font-weight: bold;">' + numberFormat(data.transaction_types_count, 0) + '</div>';
            html += '<div style="color: #666; font-size: 13px;">Transaction Types</div>';
            html += '</div>';
            html += '</div>';

            // Form (traditional POST for preview/generate actions)
            html += '<form method="POST" action="?action=generation" style="margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 4px;">';
            html += '<div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">';
            html += '<div>';
            html += '<label style="display: block; margin-bottom: 5px; font-weight: 500;">As of Date</label>';
            html += '<input type="date" name="as_of_date" value="' + escapeHtml(data.as_of_date) + '" class="form-control">';
            html += '</div>';
            html += '<div>';
            html += '<label style="display: block; margin-bottom: 5px;">';
            html += '<input type="checkbox" name="include_inactive" value="1"' + (data.include_inactive ? ' checked' : '') + '>';
            html += ' Include inactive customers';
            html += '</label>';
            html += '</div>';
            html += '<div style="display: flex; gap: 10px;">';
            html += '<button type="submit" name="preview" value="1" class="btn">Preview</button>';
            html += '<button type="submit" name="action" value="save_pending" class="btn btn-success">Generate &amp; Save</button>';

            // Download CSV link using window.open
            var downloadUrl = '?action=generation&download=1&as_of_date=' + encodeURIComponent(data.as_of_date);
            if (data.include_inactive) {
                downloadUrl += '&include_inactive=1';
            }
            html += '<a href="javascript:void(0);" onclick="window.open(\'' + escapeHtml(downloadUrl) + '\', \'_blank\');" class="btn btn-info">Download CSV</a>';
            html += '</div>';
            html += '</div>';
            html += '</form>';

            // Recent Generated Files table
            if (data.pending_files && data.pending_files.length > 0) {
                html += '<h3 style="margin-top: 30px;">Recent Generated Files</h3>';
                html += '<table>';
                html += '<thead><tr><th>Filename</th><th>Size</th><th>Generated</th></tr></thead>';
                html += '<tbody>';
                for (var i = 0; i < data.pending_files.length; i++) {
                    var file = data.pending_files[i];
                    html += '<tr>';
                    html += '<td>' + escapeHtml(file.filename) + '</td>';
                    html += '<td>' + formatFilesize(file.size) + '</td>';
                    html += '<td>' + formatDate(file.modified) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            }

            el.innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render transaction types management
 */
function render_generation_types($data)
{
    render_header("Transaction Types - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=generation">Generation</a><span>/</span>Transaction Types</div>

    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading transaction types...</p>
        </div>
    </div>

    <script>
    (function() {
        apiGet('generation_types', null, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var el = document.getElementById('page-data');
            var types = data.types || [];
            var services = data.services || [];
            var html = '';

            html += '<div class="card">';
            html += '<h2>Transaction Types</h2>';
            html += '<p class="text-muted">Map EFX codes to services for billing generation.</p>';

            // Import from CSV - traditional POST form (file upload)
            html += '<h3>Import from CSV</h3>';
            html += '<form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">';
            html += '<div style="display: flex; gap: 10px; align-items: center;">';
            html += '<input type="file" name="types_csv" accept=".csv" required style="flex: 1;">';
            html += '<button type="submit" class="btn btn-success">Upload &amp; Import</button>';
            html += '</div>';
            html += '<p class="text-muted" style="margin-top: 8px; font-size: 12px;">';
            html += 'CSV format: <code>efx_code,description,service_id</code>';
            html += '</p>';
            html += '</form>';

            // Current Transaction Types table
            html += '<h3>Current Transaction Types</h3>';

            if (types.length === 0) {
                html += '<p class="text-muted">No transaction types defined.</p>';
            } else {
                html += '<table>';
                html += '<thead><tr>';
                html += '<th>EFX Code</th>';
                html += '<th>Description</th>';
                html += '<th>Service</th>';
                html += '<th class="text-right">Actions</th>';
                html += '</tr></thead>';
                html += '<tbody>';
                for (var i = 0; i < types.length; i++) {
                    var t = types[i];
                    var serviceName = t.service_name ? t.service_name : '-';
                    html += '<tr id="type-row-' + t.id + '">';
                    html += '<td><code>' + escapeHtml(t.efx_code) + '</code></td>';
                    html += '<td>' + escapeHtml(t.description) + '</td>';
                    html += '<td>' + escapeHtml(serviceName) + '</td>';
                    html += '<td class="text-right">';
                    html += '<button type="button" class="btn btn-sm" style="background: #e74c3c;" data-delete-id="' + t.id + '">Delete</button>';
                    html += '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            }

            // Add Transaction Type form
            html += '<h3 style="margin-top: 30px;">Add Transaction Type</h3>';
            html += '<form id="add-type-form">';
            html += '<div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">';

            html += '<div>';
            html += '<label style="display: block; margin-bottom: 5px;">EFX Code</label>';
            html += '<input type="text" name="efx_code" required class="form-control" style="width: 150px;">';
            html += '</div>';

            html += '<div>';
            html += '<label style="display: block; margin-bottom: 5px;">Description</label>';
            html += '<input type="text" name="description" required class="form-control" style="width: 250px;">';
            html += '</div>';

            html += '<div>';
            html += '<label style="display: block; margin-bottom: 5px;">Service</label>';
            html += '<select name="service_id" class="form-control">';
            html += '<option value="">-- None --</option>';
            for (var s = 0; s < services.length; s++) {
                html += '<option value="' + escapeHtml(String(services[s].id)) + '">' + escapeHtml(services[s].name) + '</option>';
            }
            html += '</select>';
            html += '</div>';

            html += '<button type="submit" class="btn btn-success">Add</button>';
            html += '</div>';
            html += '</form>';


            html += '</div>'; // close card

            el.innerHTML = html;

            // Attach delete handlers
            var deleteButtons = el.querySelectorAll('[data-delete-id]');
            for (var d = 0; d < deleteButtons.length; d++) {
                (function(btn) {
                    btn.addEventListener('click', function() {
                        var typeId = btn.getAttribute('data-delete-id');
                        if (!confirm('Delete this type?')) return;

                        var body = 'type_action=delete&type_id=' + encodeURIComponent(typeId);
                        apiPost('save_generation_types', body, function(err, result) {
                            if (err) {
                                showToast(err, 'error');
                            } else {
                                var row = document.getElementById('type-row-' + typeId);
                                if (row) row.parentNode.removeChild(row);
                                showToast(result.message, 'success');
                            }
                        });
                    });
                })(deleteButtons[d]);
            }

            // Attach add form handler
            var addForm = document.getElementById('add-type-form');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var efxCode = addForm.querySelector('[name="efx_code"]').value;
                    var description = addForm.querySelector('[name="description"]').value;
                    var serviceId = addForm.querySelector('[name="service_id"]').value;

                    var body = 'efx_code=' + encodeURIComponent(efxCode) +
                        '&efx_displayname=' + encodeURIComponent(description) +
                        '&type=' + encodeURIComponent(description) +
                        '&service_id=' + encodeURIComponent(serviceId);

                    apiPost('save_generation_types', body, function(err, result) {
                        if (err) {
                            showToast(err, 'error');
                        } else {
                            showToast(result.message, 'success');
                            setTimeout(function() { window.location.reload(); }, TOAST_RELOAD_DELAY_MS);
                        }
                    });
                });
            }
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render LMS edit form
 */
function render_lms_edit($data)
{
    render_header("Edit LMS - Control Panel"); ?>
    <div id="lms-edit-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading...</p>
        </div>
    </div>

    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var lmsId = urlParams.get('lms_id');

        if (!lmsId) {
            showAjaxError('lms-edit-content', 'Missing lms_id parameter');
            return;
        }
        apiGet('lms_edit', {lms_id: lmsId}, function(err, data) {
            if (err) { showAjaxError('lms-edit-content', err); return; }

            var el = document.getElementById('lms-edit-content');
            var lms = data.lms;
            var customers = data.customers;
            var defaultRate = data.default_rate;
            var effectiveRate = data.effective_rate;
            var useDefault = lms.commission_rate === null;
            var rateValue = lms.commission_rate !== null ? lms.commission_rate : defaultRate;

            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb"><a href="?action=lms">LMS</a><span>/</span>' + escapeHtml(lms.name) + '</div>';

            // Edit card
            html += '<div class="card">';
            html += '<h2>Edit LMS: ' + escapeHtml(lms.name) + '</h2>';

            html += '<form id="lms-edit-form">';
            html += '<div style="margin-bottom: 20px;">';
            html += '<label style="display: block; margin-bottom: 10px;">';
            html += '<input type="checkbox" id="use_default" value="1"' + (useDefault ? ' checked' : '') + ' onchange="document.getElementById(\'rate_input\').disabled = this.checked;">';
            html += ' Use default commission rate (' + numberFormat(defaultRate, 2) + '%)';
            html += '</label>';
            html += '<div>';
            html += '<label style="display: block; margin-bottom: 5px;">Commission Rate (%)</label>';
            html += '<input type="number" step="any" id="rate_input" value="' + escapeHtml(String(rateValue)) + '" class="form-control" style="width: 150px;"' + (useDefault ? ' disabled' : '') + '>';
            html += '</div>';
            html += '</div>';
            html += '<button type="submit" class="btn btn-success">Save</button>';
            html += '</form>';


            html += '</div>';

            // Customers table
            if (customers && customers.length > 0) {
                html += '<div class="card">';
                html += '<h2>Customers Using This LMS (' + customers.length + ')</h2>';
                html += '<table><thead><tr>';
                html += '<th>Customer ID</th>';
                html += '<th>Name</th>';
                html += '<th>Status</th>';
                html += '</tr></thead><tbody>';
                for (var i = 0; i < customers.length; i++) {
                    var cust = customers[i];
                    var badgeClass = cust.status === 'active' ? 'badge-success' : 'badge-info';
                    html += '<tr>';
                    html += '<td><code>' + escapeHtml(String(cust.id)) + '</code></td>';
                    html += '<td>' + escapeHtml(cust.name) + '</td>';
                    html += '<td><span class="badge ' + badgeClass + '">' + escapeHtml(cust.status) + '</span></td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
                html += '</div>';
            }

            el.innerHTML = html;

            // Attach form submit handler
            document.getElementById('lms-edit-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var useDefault = document.getElementById('use_default').checked;
                var body = 'lms_id=' + encodeURIComponent(lmsId);
                if (useDefault) {
                    body += '&use_default=1';
                } else {
                    body += '&commission_rate=' + encodeURIComponent(document.getElementById('rate_input').value);
                }
                apiPost('save_lms', body, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
}
/**
 * Render LMS settings
 */ function render_lms_settings($data)
{
    render_header("LMS Settings - Control Panel"); ?>
    <div id="lms-settings-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading settings...</p>
        </div>
    </div>

    <script>
    (function() {
        apiGet('lms_settings', null, function(err, data) {
            if (err) { showAjaxError('lms-settings-content', err); return; }
            var el = document.getElementById('lms-settings-content');
            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb"><a href="?action=lms">LMS</a><span>/</span>Settings</div>';

            // Card
            html += '<div class="card">';
            html += '<h2>LMS Settings</h2>';

            // Form
            html += '<form id="lms-settings-form">';
            html += '<div style="margin-bottom: 20px;">';
            html += '<label style="display: block; margin-bottom: 5px; font-weight: 500;">Default Commission Rate (%)</label>';
            html += '<input type="number" step="any" id="default_rate_input" value="' + escapeHtml(String(data.default_rate !== null ? data.default_rate : '')) + '" class="form-control" style="width: 150px;">';
            html += '<p class="text-muted" style="margin-top: 5px; font-size: 12px;">This rate is used for LMS entries that don\'t have a custom rate set.</p>';
            html += '</div>';
            html += '<button type="submit" class="btn btn-success">Save</button>';
            html += '</form>';

            html += '</div>'; // close card

            el.innerHTML = html;

            // Attach form submit handler
            document.getElementById('lms-settings-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var rate = document.getElementById('default_rate_input').value;
                apiPost('save_lms_settings', 'default_commission_rate=' + encodeURIComponent(rate), function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render LMS commission report
 */
function render_lms_report($data)
{
    render_header("LMS Commission Report - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=lms">LMS</a><span>/</span>Commission Report</div>

    <div class="card">
        <h2>Commission Report</h2>
        <div id="lms-report-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading...</p>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('year')) params.year = urlParams.get('year');
        if (urlParams.get('month')) params.month = urlParams.get('month');
        apiGet('lms_report', params, function(err, data) {
            if (err) { showAjaxError('lms-report-content', err); return; }
            var el = document.getElementById('lms-report-content');
            if (!data.lms_data || data.lms_data.length === 0) {
                el.innerHTML = '<p class="text-muted">No data available for this period.</p>';
                return;
            }
            var html = '<table><thead><tr><th>LMS</th><th class="text-right">Customers</th>';
            html += '<th class="text-right">Revenue</th><th class="text-right">Rate</th>';
            html += '<th class="text-right">Commission</th></tr></thead><tbody>';
            for (var i = 0; i < data.lms_data.length; i++) {
                var lms = data.lms_data[i];
                html += '<tr><td>' + escapeHtml(lms.name) + (lms.is_inherited ? ' <small class="text-muted">(default rate)</small>' : '') + '</td>';
                html += '<td class="text-right">' + lms.customers.length + '</td>';
                html += '<td class="text-right">$' + numberFormat(lms.revenue, 2) + '</td>';
                html += '<td class="text-right">' + numberFormat(lms.commission_rate, 2) + '%</td>';
                html += '<td class="text-right">$' + numberFormat(lms.commission, 2) + '</td></tr>';
            }
            html += '</tbody><tfoot><tr style="font-weight:bold;"><td colspan="2">Grand Total</td>';
            html += '<td class="text-right">$' + numberFormat(data.grand_totals.revenue, 2) + '</td>';
            html += '<td></td>';
            html += '<td class="text-right">$' + numberFormat(data.grand_totals.commission, 2) + '</td>';
            html += '</tr></tfoot></table>';
            el.innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render customer pricing view - color-coded effective pricing
 */
function render_customer_pricing($data)
{
    render_header("Customer Pricing View - Control Panel"); ?>

    <style>
        .pricing-source-default { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .pricing-source-group { background: #fff3e0; border-left: 4px solid #ff9800; }
        .pricing-source-customer { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .pricing-legend { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .pricing-legend-item { display: flex; align-items: center; gap: 8px; }
        .pricing-legend-color { width: 20px; height: 20px; border-radius: 3px; }
        .pricing-legend-color.default { background: #2196f3; }
        .pricing-legend-color.group { background: #ff9800; }
        .pricing-legend-color.customer { background: #4caf50; }
        .pricing-card { margin-bottom: 15px; border-radius: 4px; overflow: hidden; }
        .pricing-card-header { padding: 12px 15px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .pricing-card-body { padding: 0; }
        .pricing-card table { margin: 0; }
        .pricing-card th, .pricing-card td { padding: 8px 15px; }
        .tier-row { transition: background 0.2s; }
        .tier-row.source-default { background: #e3f2fd; }
        .tier-row.source-group { background: #fff3e0; }
        .tier-row.source-customer { background: #e8f5e9; }
        .price-cell { font-family: monospace; font-size: 14px; }
        .summary-box { padding: 15px; border-radius: 4px; text-align: center; }
        .summary-box .number { font-size: 28px; font-weight: bold; }
        .summary-box .label { font-size: 12px; color: #666; margin-top: 4px; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .settings-item { background: #f8f9fa; padding: 12px; border-radius: 4px; }
        .settings-item .label { font-size: 11px; color: #666; text-transform: uppercase; }
        .settings-item .value { font-size: 16px; font-weight: 500; margin-top: 4px; }
        .escalator-timeline { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .escalator-year { padding: 8px 12px; background: #f0f0f0; border-radius: 4px; text-align: center; min-width: 80px; }
        .escalator-year.active { background: #fff3e0; border: 2px solid #ff9800; }
        .escalator-year .year-num { font-weight: bold; }
        .escalator-year .year-pct { font-size: 13px; color: #666; }
    </style>

    <div class="breadcrumb">
        <a href="?action=pricing_customers">Customers</a><span>/</span><span id="cp-breadcrumb-name">...</span><span>/</span>Pricing View
    </div>

    <div id="cp-page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading pricing view...</p>
        </div>
    </div>
    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var custId = urlParams.get('id');
        if (!custId) { showAjaxError('cp-page-data', 'No customer ID specified'); return; }
        apiGet('customer_pricing', {id: custId}, function(err, data) {
            if (err) { showAjaxError('cp-page-data', err); return; }
            var c = data.customer, s = data.summary, st = data.settings;
            document.getElementById('cp-breadcrumb-name').textContent = c.name;
            document.title = 'Pricing View: ' + c.name + ' - Control Panel';
            var html = '';
            // Customer header card
            html += '<div class="card"><h2>' + escapeHtml(c.name);
            html += ' <span class="badge badge-' + (c.status === 'active' ? 'success' : 'info') + '" style="margin-left:10px;">' + escapeHtml(c.status) + '</span></h2>';
            if (c.group_name) {
                html += '<p style="margin-bottom:15px;"><strong>Discount Group:</strong> <a href="?action=pricing_group_edit&id=' + c.discount_group_id + '">' + escapeHtml(c.group_name) + '</a></p>';
            } else {
                html += '<p style="margin-bottom:15px;color:#666;">No discount group (inherits directly from system defaults)</p>';
            }
            html += '<div class="pricing-legend" style="background:#f8f9fa;padding:15px;border-radius:4px;">';
            html += '<strong style="margin-right:10px;">Price Source:</strong>';
            html += '<div class="pricing-legend-item"><div class="pricing-legend-color default"></div><span>System Default</span></div>';
            html += '<div class="pricing-legend-item"><div class="pricing-legend-color group"></div><span>Discount Group Override</span></div>';
            html += '<div class="pricing-legend-item"><div class="pricing-legend-color customer"></div><span>Customer Override</span></div>';
            html += '</div></div>';
            // Summary stats
            html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px;">';
            html += '<div class="summary-box" style="background:#f8f9fa;"><div class="number">' + s.total_services + '</div><div class="label">Total Services</div></div>';
            html += '<div class="summary-box" style="background:#e3f2fd;"><div class="number" style="color:#2196f3;">' + s.using_defaults + '</div><div class="label">Using Defaults</div></div>';
            html += '<div class="summary-box" style="background:#fff3e0;"><div class="number" style="color:#ff9800;">' + s.group_overrides + '</div><div class="label">Group Overrides</div></div>';
            html += '<div class="summary-box" style="background:#e8f5e9;"><div class="number" style="color:#4caf50;">' + s.customer_overrides + '</div><div class="label">Customer Overrides</div></div>';
            html += '</div>';
            // Customer Settings
            html += '<div class="card"><h2>Customer Settings</h2><div class="settings-grid">';
            html += '<div class="settings-item"><div class="label">Monthly Minimum</div><div class="value">';
            html += st.monthly_minimum ? '$' + numberFormat(st.monthly_minimum, 2) : '<span style="color:#999;">None</span>';
            html += '</div></div>';
            html += '<div class="settings-item"><div class="label">Annualized Tiers</div><div class="value">';
            if (st.uses_annualized) {
                html += '<span style="color:#4caf50;">Enabled</span>';
                if (st.annualized_start_date) html += '<br><small>Start: ' + escapeHtml(st.annualized_start_date) + '</small>';
            } else {
                html += '<span style="color:#999;">Disabled</span>';
            }
            html += '</div></div>';
            html += '<div class="settings-item"><div class="label">Look Period</div><div class="value">';
            html += st.look_period_months ? st.look_period_months + ' months' : '<span style="color:#999;">N/A</span>';
            html += '</div></div>';
            html += '<div class="settings-item"><div class="label">LMS</div><div class="value">';
            html += c.lms_id ? escapeHtml(c.lms_id) : '<span style="color:#e74c3c;">Not Assigned</span>';
            html += '</div></div></div></div>';
            // Escalators
            html += '<div class="card"><h2>Escalators</h2>';
            if (!data.escalators || data.escalators.length === 0) {
                html += '<p class="text-muted">No escalators configured for this customer.</p>';
            } else {
                var startDate = data.escalators[0].escalator_start_date;
                var currentYear = 1;
                if (startDate) {
                    var yearsSince = Math.floor((Date.now() - new Date(startDate).getTime()) / (365.25*24*60*60*1000));
                    currentYear = Math.max(1, yearsSince + 1);
                }
                var currentDelay = 0;
                for (var e = 0; e < data.escalators.length; e++) {
                    if (data.escalators[e].year_number == currentYear && data.escalators[e].total_delay) {
                        currentDelay = data.escalators[e].total_delay;
                        break;
                    }
                }
                html += '<p><strong>Contract Start:</strong> ' + escapeHtml(startDate || '');
                if (currentDelay > 0) html += ' <span class="badge badge-warning">+' + currentDelay + ' month delay applied</span>';
                html += '</p><div class="escalator-timeline">';
                for (var e = 0; e < data.escalators.length; e++) {
                    var esc = data.escalators[e];
                    html += '<div class="escalator-year ' + (esc.year_number == currentYear ? 'active' : '') + '">';
                    html += '<div class="year-num">Year ' + esc.year_number + '</div><div class="year-pct">';
                    html += esc.escalator_percentage > 0 ? '+' + esc.escalator_percentage + '%' : 'Base';
                    if (esc.fixed_adjustment != 0) {
                        html += '<br>' + (esc.fixed_adjustment > 0 ? '+' : '') + '$' + numberFormat(Math.abs(esc.fixed_adjustment), 2);
                    }
                    html += '</div></div>';
                }
                html += '</div>';
            }
            html += '</div>';
            // Service Pricing
            html += '<div class="card"><h2>Service Pricing (' + data.pricing_data.length + ' services)</h2>';
            if (data.pricing_data.length === 0) {
                html += '<p class="text-muted">No services configured.</p>';
            } else {
                var hasGroup = !!c.discount_group_id;
                for (var p = 0; p < data.pricing_data.length; p++) {
                    var pd = data.pricing_data[p];
                    var hdrClass = 'pricing-source-default';
                    if (pd.has_customer_override) hdrClass = 'pricing-source-customer';
                    else if (pd.has_group_override) hdrClass = 'pricing-source-group';
                    html += '<div class="pricing-card"><div class="pricing-card-header ' + hdrClass + '">';
                    html += '<span>' + escapeHtml(pd.service.name) + '</span>';
                    html += '<span style="font-weight:normal;font-size:12px;">' + pd.tiers.length + ' tier' + (pd.tiers.length !== 1 ? 's' : '') + '</span></div>';
                    html += '<div class="pricing-card-body"><table><thead><tr><th>Volume Range</th><th>Effective Price</th><th>Default</th>';
                    if (hasGroup) html += '<th>Group</th>';
                    html += '<th>Customer</th><th>Source</th></tr></thead><tbody>';
                    for (var t = 0; t < pd.tiers.length; t++) {
                        var tier = pd.tiers[t];
                        html += '<tr class="tier-row source-' + tier.source + '"><td>';
                        html += numberFormat(tier.volume_start, 0) + ' - ' + (tier.volume_end ? numberFormat(tier.volume_end, 0) : 'Unlimited') + '</td>';
                        html += '<td class="price-cell"><strong>$' + numberFormat(tier.price, 4) + '</strong></td>';
                        html += '<td class="price-cell" style="color:#2196f3;">' + (tier.default_price !== null ? '$' + numberFormat(tier.default_price, 4) : '-') + '</td>';
                        if (hasGroup) html += '<td class="price-cell" style="color:#ff9800;">' + (tier.group_price !== null ? '$' + numberFormat(tier.group_price, 4) : '-') + '</td>';
                        html += '<td class="price-cell" style="color:#4caf50;">' + (tier.customer_price !== null ? '$' + numberFormat(tier.customer_price, 4) : '-') + '</td>';
                        var srcColors = {default: '#2196f3', group: '#ff9800', customer: '#4caf50'};
                        var srcLabels = {default: 'Default', group: 'Group', customer: 'Customer'};
                        html += '<td><span class="badge" style="background:' + srcColors[tier.source] + ';color:white;">' + srcLabels[tier.source] + '</span></td></tr>';
                    }
                    html += '</tbody></table></div></div>';
                }
            }
            html += '</div>';
            // Action buttons
            html += '<div style="margin-top:20px;">';
            html += '<a href="?action=pricing_customer_edit&customer_id=' + c.id + '" class="btn">Edit Pricing</a> ';
            html += '<a href="?action=pricing_customer_edit&customer_id=' + c.id + '&tab=settings" class="btn">Edit Settings</a> ';
            html += '<a href="?action=escalator_edit&customer_id=' + c.id + '" class="btn">Edit Escalators</a>';
            html += '</div>';
            document.getElementById('cp-page-data').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render monthly minimums overview
 */
function render_minimums($data)
{
    render_header("Monthly Minimums - Control Panel"); ?>
    <div class="card">
        <h2>Monthly Minimums</h2>
        <p class="text-muted">Customers with monthly minimum billing amounts configured.</p>
        <div id="minimums-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading minimums...</p>
            </div>
        </div>
    </div>

    <script>
    apiGet('minimums', null, function(err, data) {
        if (err) { showAjaxError('minimums-content', err); return; }
        var el = document.getElementById('minimums-content');
        var s = data.stats || {};
        var html = '<div style="display: flex; gap: 20px; margin: 20px 0;">';
        html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;"><div style="font-size: 24px; font-weight: bold;">' + (parseInt(s.count) || 0) + '</div><div style="color: #666; font-size: 13px;">Customers with Minimums</div></div>';
        html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;"><div style="font-size: 24px; font-weight: bold;">$' + parseFloat(s.total_minimums || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div><div style="color: #666; font-size: 13px;">Total Monthly Minimums</div></div>';
        html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;"><div style="font-size: 24px; font-weight: bold;">$' + parseFloat(s.avg_minimum || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div><div style="color: #666; font-size: 13px;">Average Minimum</div></div>';
        html += '</div>';

        if (!data.customers || data.customers.length === 0) {
            html += '<p class="text-muted">No customers have monthly minimums configured.</p>';
            html += '<p><a href="?action=pricing_customers" class="btn">Go to Customers</a> to configure minimums.</p>';
        } else {
            html += '<table><thead><tr><th>Customer</th><th>Group</th><th>Status</th><th class="text-right">Monthly Minimum</th><th>Effective Date</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < data.customers.length; i++) {
                var c = data.customers[i];
                var badgeClass = c.status === 'active' ? 'badge-success' : 'badge-info';
                html += '<tr>';
                html += '<td>' + escapeHtml(c.name) + '</td>';
                html += '<td>' + escapeHtml(c.group_name || '-') + '</td>';
                html += '<td><span class="badge ' + badgeClass + '">' + escapeHtml(c.status) + '</span></td>';
                html += '<td class="text-right">$' + parseFloat(c.monthly_minimum).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td>' + escapeHtml(c.effective_date) + '</td>';
                html += '<td class="text-right"><a href="?action=pricing_customer_edit&customer_id=' + c.id + '&tab=settings" class="btn btn-sm">Edit</a></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
        }
        el.innerHTML = html;
    });
    </script>
<?php
} /**
 * Render annualized tiers overview
 */
function render_annualized($data)
{
    render_header("Annualized Tiers - Control Panel"); ?>
    <div class="card">
        <h2>Annualized Tiers</h2>
        <p class="text-muted">Customers using annualized tier calculations (volume resets annually on their start date).</p>
        <div id="annualized-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading annualized data...</p>
            </div>
        </div>
    </div>

    <script>
    apiGet('annualized', null, function(err, data) {
        if (err) { showAjaxError('annualized-content', err); return; }
        var el = document.getElementById('annualized-content');
        var s = data.stats || {};
        var resets = data.upcoming_resets || [];

        var html = '<div style="display: flex; gap: 20px; margin: 20px 0;">';
        html += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;"><div style="font-size: 24px; font-weight: bold;">' + (parseInt(s.count) || 0) + '</div><div style="color: #666; font-size: 13px;">Annualized Customers</div></div>';
        html += '<div style="flex: 1; background: #d4edda; padding: 15px; border-radius: 4px;"><div style="font-size: 24px; font-weight: bold;">' + resets.length + '</div><div style="color: #155724; font-size: 13px;">Resets in Next 30 Days</div></div>';
        html += '</div>';

        if (resets.length > 0) {
            html += '<div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;"><strong>Upcoming Resets:</strong><ul style="margin: 10px 0 0 20px; padding: 0;">';
            for (var i = 0; i < resets.length; i++) {
                html += '<li>' + escapeHtml(resets[i].customer_name) + ' - ' + escapeHtml(resets[i].reset_date) + '</li>';
            }
            html += '</ul></div>';
        }

        if (!data.customers || data.customers.length === 0) {
            html += '<p class="text-muted">No customers have annualized tiers enabled.</p>';
            html += '<p><a href="?action=pricing_customers" class="btn">Go to Customers</a> to enable annualized pricing.</p>';
        } else {
            html += '<table><thead><tr><th>Customer</th><th>Group</th><th>Status</th><th>Start Date</th><th>Look Period</th><th>Next Reset</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < data.customers.length; i++) {
                var c = data.customers[i];
                var badgeClass = c.status === 'active' ? 'badge-success' : 'badge-info';
                var resetBadge = '-';
                if (c.next_reset) {
                    var daysUntil = (new Date(c.next_reset).getTime() - Date.now()) / 86400000;
                    var rClass = daysUntil <= 30 ? 'badge-warning' : 'badge-info';
                    resetBadge = '<span class="badge ' + rClass + '">' + escapeHtml(c.next_reset) + '</span>';
                }
                html += '<tr>';
                html += '<td>' + escapeHtml(c.name) + '</td>';
                html += '<td>' + escapeHtml(c.group_name || '-') + '</td>';
                html += '<td><span class="badge ' + badgeClass + '">' + escapeHtml(c.status) + '</span></td>';
                html += '<td>' + escapeHtml(c.annualized_start_date || 'Not set') + '</td>';
                html += '<td>' + (c.look_period_months ? c.look_period_months + ' months' : '-') + '</td>';
                html += '<td>' + resetBadge + '</td>';
                html += '<td class="text-right"><a href="?action=pricing_customer_edit&customer_id=' + c.id + '&tab=settings" class="btn btn-sm">Edit</a></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
        }
        el.innerHTML = html;
    });
    </script>
<?php
} /**
 * Render list pending configs
 */
function render_list_pending($data)
{
    render_header("Pending Configs - Control Panel"); ?>
    <div class="card">
        <h2>Pending Configurations</h2>
        <p class="text-muted mb-20">Files awaiting processing by cron job.</p>

        <?php if (empty($data["configs"])): ?>
            <p class="text-muted">No pending configurations.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["configs"] as $file): ?>
                    <tr>
                        <td><?php echo h($file["name"]); ?></td>
                        <td><?php echo format_filesize($file["size"]); ?></td>
                        <td><?php echo date(
                            "Y-m-d H:i:s",
                            $file["modified"],
                        ); ?></td>
                        <td class="text-right">
                            <a href="?action=view_config&file=<?php echo urlencode(
                                $file["name"],
                            ); ?>&source=pending" class="btn btn-sm">View</a>
                            <a href="?action=download_config&file=<?php echo urlencode(
                                $file["name"],
                            ); ?>&source=pending" class="btn btn-sm btn-success">Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render list archived configs
 */
function render_list_archive($data)
{
    render_header("Archived Configs - Control Panel"); ?>
    <div class="card">
        <h2>Archived Configurations</h2>
        <p class="text-muted mb-20">Historical record of all submitted configurations.</p>

        <?php if (empty($data["configs"])): ?>
            <p class="text-muted">No archived configurations.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["configs"] as $file): ?>
                    <tr>
                        <td><?php echo h($file["name"]); ?></td>
                        <td><?php echo format_filesize($file["size"]); ?></td>
                        <td><?php echo date(
                            "Y-m-d H:i:s",
                            $file["modified"],
                        ); ?></td>
                        <td class="text-right">
                            <a href="?action=view_config&file=<?php echo urlencode(
                                $file["name"],
                            ); ?>&source=archive" class="btn btn-sm">View</a>
                            <a href="?action=download_config&file=<?php echo urlencode(
                                $file["name"],
                            ); ?>&source=archive" class="btn btn-sm btn-success">Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render view config (CSV preview)
 */
function render_view_config($data)
{
    $source_label = $data["source"] === "pending" ? "Pending" : "Archived";
    $back_action =
        $data["source"] === "pending" ? "list_pending" : "list_archive";
    render_header("View Config - Control Panel");
    ?>
    <div class="card">
        <h2>
            <?php echo h($data["filename"]); ?>
            <span class="text-muted" style="font-weight: normal; font-size: 12px;">(<?php echo $source_label; ?>)</span>
            <a href="?action=download_config&file=<?php echo urlencode(
                $data["filename"],
            ); ?>&source=<?php echo $data["source"]; ?>" class="btn btn-sm btn-success" style="float: right;">Download</a>
        </h2>
        <p class="text-muted mb-20"><?php echo $data["count"]; ?> rows</p>

        <div class="data-preview">
            <?php if (empty($data["rows"])): ?>
                <p class="text-muted">No data in this file.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($data["headers"] as $header): ?>
                                <th><?php echo h($header); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data["rows"] as $row): ?>
                        <tr>
                            <?php foreach ($data["headers"] as $header): ?>
                                <td title="<?php echo h(
                                    isset($row[$header]) ? $row[$header] : "",
                                ); ?>">
                                    <?php echo h(
                                        isset($row[$header])
                                            ? $row[$header]
                                            : "",
                                    ); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <p><a href="?action=<?php echo $back_action; ?>">&larr; Back to <?php echo $source_label; ?></a></p>
<?php
} /**
 * Render gap/overlap warning banner for tier ranges.
 * $validation comes from validate_tier_ranges().
 */
function render_tier_range_warnings($validation)
{
    if (empty($validation)) {
        return;
    }
    $has_gaps = !empty($validation["gaps"]);
    $has_overlaps = !empty($validation["overlaps"]);
    if (!$has_gaps && !$has_overlaps) {
        return;
    }
    ?>
    <div style="margin: 10px 0;">
    <?php if ($has_overlaps): ?>
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #721c24; font-size: 13px;">
            <strong>Overlap:</strong>
            <?php foreach ($validation["overlaps"] as $o): ?>
                Volume <?php echo number_format(
                    $o["start"],
                ); ?> &ndash; <?php echo $o["end"] !== null && $o["end"] !== ""
     ? number_format($o["end"])
     : "Unlimited"; ?> is covered by multiple tiers.
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($has_gaps): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #856404; font-size: 13px;">
            <strong>Gap:</strong>
            <?php foreach ($validation["gaps"] as $g): ?>
                Volume <?php echo number_format(
                    $g["start"],
                ); ?> &ndash; <?php echo number_format(
     $g["end"],
 ); ?> has no pricing.
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
    <?php
} /**
 * Render system defaults list
 */
function render_pricing_defaults($data)
{
    render_header("System Defaults - Pricing"); ?>
    <div class="card">
        <h2 style="display: flex; justify-content: space-between; align-items: center;">
            System Default Pricing
            <span style="font-size: 12px; font-weight: normal;">
                <a href="javascript:void(0)" onclick="expandAll()" class="btn btn-sm">Expand All</a>
                <a href="javascript:void(0)" onclick="collapseAll()" class="btn btn-sm">Collapse All</a>
            </span>
        </h2>
        <p class="text-muted mb-20">Base pricing for all services. Groups and customers inherit from these defaults.</p>
        <div id="defaults-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading services...</p>
            </div>
        </div>
    </div>

    <script>
    function toggleTiers(id) {
        var el = document.getElementById('tiers-' + id);
        var icon = document.getElementById('icon-' + id);
        if (el.style.display === 'none') {
            el.style.display = 'block';
            icon.innerHTML = '&#9650;';
        } else {
            el.style.display = 'none';
            icon.innerHTML = '&#9660;';
        }
    }
    function expandAll() {
        var contents = document.querySelectorAll('.tier-content');
        var icons = document.querySelectorAll('.toggle-icon');
        for (var i = 0; i < contents.length; i++) { contents[i].style.display = 'block'; }
        for (var i = 0; i < icons.length; i++) { icons[i].innerHTML = '&#9650;'; }
    }
    function collapseAll() {
        var contents = document.querySelectorAll('.tier-content');
        var icons = document.querySelectorAll('.toggle-icon');
        for (var i = 0; i < contents.length; i++) { contents[i].style.display = 'none'; }
        for (var i = 0; i < icons.length; i++) { icons[i].innerHTML = '&#9660;'; }
    }

    apiGet('pricing_defaults', null, function(err, data) {
        if (err) { showAjaxError('defaults-content', err); return; }
        var el = document.getElementById('defaults-content');
        if (!data.services || data.services.length === 0) {
            el.innerHTML = '<p class="text-muted">No services defined.</p>';
            return;
        }
        var html = '';
        for (var i = 0; i < data.services.length; i++) {
            var svc = data.services[i];
            var tiers = svc.tiers || [];
            var priceRange = '';
            if (tiers.length > 0) {
                priceRange = '<span class="text-muted" style="margin-left: 15px;">$' +
                    numberFormat(tiers[tiers.length-1].price_per_inquiry, 2) + ' - $' +
                    numberFormat(tiers[0].price_per_inquiry, 2) + '</span>';
            }
            html += '<div class="tier-section" style="border: 1px solid #eee; border-radius: 4px; margin-bottom: 15px; overflow: hidden;">';
            html += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; cursor: pointer;" onclick="toggleTiers(\'default-' + svc.id + '\')">';
            html += '<div><strong>' + escapeHtml(svc.name) + '</strong>';
            html += '<span class="text-muted" style="margin-left: 15px;">' + svc.tier_count + ' tiers</span>';
            html += priceRange + '</div>';
            html += '<div><span class="toggle-icon" id="icon-default-' + svc.id + '" style="margin-right: 10px; color: #999;">&#9650;</span>';
            html += '<a href="?action=pricing_defaults_edit&service_id=' + svc.id + '" class="btn btn-sm" onclick="event.stopPropagation();">Edit Tiers</a></div>';
            html += '</div>';
            html += '<div class="tier-content" id="tiers-default-' + svc.id + '" style="padding: 0 15px 15px 15px;">';
            if (tiers.length > 0) {
                html += '<table style="margin-top: 10px; font-size: 13px;"><thead><tr>';
                html += '<th style="padding: 6px 10px;">Volume Start</th><th style="padding: 6px 10px;">Volume End</th><th style="padding: 6px 10px;">Price Per Inquiry</th>';
                html += '</tr></thead><tbody>';
                for (var j = 0; j < tiers.length; j++) {
                    var t = tiers[j];
                    html += '<tr>';
                    html += '<td style="padding: 6px 10px;">' + numberFormat(t.volume_start) + '</td>';
                    html += '<td style="padding: 6px 10px;">' + (t.volume_end !== null ? numberFormat(t.volume_end) : '<em>Unlimited</em>') + '</td>';
                    html += '<td style="padding: 6px 10px;">$' + numberFormat(t.price_per_inquiry, 4) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted" style="margin-top: 10px;">No tiers defined.</p>';
            }
            html += '</div></div>';
        }
        el.innerHTML = html;
    });
    </script>
<?php
} /**
 * Render system defaults edit form
 */
function render_pricing_defaults_edit($data)
{
    render_header("Edit Default Pricing - Control Panel"); ?>
    <div id="pde-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading...</p>
        </div>
    </div>

    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var serviceId = urlParams.get('service_id');

        if (!serviceId) {
            showAjaxError('pde-content', 'Missing service_id parameter');
            return;
        }
        apiGet('pricing_defaults_edit', {service_id: serviceId}, function(err, data) {
            if (err) { showAjaxError('pde-content', err); return; }

            var el = document.getElementById('pde-content');
            var service = data.service;
            var tiers = data.tiers || [];
            var validation = data.validation || {};

            var html = '';

            // Card with heading and help text
            html += '<div class="card">';
            html += '<h2>Edit Default Pricing: ' + escapeHtml(service.name) + '</h2>';
            html += '<p class="text-muted mb-20">Define volume-based pricing tiers. Leave \'Volume End\' empty for unlimited.</p>';

            // Form
            html += '<form id="tier-form">';

            // Tier table
            html += '<table id="tiers-table">';
            html += '<thead><tr>';
            html += '<th>Volume Start</th>';
            html += '<th>Volume End</th>';
            html += '<th>Price Per Inquiry</th>';
            html += '<th></th>';
            html += '</tr></thead>';
            html += '<tbody>';

            if (tiers.length === 0) {
                // One empty row if no tiers
                html += '<tr class="tier-row">';
                html += '<td><input type="number" name="volume_start[]" class="form-control" value="0" min="0" required></td>';
                html += '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>';
                html += '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="any" min="0" required></td>';
                html += '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                html += '</tr>';
            } else {
                for (var i = 0; i < tiers.length; i++) {
                    var t = tiers[i];
                    html += '<tr class="tier-row">';
                    html += '<td><input type="number" name="volume_start[]" class="form-control" value="' + escapeHtml(String(t.volume_start)) + '" min="0" required></td>';
                    html += '<td><input type="number" name="volume_end[]" class="form-control" value="' + (t.volume_end !== null ? escapeHtml(String(t.volume_end)) : '') + '" placeholder="Unlimited" min="0"></td>';
                    html += '<td><input type="number" name="price_per_inquiry[]" class="form-control" value="' + escapeHtml(String(t.price_per_inquiry)) + '" step="any" min="0" required></td>';
                    html += '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                    html += '</tr>';
                }
            }

            html += '</tbody>';
            html += '</table>';

            // Add Tier button
            html += '<div style="margin: 15px 0;">';
            html += '<button type="button" class="btn" onclick="addRow()">+ Add Tier</button>';
            html += '</div>';

            // Submit and Cancel
            html += '<div style="margin-top: 20px;">';
            html += '<button type="submit" class="btn btn-success">Save Default Pricing</button> ';
            html += '<a href="?action=pricing_defaults" class="btn">Cancel</a>';
            html += '</div>';

            html += '</form>';


            html += '</div>';

            // Breadcrumb
            html += '<div class="breadcrumb" style="margin-top: 20px;"><a href="?action=pricing_defaults">Default Pricing</a><span>/</span>Edit Tiers</div>';

            el.innerHTML = html;

            // Define addRow function
            window.addRow = function() {
                var tbody = document.querySelector('#tiers-table tbody');
                var lastRow = tbody.querySelector('.tier-row:last-child');
                var nextStart = 0;

                if (lastRow) {
                    var endInput = lastRow.querySelector('input[name="volume_end[]"]');
                    if (endInput && endInput.value) {
                        nextStart = parseInt(endInput.value, 10) + 1;
                    }
                }

                var row = document.createElement('tr');
                row.className = 'tier-row';
                row.innerHTML = '<td><input type="number" name="volume_start[]" class="form-control" value="' + nextStart + '" min="0" required></td>' +
                    '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>' +
                    '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="any" min="0" required></td>' +
                    '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                tbody.appendChild(row);
            };

            // Define removeRow function
            window.removeRow = function(btn) {
                var rows = document.querySelectorAll('.tier-row');
                if (rows.length > 1) {
                    var row = btn.closest ? btn.closest('tr') : btn.parentNode.parentNode;
                    row.parentNode.removeChild(row);
                }
            };

            // Attach form submit handler
            document.getElementById('tier-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('service_id', serviceId);
                apiPost('save_default_tiers', formData, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
}
/**
 * Render discount groups list
 */ function render_pricing_groups($data)
{
    render_header("Discount Groups - Pricing"); ?>
    <div class="card">
        <h2>Discount Groups</h2>
        <p class="text-muted mb-20">Group-level pricing templates. Members inherit these settings.</p>
        <div id="groups-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading groups...</p>
            </div>
        </div>
    </div>

    <script>
    apiGet('pricing_groups', null, function(err, data) {
        if (err) { showAjaxError('groups-content', err); return; }
        var el = document.getElementById('groups-content');
        if (!data.groups || data.groups.length === 0) {
            el.innerHTML = '<p class="text-muted">No discount groups defined.</p>';
            return;
        }
        var html = '<table><thead><tr><th>Group Name</th><th>Members</th><th>Overrides</th><th class="text-right">Actions</th></tr></thead><tbody>';
        for (var i = 0; i < data.groups.length; i++) {
            var g = data.groups[i];
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(g.name) + '</strong></td>';
            html += '<td>' + escapeHtml(String(g.member_count)) + ' customers</td>';
            html += '<td>' + escapeHtml(String(g.override_count)) + ' services</td>';
            html += '<td class="text-right"><a href="?action=pricing_group_edit&group_id=' + g.id + '" class="btn btn-sm">Edit Pricing</a></td>';
            html += '</tr>';
        }
        html += '</tbody></table>';
        el.innerHTML = html;
    });
    </script>
<?php
} /**
 * Render group services list (select which service to edit)
 */
function render_pricing_group_services($data)
{
    $group_id = $data["group"]["id"];
    $group_name = $data["group"]["name"];
    render_header("Group Pricing - " . h($group_name));
    ?>
    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading group services...</p>
        </div>
    </div>

    <script>
    (function() {
        var groupId   = <?php echo (int) $group_id; ?>;
        var groupName = <?php echo json_encode($group_name); ?>;
        function validateTierRanges(tiers) {
            var gaps = [], overlaps = [];
            if (!tiers || tiers.length === 0) return {gaps: gaps, overlaps: overlaps};

            var sorted = tiers.slice().sort(function(a, b) {
                return parseInt(a.volume_start, 10) - parseInt(b.volume_start, 10);
            });

            var firstStart = parseInt(sorted[0].volume_start, 10);
            if (firstStart > 1) {
                gaps.push({start: 1, end: firstStart - 1});
            }

            for (var i = 0; i < sorted.length - 1; i++) {
                var currEnd   = sorted[i].volume_end;
                var nextStart = parseInt(sorted[i + 1].volume_start, 10);

                if (currEnd === null || currEnd === '' || currEnd === undefined) {
                    overlaps.push({start: nextStart, end: sorted[i + 1].volume_end});
                    continue;
                }

                currEnd = parseInt(currEnd, 10);

                if (nextStart > currEnd + 1) {
                    gaps.push({start: currEnd + 1, end: nextStart - 1});
                }

                if (nextStart <= currEnd) {
                    var overlapEnd = sorted[i + 1].volume_end;
                    if (overlapEnd !== null && overlapEnd !== '' && overlapEnd !== undefined) {
                        overlapEnd = Math.min(parseInt(overlapEnd, 10), currEnd);
                    }
                    overlaps.push({start: nextStart, end: overlapEnd});
                }
            }

            return {gaps: gaps, overlaps: overlaps};
        }

        function renderTierRangeWarnings(validation) {
            if (!validation) return '';
            var hasGaps = validation.gaps && validation.gaps.length > 0;
            var hasOverlaps = validation.overlaps && validation.overlaps.length > 0;
            if (!hasGaps && !hasOverlaps) return '';

            var html = '<div style="margin: 10px 0;">';
            if (hasOverlaps) {
                html += '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #721c24; font-size: 13px;">';
                html += '<strong>Overlap:</strong> ';
                for (var i = 0; i < validation.overlaps.length; i++) {
                    var o = validation.overlaps[i];
                    html += 'Volume ' + numberFormat(o.start, 0) + ' &ndash; ';
                    html += (o.end !== null && o.end !== '' && o.end !== undefined) ? numberFormat(parseInt(o.end, 10), 0) : 'Unlimited';
                    html += ' is covered by multiple tiers. ';
                }
                html += '</div>';
            }
            if (hasGaps) {
                html += '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #856404; font-size: 13px;">';
                html += '<strong>Gap:</strong> ';
                for (var i = 0; i < validation.gaps.length; i++) {
                    var g = validation.gaps[i];
                    html += 'Volume ' + numberFormat(g.start, 0) + ' &ndash; ' + numberFormat(g.end, 0) + ' has no pricing. ';
                }
                html += '</div>';
            }
            html += '</div>';
            return html;
        }

        apiGet('pricing_group_edit', {group_id: groupId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var group    = data.group;
            var services = data.services;
            var html     = '';

            // Card header with group name + Expand/Collapse buttons
            html += '<div class="card">';
            html += '<h2 style="display: flex; justify-content: space-between; align-items: center;">';
            html += escapeHtml(group.name) + ' - Service Pricing';
            html += ' <span style="font-size: 12px; font-weight: normal;">';
            html += '<a href="javascript:void(0)" onclick="expandAll()" class="btn btn-sm">Expand All</a> ';
            html += '<a href="javascript:void(0)" onclick="collapseAll()" class="btn btn-sm">Collapse All</a>';
            html += '</span>';
            html += '</h2>';
            html += '<p class="text-muted mb-20">Select a service to override pricing. Inherited values come from System Defaults.</p>';

            // Each service
            for (var s = 0; s < services.length; s++) {
                var svc = services[s];
                var svcIdStr = 'group-' + svc.id;

                html += '<div class="tier-section" style="border: 1px solid #eee; border-radius: 4px; margin-bottom: 15px; overflow: hidden;">';

                // Clickable header row
                html += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; cursor: pointer;" onclick="toggleTiers(\'' + svcIdStr + '\')">';
                html += '<div>';
                html += '<strong>' + escapeHtml(svc.name) + '</strong>';

                if (svc.has_override) {
                    html += ' <span style="color: #27ae60; margin-left: 15px;">Overridden</span>';
                } else {
                    html += ' <span class="text-muted" style="margin-left: 15px;">Inherited</span>';
                }

                // Price range from tiers (last tier price - first tier price)
                if (svc.tiers && svc.tiers.length > 0) {
                    var lastPrice  = svc.tiers[svc.tiers.length - 1].price_per_inquiry;
                    var firstPrice = svc.tiers[0].price_per_inquiry;
                    html += ' <span class="text-muted" style="margin-left: 15px;">';
                    html += '$' + numberFormat(lastPrice, 2) + ' - $' + numberFormat(firstPrice, 2);
                    html += '</span>';
                }

                html += '</div>';
                html += '<div>';
                html += '<span class="toggle-icon" id="icon-' + svcIdStr + '" style="margin-right: 10px; color: #999;">&#9650;</span>';
                html += '<a href="?action=pricing_group_edit&amp;group_id=' + group.id + '&amp;service_id=' + svc.id + '" class="btn btn-sm" onclick="event.stopPropagation();">';
                html += svc.has_override ? 'Edit' : 'Override';
                html += '</a>';
                html += '</div>';
                html += '</div>';

                // Collapsible tier content
                html += '<div class="tier-content" id="tiers-' + svcIdStr + '" style="padding: 0 15px 15px 15px;">';

                if (svc.tiers && svc.tiers.length > 0) {
                    html += '<table style="margin-top: 10px; font-size: 13px;">';
                    html += '<thead><tr>';
                    html += '<th style="padding: 6px 10px;">Volume Start</th>';
                    html += '<th style="padding: 6px 10px;">Volume End</th>';
                    html += '<th style="padding: 6px 10px;">Price Per Inquiry</th>';
                    html += '<th style="padding: 6px 10px;">Source</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';

                    for (var t = 0; t < svc.tiers.length; t++) {
                        var tier = svc.tiers[t];
                        html += '<tr>';
                        html += '<td style="padding: 6px 10px;">' + numberFormat(tier.volume_start, 0) + '</td>';
                        html += '<td style="padding: 6px 10px;">';
                        if (tier.volume_end !== null && tier.volume_end !== undefined) {
                            html += numberFormat(tier.volume_end, 0);
                        } else {
                            html += '<em>Unlimited</em>';
                        }
                        html += '</td>';
                        html += '<td style="padding: 6px 10px;">$' + numberFormat(tier.price_per_inquiry, 4) + '</td>';
                        html += '<td style="padding: 6px 10px;">';
                        if (tier.source === 'group') {
                            html += '<span style="color: #27ae60;">Group</span>';
                        } else {
                            html += '<span class="text-muted">Default</span>';
                        }
                        html += '</td>';
                        html += '</tr>';
                    }

                    html += '</tbody></table>';

                    // Tier range validation warnings
                    html += renderTierRangeWarnings(validateTierRanges(svc.tiers));
                } else {
                    html += '<p class="text-muted" style="margin-top: 10px;">No tiers defined.</p>';
                }

                html += '</div>'; // end tier-content
                html += '</div>'; // end tier-section
            }

            html += '</div>'; // end card

            // Breadcrumb
            html += '<div class="breadcrumb"><a href="?action=pricing_groups">Groups</a><span>/</span>' + escapeHtml(group.name) + '</div>';

            document.getElementById('page-data').innerHTML = html;
        });

        // --------------------------------------------------------
        // Toggle / Expand / Collapse helpers (attached to window)
        // --------------------------------------------------------
        window.toggleTiers = function(id) {
            var el   = document.getElementById('tiers-' + id);
            var icon = document.getElementById('icon-' + id);
            if (el.style.display === 'none') {
                el.style.display = 'block';
                icon.innerHTML   = '&#9650;';
            } else {
                el.style.display = 'none';
                icon.innerHTML   = '&#9660;';
            }
        };

        window.expandAll = function() {
            var contents = document.querySelectorAll('.tier-content');
            var icons    = document.querySelectorAll('.toggle-icon');
            for (var i = 0; i < contents.length; i++) {
                contents[i].style.display = 'block';
            }
            for (var i = 0; i < icons.length; i++) {
                icons[i].innerHTML = '&#9650;';
            }
        };

        window.collapseAll = function() {
            var contents = document.querySelectorAll('.tier-content');
            var icons    = document.querySelectorAll('.toggle-icon');
            for (var i = 0; i < contents.length; i++) {
                contents[i].style.display = 'none';
            }
            for (var i = 0; i < icons.length; i++) {
                icons[i].innerHTML = '&#9660;';
            }
        };
    })();
    </script>
<?php render_footer();
} /**
 * Render group tier edit form
 */
function render_pricing_group_edit($data)
{
    render_header("Edit Group Pricing"); ?>
    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading...</p>
        </div>
    </div>

    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var groupId = urlParams.get('group_id');
        var serviceId = urlParams.get('service_id');

        if (!groupId || !serviceId) {
            showAjaxError('page-data', 'Missing group_id or service_id parameter');
            return;
        }
        function renderValidation(validation) {
            if (!validation) return '';
            var hasGaps = validation.gaps && validation.gaps.length > 0;
            var hasOverlaps = validation.overlaps && validation.overlaps.length > 0;
            if (!hasGaps && !hasOverlaps) return '';

            var vHtml = '<div style="margin: 10px 0;">';

            if (hasOverlaps) {
                vHtml += '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #721c24; font-size: 13px;">';
                vHtml += '<strong>Overlap:</strong> ';
                for (var i = 0; i < validation.overlaps.length; i++) {
                    var o = validation.overlaps[i];
                    var endStr = (o.end !== null && o.end !== '' && o.end !== undefined) ? numberFormat(o.end) : 'Unlimited';
                    vHtml += 'Volume ' + numberFormat(o.start) + ' &ndash; ' + endStr + ' is covered by multiple tiers. ';
                }
                vHtml += '</div>';
            }

            if (hasGaps) {
                vHtml += '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #856404; font-size: 13px;">';
                vHtml += '<strong>Gap:</strong> ';
                for (var j = 0; j < validation.gaps.length; j++) {
                    var g = validation.gaps[j];
                    vHtml += 'Volume ' + numberFormat(g.start) + ' &ndash; ' + numberFormat(g.end) + ' has no pricing. ';
                }
                vHtml += '</div>';
            }

            vHtml += '</div>';
            return vHtml;
        }

        apiGet('pricing_group_edit', {group_id: groupId, service_id: serviceId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var el = document.getElementById('page-data');
            var group = data.group;
            var service = data.service;
            var tiers = data.tiers || [];
            var hasOverride = data.has_override;
            var validation = data.validation || {};

            var html = '';

            // Card
            html += '<div class="card">';
            html += '<h2>' + escapeHtml(group.name) + ': ' + escapeHtml(service.name) + '</h2>';

            // Override / inherit message
            if (hasOverride) {
                html += '<p class="text-muted mb-20">';
                html += '<span style="color: #27ae60;">This group has custom pricing.</span> ';
                html += 'Modify tiers below or clear to inherit from defaults.';
                html += '</p>';
            } else {
                html += '<p class="text-muted mb-20">';
                html += 'Currently inheriting from System Defaults. Save to create a group override.';
                html += '</p>';
            }

            // Form
            html += '<form id="tier-form">';

            // Tier table
            html += '<table id="tiers-table">';
            html += '<thead><tr>';
            html += '<th>Volume Start</th>';
            html += '<th>Volume End</th>';
            html += '<th>Price Per Inquiry</th>';
            html += '<th></th>';
            html += '</tr></thead>';
            html += '<tbody>';

            if (tiers.length === 0) {
                html += '<tr class="tier-row">';
                html += '<td><input type="number" name="volume_start[]" class="form-control" value="0" min="0" required></td>';
                html += '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>';
                html += '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="any" min="0" required></td>';
                html += '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                html += '</tr>';
            } else {
                for (var i = 0; i < tiers.length; i++) {
                    var t = tiers[i];
                    html += '<tr class="tier-row">';
                    html += '<td><input type="number" name="volume_start[]" class="form-control" value="' + escapeHtml(String(t.volume_start)) + '" min="0" required></td>';
                    html += '<td><input type="number" name="volume_end[]" class="form-control" value="' + (t.volume_end !== null ? escapeHtml(String(t.volume_end)) : '') + '" placeholder="Unlimited" min="0"></td>';
                    html += '<td><input type="number" name="price_per_inquiry[]" class="form-control" value="' + escapeHtml(String(t.price_per_inquiry)) + '" step="any" min="0" required></td>';
                    html += '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                    html += '</tr>';
                }
            }

            html += '</tbody>';
            html += '</table>';

            // Add Tier button
            html += '<div style="margin: 15px 0;">';
            html += '<button type="button" class="btn" onclick="addRow()">+ Add Tier</button>';
            html += '</div>';

            // Validation warnings
            html += renderValidation(validation);

            // Action buttons
            html += '<div style="margin-top: 20px;">';
            html += '<button type="submit" class="btn btn-success">Save Group Pricing</button> ';
            if (hasOverride) {
                html += '<button type="button" id="clear-override-btn" class="btn" onclick="clearOverride()">Clear Override</button> ';
            }
            html += '<a href="?action=pricing_group_edit&group_id=' + encodeURIComponent(groupId) + '" class="btn">Cancel</a>';
            html += '</div>';

            html += '</form>';


            html += '</div>';

            // Back link
            html += '<p style="margin-top: 20px;"><a href="?action=pricing_group_edit&group_id=' + encodeURIComponent(groupId) + '">&larr; Back to Services</a></p>';

            el.innerHTML = html;

            // addRow
            window.addRow = function() {
                var tbody = document.querySelector('#tiers-table tbody');
                var lastRow = tbody.querySelector('.tier-row:last-child');
                var nextStart = 0;

                if (lastRow) {
                    var endInput = lastRow.querySelector('input[name="volume_end[]"]');
                    if (endInput && endInput.value) {
                        nextStart = parseInt(endInput.value, 10) + 1;
                    }
                }

                var row = document.createElement('tr');
                row.className = 'tier-row';
                row.innerHTML = '<td><input type="number" name="volume_start[]" class="form-control" value="' + nextStart + '" min="0" required></td>' +
                    '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>' +
                    '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="any" min="0" required></td>' +
                    '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                tbody.appendChild(row);
            };

            // removeRow
            window.removeRow = function(btn) {
                var rows = document.querySelectorAll('.tier-row');
                if (rows.length > 1) {
                    var row = btn.closest ? btn.closest('tr') : btn.parentNode.parentNode;
                    row.parentNode.removeChild(row);
                }
            };

            // clearOverride
            window.clearOverride = function() {
                if (!confirm('Clear override and inherit from defaults?')) {
                    return;
                }
                var clearData = new FormData();
                clearData.append('group_id', groupId);
                clearData.append('service_id', serviceId);
                apiPost('clear_group_tiers', clearData, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            };

            // Form submit handler
            document.getElementById('tier-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('group_id', groupId);
                formData.append('service_id', serviceId);
                apiPost('save_group_tiers', formData, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render customers list
 */
function render_pricing_customers($data)
{
    render_header("Customers - Pricing"); ?>
    <div class="card">
        <h2>Customer Pricing</h2>

        <?php
        $search_val = isset($data["search"]) ? $data["search"] : "";
        render_search_bar("pricing_customers", [
            "search" => $search_val,
            "placeholder" => "Search customers or groups...",
            "filters" => [
                [
                    "name" => "status",
                    "options" => [
                        "active" => "Active",
                        "paused" => "Paused",
                        "decommissioned" => "Decommissioned",
                        "all" => "All Statuses",
                    ],
                    "current" => $data["status_filter"],
                ],
            ],
        ]);?>

        <div id="customers-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading customers...</p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('search')) params.search = urlParams.get('search');
        if (urlParams.get('status')) params.status = urlParams.get('status');
        if (urlParams.get('page')) params.page = urlParams.get('page');

        apiGet('pricing_customers', params, function(err, data) {
            if (err) { showAjaxError('customers-content', err); return; }
            var el = document.getElementById('customers-content');
            if (!data.customers || data.customers.length === 0) {
                el.innerHTML = '<p class="text-muted">No customers found.</p>';
                return;
            }
            var html = '<table><thead><tr><th>Customer</th><th>Discount Group</th><th>Status</th><th>Contract Start</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < data.customers.length; i++) {
                var c = data.customers[i];
                var sc = c.status === 'active' ? 'color: #27ae60;' : (c.status === 'paused' ? 'color: #f39c12;' : 'color: #e74c3c;');
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(c.name) + '</strong></td>';
                html += '<td>' + (c.group_name ? escapeHtml(c.group_name) : '<span class="text-muted">None</span>') + '</td>';
                html += '<td><span style="' + sc + '">' + escapeHtml(c.status.charAt(0).toUpperCase() + c.status.slice(1)) + '</span></td>';
                html += '<td>' + (c.contract_start_date ? escapeHtml(c.contract_start_date) : '-') + '</td>';
                html += '<td class="text-right"><a href="?action=customer_pricing&id=' + c.id + '" class="btn btn-sm btn-info">View</a> ';
                html += '<a href="?action=pricing_customer_edit&customer_id=' + c.id + '" class="btn btn-sm">Edit</a></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';

            if (data.pagination && data.pagination.total_pages > 1) {
                html += buildPagination(data.pagination, '?action=pricing_customers', params);
            }
            el.innerHTML = html;
        });
    })();
    </script>
<?php
} /**
 * Render customer services list (select which service to edit)
 */
function render_pricing_customer_services($data)
{
    render_header("Customer Pricing - " . h($data["customer"]["name"])); ?>
    <div class="card">
        <h2><?php echo h($data["customer"]["name"]); ?></h2>
        <p class="text-muted mb-20">
            <?php if ($data["customer"]["group_name"]): ?>
                Group: <strong><?php echo h(
                    $data["customer"]["group_name"],
                ); ?></strong> |
            <?php else: ?>
                <span style="color: #e67e22;">No discount group (inherits from defaults)</span> |
            <?php endif; ?>
            Status:
            <?php
            $status_class = "";
            if ($data["customer"]["status"] === "active") {
                $status_class = "color: #27ae60;";
            } elseif ($data["customer"]["status"] === "paused") {
                $status_class = "color: #f39c12;";
            } else {
                $status_class = "color: #e74c3c;";
            }
            ?>
            <span style="<?php echo $status_class; ?>"><?php echo ucfirst($data["customer"]["status"]); ?></span>
            <?php if ($data["customer"]["contract_start_date"]): ?>
                | Contract: <?php echo h(
                    $data["customer"]["contract_start_date"],
                ); ?>
            <?php endif; ?>
        </p>

        <div style="margin-bottom: 20px;">
            <a href="?action=pricing_customer_edit&customer_id=<?php echo $data[
                "customer"
            ][
                "id"
            ]; ?>&tab=services" class="btn <?php echo $data["tab"] === "services" ? "btn-success" : ""; ?>">Service Pricing</a>
            <a href="?action=pricing_customer_edit&customer_id=<?php echo $data[
                "customer"
            ][
                "id"
            ]; ?>&tab=settings" class="btn <?php echo $data["tab"] === "settings" ? "btn-success" : ""; ?>">Settings</a>
            <a href="?action=escalator_edit&customer_id=<?php echo $data[
                "customer"
            ]["id"]; ?>" class="btn">Escalators</a>
        </div>
    </div>

    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <p>Loading services...</p>
        </div>
    </div>

    <script>
    (function() {
        var customerId = <?php echo (int) $data["customer"]["id"]; ?>;
        function validateTierRanges(tiers) {
            var result = {gaps: [], overlaps: []};
            if (!tiers || tiers.length === 0) return result;

            var sorted = tiers.slice().sort(function(a, b) {
                return parseInt(a.volume_start) - parseInt(b.volume_start);
            });

            var firstStart = parseInt(sorted[0].volume_start);
            if (firstStart > 1) {
                result.gaps.push({start: 1, end: firstStart - 1});
            }

            for (var i = 0; i < sorted.length - 1; i++) {
                var currEnd = sorted[i].volume_end;
                var nextStart = parseInt(sorted[i + 1].volume_start);

                if (currEnd === null || currEnd === '' || currEnd === undefined) {
                    result.overlaps.push({start: nextStart, end: sorted[i + 1].volume_end});
                    continue;
                }

                currEnd = parseInt(currEnd);

                if (nextStart > currEnd + 1) {
                    result.gaps.push({start: currEnd + 1, end: nextStart - 1});
                }

                if (nextStart <= currEnd) {
                    var overlapEnd = sorted[i + 1].volume_end;
                    if (overlapEnd !== null && overlapEnd !== '' && overlapEnd !== undefined) {
                        overlapEnd = Math.min(parseInt(overlapEnd), currEnd);
                    } else {
                        overlapEnd = currEnd;
                    }
                    result.overlaps.push({start: nextStart, end: overlapEnd});
                }
            }

            return result;
        }

        function buildTierWarnings(validation) {
            if (!validation) return '';
            var hasGaps = validation.gaps && validation.gaps.length > 0;
            var hasOverlaps = validation.overlaps && validation.overlaps.length > 0;
            if (!hasGaps && !hasOverlaps) return '';

            var html = '<div style="margin: 10px 0;">';
            if (hasOverlaps) {
                html += '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #721c24; font-size: 13px;">';
                html += '<strong>Overlap:</strong> ';
                for (var i = 0; i < validation.overlaps.length; i++) {
                    var o = validation.overlaps[i];
                    html += 'Volume ' + numberFormat(o.start) + ' &ndash; ';
                    html += (o.end !== null && o.end !== '' && o.end !== undefined) ? numberFormat(o.end) : 'Unlimited';
                    html += ' is covered by multiple tiers. ';
                }
                html += '</div>';
            }
            if (hasGaps) {
                html += '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 10px 14px; margin-bottom: 8px; color: #856404; font-size: 13px;">';
                html += '<strong>Gap:</strong> ';
                for (var i = 0; i < validation.gaps.length; i++) {
                    var g = validation.gaps[i];
                    html += 'Volume ' + numberFormat(g.start) + ' &ndash; ' + numberFormat(g.end) + ' has no pricing. ';
                }
                html += '</div>';
            }
            html += '</div>';
            return html;
        }

        function buildSourceLabel(source) {
            if (source === 'customer') {
                return '<span style="color: #27ae60; font-weight: bold;">Customer</span>';
            } else if (source === 'group') {
                return '<span style="color: #3498db;">Group</span>';
            } else {
                return '<span class="text-muted">Default</span>';
            }
        }

        function buildSourceStyle(source) {
            if (source === 'customer') {
                return 'color: #27ae60; font-weight: bold;';
            } else if (source === 'group') {
                return 'color: #3498db;';
            } else {
                return 'color: #999;';
            }
        }

        function buildSourceName(source) {
            if (source === 'customer') return 'Customer';
            if (source === 'group') return 'Group';
            return 'Default';
        }

        function renderServices(data) {
            var services = data.services;
            var html = '';

            html += '<div class="card">';
            html += '<h2 style="display: flex; justify-content: space-between; align-items: center;">';
            html += 'Service Pricing';
            html += '<span style="font-size: 12px; font-weight: normal;">';
            html += '<a href="javascript:void(0)" onclick="expandAll()" class="btn btn-sm">Expand All</a> ';
            html += '<a href="javascript:void(0)" onclick="collapseAll()" class="btn btn-sm">Collapse All</a>';
            html += '</span>';
            html += '</h2>';
            html += '<p class="text-muted mb-20">Select a service to override pricing. Inherited values show their source.</p>';

            for (var i = 0; i < services.length; i++) {
                var service = services[i];
                var sourceStyle = buildSourceStyle(service.source);
                var sourceName = buildSourceName(service.source);
                var tierId = 'cust-' + service.id;

                html += '<div class="tier-section" style="border: 1px solid #eee; border-radius: 4px; margin-bottom: 15px; overflow: hidden;">';

                // Header row
                html += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; cursor: pointer;" onclick="toggleTiers(\'' + tierId + '\')">';
                html += '<div>';
                html += '<strong>' + escapeHtml(service.name) + '</strong>';
                if (service.has_override) {
                    html += '<span style="color: #27ae60; margin-left: 15px;">Overridden</span>';
                } else {
                    html += '<span class="text-muted" style="margin-left: 15px;">Inherited</span>';
                }
                html += '<span style="' + sourceStyle + ' margin-left: 10px;">(' + escapeHtml(sourceName) + ')</span>';
                if (service.tiers && service.tiers.length > 0) {
                    var lastTier = service.tiers[service.tiers.length - 1];
                    var firstTier = service.tiers[0];
                    html += '<span class="text-muted" style="margin-left: 15px;">';
                    html += '$' + numberFormat(lastTier.price_per_inquiry, 2);
                    html += ' - $' + numberFormat(firstTier.price_per_inquiry, 2);
                    html += '</span>';
                }
                html += '</div>';

                html += '<div>';
                html += '<span class="toggle-icon" id="icon-' + tierId + '" style="margin-right: 10px; color: #999;">&#9650;</span>';
                html += '<a href="?action=pricing_customer_edit&customer_id=' + customerId + '&service_id=' + service.id + '" class="btn btn-sm" onclick="event.stopPropagation();">';
                html += service.has_override ? 'Edit' : 'Override';
                html += '</a>';
                html += '</div>';
                html += '</div>';

                // Tier content
                html += '<div class="tier-content" id="tiers-' + tierId + '" style="padding: 0 15px 15px 15px;">';
                if (service.tiers && service.tiers.length > 0) {
                    html += '<table style="margin-top: 10px; font-size: 13px;">';
                    html += '<thead><tr>';
                    html += '<th style="padding: 6px 10px;">Volume Start</th>';
                    html += '<th style="padding: 6px 10px;">Volume End</th>';
                    html += '<th style="padding: 6px 10px;">Price Per Inquiry</th>';
                    html += '<th style="padding: 6px 10px;">Source</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';
                    for (var t = 0; t < service.tiers.length; t++) {
                        var tier = service.tiers[t];
                        html += '<tr>';
                        html += '<td style="padding: 6px 10px;">' + numberFormat(tier.volume_start) + '</td>';
                        html += '<td style="padding: 6px 10px;">';
                        if (tier.volume_end !== null && tier.volume_end !== undefined) {
                            html += numberFormat(tier.volume_end);
                        } else {
                            html += '<em>Unlimited</em>';
                        }
                        html += '</td>';
                        html += '<td style="padding: 6px 10px;">$' + numberFormat(tier.price_per_inquiry, 4) + '</td>';
                        html += '<td style="padding: 6px 10px;">' + buildSourceLabel(tier.source) + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                    html += buildTierWarnings(validateTierRanges(service.tiers));
                } else {
                    html += '<p class="text-muted" style="margin-top: 10px;">No tiers defined.</p>';
                }
                html += '</div>';

                html += '</div>';
            }

            html += '</div>';
            return html;
        }

        apiGet('pricing_customer_edit', {customer_id: customerId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }
            document.getElementById('page-data').innerHTML = renderServices(data);
        });
    })();

    function toggleTiers(id) {
        var el = document.getElementById('tiers-' + id);
        var icon = document.getElementById('icon-' + id);
        if (el.style.display === 'none') {
            el.style.display = 'block';
            icon.innerHTML = '&#9650;';
        } else {
            el.style.display = 'none';
            icon.innerHTML = '&#9660;';
        }
    }
    function expandAll() {
        var contents = document.querySelectorAll('.tier-content');
        var icons = document.querySelectorAll('.toggle-icon');
        for (var i = 0; i < contents.length; i++) {
            contents[i].style.display = 'block';
        }
        for (var i = 0; i < icons.length; i++) {
            icons[i].innerHTML = '&#9650;';
        }
    }
    function collapseAll() {
        var contents = document.querySelectorAll('.tier-content');
        var icons = document.querySelectorAll('.toggle-icon');
        for (var i = 0; i < contents.length; i++) {
            contents[i].style.display = 'none';
        }
        for (var i = 0; i < icons.length; i++) {
            icons[i].innerHTML = '&#9660;';
        }
    }
    </script>

    <div class="breadcrumb"><a href="?action=pricing_customers">Customers</a><span>/</span><?php echo h(
        $data["customer"]["name"],
    ); ?></div>
<?php render_footer();
} /**
 * Render customer tier edit form
 */
function render_pricing_customer_edit($data)
{
    render_header("Edit Customer Pricing - Control Panel"); ?>
    <div id="pce-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading...</p>
        </div>
    </div>

    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var customerId = urlParams.get('customer_id');
        var serviceId = urlParams.get('service_id');

        if (!customerId || !serviceId) {
            showAjaxError('pce-content', 'Missing customer_id or service_id parameter');
            return;
        }

        apiGet('pricing_customer_edit', {customer_id: customerId, service_id: serviceId}, function(err, data) {
            if (err) { showAjaxError('pce-content', err); return; }

            var el = document.getElementById('pce-content');
            var customer = data.customer;
            var service = data.service;
            var tiers = data.tiers || [];
            var defaultTiers = data.default_tiers || [];
            var groupTiers = data.group_tiers || [];
            var hasOverride = data.has_customer_override;
            var validation = data.validation || {};

            // Determine which tiers to pre-populate
            var populateTiers = hasOverride ? tiers : defaultTiers;

            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb">';
            html += '<a href="?action=customer_pricing&id=' + escapeHtml(String(customer.id)) + '">Customer Pricing</a>';
            html += '<span>/</span>';
            html += '<a href="?action=customer_pricing&id=' + escapeHtml(String(customer.id)) + '">' + escapeHtml(customer.name) + '</a>';
            html += '<span>/</span>Edit';
            html += '</div>';

            // Card
            html += '<div class="card">';
            html += '<h2>Edit Pricing: ' + escapeHtml(customer.name) + ' - ' + escapeHtml(service.name) + '</h2>';

            // Info box showing inheritance
            html += '<div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 4px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #555;">';
            html += 'Default tiers: ' + defaultTiers.length;
            html += ' | Group tiers: ' + groupTiers.length;
            html += ' | Customer override: ' + (hasOverride ? 'Yes' : 'No');
            html += '</div>';

            // Form
            html += '<form id="tier-form">';

            // Tier table
            html += '<table id="tiers-table">';
            html += '<thead><tr>';
            html += '<th>Volume Start</th>';
            html += '<th>Volume End</th>';
            html += '<th>Price Per Inquiry</th>';
            html += '<th></th>';
            html += '</tr></thead>';
            html += '<tbody>';

            if (populateTiers.length === 0) {
                // One empty row if no tiers
                html += '<tr class="tier-row">';
                html += '<td><input type="number" name="volume_start[]" class="form-control" value="0" min="0" required></td>';
                html += '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>';
                html += '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="any" min="0" required></td>';
                html += '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                html += '</tr>';
            } else {
                for (var i = 0; i < populateTiers.length; i++) {
                    var t = populateTiers[i];
                    html += '<tr class="tier-row">';
                    html += '<td><input type="number" name="volume_start[]" class="form-control" value="' + escapeHtml(String(t.volume_start)) + '" min="0" required></td>';
                    html += '<td><input type="number" name="volume_end[]" class="form-control" value="' + (t.volume_end !== null ? escapeHtml(String(t.volume_end)) : '') + '" placeholder="Unlimited" min="0"></td>';
                    html += '<td><input type="number" name="price_per_inquiry[]" class="form-control" value="' + escapeHtml(String(t.price_per_inquiry)) + '" step="any" min="0" required></td>';
                    html += '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                    html += '</tr>';
                }
            }

            html += '</tbody>';
            html += '</table>';

            // Add Tier button
            html += '<div style="margin: 15px 0;">';
            html += '<button type="button" class="btn" onclick="addRow()">+ Add Tier</button>';
            html += '</div>';

            // Submit, Cancel, and Remove Override
            html += '<div style="margin-top: 20px;">';
            html += '<button type="submit" class="btn btn-success">Save Customer Pricing</button> ';
            html += '<a href="?action=customer_pricing&id=' + escapeHtml(String(customer.id)) + '" class="btn">Cancel</a>';
            if (hasOverride) {
                html += ' <a href="?action=pricing_customer_edit&customer_id=' + encodeURIComponent(customer.id) + '&service_id=' + encodeURIComponent(service.id) + '&remove_override=1" class="btn" style="background: #dc3545; color: #fff;" onclick="return confirm(\'Remove customer override and revert to inherited pricing?\')">Remove Override</a>';
            }
            html += '</div>';

            html += '</form>';


            html += '</div>'; // close card

            el.innerHTML = html;

            // Define addRow function
            window.addRow = function() {
                var tbody = document.querySelector('#tiers-table tbody');
                var lastRow = tbody.querySelector('.tier-row:last-child');
                var nextStart = 0;

                if (lastRow) {
                    var endInput = lastRow.querySelector('input[name="volume_end[]"]');
                    if (endInput && endInput.value) {
                        nextStart = parseInt(endInput.value, 10) + 1;
                    }
                }

                var row = document.createElement('tr');
                row.className = 'tier-row';
                row.innerHTML = '<td><input type="number" name="volume_start[]" class="form-control" value="' + nextStart + '" min="0" required></td>' +
                    '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>' +
                    '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="any" min="0" required></td>' +
                    '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
                tbody.appendChild(row);
            };

            // Define removeRow function
            window.removeRow = function(btn) {
                var rows = document.querySelectorAll('.tier-row');
                if (rows.length > 1) {
                    var row = btn.closest ? btn.closest('tr') : btn.parentNode.parentNode;
                    row.parentNode.removeChild(row);
                }
            };

            // Attach form submit handler
            document.getElementById('tier-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('customer_id', customerId);
                formData.append('service_id', serviceId);
                apiPost('save_customer_tiers', formData, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render customer settings form (monthly minimum, annualized, etc.)
 */
function render_pricing_customer_settings($data)
{
    render_header("Customer Settings - " . h($data["customer"]["name"])); ?>
    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading customer settings...</p>
        </div>
    </div>

    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var customerId = params.get('customer_id');
        if (!customerId) {
            showAjaxError('page-data', 'No customer_id specified');
            return;
        }

        apiGet('pricing_customer_settings', {customer_id: customerId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var customer = data.customer;
            var settings = data.settings;
            var allLms = data.all_lms || [];
            var tab = data.tab || 'settings';
            var el = document.getElementById('page-data');
            var html = '';

            // Customer header card
            html += '<div class="card">';
            html += '<h2>' + escapeHtml(customer.name) + '</h2>';
            html += '<p class="text-muted mb-20">';
            if (customer.group_name) {
                html += 'Group: <strong>' + escapeHtml(customer.group_name) + '</strong> | ';
            } else {
                html += '<span style="color: #e67e22;">No discount group</span> | ';
            }
            html += 'Status: ';
            var statusStyle = '';
            if (customer.status === 'active') {
                statusStyle = 'color: #27ae60;';
            } else if (customer.status === 'paused') {
                statusStyle = 'color: #f39c12;';
            } else {
                statusStyle = 'color: #e74c3c;';
            }
            html += '<span style="' + statusStyle + '">' + escapeHtml(customer.status.charAt(0).toUpperCase() + customer.status.slice(1)) + '</span>';
            html += '</p>';

            // Tab navigation
            html += '<div style="margin-bottom: 20px;">';
            html += '<a href="?action=pricing_customer_edit&customer_id=' + encodeURIComponent(customer.id) + '&tab=services" class="btn' + (tab === 'services' ? ' btn-success' : '') + '">Service Pricing</a>';
            html += '<a href="?action=pricing_customer_edit&customer_id=' + encodeURIComponent(customer.id) + '&tab=settings" class="btn' + (tab === 'settings' ? ' btn-success' : '') + '">Settings</a>';
            html += '<a href="?action=escalator_edit&customer_id=' + encodeURIComponent(customer.id) + '" class="btn">Escalators</a>';
            html += '</div>';
            html += '</div>';

            // Customer Settings card with form
            html += '<div class="card">';
            html += '<h2>Customer Settings</h2>';

            html += '<form id="customer-settings-form">';
            html += '<input type="hidden" name="customer_id" value="' + escapeHtml(String(customer.id)) + '">';

            // LMS dropdown
            html += '<div class="form-group">';
            html += '<label for="lms_id">LMS (Loan Management System) <span style="color: #dc3545;">*</span></label>';
            html += '<select name="lms_id" id="lms_id" class="form-control" style="width: 300px;">';
            html += '<option value="">-- Select LMS --</option>';
            for (var i = 0; i < allLms.length; i++) {
                var lms = allLms[i];
                var selected = (customer.lms_id && String(customer.lms_id) === String(lms.id)) ? ' selected' : '';
                html += '<option value="' + escapeHtml(String(lms.id)) + '"' + selected + '>' + escapeHtml(lms.name) + '</option>';
            }
            html += '</select>';
            html += '<small class="text-muted">Required for billing calculations. <a href="?action=lms&sync=1">Sync LMS list</a> if empty.</small>';

            // Warning if no LMS assigned
            if (!customer.lms_id) {
                html += '<div style="margin-top: 8px; padding: 10px; background: #f8d7da; border-radius: 4px; font-size: 13px; color: #721c24;">';
                html += '<strong>Warning:</strong> This customer has no LMS assigned. LMS is required for revenue/commission calculations.';
                html += '</div>';
            }
            html += '</div>';

            html += '<hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">';

            // Monthly Minimum
            html += '<div class="form-group">';
            html += '<label for="monthly_minimum">Monthly Minimum ($)</label>';
            html += '<input type="number" name="monthly_minimum" id="monthly_minimum" class="form-control"';
            html += ' value="' + (settings.monthly_minimum !== null ? escapeHtml(String(settings.monthly_minimum)) : '') + '"';
            html += ' step="any" min="0" placeholder="No minimum">';
            html += '<small class="text-muted">Leave empty for no minimum charge. When set, if the customer\'s monthly usage is below this amount, a "gap" line item will be added to bring the invoice total to this minimum.</small>';

            // Info box if minimum is set
            if (settings.monthly_minimum && settings.monthly_minimum > 0) {
                html += '<div style="margin-top: 8px; padding: 10px; background: #e8f4fd; border-radius: 4px; font-size: 13px;">';
                html += '<strong>Current Minimum:</strong> $' + numberFormat(settings.monthly_minimum, 2) + '/month';
                html += '<br><span class="text-muted">If monthly usage &lt; $' + numberFormat(settings.monthly_minimum, 2) + ', a gap line item will be added.</span>';
                html += '</div>';
            }
            html += '</div>';

            // Uses Annualized Tiers checkbox
            html += '<div class="form-group">';
            html += '<label>';
            html += '<input type="checkbox" name="uses_annualized" value="1"' + (settings.uses_annualized ? ' checked' : '') + '>';
            html += ' Uses Annualized Tiers';
            html += '</label>';
            html += '<small class="text-muted" style="display: block;">Enable volume calculation over a look-back period.</small>';
            html += '</div>';

            // Annualized Start Date
            html += '<div class="form-group">';
            html += '<label for="annualized_start_date">Annualized Start Date</label>';
            html += '<input type="date" name="annualized_start_date" id="annualized_start_date" class="form-control"';
            html += ' value="' + (settings.annualized_start_date ? escapeHtml(settings.annualized_start_date) : '') + '">';
            html += '<small class="text-muted">When annualized tier calculation begins.</small>';
            html += '</div>';

            // Look Period Months
            html += '<div class="form-group">';
            html += '<label for="look_period_months">Look Period (Months)</label>';
            html += '<input type="number" name="look_period_months" id="look_period_months" class="form-control"';
            html += ' value="' + (settings.look_period_months !== null ? escapeHtml(String(settings.look_period_months)) : '') + '"';
            html += ' min="1" max="12" placeholder="e.g., 6">';
            html += '<small class="text-muted">Number of months to look back for volume calculation.</small>';
            html += '</div>';

            // Submit buttons
            html += '<div style="margin-top: 20px;">';
            html += '<button type="submit" class="btn btn-success">Save Settings</button>';
            html += '<a href="?action=pricing_customer_edit&customer_id=' + encodeURIComponent(customer.id) + '" class="btn">Cancel</a>';
            html += '</div>';


            html += '</form>';
            html += '</div>'; // close settings card

            // Breadcrumb
            html += '<div class="breadcrumb" style="margin-top: 20px;"><a href="?action=pricing_customers">Customers</a><span>/</span>Edit Tiers</div>';

            el.innerHTML = html;

            // Attach form submit handler
            document.getElementById('customer-settings-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = '';
                formData += 'customer_id=' + encodeURIComponent(customerId);
                formData += '&lms_id=' + encodeURIComponent(document.getElementById('lms_id').value);
                formData += '&monthly_minimum=' + encodeURIComponent(document.getElementById('monthly_minimum').value);

                var annualizedCheckbox = document.querySelector('input[name="uses_annualized"]');
                formData += '&uses_annualized=' + (annualizedCheckbox && annualizedCheckbox.checked ? '1' : '0');

                formData += '&annualized_start_date=' + encodeURIComponent(document.getElementById('annualized_start_date').value);
                formData += '&look_period_months=' + encodeURIComponent(document.getElementById('look_period_months').value);

                apiPost('save_customer_settings', formData, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render escalators list (customers with escalators)
 */
function render_escalators($data)
{
    render_header("Escalators"); ?>
    <div class="card">
        <h2>Customer Escalators</h2>
        <p class="text-muted mb-20">Annual price increases scheduled per customer contract.</p>

        <?php
        $search_val = isset($data["search"]) ? $data["search"] : "";
        render_search_bar("escalators", [
            "search" => $search_val,
            "placeholder" => "Search customers...",
        ]);?>

        <div id="escalators-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading escalators...</p>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Add Escalators</h2>
        <p class="text-muted">To add escalators to a customer, go to their customer pricing page.</p>
        <a href="?action=pricing_customers" class="btn">Go to Customers</a>
    </div>

    <script>
    (function() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('search')) params.search = urlParams.get('search');
        if (urlParams.get('page')) params.page = urlParams.get('page');

        apiGet('escalators', params, function(err, data) {
            if (err) { showAjaxError('escalators-content', err); return; }
            var el = document.getElementById('escalators-content');
            if (!data.customers || data.customers.length === 0) {
                el.innerHTML = '<p class="text-muted">No customers have escalators configured.</p>' +
                    '<p><a href="?action=pricing_customers" class="btn">Go to Customers</a> to add escalators to a customer.</p>';
                return;
            }
            var html = '<table><thead><tr><th>Customer</th><th>Group</th><th>Start Date</th><th>Years Defined</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < data.customers.length; i++) {
                var c = data.customers[i];
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(c.name) + '</strong></td>';
                html += '<td>' + (c.group_name ? escapeHtml(c.group_name) : '<span class="text-muted">None</span>') + '</td>';
                html += '<td>' + (c.start_date ? escapeHtml(c.start_date) : '-') + '</td>';
                html += '<td>' + escapeHtml(String(c.escalator_count)) + ' years</td>';
                html += '<td class="text-right"><a href="?action=escalator_edit&customer_id=' + c.id + '" class="btn btn-sm">Edit</a></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
            if (data.pagination && data.pagination.total_pages > 1) {
                html += buildPagination(data.pagination, '?action=escalators', params);
            }
            el.innerHTML = html;
        });
    })();
    </script>
<?php
}
/**
 * Render escalator edit form for a customer
 */ function render_escalator_edit($data)
{
    render_header("Edit Escalators - Control Panel"); ?>
    <div id="esc-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading...</p>
        </div>
    </div>

    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var customerId = params.get('customer_id');

        if (!customerId) {
            showAjaxError('esc-content', 'Missing customer_id parameter');
            return;
        }

        function buildEscalatorRow(esc) {
            var row = '<tr>';
            row += '<td><input type="number" name="year_number[]" value="' + escapeHtml(String(esc.year_number)) + '" class="form-control" readonly style="width:80px;"></td>';
            row += '<td><input type="number" step="any" name="escalator_percentage[]" value="' + escapeHtml(String(esc.escalator_percentage)) + '" class="form-control" style="width:120px;"></td>';
            row += '<td><input type="number" step="any" name="fixed_adjustment[]" value="' + escapeHtml(String(esc.fixed_adjustment)) + '" class="form-control" style="width:120px;"></td>';
            row += '<td>';
            if (esc.total_delay > 0) {
                row += escapeHtml(String(esc.total_delay)) + ' month' + (esc.total_delay > 1 ? 's' : '') + ' ';
            }
            row += '<a href="?action=escalator_delay&customer_id=' + encodeURIComponent(customerId) + '&year_number=' + encodeURIComponent(String(esc.year_number)) + '">+1 Month</a>';
            row += '</td>';
            row += '<td><button type="button" class="btn btn-danger btn-sm" onclick="removeEscalatorRow(this)">Remove</button></td>';
            row += '</tr>';
            return row;
        }

        apiGet('escalator_edit', {customer_id: customerId}, function(err, data) {
            if (err) { showAjaxError('esc-content', err); return; }

            var el = document.getElementById('esc-content');
            var customer = data.customer;
            var escalators = data.escalators;
            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb"><a href="?action=escalators">Escalators</a><span>/</span>Edit</div>';

            // Card
            html += '<div class="card">';
            html += '<h2>' + escapeHtml(customer.name) + ' - Escalators</h2>';
            html += '<p class="text-muted">Configure annual price escalation increases for this customer. Each year can have a percentage increase and/or a fixed dollar adjustment.</p>';

            // Form
            html += '<form id="escalator-form">';

            // Determine start date
            var startDate = customer.contract_start_date;
            if (escalators && escalators.length > 0 && escalators[0].escalator_start_date) {
                startDate = escalators[0].escalator_start_date;
            }

            html += '<div style="margin-bottom: 20px;">';
            html += '<label style="display: block; margin-bottom: 5px; font-weight: 500;">Escalator Start Date</label>';
            html += '<input type="date" name="escalator_start_date" value="' + escapeHtml(String(startDate)) + '" class="form-control" style="width: 200px;">';
            html += '</div>';

            // Escalator table
            html += '<table id="escalator-table">';
            html += '<thead><tr>';
            html += '<th>Year</th>';
            html += '<th>Percentage Increase (%)</th>';
            html += '<th>Fixed Adjustment ($)</th>';
            html += '<th>Total Delay</th>';
            html += '<th>Remove</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            if (escalators && escalators.length > 0) {
                for (var i = 0; i < escalators.length; i++) {
                    html += buildEscalatorRow(escalators[i]);
                }
            } else {
                html += buildEscalatorRow({year_number: 1, escalator_percentage: 0, fixed_adjustment: 0, total_delay: 0});
            }

            html += '</tbody></table>';

            html += '<div style="margin-top: 10px; margin-bottom: 20px;">';
            html += '<button type="button" class="btn btn-info" onclick="addEscalatorRow()">+ Add Year</button>';
            html += '</div>';

            html += '<div style="margin-top: 20px;">';
            html += '<button type="submit" class="btn btn-success">Save Escalators</button> ';
            html += '<a href="?action=escalators" class="btn btn-default">Cancel</a>';
            html += '</div>';

            html += '</form>';


            html += '</div>'; // close card

            el.innerHTML = html;

            // Add Year handler
            window.addEscalatorRow = function() {
                var table = document.getElementById('escalator-table');
                var tbody = table.getElementsByTagName('tbody')[0];
                var rows = tbody.getElementsByTagName('tr');
                var maxYear = 0;
                for (var i = 0; i < rows.length; i++) {
                    var yearInput = rows[i].getElementsByTagName('input')[0];
                    if (yearInput) {
                        var val = parseInt(yearInput.value, 10);
                        if (val > maxYear) { maxYear = val; }
                    }
                }
                var nextYear = maxYear + 1;
                var newRow = document.createElement('tr');
                var rowHtml = '';
                rowHtml += '<td><input type="number" name="year_number[]" value="' + nextYear + '" class="form-control" readonly style="width:80px;"></td>';
                rowHtml += '<td><input type="number" step="any" name="escalator_percentage[]" value="0" class="form-control" style="width:120px;"></td>';
                rowHtml += '<td><input type="number" step="any" name="fixed_adjustment[]" value="0" class="form-control" style="width:120px;"></td>';
                rowHtml += '<td><a href="?action=escalator_delay&customer_id=' + encodeURIComponent(customerId) + '&year_number=' + nextYear + '">+1 Month</a></td>';
                rowHtml += '<td><button type="button" class="btn btn-danger btn-sm" onclick="removeEscalatorRow(this)">Remove</button></td>';
                newRow.innerHTML = rowHtml;
                tbody.appendChild(newRow);
            };

            // Remove row handler (minimum 1 row)
            window.removeEscalatorRow = function(btn) {
                var table = document.getElementById('escalator-table');
                var tbody = table.getElementsByTagName('tbody')[0];
                var rows = tbody.getElementsByTagName('tr');
                if (rows.length <= 1) { return; }
                var row = btn.parentNode.parentNode;
                tbody.removeChild(row);
            };

            // Form submit handler
            document.getElementById('escalator-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('customer_id', customerId);
                apiPost('save_escalators', formData, function(err, result) {
                    if (err) { showToast(err, 'error'); }
                    else { showToast(result.message, 'success'); }
                });
            });
        });
    })();
    </script>
<?php render_footer();
}
/**
 * Render business rules list (customers with rules)
 */ function render_business_rules($data)
{
    render_header("Business Rules"); ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Business Rules by Customer</h2>
            <a href="?action=business_rules_all" class="btn btn-info">View All Rules</a>
        </div>
        <p class="text-muted mb-20">Rules are synced from the remote database. You can mask/unmask rules to control billing behavior.</p>

        <?php
        $search_val = isset($data["search"]) ? $data["search"] : "";
        render_search_bar("business_rules", [
            "search" => $search_val,
            "placeholder" => "Search customers...",
        ]);?>

        <div id="rules-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading business rules...</p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('search')) params.search = urlParams.get('search');
        if (urlParams.get('page')) params.page = urlParams.get('page');

        apiGet('business_rules', params, function(err, data) {
            if (err) { showAjaxError('rules-content', err); return; }
            var el = document.getElementById('rules-content');
            if (!data.customers || data.customers.length === 0) {
                el.innerHTML = '<p class="text-muted">No customers have business rules configured.</p>';
                return;
            }
            var html = '<table><thead><tr><th>Customer</th><th>Group</th><th>Total Rules</th><th>Masked</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < data.customers.length; i++) {
                var c = data.customers[i];
                var masked = c.masked_count > 0
                    ? '<span style="color: #e67e22;">' + c.masked_count + ' masked</span>'
                    : '<span class="text-muted">None</span>';
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(c.name) + '</strong></td>';
                html += '<td>' + (c.group_name ? escapeHtml(c.group_name) : '<span class="text-muted">None</span>') + '</td>';
                html += '<td>' + c.rule_count + ' rules</td>';
                html += '<td>' + masked + '</td>';
                html += '<td class="text-right"><a href="?action=business_rule_edit&customer_id=' + c.id + '" class="btn btn-sm">Manage</a></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
            if (data.pagination && data.pagination.total_pages > 1) {
                html += buildPagination(data.pagination, '?action=business_rules', params);
            }
            el.innerHTML = html;
        });
    })();
    </script>
<?php
} /**
 * Render all business rules view
 */
function render_business_rules_all($data)
{
    render_header("All Business Rules"); ?>
    <div class="breadcrumb"><a href="?action=business_rules">Rules by Customer</a><span>/</span>All Rules</div>

    <div class="card">
        <h2>All Business Rules</h2>

        <div id="rules-all-stats" class="ajax-content" style="margin-bottom: 20px;">
            <div class="loading-skeleton"><div class="skeleton-bar w75"></div></div>
        </div>

        <?php render_search_bar("business_rules_all", [
            "search" => isset($data["search"]) ? $data["search"] : "",
            "placeholder" => "Search rules or customers...",
            "filters" => [
                [
                    "name" => "masked",
                    "options" => [
                        "" => "All Rules",
                        "1" => "Masked Only",
                        "0" => "Unmasked Only",
                    ],
                    "current" => isset($data["filter_masked"])
                        ? $data["filter_masked"]
                        : "",
                ],
            ],
        ]); ?>

        <div id="rules-all-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading rules...</p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('search')) params.search = urlParams.get('search');
        if (urlParams.get('masked')) params.masked = urlParams.get('masked');
        if (urlParams.get('page')) params.page = urlParams.get('page');

        apiGet('business_rules_all', params, function(err, data) {
            if (err) { showAjaxError('rules-all-content', err); return; }

            // Stats
            var s = data.stats || {};
            var statsHtml = '<div style="display: flex; gap: 20px;">';
            statsHtml += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;"><div style="font-size: 24px; font-weight: bold;">' + (s.total_rules || 0) + '</div><div style="color: #666; font-size: 13px;">Total Rules</div></div>';
            statsHtml += '<div style="flex: 1; background: #fff3cd; padding: 15px; border-radius: 4px; text-align: center;"><div style="font-size: 24px; font-weight: bold; color: #856404;">' + (s.masked_rules || 0) + '</div><div style="color: #666; font-size: 13px;">Masked Rules</div></div>';
            statsHtml += '<div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;"><div style="font-size: 24px; font-weight: bold;">' + (s.customers_with_rules || 0) + '</div><div style="color: #666; font-size: 13px;">Customers</div></div>';
            statsHtml += '</div>';
            document.getElementById('rules-all-stats').innerHTML = statsHtml;

            // Table
            var el = document.getElementById('rules-all-content');
            if (!data.rules || data.rules.length === 0) {
                el.innerHTML = '<p class="text-muted">No rules found.</p>';
                return;
            }
            var html = '<table><thead><tr><th>Customer</th><th>Rule Name</th><th>Description</th><th>Status</th><th class="text-right">Actions</th></tr></thead><tbody>';
            for (var i = 0; i < data.rules.length; i++) {
                var r = data.rules[i];
                var rowStyle = r.is_masked ? ' style="background: #fff3cd;"' : '';
                html += '<tr' + rowStyle + '>';
                html += '<td><a href="?action=business_rule_edit&customer_id=' + r.customer_id + '">' + escapeHtml(r.customer_name) + '</a>';
                if (r.customer_status !== 'active') {
                    var bClass = r.customer_status === 'paused' ? 'badge-warning' : 'badge-default';
                    html += ' <span class="badge ' + bClass + '">' + escapeHtml(r.customer_status) + '</span>';
                }
                html += '</td>';
                html += '<td><code style="font-size: 12px;">' + escapeHtml(r.rule_name) + '</code></td>';
                html += '<td class="text-muted" style="font-size: 12px;">' + escapeHtml(r.rule_description || '-') + '</td>';
                html += '<td>' + (r.is_masked ? '<span class="badge badge-warning">Masked</span>' : '<span class="badge badge-success">Active</span>') + '</td>';
                html += '<td class="text-right"><a href="?action=business_rule_toggle&customer_id=' + r.customer_id + '&rule=' + encodeURIComponent(r.rule_name) + '&return=all" class="btn btn-sm">' + (r.is_masked ? 'Unmask' : 'Mask') + '</a></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
            if (data.pagination && data.pagination.total_pages > 1) {
                html += buildPagination(data.pagination, '?action=business_rules_all', params);
            }
            el.innerHTML = html;
        });
    })();
    </script>
<?php
} /**
 * Render business rule edit for a customer
 */
function render_business_rule_edit($data)
{
    render_header("Business Rules - Control Panel"); ?>
    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading business rules...</p>
        </div>
    </div>
    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var customerId = params.get('customer_id');
        if (!customerId) {
            showAjaxError('page-data', 'No customer ID specified');
            return;
        }

        function loadRules() {
            apiGet('business_rule_edit', {customer_id: customerId}, function(err, data) {
                if (err) { showAjaxError('page-data', err); return; }

                var customer = data.customer;
                var rules = data.rules || [];
                var html = '';

                html += '<div class="card">';
                html += '<h2>' + escapeHtml(customer.name) + ' - Business Rules</h2>';
                html += '<p class="text-muted mb-20">';
                html += 'Masked rules are excluded from billing calculations. Toggle a rule\'s status using the buttons below.';
                html += '</p>';

                if (rules.length === 0) {
                    html += '<p class="text-muted">No business rules defined for this customer.</p>';
                } else {
                    html += '<table>';
                    html += '<thead><tr>';
                    html += '<th>Rule Name</th>';
                    html += '<th>Description</th>';
                    html += '<th>Status</th>';
                    html += '<th class="text-right">Actions</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';
                    for (var i = 0; i < rules.length; i++) {
                        var rule = rules[i];
                        html += '<tr>';
                        html += '<td><strong>' + escapeHtml(rule.rule_name) + '</strong></td>';
                        html += '<td>' + (rule.rule_description ? escapeHtml(rule.rule_description) : '<span class="text-muted">No description</span>') + '</td>';
                        html += '<td>';
                        if (rule.is_masked) {
                            html += '<span style="color: #e67e22; font-weight: bold;">MASKED</span>';
                        } else {
                            html += '<span style="color: #27ae60;">Active</span>';
                        }
                        html += '</td>';
                        html += '<td class="text-right">';
                        if (rule.is_masked) {
                            html += '<button class="btn btn-sm btn-success" data-rule="' + escapeHtml(rule.rule_name) + '" data-action="unmask">Unmask</button>';
                        } else {
                            html += '<button class="btn btn-sm" data-rule="' + escapeHtml(rule.rule_name) + '" data-action="mask">Mask</button>';
                        }
                        html += '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                }

                html += '</div>';

                html += '<div class="breadcrumb"><a href="?action=business_rules">Rules</a><span>/</span>' +
                    escapeHtml(customer.name) + '</div>';

                document.getElementById('page-data').innerHTML = html;

                // Bind toggle buttons
                var buttons = document.getElementById('page-data').querySelectorAll('button[data-rule]');
                for (var b = 0; b < buttons.length; b++) {
                    (function(btn) {
                        btn.addEventListener('click', function() {
                            var ruleName = btn.getAttribute('data-rule');
                            var maskAction = btn.getAttribute('data-action');
                            var confirmMsg = maskAction === 'mask'
                                ? 'Mask this rule? It will be excluded from billing.'
                                : 'Unmask this rule? It will be included in billing.';
                            if (!confirm(confirmMsg)) return;
                            btn.disabled = true;
                            btn.textContent = maskAction === 'mask' ? 'Masking...' : 'Unmasking...';
                            apiPost('toggle_business_rule',
                                'customer_id=' + encodeURIComponent(customerId) +
                                '&rule_name=' + encodeURIComponent(ruleName) +
                                '&mask_action=' + encodeURIComponent(maskAction),
                                function(postErr) {
                                    if (postErr) {
                                        showToast(postErr, 'error');
                                        btn.disabled = false;
                                        btn.textContent = maskAction === 'mask' ? 'Mask' : 'Unmask';
                                        return;
                                    }
                                    loadRules();
                                }
                            );
                        });
                    })(buttons[b]);
                }
            });
        }

        loadRules();
    })();
    </script>
<?php render_footer();
} /**
 * Render history/audit trail
 */
function render_history($data)
{
    render_header("History - Audit Trail"); ?>
    <div class="card">
        <h2>Change History</h2>
        <p class="text-muted mb-20">Audit trail of all configuration changes. Data is append-only with effective dates.</p>

        <form method="get" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="action" value="history">

            <label>Category:</label>
            <select name="filter" class="form-control" style="width: auto;">
                <option value="all" <?php echo isset($data["filter"]) &&
                $data["filter"] === "all"
                    ? "selected"
                    : ""; ?>>All Changes</option>
                <option value="pricing" <?php echo isset($data["filter"]) &&
                $data["filter"] === "pricing"
                    ? "selected"
                    : ""; ?>>Pricing Tiers</option>
                <option value="settings" <?php echo isset($data["filter"]) &&
                $data["filter"] === "settings"
                    ? "selected"
                    : ""; ?>>Customer Settings</option>
                <option value="escalators" <?php echo isset($data["filter"]) &&
                $data["filter"] === "escalators"
                    ? "selected"
                    : ""; ?>>Escalators</option>
                <option value="rules" <?php echo isset($data["filter"]) &&
                $data["filter"] === "rules"
                    ? "selected"
                    : ""; ?>>Business Rules</option>
            </select>

            <label>Customer:</label>
            <select name="customer_id" id="history-customer-select" class="form-control" style="width: auto;">
                <option value="">All Customers</option>
            </select>

            <button type="submit" class="btn">Filter</button>
        </form>

        <div id="history-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading history...</p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('filter')) params.filter = urlParams.get('filter');
        if (urlParams.get('customer_id')) params.customer_id = urlParams.get('customer_id');
        if (urlParams.get('page')) params.page = urlParams.get('page');

        var catColors = {pricing:'#3498db', settings:'#9b59b6', escalators:'#e67e22', rules:'#1abc9c'};

        apiGet('history', params, function(err, data) {
            if (err) { showAjaxError('history-content', err); return; }

            // Populate customer dropdown
            var sel = document.getElementById('history-customer-select');
            if (data.customers) {
                for (var i = 0; i < data.customers.length; i++) {
                    var opt = document.createElement('option');
                    opt.value = data.customers[i].id;
                    opt.textContent = data.customers[i].name;
                    if (params.customer_id && String(data.customers[i].id) === String(params.customer_id)) opt.selected = true;
                    sel.appendChild(opt);
                }
            }

            // Table
            var el = document.getElementById('history-content');
            if (!data.history || data.history.length === 0) {
                el.innerHTML = '<p class="text-muted">No history records found.</p>';
                return;
            }
            var html = '<table><thead><tr><th>Date/Time</th><th>Category</th><th>Effective Date</th><th>Description</th></tr></thead><tbody>';
            for (var i = 0; i < data.history.length; i++) {
                var h = data.history[i];
                var color = catColors[h.category] || '#999';
                html += '<tr>';
                html += '<td style="white-space: nowrap;">' + escapeHtml(h.date) + '</td>';
                html += '<td><span style="background: ' + color + '; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">' + escapeHtml(h.category) + '</span></td>';
                html += '<td style="white-space: nowrap;">' + escapeHtml(h.effective_date) + '</td>';
                html += '<td>' + escapeHtml(h.description) + '</td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
            if (data.pagination && data.pagination.total_pages > 1) {
                html += buildPagination(data.pagination, '?action=history', params);
            }
            el.innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render billing calendar year view
 */
function render_calendar($data)
{
    render_header("Billing Calendar - Control Panel"); ?>
    <div id="calendar-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading calendar...</p>
        </div>
    </div>
    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var year = urlParams.get('year') || new Date().getFullYear();
        apiGet('calendar', {year: year}, function(err, data) {
            if (err) { showAjaxError('calendar-content', err); return; }
            var y = data.year;
            document.title = 'Billing Calendar - ' + y;
            var html = '<div class="card">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">';
            html += '<h2>Billing Calendar ' + y + '</h2><div>';
            html += '<a href="?action=calendar&year=' + (y-1) + '" class="btn btn-sm">&larr; ' + (y-1) + '</a> ';
            html += '<a href="?action=calendar&year=' + new Date().getFullYear() + '" class="btn btn-sm">Today</a> ';
            html += '<a href="?action=calendar&year=' + (y+1) + '" class="btn btn-sm">' + (y+1) + ' &rarr;</a>';
            html += '</div></div>';
            html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px;">';
            for (var i = 0; i < data.months.length; i++) {
                var m = data.months[i];
                var borderColor = '#ddd', bgColor = '#fff';
                if (m.is_current) { borderColor = '#007bff'; bgColor = '#e3f2fd'; }
                else if (m.is_complete) { borderColor = '#28a745'; bgColor = '#d4edda'; }
                else if (m.is_past) { borderColor = '#dc3545'; }
                html += '<a href="?action=calendar_month&year=' + m.year + '&month=' + m.month + '" style="text-decoration:none;color:inherit;">';
                html += '<div style="border:2px solid ' + borderColor + ';border-radius:8px;padding:15px;text-align:center;background:' + bgColor + ';transition:transform 0.1s;cursor:pointer;" onmouseover="this.style.transform=\'scale(1.02)\'" onmouseout="this.style.transform=\'scale(1)\'">';
                html += '<div style="font-size:18px;font-weight:bold;margin-bottom:5px;">' + escapeHtml(m.month_name) + '</div>';
                html += '<div style="display:flex;justify-content:center;gap:10px;font-size:12px;">';
                if (m.event_count > 0) html += '<span style="background:#e67e22;color:white;padding:2px 6px;border-radius:10px;">' + m.event_count + ' event' + (m.event_count > 1 ? 's' : '') + '</span>';
                if (m.warning_count > 0) html += '<span style="background:#dc3545;color:white;padding:2px 6px;border-radius:10px;">' + m.warning_count + ' warning' + (m.warning_count > 1 ? 's' : '') + '</span>';
                html += '</div>';
                html += '<div style="margin-top:8px;font-size:11px;color:#666;">';
                if (m.is_complete) html += '<span style="color:#28a745;">&#10003; Complete</span>';
                else if (m.is_past) html += '<span style="color:#dc3545;">&#9888; Incomplete</span>';
                else if (m.is_current) html += '<span style="color:#007bff;">&#9679; Current Month</span>';
                else html += '<span>Upcoming</span>';
                html += '</div></div></a>';
            }
            html += '</div>';
            html += '<div style="background:#f8f9fa;padding:15px;border-radius:5px;text-align:center;margin-bottom:20px;">';
            html += '<div style="font-size:18px;font-weight:bold;margin-bottom:5px;">Progress Summary</div>';
            html += '<div style="display:flex;justify-content:space-around;font-size:14px;">';
            html += '<span>Completed: <strong style="color:#28a745;">' + data.completed_months + '</strong></span>';
            html += '<span>Escalators: <strong style="color:#e67e22;">' + data.total_escalators + '</strong></span>';
            html += '<span>Resets: <strong style="color:#3498db;">' + data.total_resets + '</strong></span>';
            html += '</div>';
            if (data.next_incomplete) {
                var mn = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                html += '<a href="?action=calendar_month&year=' + data.next_incomplete.year + '&month=' + data.next_incomplete.month + '" class="btn btn-sm btn-success" style="margin-top:10px;">&#9989; What\'s Next? (' + mn[data.next_incomplete.month - 1] + ' ' + data.next_incomplete.year + ')</a>';
            }
            html += '</div>';
            html += '<div class="card"><h3>Legend</h3>';
            html += '<div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;">';
            html += '<div><span style="display:inline-block;width:20px;height:20px;background:#d4edda;border:2px solid #28a745;border-radius:3px;vertical-align:middle;margin-right:5px;"></span> Complete (monthly report ingested)</div>';
            html += '<div><span style="display:inline-block;width:20px;height:20px;background:#e3f2fd;border:2px solid #007bff;border-radius:3px;vertical-align:middle;margin-right:5px;"></span> Current Month</div>';
            html += '<div><span style="display:inline-block;width:20px;height:20px;background:#fff;border:2px solid #dc3545;border-radius:3px;vertical-align:middle;margin-right:5px;"></span> Past &amp; Incomplete</div>';
            html += '<div><span style="display:inline-block;width:20px;height:20px;background:#fff;border:2px solid #ddd;border-radius:3px;vertical-align:middle;margin-right:5px;"></span> Upcoming</div>';
            html += '</div></div>';
            html += '</div>';
            document.getElementById('calendar-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
}
/**
 * Render billing calendar month checklist view
 */ function render_calendar_month($data)
{
    render_header("Billing Checklist - Control Panel"); ?>
    <div id="cal-month-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading checklist...</p>
        </div>
    </div>
    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var year = urlParams.get('year') || new Date().getFullYear();
        var month = urlParams.get('month') || (new Date().getMonth() + 1);
        apiGet('calendar_month', {year: year, month: month}, function(err, data) {
            if (err) { showAjaxError('cal-month-content', err); return; }
            document.title = data.month_name + ' ' + data.year + ' - Billing Checklist';
            var html = '<div class="card">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;"><div>';
            html += '<a href="?action=calendar&year=' + data.year + '" style="color:#666;text-decoration:none;">&larr; Back to Calendar</a>';
            html += '<h2 style="margin:10px 0 0 0;">' + escapeHtml(data.month_name) + ' ' + data.year + ' Checklist';
            if (data.is_complete) html += ' <span style="background:#28a745;color:white;padding:4px 12px;border-radius:15px;font-size:12px;margin-left:10px;">COMPLETE</span>';
            else if (data.is_current) html += ' <span style="background:#007bff;color:white;padding:4px 12px;border-radius:15px;font-size:12px;margin-left:10px;">CURRENT</span>';
            html += '</h2></div><div>';
            html += '<a href="?action=calendar_month&year=' + data.prev.year + '&month=' + data.prev.month + '" class="btn btn-sm">&larr; Prev</a> ';
            html += '<a href="?action=calendar_month&year=' + new Date().getFullYear() + '&month=' + (new Date().getMonth()+1) + '" class="btn btn-sm">Today</a> ';
            html += '<a href="?action=calendar_month&year=' + data.next.year + '&month=' + data.next.month + '" class="btn btn-sm">Next &rarr;</a>';
            html += '</div></div>';
            if (data.total_items === 0) {
                html += '<div style="text-align:center;padding:40px;color:#666;"><div style="font-size:48px;margin-bottom:10px;">&#10003;</div>';
                html += '<div style="font-size:18px;">No special items for this month</div>';
                html += '<div style="font-size:13px;margin-top:5px;">Standard billing should proceed normally</div></div>';
            } else {
                var cl = data.checklist;
                if (cl.warnings && cl.warnings.length > 0) {
                    html += '<div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;padding:15px;margin-bottom:20px;">';
                    html += '<h3 style="margin:0 0 10px 0;color:#721c24;">&#9888; Warnings (' + cl.warnings.length + ')</h3><ul style="margin:0;padding-left:20px;">';
                    for (var i = 0; i < cl.warnings.length; i++) {
                        html += '<li style="margin-bottom:5px;">' + escapeHtml(cl.warnings[i].message);
                        if (cl.warnings[i].customer_id) html += ' <a href="?action=pricing_customer_edit&customer_id=' + cl.warnings[i].customer_id + '" style="font-size:11px;">[view]</a>';
                        html += '</li>';
                    }
                    html += '</ul></div>';
                }
                if (cl.whats_excluded && cl.whats_excluded.length > 0) {
                    html += '<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:5px;padding:15px;margin-bottom:20px;">';
                    html += '<h3 style="margin:0 0 10px 0;color:#856404;">&#10007; What\'s Excluded (' + cl.whats_excluded.length + ')</h3>';
                    html += '<p style="margin:0 0 10px 0;font-size:12px;color:#856404;">These customers will NOT be billed this month:</p><ul style="margin:0;padding-left:20px;">';
                    for (var i = 0; i < cl.whats_excluded.length; i++) {
                        html += '<li style="margin-bottom:5px;">' + escapeHtml(cl.whats_excluded[i].message);
                        html += ' <a href="?action=pricing_customer_edit&customer_id=' + cl.whats_excluded[i].customer_id + '" style="font-size:11px;">[view]</a></li>';
                    }
                    html += '</ul></div>';
                }
                if (cl.whats_new && cl.whats_new.length > 0) {
                    html += '<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;padding:15px;margin-bottom:20px;">';
                    html += '<h3 style="margin:0 0 10px 0;color:#155724;">&#9733; What\'s New (' + cl.whats_new.length + ')</h3>';
                    html += '<p style="margin:0 0 10px 0;font-size:12px;color:#155724;">New customers added since last month:</p><ul style="margin:0;padding-left:20px;">';
                    for (var i = 0; i < cl.whats_new.length; i++) {
                        html += '<li style="margin-bottom:5px;">' + escapeHtml(cl.whats_new[i].message);
                        html += ' <a href="?action=pricing_customer_edit&customer_id=' + cl.whats_new[i].customer_id + '" style="font-size:11px;">[view config]</a></li>';
                    }
                    html += '</ul></div>';
                }
                if (cl.whats_changing && cl.whats_changing.length > 0) {
                    html += '<div style="background:#fff3cd;border:1px solid #ffeeba;border-radius:5px;padding:15px;margin-bottom:20px;">';
                    html += '<h3 style="margin:0 0 10px 0;color:#856404;">&#8593; What\'s Changing (' + cl.whats_changing.length + ')</h3>';
                    html += '<p style="margin:0 0 10px 0;font-size:12px;color:#856404;">Price changes taking effect this month:</p>';
                    html += '<table style="width:100%;font-size:13px;"><thead><tr style="text-align:left;"><th>Type</th><th>Description</th><th>Effective</th><th></th></tr></thead><tbody>';
                    for (var i = 0; i < cl.whats_changing.length; i++) {
                        var c = cl.whats_changing[i];
                        var tColor = c.type === 'escalator' ? '#e67e22' : '#3498db';
                        html += '<tr><td><span style="background:' + tColor + ';color:white;padding:2px 6px;border-radius:3px;font-size:10px;text-transform:uppercase;">' + escapeHtml(c.type) + '</span></td>';
                        html += '<td>' + escapeHtml(c.message) + '</td>';
                        html += '<td>' + escapeHtml(c.effective_date || '') + '</td>';
                        html += '<td><a href="?action=pricing_customer_edit&customer_id=' + c.customer_id + '" style="font-size:11px;">[view]</a></td></tr>';
                    }
                    html += '</tbody></table></div>';
                }
                if (cl.whats_different && cl.whats_different.length > 0) {
                    html += '<div style="background:#e2e3e5;border:1px solid #d6d8db;border-radius:5px;padding:15px;margin-bottom:20px;">';
                    html += '<h3 style="margin:0 0 10px 0;color:#383d41;">&#9881; Config Changes (' + cl.whats_different.length + ')</h3>';
                    html += '<p style="margin:0 0 10px 0;font-size:12px;color:#383d41;">Configuration changes made since last month:</p>';
                    html += '<ul style="margin:0;padding-left:20px;font-size:13px;">';
                    var showCount = Math.min(cl.whats_different.length, 10);
                    for (var i = 0; i < showCount; i++) {
                        html += '<li style="margin-bottom:3px;">' + escapeHtml(cl.whats_different[i].message);
                        html += ' <span style="color:#999;font-size:11px;">(' + escapeHtml(cl.whats_different[i].date || '') + ')</span></li>';
                    }
                    if (cl.whats_different.length > 10) html += '<li style="color:#666;">...and ' + (cl.whats_different.length - 10) + ' more</li>';
                    html += '</ul></div>';
                }
            }
            html += '</div>';
            // MTD Summary
            if (data.mtd && data.mtd.report_count > 0) {
                html += '<div class="card"><div style="display:flex;justify-content:space-between;align-items:center;">';
                html += '<h3 style="margin:0;">Month-to-Date Summary</h3>';
                html += '<a href="?action=mtd_dashboard&year=' + data.year + '&month=' + data.month + '" class="btn btn-sm">View Full Dashboard &rarr;</a></div>';
                html += '<p style="font-size:12px;color:#666;margin:10px 0 15px 0;">Based on ' + data.mtd.report_count + ' daily report(s), latest: ' + escapeHtml(data.mtd.latest_date || '') + '</p>';
                html += '<div style="display:flex;gap:20px;flex-wrap:wrap;">';
                html += '<div style="background:#f8f9fa;padding:15px;border-radius:5px;flex:1;min-width:120px;"><div style="font-size:24px;font-weight:bold;">' + numberFormat(data.mtd.customer_count, 0) + '</div><div style="color:#666;font-size:12px;">Customers Billed</div></div>';
                html += '<div style="background:#f8f9fa;padding:15px;border-radius:5px;flex:1;min-width:120px;"><div style="font-size:24px;font-weight:bold;">' + numberFormat(data.mtd.total_transactions, 0) + '</div><div style="color:#666;font-size:12px;">Transactions</div></div>';
                html += '<div style="background:#f8f9fa;padding:15px;border-radius:5px;flex:1;min-width:120px;"><div style="font-size:24px;font-weight:bold;">$' + numberFormat(data.mtd.total_revenue, 2) + '</div><div style="color:#666;font-size:12px;">Revenue MTD</div></div>';
                html += '</div></div>';
            }
            html += '<div class="card"><h3>Final Output</h3><p>When ready, generate the monthly billing report:</p>';
            html += '<a href="?action=generation" class="btn btn-success">Go to Report Generation</a></div>';
            document.getElementById('cal-month-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render Month-to-Date Dashboard
 */
function render_mtd_dashboard($data)
{
    render_header("MTD Dashboard - Control Panel"); ?>
    <div id="mtd-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading MTD dashboard...</p>
        </div>
    </div>
    <script>
    (function() {
        var urlParams = new URLSearchParams(window.location.search);
        var year = urlParams.get('year') || new Date().getFullYear();
        var month = urlParams.get('month') || (new Date().getMonth() + 1);
        var mn = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        apiGet('mtd_dashboard', {year: year, month: month}, function(err, data) {
            if (err) { showAjaxError('mtd-content', err); return; }
            document.title = 'MTD Dashboard - ' + data.month_name + ' ' + data.year;
            var html = '<div class="card">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;"><div>';
            html += '<a href="?action=calendar&year=' + data.year + '" style="color:#666;text-decoration:none;">&larr; Back to Calendar</a>';
            html += '<h2 style="margin:10px 0 0 0;">' + escapeHtml(data.month_name) + ' ' + data.year + ' - Month to Date';
            if (data.is_current) html += ' <span style="background:#007bff;color:white;padding:4px 12px;border-radius:15px;font-size:12px;margin-left:10px;">LIVE</span>';
            html += '</h2>';
            if (data.through_day > 0) html += '<p style="color:#666;font-size:13px;margin-top:5px;">Data through day ' + data.through_day + ' (' + data.mtd.report_count + ' daily reports)</p>';
            html += '</div><div>';
            html += '<a href="?action=mtd_dashboard&year=' + data.prev.year + '&month=' + data.prev.month + '" class="btn btn-sm">&larr; Prev</a> ';
            html += '<a href="?action=mtd_dashboard&year=' + new Date().getFullYear() + '&month=' + (new Date().getMonth()+1) + '" class="btn btn-sm">Today</a> ';
            html += '<a href="?action=mtd_dashboard&year=' + data.next.year + '&month=' + data.next.month + '" class="btn btn-sm">Next &rarr;</a>';
            html += '</div></div>';
            if (!data.mtd || data.mtd.report_count == 0) {
                html += '<div style="text-align:center;padding:60px;color:#666;"><div style="font-size:48px;margin-bottom:15px;">&#128202;</div>';
                html += '<div style="font-size:18px;">No daily reports for this month yet</div>';
                html += '<div style="font-size:13px;margin-top:5px;">Daily reports are automatically ingested to populate this dashboard</div>';
                html += '<div style="margin-top:20px;"><a href="?action=ingestion" class="btn">Go to Ingestion</a></div></div>';
            } else {
                var avgPerDay = data.through_day > 0 ? data.mtd.total_revenue / data.through_day : 0;
                var daysInMonth = new Date(data.year, data.month, 0).getDate();
                var projected = avgPerDay * daysInMonth;
                html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:30px;">';
                html += '<div style="background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:#28a745;">$' + numberFormat(data.mtd.total_revenue, 2) + '</div><div style="color:#666;font-size:12px;margin-top:5px;">Revenue MTD</div>';
                if (data.prev_mtd && data.prev_mtd.total_revenue > 0) {
                    var rc = data.revenue_change;
                    html += '<div style="font-size:12px;margin-top:8px;color:' + (rc >= 0 ? '#28a745' : '#dc3545') + ';">' + (rc >= 0 ? '&#9650;' : '&#9660;') + ' ' + Math.abs(Math.round(rc * 10) / 10) + '% vs last month</div>';
                }
                html += '</div>';
                html += '<div style="background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:#3498db;">' + numberFormat(data.mtd.total_transactions, 0) + '</div><div style="color:#666;font-size:12px;margin-top:5px;">Transactions MTD</div>';
                if (data.prev_mtd && data.prev_mtd.total_transactions > 0) {
                    var tc = data.trans_change;
                    html += '<div style="font-size:12px;margin-top:8px;color:' + (tc >= 0 ? '#28a745' : '#dc3545') + ';">' + (tc >= 0 ? '&#9650;' : '&#9660;') + ' ' + Math.abs(Math.round(tc * 10) / 10) + '% vs last month</div>';
                }
                html += '</div>';
                html += '<div style="background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:#9b59b6;">' + numberFormat(data.mtd.customer_count, 0) + '</div><div style="color:#666;font-size:12px;margin-top:5px;">Active Customers</div></div>';
                html += '<div style="background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:#e67e22;">$' + numberFormat(projected, 2) + '</div><div style="color:#666;font-size:12px;margin-top:5px;">Projected Month End</div>';
                html += '<div style="font-size:11px;color:#999;margin-top:5px;">Based on $' + numberFormat(avgPerDay, 2) + '/day avg</div></div>';
                html += '</div>';
                // Daily Revenue
                if (data.cumulative && data.cumulative.length > 0) {
                    var maxRev = 0;
                    for (var i = 0; i < data.cumulative.length; i++) { if (data.cumulative[i].daily_revenue > maxRev) maxRev = data.cumulative[i].daily_revenue; }
                    html += '<h3 style="margin-bottom:15px;">Daily Revenue</h3>';
                    html += '<div style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:30px;overflow-x:auto;">';
                    html += '<table style="width:100%;font-size:12px;border-collapse:collapse;"><thead><tr style="border-bottom:1px solid #ddd;">';
                    html += '<th style="text-align:left;padding:5px;width:80px;">Date</th><th style="text-align:left;padding:5px;">Revenue</th>';
                    html += '<th style="text-align:right;padding:5px;width:100px;">Amount</th><th style="text-align:right;padding:5px;width:100px;">Cumulative</th></tr></thead><tbody>';
                    for (var i = 0; i < data.cumulative.length; i++) {
                        var d = data.cumulative[i];
                        var bw = maxRev > 0 ? (d.daily_revenue / maxRev) * 100 : 0;
                        html += '<tr style="border-bottom:1px solid #eee;"><td style="padding:5px;">' + escapeHtml(d.date).substr(5) + '</td>';
                        html += '<td style="padding:5px;"><div style="background:#28a745;height:16px;width:' + bw + '%;min-width:2px;border-radius:2px;"></div></td>';
                        html += '<td style="text-align:right;padding:5px;">$' + numberFormat(d.daily_revenue, 2) + '</td>';
                        html += '<td style="text-align:right;padding:5px;color:#666;">$' + numberFormat(d.cumulative_revenue, 2) + '</td></tr>';
                    }
                    html += '</tbody></table></div>';
                }
                // Services
                if (data.services && data.services.length > 0) {
                    html += '<h3 style="margin-bottom:15px;">Revenue by Service</h3><div style="margin-bottom:30px;"><table><thead><tr>';
                    html += '<th>Service</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th class="text-right">% of Total</th></tr></thead><tbody>';
                    for (var i = 0; i < data.services.length; i++) {
                        var sv = data.services[i];
                        var pct = data.mtd.total_revenue > 0 ? (sv.revenue / data.mtd.total_revenue) * 100 : 0;
                        html += '<tr><td>' + escapeHtml(sv.service_name || '(Unknown)') + '</td><td class="text-right">' + numberFormat(sv.transactions, 0) + '</td>';
                        html += '<td class="text-right">$' + numberFormat(sv.revenue, 2) + '</td><td class="text-right">' + numberFormat(pct, 1) + '%</td></tr>';
                    }
                    html += '</tbody></table></div>';
                }
                // Top Customers
                if (data.customers && data.customers.length > 0) {
                    html += '<h3 style="margin-bottom:15px;">Top Customers</h3><div style="margin-bottom:20px;"><table><thead><tr>';
                    html += '<th>Customer</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th class="text-right">% of Total</th></tr></thead><tbody>';
                    var showCust = Math.min(data.customers.length, 15);
                    for (var i = 0; i < showCust; i++) {
                        var cu = data.customers[i];
                        var pct = data.mtd.total_revenue > 0 ? (cu.revenue / data.mtd.total_revenue) * 100 : 0;
                        html += '<tr><td>';
                        if (cu.customer_id) html += '<a href="?action=pricing_customer_edit&customer_id=' + cu.customer_id + '">' + escapeHtml(cu.customer_name || 'Customer #' + cu.customer_id) + '</a>';
                        else html += escapeHtml(cu.customer_name || '(Unknown)');
                        html += '</td><td class="text-right">' + numberFormat(cu.transactions, 0) + '</td>';
                        html += '<td class="text-right">$' + numberFormat(cu.revenue, 2) + '</td><td class="text-right">' + numberFormat(pct, 1) + '%</td></tr>';
                    }
                    if (data.customers.length > 15) html += '<tr><td colspan="4" style="text-align:center;color:#666;font-style:italic;">...and ' + (data.customers.length - 15) + ' more customers</td></tr>';
                    html += '</tbody></table></div>';
                }
            }
            html += '</div>';
            html += '<div style="display:flex;gap:15px;">';
            html += '<a href="?action=calendar_month&year=' + data.year + '&month=' + data.month + '" class="btn">View Month Checklist</a>';
            html += '<a href="?action=ingestion" class="btn">Manage Reports</a></div>';
            document.getElementById('mtd-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
}
/**
 * Render export options page
 */ function render_export()
{
    render_header("Export Data"); ?>
    <div class="card">
        <h2>Export Configuration Data</h2>
        <p class="text-muted mb-20">Download current configuration data as CSV files for backup or reporting.</p>

        <table>
            <thead>
                <tr>
                    <th>Export Type</th>
                    <th>Description</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Pricing Tiers</strong></td>
                    <td>All current pricing tiers (defaults, group overrides, customer overrides)</td>
                    <td class="text-right">
                        <a href="?action=export_pricing" class="btn btn-sm btn-success">Download CSV</a>
                    </td>
                </tr>
                <tr>
                    <td><strong>Customer Settings</strong></td>
                    <td>Customer configurations (monthly minimum, annualized settings)</td>
                    <td class="text-right">
                        <a href="?action=export_settings" class="btn btn-sm btn-success">Download CSV</a>
                    </td>
                </tr>
                <tr>
                    <td><strong>Escalators</strong></td>
                    <td>Annual price escalators with delays</td>
                    <td class="text-right">
                        <a href="?action=export_escalators" class="btn btn-sm btn-success">Download CSV</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Upload Configuration</h2>
        <p class="text-muted">Upload a configuration CSV file to submit for processing by the cron job.</p>
        <a href="?action=upload_config" class="btn">Upload Config CSV</a>
    </div>
<?php
} // ============================================================
// BILLING REPORTS VIEWS
// ============================================================
/**
 * Render Billing Reports main view
 * Shows ingestion reports and generated reports
 */
function render_billing_reports($data)
{
    render_header("Billing Reports - Control Panel"); ?>
    <style>
        .br-header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .br-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 600; }
        .br-header .subtitle { opacity: 0.9; font-size: 14px; }
        .br-tabs { display: flex; gap: 0; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .br-tab { padding: 12px 24px; cursor: pointer; border: none; background: none; font-size: 14px; font-weight: 500; color: #666; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .br-tab:hover { color: #333; }
        .br-tab.active { color: #2c3e50; border-bottom-color: #2c3e50; }
        .br-panel { display: none; }
        .br-panel.active { display: block; }
        .br-section { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .br-section h3 { margin: 0 0 15px 0; font-size: 16px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .br-section h3 .count { font-size: 12px; color: #666; font-weight: normal; background: #f0f0f0; padding: 2px 8px; border-radius: 10px; }
        .br-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .br-table th, .br-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #eee; }
        .br-table th { font-size: 10px; text-transform: uppercase; color: #666; background: #f8f9fa; }
        .br-table tr:hover { background: #f8f9fa; }
        .br-table .mono { font-family: monospace; font-size: 12px; }
        .br-table .actions { white-space: nowrap; }
        .br-table .actions a { margin-left: 8px; }
        .br-link { color: #2c3e50; text-decoration: none; font-weight: 500; }
        .br-link:hover { text-decoration: underline; }
        .br-empty { color: #999; font-style: italic; padding: 20px; text-align: center; }
        .br-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .br-badge.tier { background: #e8f5e9; color: #2e7d32; }
        .br-badge.displayname { background: #e3f2fd; color: #1565c0; }
        .br-badge.custom { background: #fff3e0; color: #ef6c00; }
        .br-badge.daily { background: #f3e5f5; color: #7b1fa2; }
        .br-badge.monthly { background: #fce4ec; color: #c2185b; }
        .br-badge.ebcdic { background: #eceff1; color: #546e7a; }
    </style>

    <div class="br-header">
        <h1>Billing Reports</h1>
        <div class="subtitle">Ingestion reports from external systems and generated configuration exports</div>
    </div>

    <div class="br-tabs">
        <button class="br-tab active" data-tab="ingestion">Ingestion Reports</button>
        <button class="br-tab" data-tab="generated">Generated Reports</button>
    </div>

    <!-- Ingestion Reports Panel -->
    <div class="br-panel active" id="panel-ingestion">
        <div id="ingestion-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading ingestion reports...</p>
            </div>
        </div>
    </div>

    <!-- Generated Reports Panel -->
    <div class="br-panel" id="panel-generated">
        <div id="generated-content" class="ajax-content">
            <div class="loading-skeleton">
                <div class="skeleton-bar w75"></div>
                <div class="skeleton-bar w90"></div>
                <div class="skeleton-bar w50"></div>
                <p>Loading generated reports...</p>
            </div>
        </div>
    </div>

    <script>
    function brFileTable(files, type, badgeClass, badgeLabel, limit) {
        if (!files || files.length === 0) return '<div class="br-empty">No ' + badgeLabel.toLowerCase() + ' reports found</div>';
        var shown = limit ? files.slice(0, limit) : files;
        var html = '<table class="br-table"><thead><tr><th>Filename</th><th>Size</th><th>Modified</th><th class="text-right">Actions</th></tr></thead><tbody>';
        for (var i = 0; i < shown.length; i++) {
            var f = shown[i];
            html += '<tr><td><span class="br-badge ' + badgeClass + '">' + badgeLabel + '</span> ';
            html += '<a href="?action=view_billing_report&type=ingestion&file=' + encodeURIComponent(f.name) + '" class="br-link">' + escapeHtml(f.name) + '</a></td>';
            html += '<td class="mono">' + formatFilesize(f.size) + '</td>';
            html += '<td>' + formatDate(f.modified) + '</td>';
            html += '<td class="text-right actions"><a href="?action=view_billing_report&type=ingestion&file=' + encodeURIComponent(f.name) + '" class="btn btn-sm">View</a> ';
            html += '<a href="?action=download_billing_report&type=ingestion&file=' + encodeURIComponent(f.name) + '" class="btn btn-sm btn-success">Download</a></td></tr>';
        }
        html += '</tbody></table>';
        if (limit && files.length > limit) {
            html += '<p style="margin-top: 10px; color: #666; font-size: 12px;">Showing ' + limit + ' of ' + files.length + ' files</p>';
        }
        return html;
    }

    function brGenTable(reports, badgeClass, badgeLabel, showType) {
        if (!reports || reports.length === 0) return '<div class="br-empty">No ' + badgeLabel.toLowerCase() + ' reports archived yet</div>';
        var html = '<table class="br-table"><thead><tr><th>Filename</th>';
        if (showType) html += '<th>Type</th>';
        html += '<th>Records</th><th>Size</th><th>Generated</th><th>Notes</th><th class="text-right">Actions</th></tr></thead><tbody>';
        for (var i = 0; i < reports.length; i++) {
            var r = reports[i];
            var opacity = !r.exists ? ' style="opacity: 0.5;"' : '';
            html += '<tr' + opacity + '><td><span class="br-badge ' + badgeClass + '">' + badgeLabel + '</span> ';
            if (r.exists) {
                html += '<a href="?action=view_billing_report&type=generated&id=' + r.id + '" class="br-link">' + escapeHtml(r.file_name) + '</a>';
            } else {
                html += '<span style="color: #999;">' + escapeHtml(r.file_name) + ' (missing)</span>';
            }
            html += '</td>';
            if (showType) html += '<td>' + escapeHtml(r.report_subtype || r.report_type || '') + '</td>';
            html += '<td class="mono">' + (r.record_count ? parseInt(r.record_count).toLocaleString() : '0') + '</td>';
            html += '<td class="mono">' + formatFilesize(r.file_size || 0) + '</td>';
            html += '<td>' + (r.generated_at ? r.generated_at.substring(0, 16).replace('T', ' ') : '') + '</td>';
            html += '<td>' + escapeHtml(r.notes || '') + '</td>';
            html += '<td class="text-right actions">';
            if (r.exists) {
                html += '<a href="?action=view_billing_report&type=generated&id=' + r.id + '" class="btn btn-sm">View</a> ';
                html += '<a href="?action=download_billing_report&type=generated&id=' + r.id + '" class="btn btn-sm btn-success">Download</a>';
                if (badgeClass === 'tier') {
                    html += ' <a href="?action=regenerate_report&report_type=tier_pricing&compare_id=' + r.id + '" class="btn btn-sm" style="background: #8e44ad; color: white;">Compare</a>';
                }
            }
            html += '</td></tr>';
        }
        html += '</tbody></table>';
        return html;
    }

    apiGet('billing_reports', null, function(err, data) {
        if (err) {
            showAjaxError('ingestion-content', err);
            showAjaxError('generated-content', err);
            return;
        }
        var ing = data.ingestion_reports || {};
        var gen = data.generated_reports || {};

        // Ingestion panel
        var ihtml = '';
        ihtml += '<div class="br-section"><h3>Daily Humanreadable <span class="count">' + ((ing.daily_humanreadable || []).length) + ' files</span></h3>';
        ihtml += brFileTable(ing.daily_humanreadable, 'ingestion', 'daily', 'Daily', 10);
        ihtml += '</div>';
        ihtml += '<div class="br-section"><h3>Monthly Humanreadable <span class="count">' + ((ing.monthly_humanreadable || []).length) + ' files</span></h3>';
        ihtml += brFileTable(ing.monthly_humanreadable, 'ingestion', 'monthly', 'Monthly');
        ihtml += '</div>';
        ihtml += '<div class="br-section"><h3>Monthly EBCDIC <span class="count">' + ((ing.monthly_ebcdic || []).length) + ' files</span></h3>';
        ihtml += brFileTable(ing.monthly_ebcdic, 'ingestion', 'ebcdic', 'EBCDIC');
        ihtml += '</div>';
        document.getElementById('ingestion-content').innerHTML = ihtml;

        // Generated panel
        var ghtml = '';
        ghtml += '<div class="br-section"><h3>Tier Pricing Reports <span class="count">' + ((gen.tier_pricing || []).length) + ' reports</span></h3>';
        ghtml += brGenTable(gen.tier_pricing, 'tier', 'Tier Pricing', false);
        if (gen.tier_pricing && gen.tier_pricing.length >= 2) {
            ghtml += '<div style="margin-top: 15px;"><form method="get" style="display: inline-flex; gap: 10px; align-items: center;">';
            ghtml += '<input type="hidden" name="action" value="compare_reports"><label style="font-size: 12px;">Compare two archived reports:</label>';
            ghtml += '<select name="report_id_1" style="font-size: 12px; padding: 4px;">';
            for (var i = 0; i < gen.tier_pricing.length; i++) { ghtml += '<option value="' + gen.tier_pricing[i].id + '">' + escapeHtml(gen.tier_pricing[i].file_name) + '</option>'; }
            ghtml += '</select><span style="font-size: 12px;">vs</span><select name="report_id_2" style="font-size: 12px; padding: 4px;">';
            for (var i = gen.tier_pricing.length - 1; i >= 0; i--) { ghtml += '<option value="' + gen.tier_pricing[i].id + '">' + escapeHtml(gen.tier_pricing[i].file_name) + '</option>'; }
            ghtml += '</select><button type="submit" class="btn btn-sm" style="background: #8e44ad; color: white;">Compare</button></form></div>';
        }
        ghtml += '</div>';
        ghtml += '<div class="br-section"><h3>DisplayName to Type Mappings <span class="count">' + ((gen.displayname_to_type || []).length) + ' reports</span></h3>';
        ghtml += brGenTable(gen.displayname_to_type, 'displayname', 'Mapping', false);
        ghtml += '</div>';
        ghtml += '<div class="br-section"><h3>Custom Reports <span class="count">' + ((gen.custom || []).length) + ' reports</span></h3>';
        ghtml += brGenTable(gen.custom, 'custom', 'Custom', true);
        ghtml += '</div>';
        document.getElementById('generated-content').innerHTML = ghtml;
    });

    // Tab switching
    document.querySelectorAll('.br-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.br-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.br-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('panel-' + this.dataset.tab).classList.add('active');
        });
    });
    </script>
<?php render_footer();
} /**
 * Render view billing report (CSV preview)
 */
function render_view_billing_report($data)
{
    render_header("View Report - Control Panel"); ?>
<style>
.report-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}
.report-header h2 {
    margin: 0;
    font-size: 1.4em;
    color: #333;
}
.report-header .actions {
    display: flex;
    gap: 10px;
}
.report-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.9em;
    color: #555;
}
.report-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}
.report-meta .meta-item strong {
    color: #333;
}
.data-preview {
    overflow-x: auto;
    margin-top: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
}
.data-preview table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88em;
}
.data-preview table th,
.data-preview table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
    white-space: nowrap;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.data-preview table th {
    background: #f1f3f5;
    font-weight: 600;
    color: #333;
    position: sticky;
    top: 0;
    z-index: 1;
}
.data-preview table tr:hover td {
    background: #f8f9fa;
}
.data-preview table tr:last-child td {
    border-bottom: none;
}
.data-preview-note {
    margin-top: 10px;
    padding: 8px 12px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    font-size: 0.88em;
    color: #856404;
}
</style>

<div id="vbr-content" class="ajax-content">
    <div class="card">
        <div style="padding: 40px; text-align: center; color: #999;">
            <div class="loading-skeleton" style="width: 200px; height: 24px; background: #eee; border-radius: 4px; margin: 0 auto 16px;"></div>
            <div class="loading-skeleton" style="width: 300px; height: 16px; background: #eee; border-radius: 4px; margin: 0 auto 12px;"></div>
            <div class="loading-skeleton" style="width: 100%; height: 200px; background: #eee; border-radius: 4px; margin: 0 auto;"></div>
            <p>Loading report data...</p>
        </div>
    </div>
</div>

<script>
(function() {
    var params = new URLSearchParams(window.location.search);
    var type = params.get('type');
    var id = params.get('id');
    var file = params.get('file');

    apiGet('view_billing_report', {type: type, id: id, file: file}, function(err, data) {
        var container = document.getElementById('vbr-content');
        if (err) {
            container.innerHTML = '<div class="card"><div style="padding:20px;color:#c00;">Error loading report: ' + escapeHtml(err) + '</div></div>';
            return;
        }

        var downloadUrl = '?action=download_billing_report&type=' + encodeURIComponent(data.type);
        if (data.type === 'generated' && data.report_info) {
            downloadUrl += '&id=' + data.report_info.id;
        } else {
            downloadUrl += '&file=' + encodeURIComponent(data.filename);
        }

        var html = '';
        html += '<div class="card">';

        // Report header
        html += '<div class="report-header">';
        html += '<h2>' + escapeHtml(data.filename) + '</h2>';
        html += '<div class="actions">';
        html += '<a href="' + downloadUrl + '" class="btn btn-primary">Download</a>';
        html += '<a href="?action=billing_reports" class="btn btn-secondary">Back</a>';
        html += '</div>';
        html += '</div>';

        // Report meta
        html += '<div class="report-meta">';
        html += '<div class="meta-item"><strong>Rows:</strong> ' + numberFormat(data.count) + '</div>';
        html += '<div class="meta-item"><strong>Columns:</strong> ' + numberFormat(data.headers.length) + '</div>';
        if (data.report_info) {
            if (data.report_info.generated_at) {
                html += '<div class="meta-item"><strong>Generated:</strong> ' + escapeHtml(data.report_info.generated_at) + '</div>';
            }
            if (data.report_info.notes) {
                html += '<div class="meta-item"><strong>Notes:</strong> ' + escapeHtml(data.report_info.notes) + '</div>';
            }
        }
        html += '</div>';

        // Data preview table
        html += '<div class="data-preview">';
        html += '<table>';
        html += '<thead><tr>';
        for (var h = 0; h < data.headers.length; h++) {
            html += '<th>' + escapeHtml(data.headers[h]) + '</th>';
        }
        html += '</tr></thead>';
        html += '<tbody>';
        for (var r = 0; r < data.rows.length; r++) {
            html += '<tr>';
            for (var c = 0; c < data.headers.length; c++) {
                var cellValue = data.rows[r][data.headers[c]] !== undefined ? data.rows[r][data.headers[c]] : '';
                html += '<td title="' + escapeHtml(String(cellValue)) + '">' + escapeHtml(String(cellValue)) + '</td>';
            }
            html += '</tr>';
        }
        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        // Truncation note
        if (data.count > 100) {
            html += '<div class="data-preview-note">Showing first 100 of ' + numberFormat(data.count) + ' rows</div>';
        }

        html += '</div>';

        // Breadcrumb
        html += '<div class="breadcrumb" style="margin-top:15px;font-size:0.9em;color:#888;">';
        html += '<a href="?action=billing_reports">Billing Reports</a> &gt; ' + escapeHtml(data.filename);
        html += '</div>';

        container.innerHTML = html;
    });
})();
</script>
<?php render_footer();
} /**
 * Render compare reports view (side-by-side diff)
 */
function render_compare_reports($data)
{
    render_header("Compare Reports - Control Panel");
    $comparison = $data["comparison"];
    ?>
    <style>
        .compare-header { background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .compare-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 600; }
        .compare-header .subtitle { opacity: 0.9; font-size: 14px; }
        .compare-files { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .compare-file { background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .compare-file h4 { margin: 0 0 10px 0; font-size: 14px; color: #333; }
        .compare-file .meta { font-size: 12px; color: #666; }
        .compare-file .meta span { margin-right: 15px; }
        .compare-file.new { border-left: 4px solid #27ae60; }
        .compare-file.old { border-left: 4px solid #3498db; }
        .compare-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .compare-stat { background: white; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .compare-stat .num { font-size: 32px; font-weight: 700; }
        .compare-stat .lbl { font-size: 11px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .compare-stat.added .num { color: #27ae60; }
        .compare-stat.removed .num { color: #e74c3c; }
        .compare-stat.changed .num { color: #f39c12; }
        .compare-stat.unchanged .num { color: #95a5a6; }
        .compare-section { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .compare-section h3 { margin: 0 0 15px 0; font-size: 16px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .compare-section h3 .count { font-size: 12px; font-weight: normal; color: #666; }
        .compare-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .compare-table th, .compare-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
        .compare-table th { font-size: 10px; text-transform: uppercase; color: #666; background: #f8f9fa; }
        .compare-table tr:hover { background: #f8f9fa; }
        .compare-table .mono { font-family: monospace; }
        .diff-added { background: #d5f5e3; }
        .diff-removed { background: #fadbd8; }
        .diff-changed { background: #fef9e7; }
        .diff-value { display: inline-block; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 11px; }
        .diff-value.old { background: #fadbd8; color: #922b21; text-decoration: line-through; }
        .diff-value.new { background: #d5f5e3; color: #1e8449; }
        .empty-state { text-align: center; padding: 30px; color: #999; font-style: italic; }
    </style>

    <div class="compare-header">
        <h1>Compare Reports</h1>
        <div class="subtitle">Side-by-side comparison of tier pricing configurations</div>
    </div>

    <!-- File Info -->
    <div class="compare-files">
        <div class="compare-file new">
            <h4><?php echo h($data["file1"]["label"]); ?></h4>
            <div class="meta">
                <span><strong>Generated:</strong> <?php echo h(
                    $data["file1"]["generated"],
                ); ?></span>
                <span><strong>Rows:</strong> <?php echo number_format(
                    $data["file1"]["row_count"],
                ); ?></span>
            </div>
        </div>
        <div class="compare-file old">
            <h4><?php echo h($data["file2"]["label"]); ?></h4>
            <div class="meta">
                <span><strong>Generated:</strong> <?php echo h(
                    $data["file2"]["generated"],
                ); ?></span>
                <span><strong>Rows:</strong> <?php echo number_format(
                    $data["file2"]["row_count"],
                ); ?></span>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="compare-summary">
        <div class="compare-stat added">
            <div class="num"><?php echo number_format(
                count($comparison["added"]),
            ); ?></div>
            <div class="lbl">Added</div>
        </div>
        <div class="compare-stat removed">
            <div class="num"><?php echo number_format(
                count($comparison["removed"]),
            ); ?></div>
            <div class="lbl">Removed</div>
        </div>
        <div class="compare-stat changed">
            <div class="num"><?php echo number_format(
                count($comparison["changed"]),
            ); ?></div>
            <div class="lbl">Changed</div>
        </div>
        <div class="compare-stat unchanged">
            <div class="num"><?php echo number_format(
                $comparison["unchanged"],
            ); ?></div>
            <div class="lbl">Unchanged</div>
        </div>
    </div>

    <!-- Changed Rows -->
    <?php if (!empty($comparison["changed"])): ?>
    <div class="compare-section">
        <h3>Changed Rows <span class="count">(<?php echo count(
            $comparison["changed"],
        ); ?>)</span></h3>
        <table class="compare-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>EFX Code</th>
                    <th>Tier</th>
                    <th>Changes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (
                    array_slice($comparison["changed"], 0, 50)
                    as $change
                ): ?>
                <tr class="diff-changed">
                    <td class="mono"><?php echo h(
                        $change["row"]["cust_id"],
                    ); ?></td>
                    <td class="mono"><?php echo h(
                        $change["row"]["EFX_code"],
                    ); ?></td>
                    <td class="mono"><?php echo h(
                        $change["row"]["start_trans"],
                    ); ?>-<?php echo h($change["row"]["end_trans"]); ?></td>
                    <td>
                        <?php foreach ($change["changes"] as $col => $vals): ?>
                            <div style="margin-bottom: 4px;">
                                <strong><?php echo h($col); ?>:</strong>
                                <span class="diff-value old"><?php echo h(
                                    $vals["old"],
                                ); ?></span>
                                &rarr;
                                <span class="diff-value new"><?php echo h(
                                    $vals["new"],
                                ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($comparison["changed"]) > 50): ?>
            <p style="margin-top: 10px; color: #666; font-size: 12px;">Showing 50 of <?php echo count(
                $comparison["changed"],
            ); ?> changed rows</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Added Rows -->
    <?php if (!empty($comparison["added"])): ?>
    <div class="compare-section">
        <h3>Added Rows <span class="count">(<?php echo count(
            $comparison["added"],
        ); ?>)</span></h3>
        <table class="compare-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Group</th>
                    <th>EFX Code</th>
                    <th>Type</th>
                    <th>Tier</th>
                    <th>Adj Price</th>
                    <th>Base Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (
                    array_slice($comparison["added"], 0, 50)
                    as $row
                ): ?>
                <tr class="diff-added">
                    <td class="mono"><?php echo h($row["cust_id"]); ?></td>
                    <td><?php echo h($row["discount_group"]); ?></td>
                    <td class="mono"><?php echo h($row["EFX_code"]); ?></td>
                    <td><?php echo h($row["type"]); ?></td>
                    <td class="mono"><?php echo h(
                        $row["start_trans"],
                    ); ?>-<?php echo h($row["end_trans"]); ?></td>
                    <td class="mono"><?php echo h($row["adj_price"]); ?></td>
                    <td class="mono"><?php echo h($row["base_price"]); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($comparison["added"]) > 50): ?>
            <p style="margin-top: 10px; color: #666; font-size: 12px;">Showing 50 of <?php echo count(
                $comparison["added"],
            ); ?> added rows</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Removed Rows -->
    <?php if (!empty($comparison["removed"])): ?>
    <div class="compare-section">
        <h3>Removed Rows <span class="count">(<?php echo count(
            $comparison["removed"],
        ); ?>)</span></h3>
        <table class="compare-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Group</th>
                    <th>EFX Code</th>
                    <th>Type</th>
                    <th>Tier</th>
                    <th>Adj Price</th>
                    <th>Base Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (
                    array_slice($comparison["removed"], 0, 50)
                    as $row
                ): ?>
                <tr class="diff-removed">
                    <td class="mono"><?php echo h($row["cust_id"]); ?></td>
                    <td><?php echo h($row["discount_group"]); ?></td>
                    <td class="mono"><?php echo h($row["EFX_code"]); ?></td>
                    <td><?php echo h($row["type"]); ?></td>
                    <td class="mono"><?php echo h(
                        $row["start_trans"],
                    ); ?>-<?php echo h($row["end_trans"]); ?></td>
                    <td class="mono"><?php echo h($row["adj_price"]); ?></td>
                    <td class="mono"><?php echo h($row["base_price"]); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($comparison["removed"]) > 50): ?>
            <p style="margin-top: 10px; color: #666; font-size: 12px;">Showing 50 of <?php echo count(
                $comparison["removed"],
            ); ?> removed rows</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (
        empty($comparison["added"]) &&
        empty($comparison["removed"]) &&
        empty($comparison["changed"])
    ): ?>
    <div class="compare-section">
        <div class="empty-state">No differences found between these reports.</div>
    </div>
    <?php endif; ?>

    <div class="breadcrumb">
        <a href="?action=billing_reports">Billing Reports</a>
        <span>/</span>
        Compare
    </div>
<?php render_footer();
} // ============================================================
// BILLING DASHBOARD VIEWS
// ============================================================
/**
 * Render Billing Dashboard main view
 * LMS Performance + Tier Proximity Analysis
 */
function render_billing_intelligence($data)
{
    render_header("Billing Dashboard - Control Panel"); ?>
    <style>
        .bi-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .bi-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 600; }
        .bi-header .subtitle { opacity: 0.9; font-size: 14px; }
        .bi-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 25px; }
        .bi-stat { background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; }
        .bi-stat .value { font-size: 26px; font-weight: 700; color: #1e3c72; }
        .bi-stat .label { font-size: 11px; color: #666; text-transform: uppercase; margin-top: 4px; }
        .bi-section { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .bi-section h3 { margin: 0 0 15px 0; font-size: 16px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .bi-section h3 .period { font-size: 12px; color: #666; font-weight: normal; }
        .bi-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .bi-table th, .bi-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #eee; }
        .bi-table th { font-size: 10px; text-transform: uppercase; color: #666; background: #f8f9fa; }
        .bi-table tr:hover { background: #f8f9fa; }
        .bi-table .mono { font-family: monospace; font-size: 12px; }
        .bi-table .positive { color: #27ae60; }
        .bi-table .negative { color: #e74c3c; }
        .bi-link { color: #2a5298; text-decoration: none; font-weight: 500; }
        .bi-link:hover { text-decoration: underline; }
        .progress-bar { background: #eee; border-radius: 10px; height: 20px; overflow: hidden; position: relative; }
        .progress-bar .fill { height: 100%; border-radius: 10px; transition: width 0.3s; display: flex; align-items: center; justify-content: flex-end; padding-right: 6px; font-size: 10px; font-weight: 600; color: white; }
        .progress-bar .fill.green { background: linear-gradient(90deg, #27ae60, #2ecc71); }
        .progress-bar .fill.yellow { background: linear-gradient(90deg, #f39c12, #f1c40f); }
        .progress-bar .fill.red { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        .tier-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .tier-badge.likely { background: #d5f5e3; color: #1e8449; }
        .tier-badge.possible { background: #fef9e7; color: #b7950b; }
        .tier-badge.unlikely { background: #fadbd8; color: #922b21; }
        .lms-totals { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .lms-total-item { text-align: center; }
        .lms-total-item .value { font-size: 20px; font-weight: 700; font-family: monospace; }
        .lms-total-item .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .month-progress { background: #f8f9fa; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; }
        .month-progress .days { font-size: 24px; font-weight: 700; color: #1e3c72; }
        .month-progress .info { font-size: 12px; color: #666; }
        .upgrade-summary { display: flex; gap: 15px; margin-bottom: 15px; }
        .upgrade-stat { flex: 1; padding: 12px; border-radius: 8px; text-align: center; }
        .upgrade-stat.likely { background: #d5f5e3; }
        .upgrade-stat.possible { background: #fef9e7; }
        .upgrade-stat.unlikely { background: #fadbd8; }
        .upgrade-stat .num { font-size: 28px; font-weight: 700; }
        .upgrade-stat .lbl { font-size: 11px; text-transform: uppercase; }
    </style>
    <div id="bi-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading billing dashboard...</p>
        </div>
    </div>
    <script>
    (function() {
        var mn = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        apiGet('billing_intelligence', null, function(err, data) {
            if (err) { showAjaxError('bi-content', err); return; }
            var p = data.current_period;
            var monthName = mn[p.month - 1] + ' ' + p.year;
            var tier = data.tier_proximity;
            var lms = data.lms_performance;
            var html = '';
            // Header
            html += '<div class="bi-header"><h1>Billing Dashboard</h1>';
            html += '<div class="subtitle">' + monthName + ' | Day ' + tier.days_elapsed + ' of ' + tier.days_in_month + ' (' + Math.round(tier.month_progress_pct) + '% complete)</div></div>';
            // LMS Performance
            html += '<div class="bi-section"><h3>LMS Performance <span class="period">' + monthName + ' MTD</span></h3>';
            if (lms.lms_list && lms.lms_list.length > 0) {
                var t = lms.totals;
                html += '<div class="lms-totals">';
                html += '<div class="lms-total-item"><div class="value" style="color:#27ae60;">$' + numberFormat(t.revenue, 0) + '</div><div class="label">Revenue</div></div>';
                html += '<div class="lms-total-item"><div class="value" style="color:#e74c3c;">$' + numberFormat(t.cogs, 0) + '</div><div class="label">COGS</div></div>';
                html += '<div class="lms-total-item"><div class="value" style="color:#3498db;">$' + numberFormat(t.gross_profit, 0) + '</div><div class="label">Gross Profit</div></div>';
                html += '<div class="lms-total-item"><div class="value" style="color:#9b59b6;">$' + numberFormat(t.commission, 0) + '</div><div class="label">Commission</div></div>';
                html += '<div class="lms-total-item"><div class="value" style="color:#2ecc71;">$' + numberFormat(t.net_profit, 0) + '</div><div class="label">Net Profit</div></div>';
                html += '</div>';
                html += '<div style="overflow-x:auto;"><table class="bi-table"><thead><tr><th>LMS</th><th class="text-right">Customers</th><th class="text-right">Txns</th><th class="text-right">Revenue</th><th class="text-right">COGS</th><th class="text-right">Gross</th><th class="text-right">Rate</th><th class="text-right">Commission</th><th class="text-right">Net</th><th class="text-right">Margin</th></tr></thead><tbody>';
                for (var i = 0; i < lms.lms_list.length; i++) {
                    var l = lms.lms_list[i];
                    html += '<tr><td><a href="?action=lms_edit&lms_id=' + encodeURIComponent(l.id) + '" class="bi-link">' + escapeHtml(l.name) + '</a>';
                    if (l.is_default_rate) html += ' <small class="text-muted">(dflt)</small>';
                    html += '</td><td class="text-right">' + l.customer_count + '</td>';
                    html += '<td class="text-right mono">' + numberFormat(l.transactions, 0) + '</td>';
                    html += '<td class="text-right mono positive">$' + numberFormat(l.revenue, 0) + '</td>';
                    html += '<td class="text-right mono negative">$' + numberFormat(l.cogs, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(l.gross_profit, 0) + '</td>';
                    html += '<td class="text-right">' + numberFormat(l.commission_rate, 1) + '%</td>';
                    html += '<td class="text-right mono" style="color:#9b59b6;">$' + numberFormat(l.commission, 0) + '</td>';
                    html += '<td class="text-right mono positive">$' + numberFormat(l.net_profit, 0) + '</td>';
                    html += '<td class="text-right">' + numberFormat(l.margin_pct, 1) + '%</td></tr>';
                }
                html += '</tbody></table></div>';
            } else {
                html += '<p class="text-muted">No LMS data available for this period.</p>';
            }
            html += '</div>';
            // Tier Proximity
            html += '<div class="bi-section"><h3>Tier Proximity Analysis <span class="period">' + tier.days_remaining + ' days remaining</span></h3>';
            html += '<div class="month-progress"><div class="days">' + tier.days_remaining + '</div><div class="info"><strong>Days Remaining</strong><br>' + tier.days_elapsed + ' days elapsed, ' + Math.round(tier.month_progress_pct) + '% through month</div>';
            html += '<div style="flex:1;"><div class="progress-bar"><div class="fill green" style="width:' + tier.month_progress_pct + '%;">' + Math.round(tier.month_progress_pct) + '%</div></div></div></div>';
            html += '<div class="upgrade-summary">';
            html += '<div class="upgrade-stat likely"><div class="num" style="color:#1e8449;">' + tier.likely_to_upgrade + '</div><div class="lbl">Likely to Upgrade</div></div>';
            html += '<div class="upgrade-stat possible"><div class="num" style="color:#b7950b;">' + tier.possible_upgrade + '</div><div class="lbl">Possible</div></div>';
            html += '<div class="upgrade-stat unlikely"><div class="num" style="color:#922b21;">' + tier.unlikely_upgrade + '</div><div class="lbl">Unlikely</div></div>';
            html += '</div>';
            if (tier.customers && tier.customers.length > 0) {
                html += '<div style="overflow-x:auto;"><table class="bi-table"><thead><tr><th>Customer</th><th>Service</th><th class="text-right">MTD Vol</th><th class="text-right">Next Tier @</th><th class="text-right">Need</th><th>Progress</th><th class="text-right">Daily Rate</th><th class="text-right">Projected</th><th class="text-right">Probability</th><th class="text-right">Price Drop</th><th>Chart</th></tr></thead><tbody>';
                var showCount = Math.min(tier.customers.length, 20);
                for (var i = 0; i < showCount; i++) {
                    var c = tier.customers[i];
                    var probClass = c.hit_probability_pct >= 70 ? 'likely' : (c.hit_probability_pct >= 30 ? 'possible' : 'unlikely');
                    var barClass = c.hit_probability_pct >= 70 ? 'green' : (c.hit_probability_pct >= 30 ? 'yellow' : 'red');
                    html += '<tr><td><a href="?action=billing_customer&id=' + encodeURIComponent(c.customer_id) + '" class="bi-link">' + escapeHtml(c.customer_name) + '</a></td>';
                    html += '<td><small>' + escapeHtml(c.efx_code) + '</small></td>';
                    html += '<td class="text-right mono">' + numberFormat(c.mtd_count, 0) + '</td>';
                    html += '<td class="text-right mono">' + numberFormat(c.next_tier_threshold, 0) + '</td>';
                    html += '<td class="text-right mono">' + numberFormat(c.distance_to_next, 0) + '</td>';
                    html += '<td style="min-width:100px;"><div class="progress-bar" style="height:16px;"><div class="fill ' + barClass + '" style="width:' + Math.min(100, c.progress_to_next_pct) + '%;"></div></div></td>';
                    html += '<td class="text-right mono">' + numberFormat(c.daily_rate, 0) + '/day</td>';
                    html += '<td class="text-right mono">' + numberFormat(c.projected_eom, 0) + '</td>';
                    html += '<td class="text-right"><span class="tier-badge ' + probClass + '">' + Math.round(c.hit_probability_pct) + '%</span></td>';
                    html += '<td class="text-right positive">-' + numberFormat(c.price_reduction_pct, 1) + '%</td>';
                    html += '<td><a href="?action=billing_customer_daily&id=' + encodeURIComponent(c.customer_id) + '&year=' + p.year + '&month=' + p.month + '&efx_code=' + encodeURIComponent(c.efx_code) + '" class="bi-link">View</a></td></tr>';
                }
                html += '</tbody></table></div>';
                if (tier.customers.length > 20) html += '<p class="text-muted" style="margin-top:10px;">Showing top 20 of ' + tier.customers.length + ' customers approaching next tier.</p>';
            } else {
                html += '<p class="text-muted">No customers currently approaching a tier upgrade.</p>';
            }
            html += '</div>';
            // Recent Months + Audit Health
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">';
            html += '<div class="bi-section"><h3>Recent Months</h3>';
            if (data.monthly_data && data.monthly_data.length > 0) {
                html += '<table class="bi-table"><thead><tr><th>Month</th><th class="text-right">Txns</th><th class="text-right">Revenue</th></tr></thead><tbody>';
                for (var i = 0; i < data.monthly_data.length; i++) {
                    var m = data.monthly_data[i];
                    html += '<tr><td><a href="?action=billing_month&year=' + m.year + '&month=' + m.month + '" class="bi-link">' + mn[m.month - 1].substr(0, 3) + ' ' + m.year + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(m.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(m.revenue, 0) + '</td></tr>';
                }
                html += '</tbody></table>';
            } else html += '<p class="text-muted">No monthly data.</p>';
            html += '</div>';
            html += '<div class="bi-section"><h3>Audit Health</h3>';
            var v = data.variance_stats;
            if (v && v.total_audited > 0) {
                html += '<div style="display:flex;gap:15px;margin-bottom:15px;">';
                html += '<div style="text-align:center;flex:1;"><div style="font-size:28px;font-weight:700;color:#27ae60;">' + Math.round(v.match_pct) + '%</div><div style="font-size:11px;color:#666;">MATCH</div></div>';
                html += '<div style="text-align:center;flex:1;"><div style="font-size:28px;font-weight:700;color:#f39c12;">' + Math.round(v.small_var_pct) + '%</div><div style="font-size:11px;color:#666;">SMALL VAR</div></div>';
                html += '<div style="text-align:center;flex:1;"><div style="font-size:28px;font-weight:700;color:#e74c3c;">' + Math.round(v.large_var_pct) + '%</div><div style="font-size:11px;color:#666;">LARGE VAR</div></div>';
                html += '</div><p class="text-muted" style="font-size:11px;">Based on sample of ' + v.total_audited + ' lines</p>';
            } else html += '<p class="text-muted">No audit data available.</p>';
            html += '<div style="margin-top:15px;"><a href="?action=ingestion" class="btn btn-sm">View Reports</a></div></div>';
            html += '</div>';
            document.getElementById('bi-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render Billing Dashboard - Month drill-down
 */
function render_billing_month($data)
{
    $year = get_param("year", date("Y"));
    $month = get_param("month", date("n"));
    render_header("Billing: Month View - Control Panel");
    ?>
    <style>
        .bi-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .bi-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 600; }
        .bi-header .subtitle { opacity: 0.9; font-size: 14px; }
        .bi-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .bi-stat { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; }
        .bi-stat .value { font-size: 32px; font-weight: 700; color: #1e3c72; }
        .bi-stat .label { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .bi-stat.revenue .value { color: #27ae60; }
        .bi-section { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .bi-section h3 { margin: 0 0 15px 0; font-size: 16px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .bi-table { width: 100%; border-collapse: collapse; }
        .bi-table th, .bi-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .bi-table th { font-size: 11px; text-transform: uppercase; color: #666; background: #f8f9fa; }
        .bi-table tr:hover { background: #f8f9fa; }
        .bi-table .mono { font-family: monospace; }
        .bi-link { color: #2a5298; text-decoration: none; font-weight: 500; }
        .bi-link:hover { text-decoration: underline; }
        .bi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        @media (max-width: 900px) { .bi-grid { grid-template-columns: 1fr; } }
        .month-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .month-nav a { color: #2a5298; text-decoration: none; padding: 8px 16px; border: 1px solid #2a5298; border-radius: 4px; }
        .month-nav a:hover { background: #2a5298; color: white; }
    </style>

    <div class="breadcrumb">
        <a href="?action=billing_intelligence">Billing Dashboard</a>
        <span>/</span>
        Monthly View
    </div>

    <div id="bm-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading billing month...</p>
        </div>
    </div>

    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var year = params.get('year') || new Date().getFullYear();
        var month = params.get('month') || (new Date().getMonth()+1);
        year = parseInt(year, 10);
        month = parseInt(month, 10);

        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        apiGet('billing_month', {year: year, month: month}, function(err, data) {
            if (err) { showAjaxError('bm-content', err); return; }

            var stats = data.stats || {};
            var monthName = data.month_name || monthNames[month - 1] || '';

            // Prev/next month calculation
            var prevMonth = month - 1;
            var prevYear = year;
            if (prevMonth < 1) { prevMonth = 12; prevYear = prevYear - 1; }
            var nextMonth = month + 1;
            var nextYear = year;
            if (nextMonth > 12) { nextMonth = 1; nextYear = nextYear + 1; }

            var prevLabel = monthNames[prevMonth - 1].substr(0, 3) + ' ' + prevYear;
            var nextLabel = monthNames[nextMonth - 1].substr(0, 3) + ' ' + nextYear;

            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb">';
            html += '<a href="?action=billing_intelligence">Billing Dashboard</a>';
            html += ' <span>/</span> ';
            html += escapeHtml(monthName) + ' ' + year;
            html += '</div>';

            // Header
            html += '<div class="bi-header">';
            html += '<h1>' + escapeHtml(monthName) + ' ' + year + '</h1>';
            html += '<div class="subtitle">Monthly billing summary and breakdown</div>';
            html += '</div>';

            // Month Navigation
            html += '<div class="month-nav">';
            html += '<a href="?action=billing_month&year=' + prevYear + '&month=' + prevMonth + '">';
            html += '&larr; ' + escapeHtml(prevLabel);
            html += '</a>';
            html += '<a href="?action=billing_month&year=' + nextYear + '&month=' + nextMonth + '">';
            html += escapeHtml(nextLabel) + ' &rarr;';
            html += '</a>';
            html += '</div>';

            // Stats
            html += '<div class="bi-stats">';
            html += '<div class="bi-stat revenue"><div class="value">$' + numberFormat(stats.total_revenue || 0, 0) + '</div><div class="label">Revenue</div></div>';
            html += '<div class="bi-stat"><div class="value">' + numberFormat(stats.total_transactions || 0, 0) + '</div><div class="label">Transactions</div></div>';
            html += '<div class="bi-stat"><div class="value">' + numberFormat(stats.unique_customers || 0, 0) + '</div><div class="label">Customers</div></div>';
            html += '<div class="bi-stat"><div class="value">' + numberFormat(stats.report_count || 0, 0) + '</div><div class="label">Reports</div></div>';
            html += '</div>';

            // Grid: Customer Breakdown + Service Breakdown
            html += '<div class="bi-grid">';

            // Customer Breakdown
            html += '<div class="bi-section"><h3>Customer Breakdown</h3>';
            if (data.customer_breakdown && data.customer_breakdown.length > 0) {
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Customer</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th>';
                html += '</tr></thead><tbody>';
                var custLimit = Math.min(data.customer_breakdown.length, 15);
                for (var i = 0; i < custLimit; i++) {
                    var cust = data.customer_breakdown[i];
                    var custName = cust.customer_name || cust.customer_id;
                    html += '<tr>';
                    html += '<td><a href="?action=billing_customer&id=' + encodeURIComponent(cust.customer_id) + '" class="bi-link">' + escapeHtml(custName) + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(cust.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(cust.revenue, 0) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No customer data for this month.</p>';
            }
            html += '</div>';

            // Service Breakdown
            html += '<div class="bi-section"><h3>Service Breakdown</h3>';
            if (data.service_breakdown && data.service_breakdown.length > 0) {
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Service</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th>';
                html += '</tr></thead><tbody>';
                var svcLimit = Math.min(data.service_breakdown.length, 15);
                for (var i = 0; i < svcLimit; i++) {
                    var svc = data.service_breakdown[i];
                    var svcName = svc.service_name || svc.efx_code;
                    html += '<tr>';
                    html += '<td><a href="?action=billing_service&code=' + encodeURIComponent(svc.efx_code) + '" class="bi-link">' + escapeHtml(svcName) + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(svc.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(svc.revenue, 0) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No service data for this month.</p>';
            }
            html += '</div>';

            html += '</div>'; // end .bi-grid

            // Daily Breakdown
            if (data.daily_data && data.daily_data.length > 0) {
                html += '<div class="bi-section"><h3>Daily Breakdown</h3>';
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Date</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th class="text-right">Customers</th>';
                html += '</tr></thead><tbody>';
                for (var i = 0; i < data.daily_data.length; i++) {
                    var day = data.daily_data[i];
                    html += '<tr>';
                    html += '<td>' + escapeHtml(day.report_date) + '</td>';
                    html += '<td class="text-right mono">' + numberFormat(day.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(day.revenue, 2) + '</td>';
                    html += '<td class="text-right">' + numberFormat(day.customers, 0) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
            }

            // Reports
            if (data.reports && data.reports.length > 0) {
                html += '<div class="bi-section"><h3>Reports</h3>';
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Type</th><th>Date</th><th>Records</th><th>Actions</th>';
                html += '</tr></thead><tbody>';
                for (var i = 0; i < data.reports.length; i++) {
                    var report = data.reports[i];
                    var badgeClass = report.report_type === 'monthly' ? 'badge-success' : 'badge-info';
                    html += '<tr>';
                    html += '<td><span class="badge ' + badgeClass + '">' + escapeHtml(report.report_type) + '</span></td>';
                    html += '<td>' + escapeHtml(report.report_date) + '</td>';
                    html += '<td>' + numberFormat(report.record_count, 0) + '</td>';
                    html += '<td>';
                    html += '<a href="?action=ingestion_view&id=' + encodeURIComponent(report.id) + '" class="btn btn-sm">View</a> ';
                    html += '<a href="?action=report_audit&id=' + encodeURIComponent(report.id) + '" class="btn btn-sm">Audit</a>';
                    html += '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
            }

            document.getElementById('bm-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render Billing Dashboard - Customer drill-down
 */
function render_billing_customer($data)
{
    render_header("Customer Billing - Control Panel"); ?>
    <style>
        .bi-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .bi-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 600; }
        .bi-header .subtitle { opacity: 0.9; font-size: 14px; }
        .bi-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .bi-stat { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; }
        .bi-stat .value { font-size: 32px; font-weight: 700; color: #1e3c72; }
        .bi-stat .label { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .bi-stat.revenue .value { color: #27ae60; }
        .bi-section { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .bi-section h3 { margin: 0 0 15px 0; font-size: 16px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .bi-table { width: 100%; border-collapse: collapse; }
        .bi-table th, .bi-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .bi-table th { font-size: 11px; text-transform: uppercase; color: #666; background: #f8f9fa; }
        .bi-table tr:hover { background: #f8f9fa; }
        .bi-table .mono { font-family: monospace; }
        .bi-link { color: #2a5298; text-decoration: none; font-weight: 500; }
        .bi-link:hover { text-decoration: underline; }
        .bi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        @media (max-width: 900px) { .bi-grid { grid-template-columns: 1fr; } }
    </style>
    <div id="bc-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading customer billing data...</p>
        </div>
    </div>
    <script>
    (function() {
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var params = new URLSearchParams(window.location.search);
        var customerId = params.get('id');

        apiGet('billing_customer', {id: customerId}, function(err, data) {
            if (err) { showAjaxError('bc-content', err); return; }

            var customer = data.customer;
            var stats = data.stats;
            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb">';
            html += '<a href="?action=billing_intelligence">Billing Dashboard</a>';
            html += '<span>/</span>';
            html += escapeHtml(customer.customer_name);
            html += '</div>';

            // Header
            html += '<div class="bi-header" style="display: flex; justify-content: space-between; align-items: center;">';
            html += '<div>';
            html += '<h1>' + escapeHtml(customer.customer_name) + '</h1>';
            html += '<div class="subtitle">Customer ID: ' + escapeHtml(customer.customer_id) + ' | First seen: ' + escapeHtml(stats.first_seen || 'N/A') + ' | Last seen: ' + escapeHtml(stats.last_seen || 'N/A') + '</div>';
            html += '</div>';
            html += '<a href="?action=billing_customer_daily&id=' + encodeURIComponent(customer.customer_id) + '" class="btn" style="background: white; color: #1e3c72; font-weight: 600; padding: 12px 20px; border-radius: 6px; text-decoration: none;">View Daily Chart</a>';
            html += '</div>';

            // Stats
            html += '<div class="bi-stats">';
            html += '<div class="bi-stat revenue"><div class="value">$' + numberFormat(stats.total_revenue || 0, 0) + '</div><div class="label">Total Revenue</div></div>';
            html += '<div class="bi-stat"><div class="value">' + numberFormat(stats.total_transactions || 0, 0) + '</div><div class="label">Total Transactions</div></div>';
            html += '<div class="bi-stat"><div class="value">' + (stats.report_count || 0) + '</div><div class="label">Reports</div></div>';
            html += '</div>';

            // Grid: Monthly Trend + Services Used
            html += '<div class="bi-grid">';

            // Monthly Trend
            html += '<div class="bi-section">';
            html += '<h3>Monthly Trend</h3>';
            if (data.monthly_trend && data.monthly_trend.length > 0) {
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Month</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th>Chart</th>';
                html += '</tr></thead><tbody>';
                var trendSlice = data.monthly_trend.slice(0, 12);
                for (var i = 0; i < trendSlice.length; i++) {
                    var m = trendSlice[i];
                    var mName = monthNames[m.month - 1].substr(0, 3) + ' ' + m.year;
                    html += '<tr>';
                    html += '<td><a href="?action=billing_month&year=' + m.year + '&month=' + m.month + '" class="bi-link">' + escapeHtml(mName) + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(m.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(m.revenue, 0) + '</td>';
                    html += '<td><a href="?action=billing_customer_daily&id=' + encodeURIComponent(customer.customer_id) + '&year=' + m.year + '&month=' + m.month + '" class="bi-link">View</a></td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No monthly data available.</p>';
            }
            html += '</div>';

            // Services Used
            html += '<div class="bi-section">';
            html += '<h3>Services Used</h3>';
            if (data.service_breakdown && data.service_breakdown.length > 0) {
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Service</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th class="text-right">Avg Price</th>';
                html += '</tr></thead><tbody>';
                for (var i = 0; i < data.service_breakdown.length; i++) {
                    var svc = data.service_breakdown[i];
                    html += '<tr>';
                    html += '<td><a href="?action=billing_service&code=' + encodeURIComponent(svc.efx_code) + '" class="bi-link">' + escapeHtml(svc.service_name || svc.efx_code) + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(svc.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(svc.revenue, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(svc.avg_unit_cost, 4) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No service data available.</p>';
            }
            html += '</div>';

            html += '</div>'; // close bi-grid

            // Recent Line Items
            if (data.recent_lines && data.recent_lines.length > 0) {
                html += '<div class="bi-section">';
                html += '<h3>Recent Line Items</h3>';
                html += '<div style="overflow-x: auto;">';
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Date</th><th>Type</th><th>Service</th><th class="text-right">Count</th><th class="text-right">Unit Price</th><th class="text-right">Revenue</th><th>Audit</th>';
                html += '</tr></thead><tbody>';
                var linesSlice = data.recent_lines.slice(0, 20);
                for (var i = 0; i < linesSlice.length; i++) {
                    var line = linesSlice[i];
                    html += '<tr>';
                    html += '<td>' + escapeHtml(line.report_date) + '</td>';
                    html += '<td><span class="badge badge-' + (line.report_type === 'monthly' ? 'success' : 'info') + '">' + escapeHtml(line.report_type) + '</span></td>';
                    html += '<td>' + escapeHtml(line.tran_displayname || line.efx_code) + '</td>';
                    html += '<td class="text-right mono">' + numberFormat(line.count, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(line.actual_unit_cost, 4) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(line.revenue, 2) + '</td>';
                    html += '<td><a href="?action=line_audit&id=' + line.id + '" class="bi-link">Audit</a></td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
                html += '</div>';
                html += '</div>';
            }

            // Customer Configuration
            html += '<div class="bi-section">';
            html += '<h3>Customer Configuration</h3>';
            html += '<p>View and edit this customer\'s pricing configuration, escalators, and settings.</p>';
            html += '<a href="?action=customer_pricing&id=' + encodeURIComponent(customer.customer_id) + '" class="btn">View Pricing Configuration</a> ';
            html += '<a href="?action=pricing_customer_edit&customer_id=' + encodeURIComponent(customer.customer_id) + '" class="btn">Edit Pricing</a>';
            html += '</div>';

            document.getElementById('bc-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render Billing Dashboard - Customer Daily Chart
 * Shows cumulative vs delta (true daily) transaction counts
 */
function render_billing_customer_daily($data)
{
    render_header("Customer Daily Chart - Control Panel"); ?>
<style>
.bi-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.bi-header h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
}
.bi-header .subtitle {
    opacity: 0.85;
    font-size: 14px;
}
.bi-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}
.bi-stat {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px 20px;
    flex: 1;
    min-width: 140px;
    text-align: center;
}
.bi-stat .stat-value {
    font-size: 22px;
    font-weight: bold;
    color: #333;
}
.bi-stat .stat-label {
    font-size: 12px;
    color: #777;
    margin-top: 4px;
}
.bi-stat.projection {
    border-color: #f5a623;
    background: #fff8ef;
}
.bi-stat.projection .stat-value {
    color: #f5a623;
}
.bi-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.bi-section h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
}
.chart-container {
    position: relative;
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}
.chart-legend {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
    font-size: 13px;
}
.chart-legend span {
    display: flex;
    align-items: center;
    gap: 5px;
}
.chart-legend .swatch {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
}
.bi-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.bi-table th, .bi-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eee;
    text-align: right;
}
.bi-table th {
    background: #f8f8fa;
    font-weight: 600;
    color: #555;
    text-align: right;
}
.bi-table th:first-child, .bi-table td:first-child {
    text-align: left;
}
.bi-table tr:hover {
    background: #f9f9fb;
}
.data-table-container {
    max-height: 500px;
    overflow-y: auto;
}
.filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}
.filter-bar label {
    font-size: 13px;
    color: #555;
    font-weight: 600;
}
.filter-bar select {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}
.bar-container {
    display: inline-block;
    height: 14px;
    vertical-align: middle;
}
.bar {
    display: inline-block;
    height: 14px;
    border-radius: 3px;
    vertical-align: middle;
}
.bar.cumulative {
    background: #764ba2;
}
.bar.delta {
    background: #43a047;
}
</style>

<div id="bcd-content" class="ajax-content">
    <div style="padding:40px;text-align:center;color:#999;">Loading daily billing data...</div>
</div>

<script>
(function() {
    var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    var params = new URLSearchParams(window.location.search);
    var customerId = params.get('id');
    var year = params.get('year');
    var month = params.get('month');
    var efxCode = params.get('efx_code') || '';

    apiGet('billing_customer_daily', {id: customerId, year: year, month: month, efx_code: efxCode}, function(err, data) {
        if (err) {
            document.getElementById('bcd-content').innerHTML = '<div style="padding:40px;text-align:center;color:#c00;">Error loading data: ' + escapeHtml(err) + '</div>';
            return;
        }

        var customer = data.customer || {};
        var stats = data.stats || {};
        var chartData = data.chart_data || [];
        var availableMonths = data.available_months || [];
        var availableServices = data.available_services || [];
        var monthName = data.month_name || '';
        var yr = data.year || '';
        var mn = data.month || '';
        var isCurrentMonth = data.is_current_month || false;
        var projectedEom = data.projected_eom || 0;

        var html = '';

        // Breadcrumb
        html += '<div style="margin-bottom:15px;font-size:13px;color:#777;">';
        html += '<a href="?action=billing_dashboard">Billing Dashboard</a> &gt; ';
        html += escapeHtml(customer.customer_name) + ' &gt; ';
        html += 'Daily Chart - ' + escapeHtml(monthName) + ' ' + escapeHtml(yr);
        html += '</div>';

        // Header
        html += '<div class="bi-header">';
        html += '<h2>' + escapeHtml(customer.customer_name) + ' &mdash; Daily Chart</h2>';
        html += '<div class="subtitle">' + escapeHtml(monthName) + ' ' + escapeHtml(yr) + ' &middot; Customer ID: ' + escapeHtml(customer.customer_id) + '</div>';
        html += '</div>';

        // Filter bar
        html += '<div class="filter-bar">';

        // Month dropdown
        html += '<label>Month:</label>';
        html += '<select onchange="if(this.value){var p=this.value.split(\'-\');window.location=\'?action=billing_customer_daily&id=' + encodeURIComponent(customerId) + '&year=\'+p[0]+\'&month=\'+p[1]+\'&efx_code=' + encodeURIComponent(efxCode) + '\';}">';
        for (var i = 0; i < availableMonths.length; i++) {
            var am = availableMonths[i];
            var amVal = am.year + '-' + am.month;
            var amLabel = monthNames[am.month - 1] + ' ' + am.year;
            var amSelected = (am.year == yr && am.month == mn) ? ' selected' : '';
            html += '<option value="' + escapeHtml(amVal) + '"' + amSelected + '>' + escapeHtml(amLabel) + '</option>';
        }
        html += '</select>';

        // Service dropdown
        html += '<label>Service:</label>';
        html += '<select onchange="window.location=\'?action=billing_customer_daily&id=' + encodeURIComponent(customerId) + '&year=' + encodeURIComponent(yr) + '&month=' + encodeURIComponent(mn) + '&efx_code=\'+encodeURIComponent(this.value);">';
        html += '<option value="">All Services</option>';
        for (var j = 0; j < availableServices.length; j++) {
            var svc = availableServices[j];
            var svcSelected = (svc.efx_code == data.efx_code) ? ' selected' : '';
            html += '<option value="' + escapeHtml(svc.efx_code) + '"' + svcSelected + '>' + escapeHtml(svc.service_name) + '</option>';
        }
        html += '</select>';

        html += '</div>';

        // Stats
        html += '<div class="bi-stats">';
        html += '<div class="bi-stat"><div class="stat-value">' + numberFormat(stats.total_count) + '</div><div class="stat-label">MTD Total</div></div>';
        html += '<div class="bi-stat"><div class="stat-value">$' + numberFormat(stats.total_revenue) + '</div><div class="stat-label">MTD Revenue</div></div>';
        html += '<div class="bi-stat"><div class="stat-value">' + numberFormat(stats.avg_daily) + '</div><div class="stat-label">Avg Daily</div></div>';
        html += '<div class="bi-stat"><div class="stat-value">' + numberFormat(stats.max_daily) + '</div><div class="stat-label">Max Daily</div></div>';
        html += '<div class="bi-stat"><div class="stat-value">' + numberFormat(stats.min_daily) + '</div><div class="stat-label">Min Daily</div></div>';
        if (isCurrentMonth && projectedEom > 0) {
            html += '<div class="bi-stat projection"><div class="stat-value">' + numberFormat(projectedEom) + '</div><div class="stat-label">Projected EOM</div></div>';
        }
        html += '</div>';

        // Chart section
        html += '<div class="bi-section">';
        html += '<h3>Daily Activity Chart</h3>';
        html += '<p style="font-size:13px;color:#777;margin-bottom:10px;">Cumulative (MTD) totals shown as area, true daily deltas shown as bars.</p>';
        html += '<div class="chart-legend">';
        html += '<span><span class="swatch" style="background:#764ba2;"></span> Cumulative (MTD)</span>';
        html += '<span><span class="swatch" style="background:#43a047;"></span> Delta (Daily)</span>';
        html += '</div>';
        html += '<div class="chart-container"><canvas id="dailyChart"></canvas></div>';
        html += '</div>';

        // Data table
        html += '<div class="bi-section">';
        html += '<h3>Daily Data</h3>';
        html += '<div class="data-table-container">';
        html += '<table class="bi-table">';
        html += '<thead><tr>';
        html += '<th>Day</th><th>Date</th><th>Cumulative</th><th>Cumulative Visual</th><th>Delta</th><th>Delta Visual</th><th>Cumulative $</th><th>Delta $</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        var maxCumulative = 0;
        var maxDelta = 0;
        for (var k = 0; k < chartData.length; k++) {
            if (chartData[k].cumulative > maxCumulative) maxCumulative = chartData[k].cumulative;
            if (chartData[k].delta > maxDelta) maxDelta = chartData[k].delta;
        }

        for (var m = 0; m < chartData.length; m++) {
            var row = chartData[m];
            var cumBarWidth = maxCumulative > 0 ? Math.round((row.cumulative / maxCumulative) * 120) : 0;
            var deltaBarWidth = maxDelta > 0 ? Math.round((row.delta / maxDelta) * 80) : 0;
            html += '<tr>';
            html += '<td style="text-align:left;">' + escapeHtml(row.day) + '</td>';
            html += '<td>' + escapeHtml(row.date) + '</td>';
            html += '<td>' + numberFormat(row.cumulative) + '</td>';
            html += '<td><div class="bar-container"><span class="bar cumulative" style="width:' + cumBarWidth + 'px;"></span></div></td>';
            html += '<td>' + numberFormat(row.delta) + '</td>';
            html += '<td><div class="bar-container"><span class="bar delta" style="width:' + deltaBarWidth + 'px;"></span></div></td>';
            html += '<td>$' + numberFormat(row.cumulative_revenue) + '</td>';
            html += '<td>$' + numberFormat(row.delta_revenue) + '</td>';
            html += '</tr>';
        }

        html += '</tbody></table>';
        html += '</div></div>';

        document.getElementById('bcd-content').innerHTML = html;

        // Load Chart.js dynamically
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = function() { buildChart(data.chart_data); };
        document.head.appendChild(script);
    });

    function buildChart(chartData) {
        var labels = [];
        var cumulativeValues = [];
        var deltaValues = [];
        var cumulativeRevenue = [];
        var deltaRevenue = [];

        for (var i = 0; i < chartData.length; i++) {
            labels.push(chartData[i].day);
            cumulativeValues.push(chartData[i].cumulative);
            deltaValues.push(chartData[i].delta);
            cumulativeRevenue.push(chartData[i].cumulative_revenue);
            deltaRevenue.push(chartData[i].delta_revenue);
        }

        var ctx = document.getElementById('dailyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Cumulative (MTD)',
                        data: cumulativeValues,
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118,75,162,0.15)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y',
                        pointRadius: 2,
                        borderWidth: 2
                    },
                    {
                        type: 'bar',
                        label: 'Delta (True Daily)',
                        data: deltaValues,
                        backgroundColor: 'rgba(67,160,71,0.7)',
                        borderColor: '#43a047',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Cumulative'
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Delta'
                        },
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(tooltipItems) {
                                var idx = tooltipItems[0].dataIndex;
                                return 'Cumulative $: $' + numberFormat(cumulativeRevenue[idx]) + '\nDelta $: $' + numberFormat(deltaRevenue[idx]);
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>
<?php render_footer();
} /**
 * Render Billing Dashboard - Service drill-down
 */
function render_billing_service($data)
{
    render_header("Service Billing - Control Panel"); ?>
    <style>
        .bi-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .bi-header h1 { margin: 0 0 8px 0; font-size: 28px; font-weight: 600; }
        .bi-header .subtitle { opacity: 0.9; font-size: 14px; }
        .bi-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .bi-stat { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; }
        .bi-stat .value { font-size: 32px; font-weight: 700; color: #1e3c72; }
        .bi-stat .label { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .bi-stat.revenue .value { color: #27ae60; }
        .bi-section { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .bi-section h3 { margin: 0 0 15px 0; font-size: 16px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .bi-table { width: 100%; border-collapse: collapse; }
        .bi-table th, .bi-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .bi-table th { font-size: 11px; text-transform: uppercase; color: #666; background: #f8f9fa; }
        .bi-table tr:hover { background: #f8f9fa; }
        .bi-table .mono { font-family: monospace; }
        .bi-link { color: #2a5298; text-decoration: none; font-weight: 500; }
        .bi-link:hover { text-decoration: underline; }
        .bi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        @media (max-width: 900px) { .bi-grid { grid-template-columns: 1fr; } }
    </style>
    <div id="bs-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading service billing...</p>
        </div>
    </div>
    <script>
    (function() {
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var params = new URLSearchParams(window.location.search);
        var efxCode = params.get('code');
        apiGet('billing_service', {code: efxCode}, function(err, data) {
            if (err) { showAjaxError('bs-content', err); return; }
            var serviceName = data.service.efx_displayname || data.service.mapped_service_name || data.service.efx_code;
            var stats = data.stats;
            var html = '';

            // Breadcrumb
            html += '<div class="breadcrumb">';
            html += '<a href="?action=billing_intelligence">Billing Dashboard</a>';
            html += '<span>/</span>';
            html += escapeHtml(serviceName);
            html += '</div>';

            // Header
            html += '<div class="bi-header"><h1>' + escapeHtml(serviceName) + '</h1>';
            html += '<div class="subtitle">EFX Code: <code>' + escapeHtml(data.service.efx_code) + '</code>';
            if (data.service.mapped_service_name) {
                html += ' | Mapped to: ' + escapeHtml(data.service.mapped_service_name);
            }
            html += '</div></div>';

            // Stats
            html += '<div class="bi-stats">';
            html += '<div class="bi-stat revenue"><div class="value">$' + numberFormat(stats.total_revenue || 0, 0) + '</div><div class="label">Total Revenue</div></div>';
            html += '<div class="bi-stat"><div class="value">' + numberFormat(stats.total_transactions || 0, 0) + '</div><div class="label">Total Transactions</div></div>';
            html += '<div class="bi-stat"><div class="value">$' + numberFormat(stats.avg_unit_cost || 0, 4) + '</div><div class="label">Avg Unit Price</div></div>';
            html += '<div class="bi-stat"><div class="value">' + (stats.unique_customers || 0) + '</div><div class="label">Customers</div></div>';
            html += '</div>';

            // Grid: Monthly Trend + Customer Breakdown
            html += '<div class="bi-grid">';

            // Monthly Trend
            html += '<div class="bi-section"><h3>Monthly Trend</h3>';
            if (data.monthly_trend && data.monthly_trend.length > 0) {
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Month</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th class="text-right">Avg Price</th>';
                html += '</tr></thead><tbody>';
                var trendLimit = Math.min(data.monthly_trend.length, 12);
                for (var i = 0; i < trendLimit; i++) {
                    var m = data.monthly_trend[i];
                    var mLabel = monthNames[m.month - 1].substr(0, 3) + ' ' + m.year;
                    html += '<tr>';
                    html += '<td><a href="?action=billing_month&year=' + m.year + '&month=' + m.month + '" class="bi-link">' + escapeHtml(mLabel) + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(m.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(m.revenue, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(m.avg_unit_cost, 4) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No monthly data available.</p>';
            }
            html += '</div>';

            // Customers Using This Service
            html += '<div class="bi-section"><h3>Customers Using This Service</h3>';
            if (data.customer_breakdown && data.customer_breakdown.length > 0) {
                html += '<table class="bi-table"><thead><tr>';
                html += '<th>Customer</th><th class="text-right">Transactions</th><th class="text-right">Revenue</th><th class="text-right">Avg Price</th>';
                html += '</tr></thead><tbody>';
                var custLimit = Math.min(data.customer_breakdown.length, 15);
                for (var i = 0; i < custLimit; i++) {
                    var c = data.customer_breakdown[i];
                    var custName = c.customer_name || c.customer_id;
                    html += '<tr>';
                    html += '<td><a href="?action=billing_customer&id=' + encodeURIComponent(c.customer_id) + '" class="bi-link">' + escapeHtml(custName) + '</a></td>';
                    html += '<td class="text-right mono">' + numberFormat(c.transactions, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(c.revenue, 0) + '</td>';
                    html += '<td class="text-right mono">$' + numberFormat(c.avg_unit_cost, 4) + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No customer data available.</p>';
            }
            html += '</div>';

            html += '</div>'; // close bi-grid

            // Service Configuration
            html += '<div class="bi-section"><h3>Service Configuration</h3>';
            html += '<p>View and edit the default pricing tiers for this service.</p>';
            html += '<a href="?action=pricing_defaults" class="btn">View Default Pricing</a> ';
            html += '<a href="?action=generation_types" class="btn">Transaction Types</a>';
            html += '</div>';

            document.getElementById('bs-content').innerHTML = html;
        });
    })();
    </script>
<?php render_footer();
} /**
 * Render Admin panel - comprehensive production readiness dashboard
 */
function render_admin($data)
{
    $tab = isset($data["tab"]) ? $data["tab"] : "overview";
    render_header("Admin - Control Panel");
    ?>
    <style>
        .admin-tabs { display: flex; gap: 0; margin-bottom: 20px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .admin-tabs a { padding: 15px 25px; text-decoration: none; color: #666; font-weight: 500; border-bottom: 3px solid transparent; transition: all 0.2s; }
        .admin-tabs a:hover { color: #333; background: #f8f9fa; }
        .admin-tabs a.active { color: #3498db; border-bottom-color: #3498db; background: #f8f9fa; }

        .admin-section { margin-bottom: 30px; }
        .admin-section h3 { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; display: flex; align-items: center; gap: 10px; }
        .admin-section h3 .icon { font-size: 20px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #333; }
        .stat-card .label { font-size: 11px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .stat-card.highlight { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.highlight .number, .stat-card.highlight .label { color: white; }

        .sync-table { width: 100%; }
        .sync-table th { text-align: left; padding: 12px; background: #f8f9fa; font-size: 12px; text-transform: uppercase; }
        .sync-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .sync-table tr:hover { background: #fafafa; }

        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-badge.ok { background: #d4edda; color: #155724; }
        .status-badge.warning { background: #fff3cd; color: #856404; }
        .status-badge.error { background: #f8d7da; color: #721c24; }
        .status-badge.mock { background: #e2e3ff; color: #5a5c8a; }
        .status-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .env-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .env-item { background: #f8f9fa; padding: 12px 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
        .env-item .label { font-size: 12px; color: #666; }
        .env-item .value { font-weight: 600; font-family: monospace; font-size: 13px; }
        .env-item .value.true { color: #27ae60; }
        .env-item .value.false { color: #e74c3c; }

        .fs-table td.path { font-family: monospace; font-size: 12px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; }

        .log-table { font-size: 13px; }
        .log-table td { padding: 8px 12px; }
        .log-table .time { color: #666; font-family: monospace; font-size: 12px; }

        .danger-zone { background: #fff5f5; border: 1px solid #ffcccc; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .danger-zone h4 { color: #c00; margin-top: 0; margin-bottom: 10px; }

        .form-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-inline input[type="text"] { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 150px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input[type="text"], .form-group input[type="number"] { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 100px; }
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; min-width: 150px; }

        .btn-danger { background: #dc3545; color: white; border: none; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #333; border: none; }
        .btn-warning:hover { background: #e0a800; }
        .btn-sync { background: #17a2b8; color: white; }
        .btn-sync:hover { background: #138496; }

        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; }

        .mode-banner { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .mode-banner.mock { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .mode-banner.production { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .mode-banner h3 { margin: 0; font-size: 16px; }
        .mode-banner p { margin: 5px 0 0 0; opacity: 0.9; font-size: 13px; }
    </style>

    <!-- Environment Banner (server-rendered - uses PHP constants) -->
    <?php
    $env_code = defined("CODE_ENVIRONMENT") ? CODE_ENVIRONMENT : "mock_prod";
    $env_colors = [
        "default" => "linear-gradient(135deg, #4b6cb7 0%, #182848 100%)",
        "dev" => "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
        "rc" => "linear-gradient(135deg, #f093fb 0%, #f5576c 100%)",
        "live" => "linear-gradient(135deg, #11998e 0%, #38ef7d 100%)",
        "mock_prod" => "linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%)",
    ];
    $env_labels = [
        "default" => "Default",
        "dev" => "Development",
        "rc" => "Release Candidate",
        "live" => "Production",
        "mock_prod" => "Mock Production",
    ];
    ?>
    <div class="mode-banner" style="background: <?php echo $env_colors[
        $env_code
    ]; ?>;">
        <div>
            <h3 style="display: flex; align-items: center; gap: 10px;">
                <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 4px; font-size: 12px; text-transform: uppercase;">
                    <?php echo strtoupper($env_code); ?>
                </span>
                <?php echo $env_labels[$env_code]; ?> Environment
            </h3>
            <p>
                <?php echo isset($data["environment"]["env_description"])
                    ? htmlspecialchars($data["environment"]["env_description"])
                    : ""; ?>
                &mdash;
                <?php echo MOCK_MODE
                    ? "Mock data active"
                    : "Live data active"; ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="?mock=<?php echo MOCK_MODE
                ? "0"
                : "1"; ?>" class="btn" style="background: rgba(255,255,255,0.2); color: white;">
                <?php echo MOCK_MODE ? "Use Live Data" : "Use Mock Data"; ?>
            </a>
        </div>
    </div>

    <!-- Tabs (server-rendered - uses $data["tab"] for active class) -->
    <div class="admin-tabs">
        <a href="?action=admin&tab=overview" class="<?php echo $tab ===
        "overview"
            ? "active"
            : ""; ?>">Overview</a>
        <a href="?action=admin&tab=sync" class="<?php echo $tab === "sync"
            ? "active"
            : ""; ?>">Data Sync</a>
        <a href="?action=admin&tab=filesystem" class="<?php echo $tab ===
        "filesystem"
            ? "active"
            : ""; ?>">File System</a>
        <a href="?action=admin&tab=environment" class="<?php echo $tab ===
        "environment"
            ? "active"
            : ""; ?>">Environment</a>
        <a href="?action=admin&tab=data" class="<?php echo $tab === "data"
            ? "active"
            : ""; ?>">Data Management</a>
        <a href="?action=admin&tab=seed" class="<?php echo $tab === "seed"
            ? "active"
            : ""; ?>">Test Data</a>
    </div>

    <!-- AJAX content area -->
    <div id="admin-content" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading admin data...</p>
        </div>
    </div>

    <script>
    (function() {
        var currentTab = <?php echo json_encode($tab); ?>;

        function statusBadgeClass(status) {
            if (status === 'success' || status === 'ok') return 'ok';
            if (status === 'warning' || status === 'partial') return 'warning';
            if (status === 'error' || status === 'missing') return 'error';
            if (status === 'mock') return 'mock';
            return 'warning';
        }

        function statusBadge(text, badgeClass) {
            return '<span class="status-badge ' + badgeClass + '"><span class="dot"></span>' + escapeHtml(text) + '</span>';
        }

        function ucwords(str) {
            return str.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
        }

        // =====================================================================
        // OVERVIEW TAB
        // =====================================================================
        function renderOverview(data) {
            var stats = data.stats || {};
            var syncLog = data.sync_log || [];
            var filesystem = data.filesystem || {};
            var environment = data.environment || {};
            var html = '';

            // Database Statistics
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128202;</span> Database Statistics</h3>';
            html += '<div class="stats-grid">';

            var statCards = [
                { key: 'customers', label: 'Customers', highlight: true },
                { key: 'customers_active', label: 'Active', highlight: false },
                { key: 'services', label: 'Services', highlight: false },
                { key: 'discount_groups', label: 'Groups', highlight: false },
                { key: 'pricing_tiers', label: 'Pricing Tiers', highlight: false },
                { key: 'billing_reports', label: 'Reports', highlight: false },
                { key: 'billing_report_lines', label: 'Billing Lines', highlight: false },
                { key: 'transaction_types', label: 'Transaction Types', highlight: false }
            ];

            for (var i = 0; i < statCards.length; i++) {
                var sc = statCards[i];
                var count = (stats[sc.key] && stats[sc.key].count !== undefined) ? stats[sc.key].count : 0;
                html += '<div class="stat-card' + (sc.highlight ? ' highlight' : '') + '">';
                html += '<div class="number">' + numberFormat(count, 0) + '</div>';
                html += '<div class="label">' + escapeHtml(sc.label) + '</div>';
                html += '</div>';
            }

            html += '</div>'; // stats-grid
            html += '</div>'; // card

            // Quick Status
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#9889;</span> Quick Status</h3>';
            html += '<div class="stats-grid">';

            // File System status
            var fsOk = true;
            for (var fsKey in filesystem) {
                if (filesystem.hasOwnProperty(fsKey) && filesystem[fsKey].status !== 'ok') {
                    fsOk = false;
                }
            }
            html += '<div class="stat-card">';
            html += '<div class="number" style="font-size: 20px;">';
            html += statusBadge(fsOk ? 'OK' : 'Issues', fsOk ? 'ok' : 'warning');
            html += '</div>';
            html += '<div class="label">File System</div>';
            html += '</div>';

            // Mode status
            var mockMode = environment.mock_mode;
            html += '<div class="stat-card">';
            html += '<div class="number" style="font-size: 20px;">';
            html += statusBadge(mockMode ? 'Mock' : 'Production', mockMode ? 'mock' : 'ok');
            html += '</div>';
            html += '<div class="label">Mode</div>';
            html += '</div>';

            // Last Sync status
            var hasSyncLog = syncLog.length > 0;
            html += '<div class="stat-card">';
            html += '<div class="number" style="font-size: 20px;">';
            html += statusBadge(hasSyncLog ? 'Active' : 'Never', hasSyncLog ? 'ok' : 'warning');
            html += '</div>';
            html += '<div class="label">Last Sync</div>';
            html += '</div>';

            // Remote DB status
            var remoteDb = environment.remote_db_configured;
            html += '<div class="stat-card">';
            html += '<div class="number" style="font-size: 20px;">';
            html += statusBadge(remoteDb ? 'Yes' : 'No', remoteDb ? 'ok' : 'warning');
            html += '</div>';
            html += '<div class="label">Remote DB</div>';
            html += '</div>';

            html += '</div>'; // stats-grid
            html += '</div>'; // card

            // Recent Sync Log
            if (syncLog.length > 0) {
                html += '<div class="card admin-section">';
                html += '<h3><span class="icon">&#128203;</span> Recent Sync Activity</h3>';
                html += '<table class="log-table">';
                html += '<thead><tr><th>Time</th><th>Entity</th><th>Records</th><th>Status</th></tr></thead>';
                html += '<tbody>';

                var showCount = Math.min(syncLog.length, 5);
                for (var j = 0; j < showCount; j++) {
                    var log = syncLog[j];
                    html += '<tr>';
                    html += '<td class="time">' + escapeHtml(log.synced_at || '') + '</td>';
                    html += '<td>' + escapeHtml(ucwords(log.entity_type || '')) + '</td>';
                    html += '<td>' + numberFormat(log.record_count || 0, 0) + '</td>';
                    html += '<td>' + statusBadge(log.status || '', statusBadgeClass(log.status)) + '</td>';
                    html += '</tr>';
                }

                html += '</tbody></table>';
                html += '<p style="margin-top: 15px;"><a href="?action=admin&tab=sync">View all sync history &rarr;</a></p>';
                html += '</div>';
            }

            return html;
        }

        // =====================================================================
        // SYNC TAB
        // =====================================================================
        function renderSync(data) {
            var syncStatus = data.sync_status || {};
            var syncLog = data.sync_log || [];
            var environment = data.environment || {};
            var html = '';

            // Sync Status
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128259;</span> Data Sync Status</h3>';

            if (environment.mock_mode) {
                html += '<p class="text-muted" style="margin-bottom: 20px;">Running in <strong>Mock Mode</strong> - sync operations will log activity but use seeded data.</p>';
            } else {
                html += '<p class="text-muted" style="margin-bottom: 20px;">Running in <strong>Production Mode</strong> - sync operations will query the main database.</p>';
            }

            html += '<table class="sync-table">';
            html += '<thead><tr>';
            html += '<th>Entity</th><th>Current Count</th><th>Last Sync</th><th>Last Count</th><th>Status</th><th>Action</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            for (var entity in syncStatus) {
                if (!syncStatus.hasOwnProperty(entity)) continue;
                var st = syncStatus[entity];
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(st.display_name || entity) + '</strong></td>';
                html += '<td>' + numberFormat(st.current_count || 0, 0) + '</td>';
                html += '<td>';
                if (st.last_sync) {
                    html += '<span class="time">' + escapeHtml(st.last_sync) + '</span>';
                } else {
                    html += '<span class="text-muted">Never</span>';
                }
                html += '</td>';
                html += '<td>' + (st.last_sync_count !== null && st.last_sync_count !== undefined ? numberFormat(st.last_sync_count, 0) : '-') + '</td>';
                html += '<td>';
                if (st.last_status) {
                    html += statusBadge(st.last_status, statusBadgeClass(st.last_status));
                } else {
                    html += statusBadge('Not synced', 'warning');
                }
                html += '</td>';
                html += '<td>';
                html += '<button type="button" class="btn btn-sm btn-sync" data-sync-entity="' + escapeHtml(entity) + '">Sync</button>';
                html += '<span class="sync-result-' + escapeHtml(entity) + '" style="margin-left: 8px;"></span>';
                html += '</td>';
                html += '</tr>';
            }

            html += '</tbody></table>';

            // Sync All button
            html += '<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">';
            html += '<button type="button" class="btn btn-sync" id="btn-sync-all">Sync All Master Data</button>';
            html += '</div>';

            html += '</div>'; // card

            // Sync History
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128203;</span> Sync History</h3>';

            if (syncLog.length === 0) {
                html += '<p class="text-muted">No sync activity recorded yet.</p>';
            } else {
                html += '<table class="log-table">';
                html += '<thead><tr><th>Time</th><th>Entity</th><th>Records</th><th>Status</th><th>Notes</th></tr></thead>';
                html += '<tbody>';

                for (var k = 0; k < syncLog.length; k++) {
                    var log = syncLog[k];
                    html += '<tr>';
                    html += '<td class="time">' + escapeHtml(log.synced_at || '') + '</td>';
                    html += '<td>' + escapeHtml(ucwords(log.entity_type || '')) + '</td>';
                    html += '<td>' + numberFormat(log.record_count || 0, 0) + '</td>';
                    html += '<td>' + statusBadge(log.status || '', statusBadgeClass(log.status)) + '</td>';
                    html += '<td class="text-muted" style="font-size: 12px;">' + escapeHtml(log.notes || '') + '</td>';
                    html += '</tr>';
                }

                html += '</tbody></table>';
            }

            html += '</div>'; // card

            return html;
        }

        function bindSyncEvents() {
            // Individual sync buttons
            var syncBtns = document.querySelectorAll('[data-sync-entity]');
            for (var i = 0; i < syncBtns.length; i++) {
                (function(btn) {
                    btn.addEventListener('click', function() {
                        var entity = btn.getAttribute('data-sync-entity');
                        var resultSpan = document.querySelector('.sync-result-' + entity);
                        btn.disabled = true;
                        btn.textContent = 'Syncing...';
                        if (resultSpan) resultSpan.innerHTML = '';

                        apiPost('admin_sync', 'entity=' + encodeURIComponent(entity), function(err, result) {
                            btn.disabled = false;
                            btn.textContent = 'Sync';
                            if (err) {
                                if (resultSpan) resultSpan.innerHTML = '<span class="status-badge error">' + escapeHtml(err) + '</span>';
                            } else {
                                if (resultSpan) resultSpan.innerHTML = '<span class="status-badge ok">' + escapeHtml(result.message || 'Done') + '</span>';
                                // Reload after brief delay to show updated counts
                                setTimeout(function() { loadAdminData(); }, TOAST_RELOAD_DELAY_MS);
                            }
                        });
                    });
                })(syncBtns[i]);
            }

            // Sync All button
            var syncAllBtn = document.getElementById('btn-sync-all');
            if (syncAllBtn) {
                syncAllBtn.addEventListener('click', function() {
                    startJob('sync', {entity: 'all'}, 'Syncing All Master Data...');
                });
            }
        }

        // =====================================================================
        // FILESYSTEM TAB
        // =====================================================================
        function renderFilesystem(data) {
            var filesystem = data.filesystem || {};
            var html = '';

            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128193;</span> File System Status</h3>';
            html += '<p class="text-muted" style="margin-bottom: 20px;">Directory status for CSV exchange and storage.</p>';

            html += '<table class="sync-table fs-table">';
            html += '<thead><tr>';
            html += '<th>Directory</th><th>Path</th><th>Status</th><th>CSV Files</th><th>Permissions</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            for (var key in filesystem) {
                if (!filesystem.hasOwnProperty(key)) continue;
                var fs = filesystem[key];
                var fsStatusClass = fs.status === 'ok' ? 'ok' : (fs.status === 'partial' ? 'warning' : 'error');
                var fsStatusText = fs.exists ? (fs.status === 'ok' ? 'OK' : 'Partial') : 'Missing';

                html += '<tr>';
                html += '<td><strong>' + escapeHtml(fs.description || key) + '</strong></td>';
                html += '<td class="path" title="' + escapeHtml(fs.path || '') + '">' + escapeHtml(fs.path || '') + '</td>';
                html += '<td>' + statusBadge(fsStatusText, fsStatusClass) + '</td>';
                html += '<td>' + (fs.exists ? numberFormat(fs.file_count || 0, 0) : '-') + '</td>';
                html += '<td>';
                if (fs.exists) {
                    html += '<span style="color: ' + (fs.readable ? '#27ae60' : '#e74c3c') + ';">R</span> ';
                    html += '<span style="color: ' + (fs.writable ? '#27ae60' : '#e74c3c') + ';">W</span>';
                } else {
                    html += '-';
                }
                html += '</td>';
                html += '</tr>';
            }

            html += '</tbody></table>';

            html += '<div style="margin-top: 20px;">';
            html += '<button type="button" class="btn btn-success" id="btn-fix-dirs">Create/Fix Directories</button>';
            html += '</div>';

            html += '</div>'; // card

            return html;
        }

        function bindFilesystemEvents() {
            var fixBtn = document.getElementById('btn-fix-dirs');
            if (fixBtn) {
                fixBtn.addEventListener('click', function() {
                    fixBtn.disabled = true;
                    fixBtn.textContent = 'Working...';
                    apiPost('admin_fix_directories', '', function(err, result) {
                        fixBtn.disabled = false;
                        fixBtn.textContent = 'Create/Fix Directories';
                        if (err) {
                            showToast(err, 'error');
                        } else {
                            showToast(result.message || 'Directories fixed.', 'success');
                            loadAdminData();
                        }
                    });
                });
            }
        }

        // =====================================================================
        // ENVIRONMENT TAB
        // =====================================================================
        function renderEnvironment(data) {
            var env = data.environment || {};
            var html = '';

            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#9881;</span> Environment Configuration</h3>';
            html += '<div class="env-grid">';

            // Mock Mode
            html += '<div class="env-item">';
            html += '<span class="label">Mock Mode</span>';
            html += '<span class="value ' + (env.mock_mode ? 'true' : 'false') + '">' + (env.mock_mode ? 'ENABLED' : 'DISABLED') + '</span>';
            html += '</div>';

            // PHP Version
            html += '<div class="env-item">';
            html += '<span class="label">PHP Version</span>';
            html += '<span class="value">' + escapeHtml(env.php_version || '') + '</span>';
            html += '</div>';

            // SQLite Version
            html += '<div class="env-item">';
            html += '<span class="label">SQLite Version</span>';
            html += '<span class="value">' + escapeHtml(env.sqlite_version || '') + '</span>';
            html += '</div>';

            // Shared Base Path
            html += '<div class="env-item">';
            html += '<span class="label">Shared Base Path</span>';
            html += '<span class="value" style="font-size: 11px;">' + escapeHtml(env.shared_base_path || '') + '</span>';
            html += '</div>';

            // Memory Limit
            html += '<div class="env-item">';
            html += '<span class="label">Memory Limit</span>';
            html += '<span class="value">' + escapeHtml(env.memory_limit || '') + '</span>';
            html += '</div>';

            // Max Execution Time
            html += '<div class="env-item">';
            html += '<span class="label">Max Execution Time</span>';
            html += '<span class="value">' + escapeHtml(env.max_execution_time || '') + 's</span>';
            html += '</div>';

            // Upload Max Filesize
            html += '<div class="env-item">';
            html += '<span class="label">Upload Max Filesize</span>';
            html += '<span class="value">' + escapeHtml(env.upload_max_filesize || '') + '</span>';
            html += '</div>';

            // Remote DB Configured
            html += '<div class="env-item">';
            html += '<span class="label">Remote DB Configured</span>';
            html += '<span class="value ' + (env.remote_db_configured ? 'true' : 'false') + '">' + (env.remote_db_configured ? 'YES' : 'NO') + '</span>';
            html += '</div>';

            // Session Active
            html += '<div class="env-item">';
            html += '<span class="label">Session Active</span>';
            html += '<span class="value ' + (env.session_active ? 'true' : 'false') + '">' + (env.session_active ? 'YES' : 'NO') + '</span>';
            html += '</div>';

            html += '</div>'; // env-grid
            html += '</div>'; // card

            // Production Configuration
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128268;</span> Production Configuration</h3>';
            html += '<p class="text-muted">To connect to the production database, configure these constants in <code>control_panel.php</code>:</p>';
            html += '<pre style="background: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px;">';
            html += '// Remote database connection\n';
            html += 'define("REMOTE_DB_HOST", "your-db-host");\n';
            html += 'define("REMOTE_DB_NAME", "main_application");\n';
            html += 'define("REMOTE_DB_USER", "billing_readonly");\n';
            html += 'define("REMOTE_DB_PASS", "secure_password");\n';
            html += '\n';
            html += '// Then update db.php remote_db_query() function';
            html += '</pre>';

            html += '</div>'; // card

            return html;
        }

        // =====================================================================
        // DATA MANAGEMENT TAB
        // =====================================================================
        function renderData(data) {
            var html = '';

            var clearable = [
                { entity: 'billing_reports', label: 'Billing Reports & Lines' },
                { entity: 'pricing_tiers', label: 'Pricing Tiers' },
                { entity: 'customer_settings', label: 'Customer Settings' },
                { entity: 'customer_escalators', label: 'Customer Escalators' },
                { entity: 'customers', label: 'Customers' },
                { entity: 'services', label: 'Services' },
                { entity: 'discount_groups', label: 'Discount Groups' },
                { entity: 'lms', label: 'LMS' },
                { entity: 'business_rules', label: 'Business Rules' }
            ];

            // Clear Entity Data
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128451;</span> Clear Entity Data</h3>';
            html += '<p class="text-muted">Remove all records from a specific table. Use with caution.</p>';

            html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">';

            for (var i = 0; i < clearable.length; i++) {
                var item = clearable[i];
                html += '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">';
                html += '<strong>' + escapeHtml(item.label) + '</strong>';
                html += '<div class="form-inline" style="margin-top: 10px;" data-clear-entity="' + escapeHtml(item.entity) + '">';
                html += '<input type="text" class="clear-confirm-input" placeholder="Type \'' + escapeHtml(item.entity) + '\'" autocomplete="off" style="flex: 1;">';
                html += '<button type="button" class="btn btn-sm btn-danger clear-entity-btn">Clear</button>';
                html += '</div>';
                html += '<span class="clear-result-' + escapeHtml(item.entity) + '" style="display: block; margin-top: 5px; font-size: 12px;"></span>';
                html += '</div>';
            }

            html += '</div>'; // grid
            html += '</div>'; // card

            // Clear Entire Database
            html += '<div class="card admin-section">';
            html += '<div class="danger-zone">';
            html += '<h4>Clear Entire Database</h4>';
            html += '<p>This will permanently delete ALL data from the database. This action cannot be undone.</p>';
            html += '<div class="form-inline" id="clear-all-form">';
            html += '<input type="text" id="clear-all-confirm" placeholder="Type \'CLEAR\'" autocomplete="off">';
            html += '<button type="button" class="btn btn-danger" id="btn-clear-all">Clear All Data</button>';
            html += '</div>';
            html += '<span id="clear-all-result" style="display: block; margin-top: 5px; font-size: 12px;"></span>';
            html += '</div>';
            html += '</div>'; // card

            return html;
        }

        function bindDataEvents() {
            // Clear entity buttons
            var clearForms = document.querySelectorAll('[data-clear-entity]');
            for (var i = 0; i < clearForms.length; i++) {
                (function(form) {
                    var entity = form.getAttribute('data-clear-entity');
                    var btn = form.querySelector('.clear-entity-btn');
                    var input = form.querySelector('.clear-confirm-input');
                    var resultSpan = document.querySelector('.clear-result-' + entity);

                    btn.addEventListener('click', function() {
                        if (input.value !== entity) {
                            if (resultSpan) resultSpan.innerHTML = '<span style="color: #e74c3c;">Type \'' + escapeHtml(entity) + '\' to confirm.</span>';
                            return;
                        }

                        btn.disabled = true;
                        btn.textContent = 'Clearing...';
                        if (resultSpan) resultSpan.innerHTML = '';

                        apiPost('admin_clear_entity', 'entity=' + encodeURIComponent(entity), function(err, result) {
                            btn.disabled = false;
                            btn.textContent = 'Clear';
                            input.value = '';
                            if (err) {
                                if (resultSpan) resultSpan.innerHTML = '<span style="color: #e74c3c;">' + escapeHtml(err) + '</span>';
                            } else {
                                if (resultSpan) resultSpan.innerHTML = '<span style="color: #27ae60;">' + escapeHtml(result.message || 'Cleared.') + '</span>';
                            }
                        });
                    });
                })(clearForms[i]);
            }

            // Clear all database
            var clearAllBtn = document.getElementById('btn-clear-all');
            var clearAllInput = document.getElementById('clear-all-confirm');
            var clearAllResult = document.getElementById('clear-all-result');

            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function() {
                    if (clearAllInput.value !== 'CLEAR') {
                        if (clearAllResult) clearAllResult.innerHTML = '<span style="color: #e74c3c;">Type \'CLEAR\' to confirm.</span>';
                        return;
                    }

                    clearAllBtn.disabled = true;
                    clearAllBtn.textContent = 'Clearing...';
                    if (clearAllResult) clearAllResult.innerHTML = '';

                    apiPost('admin_clear', 'confirm_text=CLEAR', function(err, result) {
                        clearAllBtn.disabled = false;
                        clearAllBtn.textContent = 'Clear All Data';
                        clearAllInput.value = '';
                        if (err) {
                            if (clearAllResult) clearAllResult.innerHTML = '<span style="color: #e74c3c;">' + escapeHtml(err) + '</span>';
                        } else {
                            if (clearAllResult) clearAllResult.innerHTML = '<span style="color: #27ae60;">' + escapeHtml(result.message || 'All data cleared.') + '</span>';
                        }
                    });
                });
            }
        }

        // =====================================================================
        // SEED TAB
        // =====================================================================
        function renderSeed(data) {
            var html = '';

            // Generate Test Data form
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#127793;</span> Generate Test Data</h3>';
            html += '<p>Generate audit-compatible test data. Billing reports will use calculated prices from the pricing tier system with controlled variance for testing.</p>';

            html += '<form id="seed-form">';
            html += '<div class="form-row">';

            html += '<div class="form-group">';
            html += '<label>Days of History</label>';
            html += '<input type="number" name="days" value="90" min="7" max="365">';
            html += '</div>';

            html += '<div class="form-group">';
            html += '<label>Customers</label>';
            html += '<input type="number" name="customers" value="100" min="10" max="500">';
            html += '</div>';

            html += '<div class="form-group">';
            html += '<label>Exact Match %</label>';
            html += '<input type="number" name="exact_pct" value="85" min="0" max="100">';
            html += '</div>';

            html += '<div class="form-group">';
            html += '<label>Small Variance %</label>';
            html += '<input type="number" name="small_pct" value="10" min="0" max="100">';
            html += '</div>';

            html += '<div class="form-group">';
            html += '<label>Large Variance %</label>';
            html += '<input type="number" name="large_pct" value="5" min="0" max="100">';
            html += '</div>';

            html += '</div>'; // form-row

            html += '<div class="form-group checkbox-group">';
            html += '<input type="checkbox" name="clear_first" value="1" id="clear_first" checked>';
            html += '<label for="clear_first" style="margin-bottom: 0; font-weight: normal;">Clear database before seeding</label>';
            html += '</div>';

            html += '<p style="margin-top: 15px;">';
            html += '<button type="submit" class="btn btn-warning">Reseed Database</button>';
            html += '</p>';

            html += '</form>';
            html += '</div>'; // card

            // Test Data Info
            html += '<div class="card admin-section">';
            html += '<h3><span class="icon">&#128218;</span> Test Data Info</h3>';
            html += '<p>The test data generator creates:</p>';
            html += '<ul style="margin: 15px 0 0 20px; line-height: 1.8;">';
            html += '<li><strong>Customers</strong> - With random discount groups and LMS assignments</li>';
            html += '<li><strong>Services</strong> - Standard service types with default pricing</li>';
            html += '<li><strong>Pricing Tiers</strong> - Volume-based pricing at default, group, and customer levels</li>';
            html += '<li><strong>Escalators</strong> - Annual price increases for some customers</li>';
            html += '<li><strong>Billing Reports</strong> - Daily humanreadable CSVs with transaction data</li>';
            html += '<li><strong>Transaction Types</strong> - EFX code mappings</li>';
            html += '</ul>';
            html += '<p style="margin-top: 15px;">Billing line prices are calculated using the actual pricing engine, then variance is applied:</p>';
            html += '<ul style="margin: 10px 0 0 20px; line-height: 1.8;">';
            html += '<li><strong>Exact match</strong> - Price matches calculated price exactly</li>';
            html += '<li><strong>Small variance</strong> - &plusmn;5% difference (rounding, timing)</li>';
            html += '<li><strong>Large variance</strong> - &plusmn;20% difference (errors to investigate)</li>';
            html += '</ul>';

            html += '</div>'; // card

            return html;
        }

        function bindSeedEvents() {
            var seedForm = document.getElementById('seed-form');
            if (seedForm) {
                seedForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    startJob('seed', {
                        days_of_history: seedForm.days.value,
                        customer_count: seedForm.customers.value,
                        exact_match_pct: seedForm.exact_pct.value,
                        small_variance_pct: seedForm.small_pct.value,
                        large_variance_pct: seedForm.large_pct.value,
                        clear_first: seedForm.clear_first.checked ? '1' : '0'
                    }, 'Seeding Database...');
                });
            }
        }

        // =====================================================================
        // MAIN LOADER
        // =====================================================================
        function loadAdminData() {
            var el = document.getElementById('admin-content');

            apiGet('admin', {tab: currentTab}, function(err, data) {
                if (err) { showAjaxError('admin-content', err); return; }

                var html = '';

                switch (currentTab) {
                    case 'overview':
                        html = renderOverview(data);
                        break;
                    case 'sync':
                        html = renderSync(data);
                        break;
                    case 'filesystem':
                        html = renderFilesystem(data);
                        break;
                    case 'environment':
                        html = renderEnvironment(data);
                        break;
                    case 'data':
                        html = renderData(data);
                        break;
                    case 'seed':
                        html = renderSeed(data);
                        break;
                    default:
                        html = renderOverview(data);
                        break;
                }

                el.innerHTML = html;

                // Bind events after rendering
                switch (currentTab) {
                    case 'sync':
                        bindSyncEvents();
                        break;
                    case 'filesystem':
                        bindFilesystemEvents();
                        break;
                    case 'data':
                        bindDataEvents();
                        break;
                    case 'seed':
                        bindSeedEvents();
                        break;
                }
            });
        }

        loadAdminData();
    })();
    </script>

<?php render_footer();
} /**
 * Render remote database explorer (for debugging sync issues)
 */
function render_admin_explore_remote($data)
{
    render_header("Explore Remote Database - Admin"); ?>
    <style>
        .explore-container { max-width: 1400px; margin: 0 auto; }
        .explore-header { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .explore-header h1 { margin: 0 0 10px 0; }
        .explore-header p { margin: 0; opacity: 0.9; }
        .filter-form { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filter-form input[type="text"] { padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 10px 20px; background: #8e44ad; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background: #7d3c98; }
        .tables-list { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-height: 400px; overflow-y: auto; }
        .tables-list h3 { margin-top: 0; }
        .table-link { display: inline-block; padding: 5px 10px; margin: 3px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; font-family: monospace; font-size: 13px; }
        .table-link:hover { background: #8e44ad; color: white; }
        .table-link.selected { background: #8e44ad; color: white; }
        .table-detail { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table-detail h3 { margin-top: 0; color: #8e44ad; }
        .columns-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .columns-table th, .columns-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .columns-table th { background: #f8f9fa; font-weight: 600; }
        .sample-data { overflow-x: auto; }
        .sample-data table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .sample-data th, .sample-data td { padding: 6px 10px; text-align: left; border: 1px solid #ddd; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sample-data th { background: #f0f0f0; }
        .error-box { background: #fee; border: 1px solid #c00; color: #900; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success-box { background: #efe; border: 1px solid #0a0; color: #060; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #8e44ad; }
    </style>

    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading remote database explorer...</p>
        </div>
    </div>

    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var filter = params.get('filter') || '';
        var table = params.get('table') || '';

        apiGet('admin_explore_remote', {filter: filter, table: table}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var el = document.getElementById('page-data');
            var html = '';

            html += '<div class="explore-container">';

            // Back link
            html += '<a href="?action=admin&tab=sync" class="back-link">&larr; Back to Admin</a>';

            // Header banner
            html += '<div class="explore-header">';
            html += '<h1>Remote Database Explorer</h1>';
            html += '<p>Database: <strong>' + escapeHtml(data.db_name || '') + '</strong> | ';
            html += 'Status: ';
            if (data.connected) {
                html += '<span style="color:#afa">Connected</span>';
            } else {
                html += '<span style="color:#faa">Not Connected</span>';
            }
            html += '</p>';
            html += '</div>';

            // Error box
            if (data.error) {
                html += '<div class="error-box">';
                html += '<strong>Error:</strong> ' + escapeHtml(data.error);
                html += '</div>';
            }

            // Filter form
            html += '<div class="filter-form">';
            html += '<form id="explore-filter-form">';
            html += '<input type="hidden" name="action" value="admin_explore_remote">';
            html += '<label><strong>Filter tables:</strong></label> ';
            html += '<input type="text" name="filter" value="' + escapeHtml(data.filter || '') + '" placeholder="e.g. customer, billing, service...">';
            html += ' <button type="submit">Search</button>';
            if (data.filter) {
                html += ' <a href="?action=admin_explore_remote" style="margin-left: 10px;">Clear filter</a>';
            }
            html += '</form>';
            html += '<p style="margin: 10px 0 0 0; color: #666; font-size: 13px;">';
            html += 'Enter a substring to filter table names. Leave empty to see all tables.';
            html += '</p>';
            html += '</div>';

            // Tables list
            var tables = data.tables || [];
            if (data.connected && tables.length > 0) {
                html += '<div class="tables-list">';
                html += '<h3>Tables (' + tables.length + ' found';
                if (data.filter) {
                    html += ' matching &quot;' + escapeHtml(data.filter) + '&quot;';
                }
                html += ')</h3>';
                for (var i = 0; i < tables.length; i++) {
                    var t = tables[i];
                    var selectedClass = (t === data.selected_table) ? ' selected' : '';
                    html += '<a href="?action=admin_explore_remote&filter=' + encodeURIComponent(data.filter || '') + '&table=' + encodeURIComponent(t) + '"';
                    html += ' class="table-link' + selectedClass + '">';
                    html += escapeHtml(t);
                    html += '</a>';
                }
                html += '</div>';
            } else if (data.connected) {
                html += '<div class="tables-list">';
                html += '<h3>No tables found';
                if (data.filter) {
                    html += ' matching &quot;' + escapeHtml(data.filter) + '&quot;';
                }
                html += '</h3>';
                html += '<p>Try a different filter or <a href="?action=admin_explore_remote">clear the filter</a> to see all tables.</p>';
                html += '</div>';
            }

            // Table detail
            if (data.selected_table) {
                html += '<div class="table-detail">';
                html += '<h3>Table: ' + escapeHtml(data.selected_table) + '</h3>';

                // Columns
                var columns = data.columns || [];
                if (columns.length > 0) {
                    html += '<h4>Columns (' + columns.length + ')</h4>';
                    html += '<table class="columns-table">';
                    html += '<tr>';
                    html += '<th>Column Name</th>';
                    html += '<th>Type</th>';
                    html += '<th>Nullable</th>';
                    html += '<th>Key</th>';
                    html += '</tr>';
                    for (var c = 0; c < columns.length; c++) {
                        var col = columns[c];
                        html += '<tr>';
                        html += '<td><code>' + escapeHtml(col.COLUMN_NAME || '') + '</code></td>';
                        html += '<td>' + escapeHtml(col.DATA_TYPE || '') + '</td>';
                        html += '<td>' + (col.IS_NULLABLE === 'YES' ? 'Yes' : 'No') + '</td>';
                        html += '<td>' + escapeHtml(col.COLUMN_KEY || '') + '</td>';
                        html += '</tr>';
                    }
                    html += '</table>';
                }

                // Sample data
                var sampleData = data.sample_data || [];
                if (sampleData.length > 0) {
                    html += '<h4>Sample Data (first 10 rows)</h4>';
                    html += '<div class="sample-data">';
                    html += '<table>';

                    // Header row from keys of first record
                    var sampleKeys = [];
                    for (var key in sampleData[0]) {
                        if (sampleData[0].hasOwnProperty(key)) {
                            sampleKeys.push(key);
                        }
                    }
                    html += '<tr>';
                    for (var k = 0; k < sampleKeys.length; k++) {
                        html += '<th>' + escapeHtml(sampleKeys[k]) + '</th>';
                    }
                    html += '</tr>';

                    // Data rows
                    for (var r = 0; r < sampleData.length; r++) {
                        html += '<tr>';
                        for (var v = 0; v < sampleKeys.length; v++) {
                            var val = sampleData[r][sampleKeys[v]];
                            var valStr = (val === null || typeof val === 'undefined') ? '' : String(val);
                            var displayStr = valStr.length > 50 ? valStr.substring(0, 50) : valStr;
                            html += '<td title="' + escapeHtml(valStr) + '">' + escapeHtml(displayStr) + '</td>';
                        }
                        html += '</tr>';
                    }

                    html += '</table>';
                    html += '</div>';
                } else if (columns.length > 0) {
                    html += '<p><em>No data in this table or unable to query.</em></p>';
                }

                html += '</div>'; // close .table-detail
            }

            // Common Search Terms
            html += '<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">';
            html += '<h4 style="margin-top: 0;">Common Search Terms</h4>';
            html += '<p>Try filtering by: ';

            var terms = ['customer', 'client', 'service', 'product', 'billing', 'price', 'discount', 'group', 'lms', 'cost', 'cog', 'rule'];
            for (var s = 0; s < terms.length; s++) {
                if (s > 0) { html += ' | '; }
                html += '<a href="?action=admin_explore_remote&filter=' + encodeURIComponent(terms[s]) + '">' + escapeHtml(terms[s]) + '</a>';
            }

            html += '</p>';
            html += '</div>';

            html += '</div>'; // close .explore-container

            el.innerHTML = html;

            // Attach filter form submit handler for URL navigation
            var filterForm = document.getElementById('explore-filter-form');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var filterVal = filterForm.querySelector('input[name="filter"]').value;
                    var url = '?action=admin_explore_remote';
                    if (filterVal) {
                        url += '&filter=' + encodeURIComponent(filterVal);
                    }
                    window.location = url;
                });
            }
        });
    })();
    </script>
<?php render_footer();
} // ============================================================
/**
 * Render billing flags management page
 */
function render_billing_flags($data)
{
    render_header("Billing Flags - Control Panel"); ?>
    <div id="page-data" class="ajax-content">
        <div class="loading-skeleton">
            <div class="skeleton-bar w75"></div>
            <div class="skeleton-bar w90"></div>
            <div class="skeleton-bar w50"></div>
            <p>Loading billing flags...</p>
        </div>
    </div>
    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        var level = params.get('level') || 'default';
        var levelId = params.get('level_id') || '';

        apiGet('billing_flags', {level: level, level_id: levelId}, function(err, data) {
            if (err) { showAjaxError('page-data', err); return; }

            var el = document.getElementById('page-data');
            var html = '';

            html += '<div class="card">';
            html += '<h2>Billing Flags</h2>';
            html += '<p class="text-muted">Configure per-service billing behavior flags (by_hit, zero_null, bav_by_trans).</p>';

            // Level Selector
            html += '<div style="margin: 15px 0; display: flex; gap: 10px; align-items: center;">';
            html += '<label><strong>Level:</strong></label>';
            html += '<a href="?action=billing_flags&level=default" class="btn btn-sm ' + (data.level === 'default' ? 'btn-primary' : '') + '">Default</a>';

            // Group dropdown
            html += '<select onchange="if(this.value) window.location=\'?action=billing_flags&level=group&level_id=\'+this.value" class="form-control" style="width: auto; display: inline-block;">';
            html += '<option value="">-- Group --</option>';
            if (data.groups) {
                for (var g = 0; g < data.groups.length; g++) {
                    var grp = data.groups[g];
                    var grpSelected = (data.level === 'group' && data.level_id == grp.id) ? ' selected' : '';
                    html += '<option value="' + escapeHtml(grp.id + '') + '"' + grpSelected + '>' + escapeHtml(grp.name) + '</option>';
                }
            }
            html += '</select>';

            // Customer dropdown
            html += '<select onchange="if(this.value) window.location=\'?action=billing_flags&level=customer&level_id=\'+this.value" class="form-control" style="width: auto; display: inline-block;">';
            html += '<option value="">-- Customer --</option>';
            if (data.customers) {
                for (var c = 0; c < data.customers.length; c++) {
                    var cust = data.customers[c];
                    var custSelected = (data.level === 'customer' && data.level_id == cust.id) ? ' selected' : '';
                    html += '<option value="' + escapeHtml(cust.id + '') + '"' + custSelected + '>' + escapeHtml(cust.name) + '</option>';
                }
            }
            html += '</select>';
            html += '</div>';

            // Entity info banner for non-default level
            if (data.level !== 'default' && data.level_entity) {
                html += '<div style="background: #e8f4fd; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px;">';
                html += 'Viewing flags for <strong>' + escapeHtml(data.level.charAt(0).toUpperCase() + data.level.slice(1)) + ': ' + escapeHtml(data.level_entity.name) + '</strong>';
                html += '</div>';
            }

            // Current Flags table
            var flags = data.current_flags || [];
            if (flags.length > 0) {
                html += '<table>';
                html += '<thead><tr>';
                html += '<th>Service</th>';
                html += '<th>EFX Code</th>';
                html += '<th>By Hit</th>';
                html += '<th>Zero Null</th>';
                html += '<th>BAV by Trans</th>';
                html += '<th>Effective Date</th>';
                html += '<th class="text-right">Actions</th>';
                html += '</tr></thead>';
                html += '<tbody>';
                for (var f = 0; f < flags.length; f++) {
                    var flag = flags[f];
                    html += '<tr>';
                    html += '<td>' + escapeHtml(flag.service_name || (flag.service_id + '')) + '</td>';
                    html += '<td>' + escapeHtml(flag.efx_code || '') + '</td>';
                    html += '<td>' + (flag.by_hit ? '<span class="badge badge-success">Yes</span>' : '<span class="badge">No</span>') + '</td>';
                    html += '<td>' + (flag.zero_null ? '<span class="badge badge-success">Yes</span>' : '<span class="badge">No</span>') + '</td>';
                    html += '<td>' + (flag.bav_by_trans ? '<span class="badge badge-success">Yes</span>' : '<span class="badge">No</span>') + '</td>';
                    html += '<td>' + escapeHtml(flag.effective_date || '') + '</td>';
                    html += '<td class="text-right">';
                    if (data.level !== 'default') {
                        html += '<button type="button" class="btn btn-sm" data-flag-id="' + escapeHtml(flag.id + '') + '" onclick="clearBillingFlag(this)">Clear</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
            } else {
                html += '<p class="text-muted">No billing flags configured at this level.</p>';
            }

            // Set Flag form
            html += '<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">';
            html += '<h3>Set Flag</h3>';
            html += '<form id="billing-flag-form">';

            html += '<div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">';

            // Service dropdown
            html += '<div>';
            html += '<label>Service</label>';
            html += '<select name="service_id" class="form-control" required>';
            html += '<option value="">Select...</option>';
            if (data.services) {
                for (var s = 0; s < data.services.length; s++) {
                    var svc = data.services[s];
                    html += '<option value="' + escapeHtml(svc.id + '') + '">' + escapeHtml(svc.name) + '</option>';
                }
            }
            html += '</select>';
            html += '</div>';

            // EFX Code dropdown
            html += '<div>';
            html += '<label>EFX Code</label>';
            html += '<select name="efx_code" class="form-control" required>';
            html += '<option value="">Select...</option>';
            if (data.transaction_types) {
                for (var t = 0; t < data.transaction_types.length; t++) {
                    var tt = data.transaction_types[t];
                    html += '<option value="' + escapeHtml(tt.efx_code) + '">' + escapeHtml(tt.efx_code) + ' - ' + escapeHtml(tt.display_name) + '</option>';
                }
            }
            html += '</select>';
            html += '</div>';

            // Checkboxes
            html += '<div><label><input type="checkbox" name="by_hit" value="1"> By Hit</label></div>';
            html += '<div><label><input type="checkbox" name="zero_null" value="1"> Zero Null</label></div>';
            html += '<div><label><input type="checkbox" name="bav_by_trans" value="1"> BAV by Trans</label></div>';

            // Save button
            html += '<div><button type="submit" class="btn btn-primary">Save</button></div>';

            html += '</div>'; // close flex row
            html += '</form>';
            html += '</div>'; // close form container

            html += '</div>'; // close card

            el.innerHTML = html;

            // Attach form submit handler
            document.getElementById('billing-flag-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('level', level);
                if (levelId) {
                    formData.append('level_id', levelId);
                }
                apiPost('save_billing_flags', formData, function(err, result) {
                    if (err) {
                        showToast(err, 'error');
                    } else {
                        showToast(result.message, 'success');
                        setTimeout(function() { window.location.reload(); }, TOAST_RELOAD_DELAY_MS);
                    }
                });
            });
        });

        // Clear flag handler (global so onclick can reach it)
        window.clearBillingFlag = function(btn) {
            if (!confirm('Clear this override?')) return;
            var flagId = btn.getAttribute('data-flag-id');
            var body = 'flag_action=delete&flag_id=' + encodeURIComponent(flagId);
            apiPost('save_billing_flags', body, function(err, result) {
                if (err) {
                    showToast(err, 'error');
                } else {
                    window.location.reload();
                }
            });
        };
    })();
    </script>
<?php render_footer();
} // END PHASE 4
// ============================================================
// ============================================================
// PHASE 5: MOCK DATA & BOOTSTRAP
// Test data generation and application entry point
// ============================================================
/**
 * Initialize mock data for testing
 * Creates directories and sample CSV files
 * Only runs when MOCK_MODE is true
 */
