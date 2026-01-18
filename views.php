<?php
// ============================================================
// VIEW FUNCTIONS (Templates)
// All render_* functions that output HTML
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
        $action === "list_reports" ||
        $action === "view_report" ||
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
                <a href="?action=list_reports" <?php echo $action ===
                    "list_reports" || $action === "view_report"
                    ? 'class="active"'
                    : ""; ?>>Reports</a>
                <a href="?action=export" <?php echo strpos(
                    $action,
                    "export",
                ) === 0
                    ? 'class="active"'
                    : ""; ?>>Export</a>
                <a href="?action=history" <?php echo $action === "history"
                    ? 'class="active"'
                    : ""; ?>>History</a>
            </div>
        </div>

        <div class="nav-spacer"></div>
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
    <div class="stats">
        <div class="stat-card">
            <div class="number"><?php echo $data["service_count"]; ?></div>
            <div class="label">Services</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $data["group_count"]; ?></div>
            <div class="label">Discount Groups</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $data["customer_active"]; ?></div>
            <div class="label">Active Customers</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $data["customer_total"]; ?></div>
            <div class="label">Total Customers</div>
        </div>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>
        <a href="?action=pricing_defaults" class="btn">Manage Defaults</a>
        <a href="?action=pricing_groups" class="btn">Manage Groups</a>
        <a href="?action=pricing_customers" class="btn">Manage Customers</a>
        <a href="?action=upload_config" class="btn btn-success">Upload Config CSV</a>
    </div>

    <?php if (!empty($data["alerts"])): ?>
    <div class="card">
        <h2>Alerts &amp; Notifications</h2>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($data["alerts"] as $alert): ?>
            <div style="padding: 12px 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;
                        <?php echo $alert["type"] === "warning"
                            ? "background: #fff3cd; border: 1px solid #ffc107;"
                            : "background: #d1ecf1; border: 1px solid #bee5eb;"; ?>">
                <span><?php echo $alert["message"]; ?></span>
                <a href="<?php echo $alert[
                    "link"
                ]; ?>" class="btn btn-sm">View</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($data["pending_configs"])): ?>
    <div class="card">
        <h2>Pending Configs</h2>
        <p class="text-muted mb-20"><?php echo count(
            $data["pending_configs"],
        ); ?> files awaiting processing.</p>
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Uploaded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (
                    array_slice($data["pending_configs"], 0, 5)
                    as $file
                ): ?>
                <tr>
                    <td><?php echo h($file["name"]); ?></td>
                    <td><?php echo format_filesize($file["size"]); ?></td>
                    <td><?php echo date(
                        "Y-m-d H:i:s",
                        $file["modified"],
                    ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($data["reports"])): ?>
    <div class="card">
        <h2>Recent Reports</h2>
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Generated</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($data["reports"], 0, 5) as $file): ?>
                <tr>
                    <td><?php echo h($file["name"]); ?></td>
                    <td><?php echo format_filesize($file["size"]); ?></td>
                    <td><?php echo date(
                        "Y-m-d H:i:s",
                        $file["modified"],
                    ); ?></td>
                    <td class="text-right">
                        <a href="?action=view_report&file=<?php echo urlencode(
                            $file["name"],
                        ); ?>" class="btn btn-sm">View</a>
                        <a href="?action=download_report&file=<?php echo urlencode(
                            $file["name"],
                        ); ?>" class="btn btn-sm btn-success">Download</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($data["reports"]) > 5): ?>
            <p style="margin-top: 15px;"><a href="?action=list_reports">View all reports &rarr;</a></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php
} /**
 * Render list reports view
 */
function render_list_reports($data)
{
    render_header("Reports - Control Panel"); ?>
    <div class="card">
        <h2>All Reports</h2>
        <?php if (empty($data["reports"])): ?>
            <p class="text-muted">No reports available.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Generated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["reports"] as $file): ?>
                    <tr>
                        <td><?php echo h($file["name"]); ?></td>
                        <td><?php echo format_filesize($file["size"]); ?></td>
                        <td><?php echo date(
                            "Y-m-d H:i:s",
                            $file["modified"],
                        ); ?></td>
                        <td class="text-right">
                            <a href="?action=view_report&file=<?php echo urlencode(
                                $file["name"],
                            ); ?>" class="btn btn-sm">View</a>
                            <a href="?action=download_report&file=<?php echo urlencode(
                                $file["name"],
                            ); ?>" class="btn btn-sm btn-success">Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render view report (CSV preview)
 */
function render_view_report($data)
{
    render_header("View Report - Control Panel"); ?>
    <div class="card">
        <h2>
            <?php echo h($data["filename"]); ?>
            <a href="?action=download_report&file=<?php echo urlencode(
                $data["filename"],
            ); ?>" class="btn btn-sm btn-success" style="float: right;">Download</a>
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

    <div class="breadcrumb"><a href="?action=list_reports">Reports</a><span>/</span><?php echo h(
        $data["report"]["report_type"],
    ); ?> - <?php echo $data["report"]["report_year"]; ?>-<?php echo str_pad($data["report"]["report_month"], 2, "0", STR_PAD_LEFT); ?></div>
<?php
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
    $drive_files = isset($data["drive_files"]) ? $data["drive_files"] : [];
    $not_imported = array_filter($drive_files, function ($f) {
        return !$f["imported"];
    });
    ?>
    <div class="card">
        <h2>Billing Report Ingestion</h2>

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo isset(
                    $data["stats"]["total_reports"],
                )
                    ? $data["stats"]["total_reports"]
                    : 0; ?></div>
                <div style="color: #666; font-size: 13px;">Imported Reports</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo isset(
                    $data["stats"]["total_rows"],
                )
                    ? number_format($data["stats"]["total_rows"])
                    : 0; ?></div>
                <div style="color: #666; font-size: 13px;">Total Rows</div>
            </div>
            <div style="flex: 1; background: <?php echo count($not_imported) > 0
                ? "#fff3cd"
                : "#d4edda"; ?>; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo count(
                    $not_imported,
                ); ?></div>
                <div style="color: #666; font-size: 13px;">Pending on Drive</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 13px; color: #666;">Date Range</div>
                <div><?php echo isset($data["stats"]["earliest"]) &&
                $data["stats"]["earliest"]
                    ? $data["stats"]["earliest"] .
                        " to " .
                        $data["stats"]["latest"]
                    : "No data"; ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div style="margin-bottom: 20px; border-bottom: 2px solid #eee;">
            <a href="?action=ingestion&tab=reports" style="display: inline-block; padding: 10px 20px; text-decoration: none; color: <?php echo $tab ===
            "reports"
                ? "#3498db"
                : "#666"; ?>; border-bottom: 2px solid <?php echo $tab === "reports" ? "#3498db" : "transparent"; ?>; margin-bottom: -2px; font-weight: <?php echo $tab === "reports" ? "600" : "normal"; ?>;">
                Imported Reports
            </a>
            <a href="?action=ingestion&tab=upload" style="display: inline-block; padding: 10px 20px; text-decoration: none; color: <?php echo $tab ===
            "upload"
                ? "#3498db"
                : "#666"; ?>; border-bottom: 2px solid <?php echo $tab === "upload" ? "#3498db" : "transparent"; ?>; margin-bottom: -2px; font-weight: <?php echo $tab === "upload" ? "600" : "normal"; ?>;">
                Upload File
            </a>
            <a href="?action=ingestion&tab=drive" style="display: inline-block; padding: 10px 20px; text-decoration: none; color: <?php echo $tab ===
            "drive"
                ? "#3498db"
                : "#666"; ?>; border-bottom: 2px solid <?php echo $tab === "drive" ? "#3498db" : "transparent"; ?>; margin-bottom: -2px; font-weight: <?php echo $tab === "drive" ? "600" : "normal"; ?>;">
                Import from Drive <?php if (
                    count($not_imported) > 0
                ): ?><span class="badge badge-warning"><?php echo count(
    $not_imported,
); ?></span><?php endif; ?>
            </a>
        </div>

        <?php if ($tab === "upload"): ?>
        <!-- Upload Tab -->
        <h3>Upload Billing CSV</h3>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="file" name="billing_csv" accept=".csv" required style="flex: 1;">
                <button type="submit" class="btn btn-success">Upload & Import</button>
            </div>
            <p class="text-muted" style="margin-top: 8px; font-size: 12px;">
                Expected format: <code>DataX_YYYY_MM_DD_humanreadable.csv</code><br>
                Columns: <code>y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id</code>
            </p>
        </form>

        <?php elseif ($tab === "drive"): ?>
        <!-- Drive Tab -->
        <h3>Import from Drive</h3>
        <p class="text-muted" style="margin-bottom: 15px;">Files available in the archive directory for import.</p>

        <?php if (empty($drive_files)): ?>
            <p class="text-muted">No billing files found in archive directory.</p>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="bulk_import" value="1">
                <div style="margin-bottom: 15px;">
                    <button type="button" class="btn btn-sm" onclick="selectAllPending()">Select All Pending</button>
                    <button type="button" class="btn btn-sm" onclick="deselectAll()">Deselect All</button>
                    <button type="submit" class="btn btn-success" style="margin-left: 20px;">Import Selected</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drive_files as $file): ?>
                        <tr style="<?php echo $file["imported"]
                            ? "opacity: 0.6;"
                            : ""; ?>">
                            <td>
                                <input type="checkbox" name="selected_files[]" value="<?php echo h(
                                    $file["filename"],
                                ); ?>"
                                    class="file-checkbox" <?php echo $file[
                                        "imported"
                                    ]
                                        ? "disabled"
                                        : ""; ?>
                                    data-pending="<?php echo $file["imported"]
                                        ? "0"
                                        : "1"; ?>">
                            </td>
                            <td><code><?php echo h(
                                $file["filename"],
                            ); ?></code></td>
                            <td><?php echo number_format(
                                $file["size"] / 1024,
                                1,
                            ); ?> KB</td>
                            <td><?php echo date(
                                "Y-m-d H:i",
                                $file["modified"],
                            ); ?></td>
                            <td>
                                <?php if ($file["imported"]): ?>
                                    <span class="badge badge-success">Imported</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if (!$file["imported"]): ?>
                                    <a href="?action=ingestion&import_file=<?php echo urlencode(
                                        $file["filename"],
                                    ); ?>" class="btn btn-sm btn-success">Import</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <script>
            function toggleAll(el) {
                var checkboxes = document.querySelectorAll('.file-checkbox:not([disabled])');
                checkboxes.forEach(function(cb) { cb.checked = el.checked; });
            }
            function selectAllPending() {
                var checkboxes = document.querySelectorAll('.file-checkbox[data-pending="1"]');
                checkboxes.forEach(function(cb) { cb.checked = true; });
            }
            function deselectAll() {
                var checkboxes = document.querySelectorAll('.file-checkbox');
                checkboxes.forEach(function(cb) { cb.checked = false; });
                document.getElementById('select-all').checked = false;
            }
            </script>
        <?php endif; ?>

        <?php else: ?>
        <!-- Reports Tab (default) -->
        <h3>Imported Reports</h3>
        <?php if (empty($data["reports"])): ?>
            <p class="text-muted">No reports imported yet. <a href="?action=ingestion&tab=drive">Import from drive</a> or <a href="?action=ingestion&tab=upload">upload a file</a>.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Date</th>
                        <th>File</th>
                        <th>Rows</th>
                        <th>Imported At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["reports"] as $report): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?php echo $report[
                                "report_type"
                            ] === "monthly"
                                ? "success"
                                : "info"; ?>">
                                <?php echo h($report["report_type"]); ?>
                            </span>
                        </td>
                        <td><?php echo h($report["report_date"]); ?></td>
                        <td><code style="font-size: 11px;"><?php echo h(
                            $report["file_path"],
                        ); ?></code></td>
                        <td><?php echo number_format(
                            $report["record_count"],
                        ); ?></td>
                        <td><?php echo date(
                            "Y-m-d H:i",
                            strtotime($report["imported_at"]),
                        ); ?></td>
                        <td class="text-right">
                            <a href="?action=ingestion_view&id=<?php echo $report[
                                "id"
                            ]; ?>" class="btn btn-sm">View</a>
                            <a href="?action=ingestion&delete=<?php echo $report[
                                "id"
                            ]; ?>" class="btn btn-sm" style="background: #e74c3c;" onclick="return confirm('Delete this report?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render single billing report view
 */
function render_ingestion_view($data)
{
    render_header("View Report - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=ingestion">Ingestion</a><span>/</span><?php echo h(
        $data["report"]["report_type"],
    ); ?> - <?php echo h($data["report"]["report_date"]); ?></div>

    <div class="card">
        <h2><?php echo ucfirst(
            $data["report"]["report_type"],
        ); ?> Report: <?php echo h($data["report"]["report_date"]); ?></h2>
        <p class="text-muted"><?php echo number_format(
            $data["report"]["record_count"],
        ); ?> rows imported on <?php echo date("Y-m-d H:i", strtotime($data["report"]["created_at"])); ?></p>

        <h3 style="margin-top: 20px;">All Line Items</h3>
        <?php if (empty($data["lines"])): ?>
            <p class="text-muted">No line items.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Cust ID</th>
                        <th>Customer Name</th>
                        <th>Hit Code</th>
                        <th>Transaction</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Revenue</th>
                        <th>EFX Code</th>
                        <th>Billing ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["lines"] as $line): ?>
                    <tr>
                        <td><?php echo h($line["year"]); ?></td>
                        <td><?php echo h($line["month"]); ?></td>
                        <td><?php echo h($line["customer_id"]); ?></td>
                        <td><?php echo h($line["customer_name"]); ?></td>
                        <td><?php echo h($line["hit_code"]); ?></td>
                        <td><?php echo h($line["tran_displayname"]); ?></td>
                        <td class="text-right">$<?php echo number_format(
                            $line["actual_unit_cost"],
                            4,
                        ); ?></td>
                        <td class="text-right"><?php echo number_format(
                            $line["count"],
                        ); ?></td>
                        <td class="text-right">$<?php echo number_format(
                            $line["revenue"],
                            2,
                        ); ?></td>
                        <td><?php echo h($line["efx_code"]); ?></td>
                        <td><?php echo h($line["billing_id"]); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render bulk ingestion page
 */
function render_ingestion_bulk($data)
{
    render_header("Bulk Ingestion - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=ingestion">Ingestion</a><span>/</span>Bulk Import</div>

    <div class="card">
        <h2>Bulk Import Results</h2>
        <?php if (!empty($data["results"])): ?>
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Status</th>
                        <th>Rows</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["results"] as $result): ?>
                    <tr>
                        <td><?php echo h($result["filename"]); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $result[
                                "success"
                            ]
                                ? "success"
                                : "error"; ?>">
                                <?php echo $result["success"]
                                    ? "OK"
                                    : "Failed"; ?>
                            </span>
                        </td>
                        <td><?php echo isset($result["rows_imported"])
                            ? $result["rows_imported"]
                            : "-"; ?></td>
                        <td><?php echo h(
                            isset($result["errors"])
                                ? implode(", ", $result["errors"])
                                : "",
                        ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No results.</p>
        <?php endif; ?>
    </div>
<?php
}
/**
 * Render generation page - generate tier_pricing.csv
 */ function render_generation($data)
{
    render_header("Generation - Control Panel"); ?>
    <div class="card">
        <h2>Generate Tier Pricing CSV</h2>

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $data[
                    "active_customers"
                ]; ?></div>
                <div style="color: #666; font-size: 13px;">Active Customers</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $data[
                    "services_count"
                ]; ?></div>
                <div style="color: #666; font-size: 13px;">Services</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $data[
                    "transaction_types_count"
                ]; ?></div>
                <div style="color: #666; font-size: 13px;">Transaction Types</div>
            </div>
        </div>

        <form method="POST" style="margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 4px;">
            <div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">As of Date</label>
                    <input type="date" name="as_of_date" value="<?php echo h(
                        $data["as_of_date"],
                    ); ?>" class="form-control">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="include_inactive" value="1" <?php echo $data[
                            "include_inactive"
                        ]
                            ? "checked"
                            : ""; ?>>
                        Include inactive customers
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="preview" value="1" class="btn">Preview</button>
                    <button type="submit" name="action" value="save_pending" class="btn btn-success">Generate & Save</button>
                    <a href="?action=generation&download=1&as_of_date=<?php
                    echo h($data["as_of_date"]);
                    echo $data["include_inactive"] ? "&include_inactive=1" : "";
                    ?>" class="btn btn-info">Download CSV</a>
                </div>
            </div>
        </form>

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

        <?php if (!empty($data["pending_files"])): ?>
        <h3 style="margin-top: 30px;">Recent Generated Files</h3>
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Generated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data["pending_files"] as $file): ?>
                <tr>
                    <td><?php echo h($file["filename"]); ?></td>
                    <td><?php echo format_filesize($file["size"]); ?></td>
                    <td><?php echo date(
                        "Y-m-d H:i:s",
                        $file["modified"],
                    ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Transaction Types</h2>
        <p class="text-muted">Manage EFX code to service mappings.</p>
        <a href="?action=generation_types" class="btn">Manage Transaction Types</a>
    </div>
<?php
} /**
 * Render transaction types management
 */
function render_generation_types($data)
{
    render_header("Transaction Types - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=generation">Generation</a><span>/</span>Transaction Types</div>

    <div class="card">
        <h2>Transaction Types</h2>
        <p class="text-muted">Map EFX codes to services for billing generation.</p>

        <h3>Import from CSV</h3>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="file" name="types_csv" accept=".csv" required style="flex: 1;">
                <button type="submit" class="btn btn-success">Upload & Import</button>
            </div>
            <p class="text-muted" style="margin-top: 8px; font-size: 12px;">
                CSV format: <code>efx_code,description,service_id</code>
            </p>
        </form>

        <h3>Current Transaction Types</h3>
        <?php if (empty($data["types"])): ?>
            <p class="text-muted">No transaction types defined.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>EFX Code</th>
                        <th>Description</th>
                        <th>Service</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["types"] as $type): ?>
                    <tr>
                        <td><code><?php echo h(
                            $type["efx_code"],
                        ); ?></code></td>
                        <td><?php echo h($type["description"]); ?></td>
                        <td><?php echo h(
                            isset($type["service_name"])
                                ? $type["service_name"]
                                : "-",
                        ); ?></td>
                        <td class="text-right">
                            <a href="?action=generation_types&delete=<?php echo $type[
                                "id"
                            ]; ?>" class="btn btn-sm" style="background: #e74c3c;" onclick="return confirm('Delete this type?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3 style="margin-top: 30px;">Add Transaction Type</h3>
        <form method="POST">
            <input type="hidden" name="add_type" value="1">
            <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="display: block; margin-bottom: 5px;">EFX Code</label>
                    <input type="text" name="efx_code" required class="form-control" style="width: 150px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">Description</label>
                    <input type="text" name="description" required class="form-control" style="width: 250px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">Service</label>
                    <select name="service_id" class="form-control">
                        <option value="">-- None --</option>
                        <?php foreach ($data["services"] as $svc): ?>
                            <option value="<?php echo $svc[
                                "id"
                            ]; ?>"><?php echo h($svc["name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Add</button>
            </div>
        </form>
    </div>
<?php
} /**
 * Render LMS list
 */
function render_lms($data)
{
    render_header("LMS Management - Control Panel"); ?>
    <div class="card">
        <h2>LMS Management</h2>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <p class="text-muted" style="margin: 0;">Manage Loan Management System entries and commission rates.</p>
            <div>
                <a href="?action=lms_settings" class="btn">Settings</a>
                <a href="?action=lms&sync=1" class="btn btn-info">Sync from Remote</a>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <strong>Default Commission Rate:</strong> <?php echo $data[
                "default_rate"
            ] !== null
                ? number_format($data["default_rate"], 2) . "%"
                : "Not set"; ?>
        </div>

        <?php if (!empty($data["unassigned_customers"])): ?>
        <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
            <strong>Warning:</strong> <?php echo count(
                $data["unassigned_customers"],
            ); ?> customer(s) have no LMS assigned.
        </div>
        <?php endif; ?>

        <?php
        $search_val = isset($data["search"]) ? $data["search"] : "";
        render_search_bar("lms", [
            "search" => $search_val,
            "placeholder" => "Search LMS...",
        ]);
        ?>

        <?php if (empty($data["lms_list"])): ?>
            <p class="text-muted">No LMS entries found. <?php echo empty(
                $search_val
            )
                ? 'Click "Sync from Remote" to import.'
                : "Try a different search."; ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>LMS ID</th>
                        <th>Name</th>
                        <th>Commission Rate</th>
                        <th>Customers</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["lms_list"] as $lms): ?>
                    <tr>
                        <td><code><?php echo h($lms["id"]); ?></code></td>
                        <td><?php echo h($lms["name"]); ?></td>
                        <td>
                            <?php echo number_format(
                                $lms["effective_rate"],
                                2,
                            ); ?>%
                            <?php if ($lms["is_inherited"]): ?>
                                <span class="text-muted" style="font-size: 11px;">(default)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $lms["customer_count"]; ?></td>
                        <td class="text-right">
                            <a href="?action=lms_edit&lms_id=<?php echo urlencode(
                                $lms["id"],
                            ); ?>" class="btn btn-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $pag_params = [];
            if (!empty($data["search"])) {
                $pag_params["search"] = $data["search"];
            }
            render_pagination($data["pagination"], "?action=lms", $pag_params);
            ?>
        <?php endif; ?>
    </div>
<?php
}
/**
 * Render LMS edit form
 */ function render_lms_edit($data)
{
    render_header("Edit LMS - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=lms">LMS</a><span>/</span><?php echo h(
        $data["lms"]["name"],
    ); ?></div>

    <div class="card">
        <h2>Edit LMS: <?php echo h($data["lms"]["name"]); ?></h2>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px;">
                    <input type="checkbox" name="use_default" value="1" <?php echo $data[
                        "lms"
                    ]["commission_rate"] === null
                        ? "checked"
                        : ""; ?> onchange="document.getElementById('rate_input').disabled = this.checked;">
                    Use default commission rate (<?php echo number_format(
                        $data["default_rate"],
                        2,
                    ); ?>%)
                </label>
                <div>
                    <label style="display: block; margin-bottom: 5px;">Commission Rate (%)</label>
                    <input type="number" step="0.01" name="commission_rate" id="rate_input"
                           value="<?php echo $data["lms"]["commission_rate"] !==
                           null
                               ? $data["lms"]["commission_rate"]
                               : $data["default_rate"]; ?>"
                           class="form-control" style="width: 150px;"
                           <?php echo $data["lms"]["commission_rate"] === null
                               ? "disabled"
                               : ""; ?>>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
        </form>
    </div>

    <?php if (!empty($data["customers"])): ?>
    <div class="card">
        <h2>Customers Using This LMS (<?php echo count(
            $data["customers"],
        ); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data["customers"] as $cust): ?>
                <tr>
                    <td><code><?php echo h($cust["id"]); ?></code></td>
                    <td><?php echo h($cust["name"]); ?></td>
                    <td><span class="badge badge-<?php echo $cust["status"] ===
                    "active"
                        ? "success"
                        : "info"; ?>"><?php echo h(
    $cust["status"],
); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
<?php
}
/**
 * Render LMS settings
 */ function render_lms_settings($data)
{
    render_header("LMS Settings - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=lms">LMS</a><span>/</span>Settings</div>

    <div class="card">
        <h2>LMS Settings</h2>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Default Commission Rate (%)</label>
                <input type="number" step="0.01" name="default_commission_rate"
                       value="<?php echo $data["default_rate"] !== null
                           ? $data["default_rate"]
                           : ""; ?>"
                       class="form-control" style="width: 150px;">
                <p class="text-muted" style="margin-top: 5px; font-size: 12px;">
                    This rate is used for LMS entries that don't have a custom rate set.
                </p>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
        </form>
    </div>
<?php
} /**
 * Render LMS commission report
 */
function render_lms_report($data)
{
    render_header("LMS Commission Report - Control Panel"); ?>
    <div class="breadcrumb"><a href="?action=lms">LMS</a><span>/</span>Commission Report</div>

    <div class="card">
        <h2>Commission Report</h2>
        <?php if (empty($data["report"])): ?>
            <p class="text-muted">No data available for this period.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>LMS</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total_commission = 0; ?>
                    <?php foreach ($data["report"] as $row): ?>
                    <tr>
                        <td><?php echo h($row["lms_name"]); ?></td>
                        <td class="text-right">$<?php echo number_format(
                            $row["revenue"],
                            2,
                        ); ?></td>
                        <td class="text-right"><?php echo number_format(
                            $row["rate"],
                            2,
                        ); ?>%</td>
                        <td class="text-right">$<?php echo number_format(
                            $row["commission"],
                            2,
                        ); ?></td>
                    </tr>
                    <?php $total_commission += $row["commission"]; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td colspan="3">Total</td>
                        <td class="text-right">$<?php echo number_format(
                            $total_commission,
                            2,
                        ); ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render customer pricing view - color-coded effective pricing
 */
function render_customer_pricing($data)
{
    render_header(
        "Pricing View: " . $data["customer"]["name"] . " - Control Panel",
    ); ?>

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
        .price-comparison { font-size: 11px; color: #666; }
        .price-comparison del { color: #999; }
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
        <a href="?action=pricing_customers">Customers</a><span>/</span><?php echo h(
            $data["customer"]["name"],
        ); ?><span>/</span>Pricing View
    </div>

    <div class="card">
        <h2>
            <?php echo h($data["customer"]["name"]); ?>
            <span class="badge badge-<?php echo $data["customer"]["status"] ===
            "active"
                ? "success"
                : "info"; ?>" style="margin-left: 10px;">
                <?php echo h($data["customer"]["status"]); ?>
            </span>
        </h2>

        <?php if ($data["customer"]["group_name"]): ?>
            <p style="margin-bottom: 15px;">
                <strong>Discount Group:</strong>
                <a href="?action=pricing_group_edit&id=<?php echo $data[
                    "customer"
                ]["discount_group_id"]; ?>"><?php echo h(
    $data["customer"]["group_name"],
); ?></a>
            </p>
        <?php else: ?>
            <p style="margin-bottom: 15px; color: #666;">No discount group (inherits directly from system defaults)</p>
        <?php endif; ?>

        <!-- Legend -->
        <div class="pricing-legend" style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
            <strong style="margin-right: 10px;">Price Source:</strong>
            <div class="pricing-legend-item">
                <div class="pricing-legend-color default"></div>
                <span>System Default</span>
            </div>
            <div class="pricing-legend-item">
                <div class="pricing-legend-color group"></div>
                <span>Discount Group Override</span>
            </div>
            <div class="pricing-legend-item">
                <div class="pricing-legend-color customer"></div>
                <span>Customer Override</span>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
        <div class="summary-box" style="background: #f8f9fa;">
            <div class="number"><?php echo $data["summary"][
                "total_services"
            ]; ?></div>
            <div class="label">Total Services</div>
        </div>
        <div class="summary-box" style="background: #e3f2fd;">
            <div class="number" style="color: #2196f3;"><?php echo $data[
                "summary"
            ]["using_defaults"]; ?></div>
            <div class="label">Using Defaults</div>
        </div>
        <div class="summary-box" style="background: #fff3e0;">
            <div class="number" style="color: #ff9800;"><?php echo $data[
                "summary"
            ]["group_overrides"]; ?></div>
            <div class="label">Group Overrides</div>
        </div>
        <div class="summary-box" style="background: #e8f5e9;">
            <div class="number" style="color: #4caf50;"><?php echo $data[
                "summary"
            ]["customer_overrides"]; ?></div>
            <div class="label">Customer Overrides</div>
        </div>
    </div>

    <!-- Customer Settings -->
    <div class="card">
        <h2>Customer Settings</h2>
        <div class="settings-grid">
            <div class="settings-item">
                <div class="label">Monthly Minimum</div>
                <div class="value">
                    <?php if ($data["settings"]["monthly_minimum"]): ?>
                        $<?php echo number_format(
                            $data["settings"]["monthly_minimum"],
                            2,
                        ); ?>
                    <?php else: ?>
                        <span style="color: #999;">None</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="settings-item">
                <div class="label">Annualized Tiers</div>
                <div class="value">
                    <?php if ($data["settings"]["uses_annualized"]): ?>
                        <span style="color: #4caf50;">Enabled</span>
                        <?php if (
                            $data["settings"]["annualized_start_date"]
                        ): ?>
                            <br><small>Start: <?php echo h(
                                $data["settings"]["annualized_start_date"],
                            ); ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #999;">Disabled</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="settings-item">
                <div class="label">Look Period</div>
                <div class="value">
                    <?php if ($data["settings"]["look_period_months"]): ?>
                        <?php echo $data["settings"][
                            "look_period_months"
                        ]; ?> months
                    <?php else: ?>
                        <span style="color: #999;">N/A</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="settings-item">
                <div class="label">LMS</div>
                <div class="value">
                    <?php if ($data["customer"]["lms_id"]): ?>
                        <?php echo h($data["customer"]["lms_id"]); ?>
                    <?php else: ?>
                        <span style="color: #e74c3c;">Not Assigned</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Escalators -->
    <div class="card">
        <h2>Escalators</h2>
        <?php if (empty($data["escalators"])): ?>
            <p class="text-muted">No escalators configured for this customer.</p>
        <?php else: ?>
            <?php
            $start_date = $data["escalators"][0]["escalator_start_date"];
            $current_year = 1;
            if ($start_date) {
                $years_since = floor(
                    (time() - strtotime($start_date)) / (365.25 * 24 * 60 * 60),
                );
                $current_year = max(1, $years_since + 1);
            }
            ?>
            <p><strong>Contract Start:</strong> <?php echo h($start_date); ?>
               <?php if ($data["total_delay"] > 0): ?>
                   <span class="badge badge-warning">+<?php echo $data[
                       "total_delay"
                   ]; ?> month delay applied</span>
               <?php endif; ?>
            </p>
            <div class="escalator-timeline">
                <?php foreach ($data["escalators"] as $esc): ?>
                    <div class="escalator-year <?php echo $esc["year_number"] ==
                    $current_year
                        ? "active"
                        : ""; ?>">
                        <div class="year-num">Year <?php echo $esc[
                            "year_number"
                        ]; ?></div>
                        <div class="year-pct">
                            <?php if ($esc["escalator_percentage"] > 0): ?>
                                +<?php echo $esc["escalator_percentage"]; ?>%
                            <?php else: ?>
                                Base
                            <?php endif; ?>
                            <?php if ($esc["fixed_adjustment"] != 0): ?>
                                <br><?php echo $esc["fixed_adjustment"] > 0
                                    ? "+"
                                    : ""; ?>$<?php echo number_format(
    $esc["fixed_adjustment"],
    2,
); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Service Pricing -->
    <div class="card">
        <h2>Service Pricing (<?php echo count(
            $data["pricing_data"],
        ); ?> services)</h2>

        <?php if (empty($data["pricing_data"])): ?>
            <p class="text-muted">No services configured.</p>
        <?php else: ?>
            <?php foreach ($data["pricing_data"] as $pd): ?>
                <?php
                $service = $pd["service"];
                $header_class = "pricing-source-default";
                if ($pd["has_customer_override"]) {
                    $header_class = "pricing-source-customer";
                } elseif ($pd["has_group_override"]) {
                    $header_class = "pricing-source-group";
                }
                ?>
                <div class="pricing-card">
                    <div class="pricing-card-header <?php echo $header_class; ?>">
                        <span><?php echo h($service["name"]); ?></span>
                        <span style="font-weight: normal; font-size: 12px;">
                            <?php echo count(
                                $pd["tiers"],
                            ); ?> tier<?php echo count($pd["tiers"]) != 1
     ? "s"
     : ""; ?>
                        </span>
                    </div>
                    <div class="pricing-card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Volume Range</th>
                                    <th>Effective Price</th>
                                    <th>Default</th>
                                    <?php if (
                                        $data["customer"]["discount_group_id"]
                                    ): ?>
                                        <th>Group</th>
                                    <?php endif; ?>
                                    <th>Customer</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pd["tiers"] as $tier): ?>
                                    <tr class="tier-row source-<?php echo $tier[
                                        "source"
                                    ]; ?>">
                                        <td>
                                            <?php echo number_format(
                                                $tier["volume_start"],
                                            ); ?>
                                            -
                                            <?php echo $tier["volume_end"]
                                                ? number_format(
                                                    $tier["volume_end"],
                                                )
                                                : "Unlimited"; ?>
                                        </td>
                                        <td class="price-cell">
                                            <strong>$<?php echo number_format(
                                                $tier["price"],
                                                4,
                                            ); ?></strong>
                                        </td>
                                        <td class="price-cell" style="color: #2196f3;">
                                            <?php echo $tier[
                                                "default_price"
                                            ] !== null
                                                ? "$" .
                                                    number_format(
                                                        $tier["default_price"],
                                                        4,
                                                    )
                                                : "-"; ?>
                                        </td>
                                        <?php if (
                                            $data["customer"][
                                                "discount_group_id"
                                            ]
                                        ): ?>
                                            <td class="price-cell" style="color: #ff9800;">
                                                <?php echo $tier[
                                                    "group_price"
                                                ] !== null
                                                    ? "$" .
                                                        number_format(
                                                            $tier[
                                                                "group_price"
                                                            ],
                                                            4,
                                                        )
                                                    : "-"; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="price-cell" style="color: #4caf50;">
                                            <?php echo $tier[
                                                "customer_price"
                                            ] !== null
                                                ? "$" .
                                                    number_format(
                                                        $tier["customer_price"],
                                                        4,
                                                    )
                                                : "-"; ?>
                                        </td>
                                        <td>
                                            <?php if (
                                                $tier["source"] === "default"
                                            ): ?>
                                                <span class="badge" style="background: #2196f3; color: white;">Default</span>
                                            <?php elseif (
                                                $tier["source"] === "group"
                                            ): ?>
                                                <span class="badge" style="background: #ff9800; color: white;">Group</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #4caf50; color: white;">Customer</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="?action=pricing_customer_edit&id=<?php echo $data["customer"][
            "id"
        ]; ?>" class="btn">Edit Pricing</a>
        <a href="?action=pricing_customer_edit&id=<?php echo $data["customer"][
            "id"
        ]; ?>&tab=settings" class="btn">Edit Settings</a>
        <a href="?action=escalator_edit&customer_id=<?php echo $data[
            "customer"
        ]["id"]; ?>" class="btn">Edit Escalators</a>
    </div>
<?php
}
/**
 * Render monthly minimums overview
 */ function render_minimums($data)
{
    render_header("Monthly Minimums - Control Panel"); ?>
    <div class="card">
        <h2>Monthly Minimums</h2>
        <p class="text-muted">Customers with monthly minimum billing amounts configured.</p>

        <div style="display: flex; gap: 20px; margin: 20px 0;">
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo (int) $data[
                    "stats"
                ]["count"]; ?></div>
                <div style="color: #666; font-size: 13px;">Customers with Minimums</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;">$<?php echo number_format(
                    (float) $data["stats"]["total_minimums"],
                    2,
                ); ?></div>
                <div style="color: #666; font-size: 13px;">Total Monthly Minimums</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;">$<?php echo number_format(
                    (float) $data["stats"]["avg_minimum"],
                    2,
                ); ?></div>
                <div style="color: #666; font-size: 13px;">Average Minimum</div>
            </div>
        </div>

        <?php if (empty($data["customers"])): ?>
            <p class="text-muted">No customers have monthly minimums configured.</p>
            <p><a href="?action=pricing_customers" class="btn">Go to Customers</a> to configure minimums.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Group</th>
                        <th>Status</th>
                        <th class="text-right">Monthly Minimum</th>
                        <th>Effective Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["customers"] as $customer): ?>
                    <tr>
                        <td><?php echo h($customer["name"]); ?></td>
                        <td><?php echo h(
                            $customer["group_name"] ?: "-",
                        ); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $customer[
                                "status"
                            ] === "active"
                                ? "success"
                                : "info"; ?>">
                                <?php echo h($customer["status"]); ?>
                            </span>
                        </td>
                        <td class="text-right">$<?php echo number_format(
                            $customer["monthly_minimum"],
                            2,
                        ); ?></td>
                        <td><?php echo h($customer["effective_date"]); ?></td>
                        <td class="text-right">
                            <a href="?action=pricing_customer_edit&id=<?php echo $customer[
                                "id"
                            ]; ?>&tab=settings" class="btn btn-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
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

        <div style="display: flex; gap: 20px; margin: 20px 0;">
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo (int) $data[
                    "stats"
                ]["count"]; ?></div>
                <div style="color: #666; font-size: 13px;">Annualized Customers</div>
            </div>
            <div style="flex: 1; background: #d4edda; padding: 15px; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo count(
                    $data["upcoming_resets"],
                ); ?></div>
                <div style="color: #155724; font-size: 13px;">Resets in Next 30 Days</div>
            </div>
        </div>

        <?php if (!empty($data["upcoming_resets"])): ?>
        <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
            <strong>Upcoming Resets:</strong>
            <ul style="margin: 10px 0 0 20px; padding: 0;">
                <?php foreach ($data["upcoming_resets"] as $reset): ?>
                <li><?php echo h($reset["customer_name"]); ?> - <?php echo h(
     $reset["reset_date"],
 ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (empty($data["customers"])): ?>
            <p class="text-muted">No customers have annualized tiers enabled.</p>
            <p><a href="?action=pricing_customers" class="btn">Go to Customers</a> to enable annualized pricing.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Group</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Look Period</th>
                        <th>Next Reset</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["customers"] as $customer): ?>
                    <tr>
                        <td><?php echo h($customer["name"]); ?></td>
                        <td><?php echo h(
                            $customer["group_name"] ?: "-",
                        ); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $customer[
                                "status"
                            ] === "active"
                                ? "success"
                                : "info"; ?>">
                                <?php echo h($customer["status"]); ?>
                            </span>
                        </td>
                        <td><?php echo h(
                            $customer["annualized_start_date"] ?: "Not set",
                        ); ?></td>
                        <td><?php echo $customer["look_period_months"]
                            ? $customer["look_period_months"] . " months"
                            : "-"; ?></td>
                        <td>
                            <?php if ($customer["next_reset"]): ?>
                                <?php
                                $days_until =
                                    (strtotime($customer["next_reset"]) -
                                        time()) /
                                    86400;
                                $badge_class =
                                    $days_until <= 30
                                        ? "badge-warning"
                                        : "badge-info";
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo h(
    $customer["next_reset"],
); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="?action=pricing_customer_edit&id=<?php echo $customer[
                                "id"
                            ]; ?>&tab=settings" class="btn btn-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
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
}
/**
 * Render list archived configs
 */ function render_list_archive($data)
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

        <?php if (empty($data["services"])): ?>
            <p class="text-muted">No services defined.</p>
        <?php else: ?>
            <?php foreach ($data["services"] as $idx => $service): ?>
            <div class="tier-section" style="border: 1px solid #eee; border-radius: 4px; margin-bottom: 15px; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; cursor: pointer;" onclick="toggleTiers('default-<?php echo $service[
                    "id"
                ]; ?>')">
                    <div>
                        <strong><?php echo h($service["name"]); ?></strong>
                        <span class="text-muted" style="margin-left: 15px;"><?php echo $service[
                            "tier_count"
                        ]; ?> tiers</span>
                        <?php if (!empty($service["tiers"])): ?>
                            <span class="text-muted" style="margin-left: 15px;">
                                $<?php echo number_format(
                                    $service["tiers"][
                                        count($service["tiers"]) - 1
                                    ]["price_per_inquiry"],
                                    2,
                                ); ?>
                                - $<?php echo number_format(
                                    $service["tiers"][0]["price_per_inquiry"],
                                    2,
                                ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="toggle-icon" id="icon-default-<?php echo $service[
                            "id"
                        ]; ?>" style="margin-right: 10px; color: #999;">&#9650;</span>
                        <a href="?action=pricing_defaults_edit&service_id=<?php echo $service[
                            "id"
                        ]; ?>" class="btn btn-sm" onclick="event.stopPropagation();">Edit Tiers</a>
                    </div>
                </div>
                <div class="tier-content" id="tiers-default-<?php echo $service[
                    "id"
                ]; ?>" style="padding: 0 15px 15px 15px;">
                    <?php if (!empty($service["tiers"])): ?>
                    <table style="margin-top: 10px; font-size: 13px;">
                        <thead>
                            <tr>
                                <th style="padding: 6px 10px;">Volume Start</th>
                                <th style="padding: 6px 10px;">Volume End</th>
                                <th style="padding: 6px 10px;">Price Per Inquiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service["tiers"] as $tier): ?>
                            <tr>
                                <td style="padding: 6px 10px;"><?php echo number_format(
                                    $tier["volume_start"],
                                ); ?></td>
                                <td style="padding: 6px 10px;"><?php echo $tier[
                                    "volume_end"
                                ] !== null
                                    ? number_format($tier["volume_end"])
                                    : "<em>Unlimited</em>"; ?></td>
                                <td style="padding: 6px 10px;">$<?php echo number_format(
                                    $tier["price_per_inquiry"],
                                    4,
                                ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p class="text-muted" style="margin-top: 10px;">No tiers defined.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
<?php
}
/**
 * Render system defaults edit form
 */ function render_pricing_defaults_edit($data)
{
    render_header("Edit Default Pricing - " . h($data["service"]["name"])); ?>
    <div class="card">
        <h2>Edit Default Pricing: <?php echo h(
            $data["service"]["name"],
        ); ?></h2>
        <p class="text-muted mb-20">Define volume-based pricing tiers. Leave "Volume End" empty for unlimited.</p>

        <form method="post" id="tier-form">
            <table id="tiers-table">
                <thead>
                    <tr>
                        <th>Volume Start</th>
                        <th>Volume End</th>
                        <th>Price Per Inquiry</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data["tiers"])): ?>
                    <tr class="tier-row">
                        <td><input type="number" name="volume_start[]" class="form-control" value="0" min="0" required></td>
                        <td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>
                        <td><input type="number" name="price_per_inquiry[]" class="form-control" step="0.01" min="0" required></td>
                        <td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($data["tiers"] as $tier): ?>
                        <tr class="tier-row">
                            <td><input type="number" name="volume_start[]" class="form-control" value="<?php echo h(
                                $tier["volume_start"],
                            ); ?>" min="0" required></td>
                            <td><input type="number" name="volume_end[]" class="form-control" value="<?php echo $tier[
                                "volume_end"
                            ] !== null
                                ? h($tier["volume_end"])
                                : ""; ?>" placeholder="Unlimited" min="0"></td>
                            <td><input type="number" name="price_per_inquiry[]" class="form-control" value="<?php echo h(
                                $tier["price_per_inquiry"],
                            ); ?>" step="0.01" min="0" required></td>
                            <td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin: 15px 0;">
                <button type="button" class="btn" onclick="addRow()">+ Add Tier</button>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">Save Default Pricing</button>
                <a href="?action=pricing_defaults" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function addRow() {
        var tbody = document.querySelector('#tiers-table tbody');
        var lastRow = tbody.querySelector('.tier-row:last-child');
        var nextStart = 0;

        if (lastRow) {
            var endInput = lastRow.querySelector('input[name="volume_end[]"]');
            if (endInput.value) {
                nextStart = parseInt(endInput.value) + 1;
            }
        }

        var row = document.createElement('tr');
        row.className = 'tier-row';
        row.innerHTML = '<td><input type="number" name="volume_start[]" class="form-control" value="' + nextStart + '" min="0" required></td>' +
            '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>' +
            '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="0.01" min="0" required></td>' +
            '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
        tbody.appendChild(row);
    }

    function removeRow(btn) {
        var rows = document.querySelectorAll('.tier-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        }
    }
    </script>

    <div class="breadcrumb" style="margin-top: 20px;"><a href="?action=pricing_defaults">Default Pricing</a><span>/</span>Edit Tiers</div>
<?php
}
/**
 * Render discount groups list
 */ function render_pricing_groups($data)
{
    render_header("Discount Groups - Pricing"); ?>
    <div class="card">
        <h2>Discount Groups</h2>
        <p class="text-muted mb-20">Group-level pricing templates. Members inherit these settings.</p>

        <?php if (empty($data["groups"])): ?>
            <p class="text-muted">No discount groups defined.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Members</th>
                        <th>Overrides</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["groups"] as $group): ?>
                    <tr>
                        <td><strong><?php echo h(
                            $group["name"],
                        ); ?></strong></td>
                        <td><?php echo $group["member_count"]; ?> customers</td>
                        <td><?php echo $group[
                            "override_count"
                        ]; ?> services</td>
                        <td class="text-right">
                            <a href="?action=pricing_group_edit&group_id=<?php echo $group[
                                "id"
                            ]; ?>" class="btn btn-sm">Edit Pricing</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render group services list (select which service to edit)
 */
function render_pricing_group_services($data)
{
    render_header("Group Pricing - " . h($data["group"]["name"])); ?>
    <div class="card">
        <h2 style="display: flex; justify-content: space-between; align-items: center;">
            <?php echo h($data["group"]["name"]); ?> - Service Pricing
            <span style="font-size: 12px; font-weight: normal;">
                <a href="javascript:void(0)" onclick="expandAll()" class="btn btn-sm">Expand All</a>
                <a href="javascript:void(0)" onclick="collapseAll()" class="btn btn-sm">Collapse All</a>
            </span>
        </h2>
        <p class="text-muted mb-20">Select a service to override pricing. Inherited values come from System Defaults.</p>

        <?php foreach ($data["services"] as $service): ?>
        <div class="tier-section" style="border: 1px solid #eee; border-radius: 4px; margin-bottom: 15px; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; cursor: pointer;" onclick="toggleTiers('group-<?php echo $service[
                "id"
            ]; ?>')">
                <div>
                    <strong><?php echo h($service["name"]); ?></strong>
                    <?php if ($service["has_override"]): ?>
                        <span style="color: #27ae60; margin-left: 15px;">Overridden</span>
                    <?php else: ?>
                        <span class="text-muted" style="margin-left: 15px;">Inherited</span>
                    <?php endif; ?>
                    <?php if (!empty($service["tiers"])): ?>
                        <span class="text-muted" style="margin-left: 15px;">
                            $<?php echo number_format(
                                $service["tiers"][count($service["tiers"]) - 1][
                                    "price_per_inquiry"
                                ],
                                2,
                            ); ?>
                            - $<?php echo number_format(
                                $service["tiers"][0]["price_per_inquiry"],
                                2,
                            ); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="toggle-icon" id="icon-group-<?php echo $service[
                        "id"
                    ]; ?>" style="margin-right: 10px; color: #999;">&#9650;</span>
                    <a href="?action=pricing_group_edit&group_id=<?php echo $data[
                        "group"
                    ]["id"]; ?>&service_id=<?php echo $service[
    "id"
]; ?>" class="btn btn-sm" onclick="event.stopPropagation();">
                        <?php echo $service["has_override"]
                            ? "Edit"
                            : "Override"; ?>
                    </a>
                </div>
            </div>
            <div class="tier-content" id="tiers-group-<?php echo $service[
                "id"
            ]; ?>" style="padding: 0 15px 15px 15px;">
                <?php if (!empty($service["tiers"])): ?>
                <table style="margin-top: 10px; font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="padding: 6px 10px;">Volume Start</th>
                            <th style="padding: 6px 10px;">Volume End</th>
                            <th style="padding: 6px 10px;">Price Per Inquiry</th>
                            <th style="padding: 6px 10px;">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($service["tiers"] as $tier): ?>
                        <tr>
                            <td style="padding: 6px 10px;"><?php echo number_format(
                                $tier["volume_start"],
                            ); ?></td>
                            <td style="padding: 6px 10px;"><?php echo $tier[
                                "volume_end"
                            ] !== null
                                ? number_format($tier["volume_end"])
                                : "<em>Unlimited</em>"; ?></td>
                            <td style="padding: 6px 10px;">$<?php echo number_format(
                                $tier["price_per_inquiry"],
                                4,
                            ); ?></td>
                            <td style="padding: 6px 10px;">
                                <?php if ($tier["source"] === "group"): ?>
                                    <span style="color: #27ae60;">Group</span>
                                <?php else: ?>
                                    <span class="text-muted">Default</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted" style="margin-top: 10px;">No tiers defined.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
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

    <div class="breadcrumb"><a href="?action=pricing_groups">Groups</a><span>/</span><?php echo h(
        $data["group"]["name"],
    ); ?></div>
<?php
} /**
 * Render group tier edit form
 */
function render_pricing_group_edit($data)
{
    render_header("Edit Group Pricing - " . h($data["group"]["name"])); ?>
    <div class="card">
        <h2><?php echo h(
            $data["group"]["name"],
        ); ?>: <?php echo h($data["service"]["name"]); ?></h2>

        <?php if ($data["has_override"]): ?>
            <p class="text-muted mb-20">
                <span style="color: #27ae60;">This group has custom pricing.</span>
                Modify tiers below or clear to inherit from defaults.
            </p>
        <?php else: ?>
            <p class="text-muted mb-20">
                Currently inheriting from System Defaults. Save to create a group override.
            </p>
        <?php endif; ?>

        <form method="post" id="tier-form">
            <table id="tiers-table">
                <thead>
                    <tr>
                        <th>Volume Start</th>
                        <th>Volume End</th>
                        <th>Price Per Inquiry</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data["tiers"])): ?>
                    <tr class="tier-row">
                        <td><input type="number" name="volume_start[]" class="form-control" value="0" min="0" required></td>
                        <td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>
                        <td><input type="number" name="price_per_inquiry[]" class="form-control" step="0.01" min="0" required></td>
                        <td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($data["tiers"] as $tier): ?>
                        <tr class="tier-row">
                            <td><input type="number" name="volume_start[]" class="form-control" value="<?php echo h(
                                $tier["volume_start"],
                            ); ?>" min="0" required></td>
                            <td><input type="number" name="volume_end[]" class="form-control" value="<?php echo $tier[
                                "volume_end"
                            ] !== null
                                ? h($tier["volume_end"])
                                : ""; ?>" placeholder="Unlimited" min="0"></td>
                            <td><input type="number" name="price_per_inquiry[]" class="form-control" value="<?php echo h(
                                $tier["price_per_inquiry"],
                            ); ?>" step="0.01" min="0" required></td>
                            <td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin: 15px 0;">
                <button type="button" class="btn" onclick="addRow()">+ Add Tier</button>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="form_action" value="save" class="btn btn-success">Save Group Pricing</button>
                <?php if ($data["has_override"]): ?>
                    <button type="submit" name="form_action" value="clear" class="btn" onclick="return confirm('Clear override and inherit from defaults?')">Clear Override</button>
                <?php endif; ?>
                <a href="?action=pricing_group_edit&group_id=<?php echo $data[
                    "group"
                ]["id"]; ?>" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function addRow() {
        var tbody = document.querySelector('#tiers-table tbody');
        var lastRow = tbody.querySelector('.tier-row:last-child');
        var nextStart = 0;

        if (lastRow) {
            var endInput = lastRow.querySelector('input[name="volume_end[]"]');
            if (endInput.value) {
                nextStart = parseInt(endInput.value) + 1;
            }
        }

        var row = document.createElement('tr');
        row.className = 'tier-row';
        row.innerHTML = '<td><input type="number" name="volume_start[]" class="form-control" value="' + nextStart + '" min="0" required></td>' +
            '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>' +
            '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="0.01" min="0" required></td>' +
            '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
        tbody.appendChild(row);
    }

    function removeRow(btn) {
        var rows = document.querySelectorAll('.tier-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        }
    }
    </script>

    <p style="margin-top: 20px;"><a href="?action=pricing_group_edit&group_id=<?php echo $data[
        "group"
    ]["id"]; ?>">&larr; Back to Services</a></p>
<?php
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
        ]);
        ?>

        <?php if (empty($data["customers"])): ?>
            <p class="text-muted">No customers found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Discount Group</th>
                        <th>Status</th>
                        <th>Contract Start</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["customers"] as $customer): ?>
                    <tr>
                        <td><strong><?php echo h(
                            $customer["name"],
                        ); ?></strong></td>
                        <td><?php echo $customer["group_name"]
                            ? h($customer["group_name"])
                            : '<span class="text-muted">None</span>'; ?></td>
                        <td>
                            <?php
                            $status_class = "";
                            if ($customer["status"] === "active") {
                                $status_class = "color: #27ae60;";
                            } elseif ($customer["status"] === "paused") {
                                $status_class = "color: #f39c12;";
                            } else {
                                $status_class = "color: #e74c3c;";
                            }
                            ?>
                            <span style="<?php echo $status_class; ?>"><?php echo ucfirst(
    $customer["status"],
); ?></span>
                        </td>
                        <td><?php echo $customer["contract_start_date"]
                            ? h($customer["contract_start_date"])
                            : "-"; ?></td>
                        <td class="text-right">
                            <a href="?action=customer_pricing&id=<?php echo $customer[
                                "id"
                            ]; ?>" class="btn btn-sm btn-info">View</a>
                            <a href="?action=pricing_customer_edit&customer_id=<?php echo $customer[
                                "id"
                            ]; ?>" class="btn btn-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $pag_params = ["status" => $data["status_filter"]];
            if (!empty($data["search"])) {
                $pag_params["search"] = $data["search"];
            }
            render_pagination(
                $data["pagination"],
                "?action=pricing_customers",
                $pag_params,
            );
            ?>
        <?php endif; ?>
    </div>
<?php
}
/**
 * Render customer services list (select which service to edit)
 */ function render_pricing_customer_services($data)
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

    <div class="card">
        <h2 style="display: flex; justify-content: space-between; align-items: center;">
            Service Pricing
            <span style="font-size: 12px; font-weight: normal;">
                <a href="javascript:void(0)" onclick="expandAll()" class="btn btn-sm">Expand All</a>
                <a href="javascript:void(0)" onclick="collapseAll()" class="btn btn-sm">Collapse All</a>
            </span>
        </h2>
        <p class="text-muted mb-20">Select a service to override pricing. Inherited values show their source.</p>

        <?php foreach ($data["services"] as $service): ?>
        <?php
        $source_label = $service["source"];
        $source_style = "";
        if ($service["source"] === "customer") {
            $source_label = "Customer";
            $source_style = "color: #27ae60; font-weight: bold;";
        } elseif ($service["source"] === "group") {
            $source_label = "Group";
            $source_style = "color: #3498db;";
        } else {
            $source_label = "Default";
            $source_style = "color: #999;";
        }
        ?>
        <div class="tier-section" style="border: 1px solid #eee; border-radius: 4px; margin-bottom: 15px; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; cursor: pointer;" onclick="toggleTiers('cust-<?php echo $service[
                "id"
            ]; ?>')">
                <div>
                    <strong><?php echo h($service["name"]); ?></strong>
                    <?php if ($service["has_override"]): ?>
                        <span style="color: #27ae60; margin-left: 15px;">Overridden</span>
                    <?php else: ?>
                        <span class="text-muted" style="margin-left: 15px;">Inherited</span>
                    <?php endif; ?>
                    <span style="<?php echo $source_style; ?> margin-left: 10px;">(<?php echo $source_label; ?>)</span>
                    <?php if (!empty($service["tiers"])): ?>
                        <span class="text-muted" style="margin-left: 15px;">
                            $<?php echo number_format(
                                $service["tiers"][count($service["tiers"]) - 1][
                                    "price_per_inquiry"
                                ],
                                2,
                            ); ?>
                            - $<?php echo number_format(
                                $service["tiers"][0]["price_per_inquiry"],
                                2,
                            ); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="toggle-icon" id="icon-cust-<?php echo $service[
                        "id"
                    ]; ?>" style="margin-right: 10px; color: #999;">&#9650;</span>
                    <a href="?action=pricing_customer_edit&customer_id=<?php echo $data[
                        "customer"
                    ]["id"]; ?>&service_id=<?php echo $service[
    "id"
]; ?>" class="btn btn-sm" onclick="event.stopPropagation();">
                        <?php echo $service["has_override"]
                            ? "Edit"
                            : "Override"; ?>
                    </a>
                </div>
            </div>
            <div class="tier-content" id="tiers-cust-<?php echo $service[
                "id"
            ]; ?>" style="padding: 0 15px 15px 15px;">
                <?php if (!empty($service["tiers"])): ?>
                <table style="margin-top: 10px; font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="padding: 6px 10px;">Volume Start</th>
                            <th style="padding: 6px 10px;">Volume End</th>
                            <th style="padding: 6px 10px;">Price Per Inquiry</th>
                            <th style="padding: 6px 10px;">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($service["tiers"] as $tier): ?>
                        <tr>
                            <td style="padding: 6px 10px;"><?php echo number_format(
                                $tier["volume_start"],
                            ); ?></td>
                            <td style="padding: 6px 10px;"><?php echo $tier[
                                "volume_end"
                            ] !== null
                                ? number_format($tier["volume_end"])
                                : "<em>Unlimited</em>"; ?></td>
                            <td style="padding: 6px 10px;">$<?php echo number_format(
                                $tier["price_per_inquiry"],
                                4,
                            ); ?></td>
                            <td style="padding: 6px 10px;">
                                <?php if ($tier["source"] === "customer"): ?>
                                    <span style="color: #27ae60;">Customer</span>
                                <?php elseif ($tier["source"] === "group"): ?>
                                    <span style="color: #3498db;">Group</span>
                                <?php else: ?>
                                    <span class="text-muted">Default</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted" style="margin-top: 10px;">No tiers defined.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
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
<?php
}
/**
 * Render customer tier edit form
 */ function render_pricing_customer_edit($data)
{
    render_header("Edit Customer Pricing - " . h($data["customer"]["name"])); ?>
    <div class="card">
        <h2><?php echo h(
            $data["customer"]["name"],
        ); ?>: <?php echo h($data["service"]["name"]); ?></h2>

        <?php if ($data["has_override"]): ?>
            <p class="text-muted mb-20">
                <span style="color: #27ae60;">This customer has custom pricing.</span>
                Modify tiers below or clear to inherit from <?php echo $data[
                    "customer"
                ]["group_name"]
                    ? "group"
                    : "defaults"; ?>.
            </p>
        <?php else: ?>
            <p class="text-muted mb-20">
                Currently inheriting from
                <strong style="color: <?php echo $data["source"] === "group"
                    ? "#3498db"
                    : "#999"; ?>;">
                    <?php echo $data["source"] === "group"
                        ? $data["customer"]["group_name"] . " (Group)"
                        : "System Defaults"; ?>
                </strong>.
                Save to create a customer override.
            </p>
        <?php endif; ?>

        <form method="post" id="tier-form">
            <table id="tiers-table">
                <thead>
                    <tr>
                        <th>Volume Start</th>
                        <th>Volume End</th>
                        <th>Price Per Inquiry</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data["tiers"])): ?>
                    <tr class="tier-row">
                        <td><input type="number" name="volume_start[]" class="form-control" value="0" min="0" required></td>
                        <td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>
                        <td><input type="number" name="price_per_inquiry[]" class="form-control" step="0.01" min="0" required></td>
                        <td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($data["tiers"] as $tier): ?>
                        <tr class="tier-row">
                            <td><input type="number" name="volume_start[]" class="form-control" value="<?php echo h(
                                $tier["volume_start"],
                            ); ?>" min="0" required></td>
                            <td><input type="number" name="volume_end[]" class="form-control" value="<?php echo $tier[
                                "volume_end"
                            ] !== null
                                ? h($tier["volume_end"])
                                : ""; ?>" placeholder="Unlimited" min="0"></td>
                            <td><input type="number" name="price_per_inquiry[]" class="form-control" value="<?php echo h(
                                $tier["price_per_inquiry"],
                            ); ?>" step="0.01" min="0" required></td>
                            <td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin: 15px 0;">
                <button type="button" class="btn" onclick="addRow()">+ Add Tier</button>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="form_action" value="save" class="btn btn-success">Save Customer Pricing</button>
                <?php if ($data["has_override"]): ?>
                    <button type="submit" name="form_action" value="clear" class="btn" onclick="return confirm('Clear override and inherit from <?php echo $data[
                        "customer"
                    ]["group_name"]
                        ? "group"
                        : "defaults"; ?>?')">Clear Override</button>
                <?php endif; ?>
                <a href="?action=pricing_customer_edit&customer_id=<?php echo $data[
                    "customer"
                ]["id"]; ?>" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function addRow() {
        var tbody = document.querySelector('#tiers-table tbody');
        var lastRow = tbody.querySelector('.tier-row:last-child');
        var nextStart = 0;

        if (lastRow) {
            var endInput = lastRow.querySelector('input[name="volume_end[]"]');
            if (endInput.value) {
                nextStart = parseInt(endInput.value) + 1;
            }
        }

        var row = document.createElement('tr');
        row.className = 'tier-row';
        row.innerHTML = '<td><input type="number" name="volume_start[]" class="form-control" value="' + nextStart + '" min="0" required></td>' +
            '<td><input type="number" name="volume_end[]" class="form-control" placeholder="Unlimited" min="0"></td>' +
            '<td><input type="number" name="price_per_inquiry[]" class="form-control" step="0.01" min="0" required></td>' +
            '<td><button type="button" class="btn btn-sm" onclick="removeRow(this)">Remove</button></td>';
        tbody.appendChild(row);
    }

    function removeRow(btn) {
        var rows = document.querySelectorAll('.tier-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        }
    }
    </script>

    <p style="margin-top: 20px;"><a href="?action=pricing_customer_edit&customer_id=<?php echo $data[
        "customer"
    ]["id"]; ?>">&larr; Back to Services</a></p>
<?php
} /**
 * Render customer settings form (monthly minimum, annualized, etc.)
 */
function render_pricing_customer_settings($data)
{
    render_header("Customer Settings - " . h($data["customer"]["name"])); ?>
    <div class="card">
        <h2><?php echo h($data["customer"]["name"]); ?></h2>
        <p class="text-muted mb-20">
            <?php if ($data["customer"]["group_name"]): ?>
                Group: <strong><?php echo h(
                    $data["customer"]["group_name"],
                ); ?></strong> |
            <?php else: ?>
                <span style="color: #e67e22;">No discount group</span> |
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

    <div class="card">
        <h2>Customer Settings</h2>

        <form method="post">
            <div class="form-group">
                <label for="lms_id">LMS (Loan Management System) <span style="color: #dc3545;">*</span></label>
                <select name="lms_id" id="lms_id" class="form-control" style="width: 300px;">
                    <option value="">-- Select LMS --</option>
                    <?php foreach ($data["all_lms"] as $lms): ?>
                    <option value="<?php echo $lms["id"]; ?>" <?php echo $data[
    "customer"
]["lms_id"] == $lms["id"]
    ? "selected"
    : ""; ?>>
                        <?php echo h($lms["name"]); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Required for billing calculations. <a href="?action=lms&sync=1">Sync LMS list</a> if empty.</small>
                <?php if (empty($data["customer"]["lms_id"])): ?>
                <div style="margin-top: 8px; padding: 10px; background: #f8d7da; border-radius: 4px; font-size: 13px; color: #721c24;">
                    <strong>Warning:</strong> This customer has no LMS assigned. LMS is required for revenue/commission calculations.
                </div>
                <?php endif; ?>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

            <div class="form-group">
                <label for="monthly_minimum">Monthly Minimum ($)</label>
                <input type="number" name="monthly_minimum" id="monthly_minimum" class="form-control"
                        value="<?php echo $data["settings"][
                            "monthly_minimum"
                        ] !== null
                            ? h($data["settings"]["monthly_minimum"])
                            : ""; ?>"
                        step="0.01" min="0" placeholder="No minimum">
                <small class="text-muted">Leave empty for no minimum charge. When set, if the customer's monthly usage is below this amount, a "gap" line item will be added to bring the invoice total to this minimum.</small>
                <?php if (
                    $data["settings"]["monthly_minimum"] &&
                    $data["settings"]["monthly_minimum"] > 0
                ): ?>
                <div style="margin-top: 8px; padding: 10px; background: #e8f4fd; border-radius: 4px; font-size: 13px;">
                    <strong>Current Minimum:</strong> $<?php echo number_format(
                        $data["settings"]["monthly_minimum"],
                        2,
                    ); ?>/month
                    <br><span class="text-muted">If monthly usage &lt; $<?php echo number_format(
                        $data["settings"]["monthly_minimum"],
                        2,
                    ); ?>, a gap line item will be added.</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="uses_annualized" value="1"
                            <?php echo $data["settings"]["uses_annualized"]
                                ? "checked"
                                : ""; ?>>
                    Uses Annualized Tiers
                </label>
                <small class="text-muted" style="display: block;">Enable volume calculation over a look-back period.</small>
            </div>

            <div class="form-group">
                <label for="annualized_start_date">Annualized Start Date</label>
                <input type="date" name="annualized_start_date" id="annualized_start_date" class="form-control"
                        value="<?php echo $data["settings"][
                            "annualized_start_date"
                        ]
                            ? h($data["settings"]["annualized_start_date"])
                            : ""; ?>">
                <small class="text-muted">When annualized tier calculation begins.</small>
            </div>

            <div class="form-group">
                <label for="look_period_months">Look Period (Months)</label>
                <input type="number" name="look_period_months" id="look_period_months" class="form-control"
                        value="<?php echo $data["settings"][
                            "look_period_months"
                        ] !== null
                            ? h($data["settings"]["look_period_months"])
                            : ""; ?>"
                        min="1" max="12" placeholder="e.g., 6">
                <small class="text-muted">Number of months to look back for volume calculation.</small>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">Save Settings</button>
                <a href="?action=pricing_customer_edit&customer_id=<?php echo $data[
                    "customer"
                ]["id"]; ?>" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    <div class="breadcrumb" style="margin-top: 20px;"><a href="?action=pricing_customers">Customers</a><span>/</span>Edit Tiers</div>
<?php
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
        ]);
        ?>

        <?php if (empty($data["customers"])): ?>
            <p class="text-muted">No customers have escalators configured.</p>
            <p><a href="?action=pricing_customers" class="btn">Go to Customers</a> to add escalators to a customer.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Group</th>
                        <th>Start Date</th>
                        <th>Years Defined</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["customers"] as $customer): ?>
                    <tr>
                        <td><strong><?php echo h(
                            $customer["name"],
                        ); ?></strong></td>
                        <td><?php echo $customer["group_name"]
                            ? h($customer["group_name"])
                            : '<span class="text-muted">None</span>'; ?></td>
                        <td><?php echo isset($customer["start_date"])
                            ? h($customer["start_date"])
                            : "-"; ?></td>
                        <td><?php echo $customer[
                            "escalator_count"
                        ]; ?> years</td>
                        <td class="text-right">
                            <a href="?action=escalator_edit&customer_id=<?php echo $customer[
                                "id"
                            ]; ?>" class="btn btn-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $pag_params = [];
            if (!empty($data["search"])) {
                $pag_params["search"] = $data["search"];
            }
            render_pagination(
                $data["pagination"],
                "?action=escalators",
                $pag_params,
            );
            ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Add Escalators</h2>
        <p class="text-muted">To add escalators to a customer, go to their customer pricing page.</p>
        <a href="?action=pricing_customers" class="btn">Go to Customers</a>
    </div>
<?php
}
/**
 * Render escalator edit form for a customer
 */ function render_escalator_edit($data)
{
    render_header("Edit Escalators - " . h($data["customer"]["name"]));
    $start_date = "";
    if (!empty($data["escalators"])) {
        $start_date = $data["escalators"][0]["escalator_start_date"];
    } elseif ($data["customer"]["contract_start_date"]) {
        $start_date = $data["customer"]["contract_start_date"];
    }
    ?>
    <div class="card">
        <h2><?php echo h($data["customer"]["name"]); ?> - Escalators</h2>
        <p class="text-muted mb-20">
            Define annual price increases. Year 1 typically has 0% (base contract year).
            Use the Delay button to push an escalator back by 1 month.
        </p>

        <form method="post" id="escalator-form">
            <div class="form-group">
                <label for="escalator_start_date">Escalator Start Date</label>
                <input type="date" name="escalator_start_date" id="escalator_start_date" class="form-control"
                        value="<?php echo h($start_date); ?>" required>
                <small class="text-muted">Usually the contract start date. Escalators apply annually from this date.</small>
            </div>

            <table id="escalator-table" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Percentage Increase (%)</th>
                        <th>Fixed Adjustment ($)</th>
                        <th>Total Delay</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data["escalators"])): ?>
                    <tr class="escalator-row">
                        <td><input type="number" name="year_number[]" class="form-control" value="1" min="1" required readonly style="width: 80px;"></td>
                        <td><input type="number" name="escalator_percentage[]" class="form-control" value="0" step="0.1" min="0"></td>
                        <td><input type="number" name="fixed_adjustment[]" class="form-control" value="0" step="0.01"></td>
                        <td><span class="text-muted">-</span></td>
                        <td><button type="button" class="btn btn-sm" onclick="removeEscalatorRow(this)">Remove</button></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($data["escalators"] as $esc): ?>
                        <tr class="escalator-row">
                            <td><input type="number" name="year_number[]" class="form-control" value="<?php echo h(
                                $esc["year_number"],
                            ); ?>" min="1" required readonly style="width: 80px;"></td>
                            <td><input type="number" name="escalator_percentage[]" class="form-control" value="<?php echo h(
                                $esc["escalator_percentage"],
                            ); ?>" step="0.1" min="0"></td>
                            <td><input type="number" name="fixed_adjustment[]" class="form-control" value="<?php echo h(
                                $esc["fixed_adjustment"],
                            ); ?>" step="0.01"></td>
                            <td>
                                <?php if ($esc["total_delay"] > 0): ?>
                                    <span style="color: #e67e22;"><?php echo $esc[
                                        "total_delay"
                                    ]; ?> month<?php echo $esc["total_delay"] >
 1
     ? "s"
     : ""; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                                <a href="?action=escalator_delay&customer_id=<?php echo $data[
                                    "customer"
                                ]["id"]; ?>&year_number=<?php echo $esc[
    "year_number"
]; ?>"
                                    class="btn btn-sm" style="margin-left: 5px;"
                                    onclick="return confirm('Add 1 month delay to Year <?php echo $esc[
                                        "year_number"
                                    ]; ?>?')">+1 Month</a>
                            </td>
                            <td><button type="button" class="btn btn-sm" onclick="removeEscalatorRow(this)">Remove</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin: 15px 0;">
                <button type="button" class="btn" onclick="addEscalatorRow()">+ Add Year</button>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">Save Escalators</button>
                <a href="?action=escalators" class="btn">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function addEscalatorRow() {
        var tbody = document.querySelector('#escalator-table tbody');
        var rows = tbody.querySelectorAll('.escalator-row');
        var nextYear = rows.length + 1;

        // Find max year
        rows.forEach(function(row) {
            var yearInput = row.querySelector('input[name="year_number[]"]');
            if (yearInput && parseInt(yearInput.value) >= nextYear) {
                nextYear = parseInt(yearInput.value) + 1;
            }
        });

        var row = document.createElement('tr');
        row.className = 'escalator-row';
        row.innerHTML = '<td><input type="number" name="year_number[]" class="form-control" value="' + nextYear + '" min="1" required readonly style="width: 80px;"></td>' +
            '<td><input type="number" name="escalator_percentage[]" class="form-control" value="0" step="0.1" min="0"></td>' +
            '<td><input type="number" name="fixed_adjustment[]" class="form-control" value="0" step="0.01"></td>' +
            '<td><span class="text-muted">-</span>' +
            '<a href="?action=escalator_delay&customer_id=<?php echo $data[
                "customer"
            ][
                "id"
            ]; ?>&year_number=' + nextYear + '" class="btn btn-sm" style="margin-left: 5px;" onclick="return confirm(\'Add 1 month delay to Year ' + nextYear + '?\')">+1 Month</a></td>' +
            '<td><button type="button" class="btn btn-sm" onclick="removeEscalatorRow(this)">Remove</button></td>';
        tbody.appendChild(row);
    }

    function removeEscalatorRow(btn) {
        var rows = document.querySelectorAll('.escalator-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        }
    }
    </script>

    <div class="breadcrumb" style="margin-top: 20px;"><a href="?action=escalators">Escalators</a><span>/</span>Edit</div>
<?php
} /**
 * Render business rules list (customers with rules)
 */
function render_business_rules($data)
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
        ]);
        ?>

        <?php if (empty($data["customers"])): ?>
            <p class="text-muted">No customers have business rules configured.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Group</th>
                        <th>Total Rules</th>
                        <th>Masked</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["customers"] as $customer): ?>
                    <tr>
                        <td><strong><?php echo h(
                            $customer["name"],
                        ); ?></strong></td>
                        <td><?php echo $customer["group_name"]
                            ? h($customer["group_name"])
                            : '<span class="text-muted">None</span>'; ?></td>
                        <td><?php echo $customer["rule_count"]; ?> rules</td>
                        <td>
                            <?php if ($customer["masked_count"] > 0): ?>
                                <span style="color: #e67e22;"><?php echo $customer[
                                    "masked_count"
                                ]; ?> masked</span>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="?action=business_rule_edit&customer_id=<?php echo $customer[
                                "id"
                            ]; ?>" class="btn btn-sm">Manage</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $pag_params = [];
            if (!empty($data["search"])) {
                $pag_params["search"] = $data["search"];
            }
            render_pagination(
                $data["pagination"],
                "?action=business_rules",
                $pag_params,
            );
            ?>
        <?php endif; ?>
    </div>
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

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $data[
                    "stats"
                ]["total_rules"]; ?></div>
                <div style="color: #666; font-size: 13px;">Total Rules</div>
            </div>
            <div style="flex: 1; background: #fff3cd; padding: 15px; border-radius: 4px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #856404;"><?php echo $data[
                    "stats"
                ]["masked_rules"]; ?></div>
                <div style="color: #666; font-size: 13px;">Masked Rules</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $data[
                    "stats"
                ]["customers_with_rules"]; ?></div>
                <div style="color: #666; font-size: 13px;">Customers</div>
            </div>
        </div>

        <?php render_search_bar("business_rules_all", [
            "search" => $data["search"],
            "placeholder" => "Search rules or customers...",
            "filters" => [
                [
                    "name" => "masked",
                    "options" => [
                        "" => "All Rules",
                        "1" => "Masked Only",
                        "0" => "Unmasked Only",
                    ],
                    "current" => $data["filter_masked"],
                ],
            ],
        ]); ?>

        <?php if (empty($data["rules"])): ?>
            <p class="text-muted">No rules found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Rule Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["rules"] as $rule): ?>
                    <tr style="<?php echo $rule["is_masked"]
                        ? "background: #fff3cd;"
                        : ""; ?>">
                        <td>
                            <a href="?action=business_rule_edit&customer_id=<?php echo $rule[
                                "customer_id"
                            ]; ?>"><?php echo h($rule["customer_name"]); ?></a>
                            <?php if ($rule["customer_status"] !== "active"): ?>
                                <span class="badge badge-<?php echo $rule[
                                    "customer_status"
                                ] === "paused"
                                    ? "warning"
                                    : "default"; ?>"><?php echo $rule[
    "customer_status"
]; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size: 12px;"><?php echo h(
                            $rule["rule_name"],
                        ); ?></code></td>
                        <td class="text-muted" style="font-size: 12px;"><?php echo h(
                            $rule["rule_description"] ?: "-",
                        ); ?></td>
                        <td>
                            <?php if ($rule["is_masked"]): ?>
                                <span class="badge badge-warning">Masked</span>
                            <?php else: ?>
                                <span class="badge badge-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="?action=business_rule_toggle&customer_id=<?php echo $rule[
                                "customer_id"
                            ]; ?>&rule=<?php echo urlencode(
    $rule["rule_name"],
); ?>&return=all" class="btn btn-sm">
                                <?php echo $rule["is_masked"]
                                    ? "Unmask"
                                    : "Mask"; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $pag_params = [];
            if (!empty($data["search"])) {
                $pag_params["search"] = $data["search"];
            }
            if ($data["filter_masked"] !== null) {
                $pag_params["masked"] = $data["filter_masked"];
            }
            render_pagination(
                $data["pagination"],
                "?action=business_rules_all",
                $pag_params,
            );
            ?>
        <?php endif; ?>
    </div>
<?php
} /**
 * Render business rule edit for a customer
 */
function render_business_rule_edit($data)
{
    render_header("Business Rules - " . h($data["customer"]["name"])); ?>
    <div class="card">
        <h2><?php echo h($data["customer"]["name"]); ?> - Business Rules</h2>
        <p class="text-muted mb-20">
            Masked rules are excluded from billing calculations. Toggle a rule's status using the buttons below.
        </p>

        <?php if (empty($data["rules"])): ?>
            <p class="text-muted">No business rules defined for this customer.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Rule Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["rules"] as $rule): ?>
                    <tr>
                        <td><strong><?php echo h(
                            $rule["rule_name"],
                        ); ?></strong></td>
                        <td><?php echo $rule["rule_description"]
                            ? h($rule["rule_description"])
                            : '<span class="text-muted">No description</span>'; ?></td>
                        <td>
                            <?php if ($rule["is_masked"]): ?>
                                <span style="color: #e67e22; font-weight: bold;">MASKED</span>
                            <?php else: ?>
                                <span style="color: #27ae60;">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($rule["is_masked"]): ?>
                                <a href="?action=business_rule_toggle&customer_id=<?php echo $data[
                                    "customer"
                                ]["id"]; ?>&rule_name=<?php echo urlencode(
    $rule["rule_name"],
); ?>&mask_action=unmask"
                                    class="btn btn-sm btn-success"
                                    onclick="return confirm('Unmask this rule? It will be included in billing.')">Unmask</a>
                            <?php else: ?>
                                <a href="?action=business_rule_toggle&customer_id=<?php echo $data[
                                    "customer"
                                ]["id"]; ?>&rule_name=<?php echo urlencode(
    $rule["rule_name"],
); ?>&mask_action=mask"
                                    class="btn btn-sm"
                                    onclick="return confirm('Mask this rule? It will be excluded from billing.')">Mask</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="breadcrumb"><a href="?action=business_rules">Rules</a><span>/</span><?php echo h(
        $data["customer"]["name"],
    ); ?></div>
<?php
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
                <option value="all" <?php echo $data["filter"] === "all"
                    ? "selected"
                    : ""; ?>>All Changes</option>
                <option value="pricing" <?php echo $data["filter"] === "pricing"
                    ? "selected"
                    : ""; ?>>Pricing Tiers</option>
                <option value="settings" <?php echo $data["filter"] ===
                "settings"
                    ? "selected"
                    : ""; ?>>Customer Settings</option>
                <option value="escalators" <?php echo $data["filter"] ===
                "escalators"
                    ? "selected"
                    : ""; ?>>Escalators</option>
                <option value="rules" <?php echo $data["filter"] === "rules"
                    ? "selected"
                    : ""; ?>>Business Rules</option>
            </select>

            <label>Customer:</label>
            <select name="customer_id" class="form-control" style="width: auto;">
                <option value="">All Customers</option>
                <?php foreach ($data["customers"] as $customer): ?>
                    <option value="<?php echo $customer[
                        "id"
                    ]; ?>" <?php echo $data["customer_id"] == $customer["id"]
    ? "selected"
    : ""; ?>>
                        <?php echo h($customer["name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">Filter</button>
        </form>

        <?php if (empty($data["history"])): ?>
            <p class="text-muted">No history records found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Category</th>
                        <th>Effective Date</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data["history"] as $item): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?php echo h(
                            $item["date"],
                        ); ?></td>
                        <td>
                            <?php
                            $cat_colors = [
                                "pricing" => "#3498db",
                                "settings" => "#9b59b6",
                                "escalators" => "#e67e22",
                                "rules" => "#1abc9c",
                            ];
                            $cat_color = isset($cat_colors[$item["category"]])
                                ? $cat_colors[$item["category"]]
                                : "#999";
                            ?>
                            <span style="background: <?php echo $cat_color; ?>; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">
                                <?php echo h($item["category"]); ?>
                            </span>
                        </td>
                        <td style="white-space: nowrap;"><?php echo h(
                            $item["effective_date"],
                        ); ?></td>
                        <td><?php echo h($item["description"]); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $pag_params = ["filter" => $data["filter"]];
            if ($data["customer_id"]) {
                $pag_params["customer_id"] = $data["customer_id"];
            }
            render_pagination(
                $data["pagination"],
                "?action=history",
                $pag_params,
            );
            ?>
        <?php endif; ?>
    </div>
<?php render_footer();
} /**
 * Render billing calendar year view
 */
function render_calendar($data)
{
    render_header("Billing Calendar - " . $data["year"]); ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Billing Calendar <?php echo $data["year"]; ?></h2>
            <div>
                <a href="?action=calendar&year=<?php echo $data["year"] -
                    1; ?>" class="btn btn-sm">&larr; <?php echo $data["year"] - 1; ?></a>
                <a href="?action=calendar&year=<?php echo date(
                    "Y",
                ); ?>" class="btn btn-sm">Today</a>
                <a href="?action=calendar&year=<?php echo $data["year"] +
                    1; ?>" class="btn btn-sm"><?php echo $data["year"] + 1; ?> &rarr;</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <?php foreach ($data["months"] as $month): ?>
            <a href="?action=calendar_month&year=<?php echo $month[
                "year"
            ]; ?>&month=<?php echo $month["month"]; ?>"
                style="text-decoration: none; color: inherit;">
                <div style="border: 2px solid <?php if ($month["is_current"]) {
                    echo "#007bff";
                } elseif ($month["is_complete"]) {
                    echo "#28a745";
                } elseif ($month["is_past"]) {
                    echo "#dc3545";
                } else {
                    echo "#ddd";
                } ?>; border-radius: 8px; padding: 15px; text-align: center; background: <?php if (
    $month["is_complete"]
) {
    echo "#d4edda";
} elseif ($month["is_current"]) {
    echo "#e3f2fd";
} else {
    echo "#fff";
} ?>; transition: transform 0.1s; cursor: pointer;"
                    onmouseover="this.style.transform='scale(1.02)'"
                    onmouseout="this.style.transform='scale(1)'">

                    <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                        <?php echo $month["month_name"]; ?>
                    </div>

                    <div style="display: flex; justify-content: center; gap: 10px; font-size: 12px;">
                        <?php if ($month["event_count"] > 0): ?>
                            <span style="background: #e67e22; color: white; padding: 2px 6px; border-radius: 10px;">
                                <?php echo $month[
                                    "event_count"
                                ]; ?> event<?php echo $month["event_count"] > 1
     ? "s"
     : ""; ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($month["warning_count"] > 0): ?>
                            <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px;">
                                <?php echo $month[
                                    "warning_count"
                                ]; ?> warning<?php echo $month[
     "warning_count"
 ] > 1
     ? "s"
     : ""; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 8px; font-size: 11px; color: #666;">
                        <?php if ($month["is_complete"]): ?>
                            <span style="color: #28a745;">&#10003; Complete</span>
                        <?php elseif ($month["is_past"]): ?>
                            <span style="color: #dc3545;">&#9888; Incomplete</span>
                        <?php elseif ($month["is_current"]): ?>
                            <span style="color: #007bff;">&#9679; Current Month</span>
                        <?php else: ?>
                            <span>Upcoming</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; flex: 1; min-width: 150px; text-align: center; margin-bottom: 20px;">
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">Progress Summary</div>
            <div style="display: flex; justify-content: space-around; font-size: 14px;">
                <span>Completed: <strong style="color: #28a745;"><?php echo $data[
                    "completed_months"
                ]; ?></strong></span>
                <span>Escalators: <strong style="color: #e67e22;"><?php echo $data[
                    "total_escalators"
                ]; ?></strong></span>
                <span>Resets: <strong style="color: #3498db;"><?php echo $data[
                    "total_resets"
                ]; ?></strong></span>
            </div>
            <a href="?action=calendar_month&year=<?php echo $data[
                "next_incomplete"
            ][
                "year"
            ]; ?>&month=<?php echo $data["next_incomplete"]["month"]; ?>"
                class="btn btn-sm btn-success" style="margin-top: 10px;">
                &#9989; What's Next? (<?php echo date(
                    "F Y",
                    mktime(
                        0,
                        0,
                        0,
                        $data["next_incomplete"]["month"],
                        1,
                        $data["next_incomplete"]["year"],
                    ),
                ); ?>)
            </a>
        </div>

        <div class="card">
            <h3>Legend</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 13px;">
                <div><span style="display: inline-block; width: 20px; height: 20px; background: #d4edda; border: 2px solid #28a745; border-radius: 3px; vertical-align: middle; margin-right: 5px;"></span> Complete (monthly report ingested)</div>
                <div><span style="display: inline-block; width: 20px; height: 20px; background: #e3f2fd; border: 2px solid #007bff; border-radius: 3px; vertical-align: middle; margin-right: 5px;"></span> Current Month</div>
                <div><span style="display: inline-block; width: 20px; height: 20px; background: #fff; border: 2px solid #dc3545; border-radius: 3px; vertical-align: middle; margin-right: 5px;"></span> Past &amp; Incomplete</div>
                <div><span style="display: inline-block; width: 20px; height: 20px; background: #fff; border: 2px solid #ddd; border-radius: 3px; vertical-align: middle; margin-right: 5px;"></span> Upcoming</div>
            </div>
        </div>
    </div>
<?php render_footer();
}
/**
 * Render billing calendar month checklist view
 */ function render_calendar_month($data)
{
    render_header(
        $data["month_name"] . " " . $data["year"] . " - Billing Checklist",
    ); ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="?action=calendar&year=<?php echo $data[
                    "year"
                ]; ?>" style="color: #666; text-decoration: none;">&larr; Back to Calendar</a>
                <h2 style="margin: 10px 0 0 0;">
                    <?php echo $data[
                        "month_name"
                    ]; ?> <?php echo $data["year"]; ?> Checklist
                    <?php if ($data["is_complete"]): ?>
                        <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px;">COMPLETE</span>
                    <?php elseif ($data["is_current"]): ?>
                        <span style="background: #007bff; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px;">CURRENT</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div>
                <a href="?action=calendar_month&year=<?php echo $data["prev"][
                    "year"
                ]; ?>&month=<?php echo $data["prev"]["month"]; ?>" class="btn btn-sm">&larr; Prev</a>
                <a href="?action=calendar_month&year=<?php echo date(
                    "Y",
                ); ?>&month=<?php echo date("n"); ?>" class="btn btn-sm">Today</a>
                <a href="?action=calendar_month&year=<?php echo $data["next"][
                    "year"
                ]; ?>&month=<?php echo $data["next"]["month"]; ?>" class="btn btn-sm">Next &rarr;</a>
            </div>
        </div>

        <?php if ($data["total_items"] === 0): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 10px;">&#10003;</div>
                <div style="font-size: 18px;">No special items for this month</div>
                <div style="font-size: 13px; margin-top: 5px;">Standard billing should proceed normally</div>
            </div>
        <?php else: ?>

            <?php if (!empty($data["checklist"]["warnings"])): ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #721c24;">&#9888; Warnings (<?php echo count(
                    $data["checklist"]["warnings"],
                ); ?>)</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($data["checklist"]["warnings"] as $item): ?>
                        <li style="margin-bottom: 5px;">
                            <?php echo h($item["message"]); ?>
                            <?php if ($item["customer_id"]): ?>
                                <a href="?action=pricing_customer_edit&customer_id=<?php echo $item[
                                    "customer_id"
                                ]; ?>" style="font-size: 11px;">[view]</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($data["checklist"]["whats_excluded"])): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #856404;">&#10007; What's Excluded (<?php echo count(
                    $data["checklist"]["whats_excluded"],
                ); ?>)</h3>
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #856404;">These customers will NOT be billed this month:</p>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach (
                        $data["checklist"]["whats_excluded"]
                        as $item
                    ): ?>
                        <li style="margin-bottom: 5px;">
                            <?php echo h($item["message"]); ?>
                            <a href="?action=pricing_customer_edit&customer_id=<?php echo $item[
                                "customer_id"
                            ]; ?>" style="font-size: 11px;">[view]</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($data["checklist"]["whats_new"])): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #155724;">&#9733; What's New (<?php echo count(
                    $data["checklist"]["whats_new"],
                ); ?>)</h3>
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #155724;">New customers added since last month - verify they are set up correctly:</p>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($data["checklist"]["whats_new"] as $item): ?>
                        <li style="margin-bottom: 5px;">
                            <?php echo h($item["message"]); ?>
                            <a href="?action=pricing_customer_edit&customer_id=<?php echo $item[
                                "customer_id"
                            ]; ?>" style="font-size: 11px;">[view config]</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($data["checklist"]["whats_changing"])): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #856404;">&#8593; What's Changing (<?php echo count(
                    $data["checklist"]["whats_changing"],
                ); ?>)</h3>
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #856404;">Price changes taking effect this month:</p>
                <table style="width: 100%; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left;">
                            <th>Type</th>
                            <th>Description</th>
                            <th>Effective</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (
                            $data["checklist"]["whats_changing"]
                            as $item
                        ): ?>
                        <tr>
                            <td>
                                <span style="background: <?php echo $item[
                                    "type"
                                ] === "escalator"
                                    ? "#e67e22"
                                    : "#3498db"; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; text-transform: uppercase;">
                                    <?php echo h($item["type"]); ?>
                                </span>
                            </td>
                            <td><?php echo h($item["message"]); ?></td>
                            <td><?php echo h($item["effective_date"]); ?></td>
                            <td><a href="?action=pricing_customer_edit&customer_id=<?php echo $item[
                                "customer_id"
                            ]; ?>" style="font-size: 11px;">[view]</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($data["checklist"]["whats_different"])): ?>
            <div style="background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #383d41;">&#9881; Config Changes (<?php echo count(
                    $data["checklist"]["whats_different"],
                ); ?>)</h3>
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #383d41;">Configuration changes made since last month:</p>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                    <?php foreach (
                        array_slice(
                            $data["checklist"]["whats_different"],
                            0,
                            10,
                        )
                        as $item
                    ): ?>
                        <li style="margin-bottom: 3px;">
                            <?php echo h($item["message"]); ?>
                            <span style="color: #999; font-size: 11px;">(<?php echo h(
                                $item["date"],
                            ); ?>)</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (
                        count($data["checklist"]["whats_different"]) > 10
                    ): ?>
                        <li style="color: #666;">...and <?php echo count(
                            $data["checklist"]["whats_different"],
                        ) - 10; ?> more</li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <?php if ($data["mtd"]["report_count"] > 0): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Month-to-Date Summary</h3>
            <a href="?action=mtd_dashboard&year=<?php echo $data[
                "year"
            ]; ?>&month=<?php echo $data[
    "month"
]; ?>" class="btn btn-sm">View Full Dashboard &rarr;</a>
        </div>
        <p style="font-size: 12px; color: #666; margin: 10px 0 15px 0;">Based on <?php echo $data[
            "mtd"
        ]["report_count"]; ?> daily report(s), latest: <?php echo h(
     $data["mtd"]["latest_date"],
 ); ?></p>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; flex: 1; min-width: 120px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo number_format(
                    $data["mtd"]["customer_count"],
                ); ?></div>
                <div style="color: #666; font-size: 12px;">Customers Billed</div>
            </div>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; flex: 1; min-width: 120px;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo number_format(
                    $data["mtd"]["total_transactions"],
                ); ?></div>
                <div style="color: #666; font-size: 12px;">Transactions</div>
            </div>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; flex: 1; min-width: 120px;">
                <div style="font-size: 24px; font-weight: bold;">$<?php echo number_format(
                    $data["mtd"]["total_revenue"],
                    2,
                ); ?></div>
                <div style="color: #666; font-size: 12px;">Revenue MTD</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>Final Output</h3>
        <p>When ready, generate the monthly billing report:</p>
        <a href="?action=generation" class="btn btn-success">Go to Report Generation</a>
    </div>
<?php render_footer();
} /**
 * Render Month-to-Date Dashboard
 */
function render_mtd_dashboard($data)
{
    render_header(
        "MTD Dashboard - " . $data["month_name"] . " " . $data["year"],
    ); ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="?action=calendar&year=<?php echo $data[
                    "year"
                ]; ?>" style="color: #666; text-decoration: none;">&larr; Back to Calendar</a>
                <h2 style="margin: 10px 0 0 0;">
                    <?php echo $data[
                        "month_name"
                    ]; ?> <?php echo $data["year"]; ?> - Month to Date
                    <?php if ($data["is_current"]): ?>
                        <span style="background: #007bff; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px;">LIVE</span>
                    <?php endif; ?>
                </h2>
                <?php if ($data["through_day"] > 0): ?>
                    <p style="color: #666; font-size: 13px; margin-top: 5px;">Data through day <?php echo $data[
                        "through_day"
                    ]; ?> (<?php echo $data["mtd"][
     "report_count"
 ]; ?> daily reports)</p>
                <?php endif; ?>
            </div>
            <div>
                <a href="?action=mtd_dashboard&year=<?php echo $data["prev"][
                    "year"
                ]; ?>&month=<?php echo $data["prev"]["month"]; ?>" class="btn btn-sm">&larr; Prev</a>
                <a href="?action=mtd_dashboard&year=<?php echo date(
                    "Y",
                ); ?>&month=<?php echo date("n"); ?>" class="btn btn-sm">Today</a>
                <a href="?action=mtd_dashboard&year=<?php echo $data["next"][
                    "year"
                ]; ?>&month=<?php echo $data["next"]["month"]; ?>" class="btn btn-sm">Next &rarr;</a>
            </div>
        </div>

        <?php if ($data["mtd"]["report_count"] == 0): ?>
            <div style="text-align: center; padding: 60px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 15px;">&#128202;</div>
                <div style="font-size: 18px;">No daily reports for this month yet</div>
                <div style="font-size: 13px; margin-top: 5px;">Daily reports are automatically ingested to populate this dashboard</div>
                <div style="margin-top: 20px;">
                    <a href="?action=ingestion" class="btn">Go to Ingestion</a>
                </div>
            </div>
        <?php else: ?>

            <!-- Key Metrics -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;">$<?php echo number_format(
                        $data["mtd"]["total_revenue"],
                        2,
                    ); ?></div>
                    <div style="color: #666; font-size: 12px; margin-top: 5px;">Revenue MTD</div>
                    <?php if ($data["prev_mtd"]["total_revenue"] > 0): ?>
                        <div style="font-size: 12px; margin-top: 8px; color: <?php echo $data[
                            "revenue_change"
                        ] >= 0
                            ? "#28a745"
                            : "#dc3545"; ?>;">
                            <?php echo $data["revenue_change"] >= 0
                                ? "&#9650;"
                                : "&#9660;"; ?>
                            <?php echo abs(
                                round($data["revenue_change"], 1),
                            ); ?>% vs last month
                        </div>
                    <?php endif; ?>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #3498db;"><?php echo number_format(
                        $data["mtd"]["total_transactions"],
                    ); ?></div>
                    <div style="color: #666; font-size: 12px; margin-top: 5px;">Transactions MTD</div>
                    <?php if ($data["prev_mtd"]["total_transactions"] > 0): ?>
                        <div style="font-size: 12px; margin-top: 8px; color: <?php echo $data[
                            "trans_change"
                        ] >= 0
                            ? "#28a745"
                            : "#dc3545"; ?>;">
                            <?php echo $data["trans_change"] >= 0
                                ? "&#9650;"
                                : "&#9660;"; ?>
                            <?php echo abs(
                                round($data["trans_change"], 1),
                            ); ?>% vs last month
                        </div>
                    <?php endif; ?>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #9b59b6;"><?php echo number_format(
                        $data["mtd"]["customer_count"],
                    ); ?></div>
                    <div style="color: #666; font-size: 12px; margin-top: 5px;">Active Customers</div>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <?php
                    $avg_per_day =
                        $data["through_day"] > 0
                            ? $data["mtd"]["total_revenue"] /
                                $data["through_day"]
                            : 0;
                    $days_in_month = date(
                        "t",
                        mktime(0, 0, 0, $data["month"], 1, $data["year"]),
                    );
                    $projected = $avg_per_day * $days_in_month;
                    ?>
                    <div style="font-size: 28px; font-weight: bold; color: #e67e22;">$<?php echo number_format(
                        $projected,
                        2,
                    ); ?></div>
                    <div style="color: #666; font-size: 12px; margin-top: 5px;">Projected Month End</div>
                    <div style="font-size: 11px; color: #999; margin-top: 5px;">Based on $<?php echo number_format(
                        $avg_per_day,
                        2,
                    ); ?>/day avg</div>
                </div>
            </div>

            <!-- Daily Breakdown Chart (Text-based) -->
            <h3 style="margin-bottom: 15px;">Daily Revenue</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px; overflow-x: auto;">
                <?php
                $max_revenue = 0;
                foreach ($data["cumulative"] as $day) {
                    if ($day["daily_revenue"] > $max_revenue) {
                        $max_revenue = $day["daily_revenue"];
                    }
                }
                ?>
                <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <th style="text-align: left; padding: 5px; width: 80px;">Date</th>
                            <th style="text-align: left; padding: 5px;">Revenue</th>
                            <th style="text-align: right; padding: 5px; width: 100px;">Amount</th>
                            <th style="text-align: right; padding: 5px; width: 100px;">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data["cumulative"] as $day): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 5px;"><?php echo substr(
                                $day["date"],
                                5,
                            ); ?></td>
                            <td style="padding: 5px;">
                                <?php $bar_width =
                                    $max_revenue > 0
                                        ? ($day["daily_revenue"] /
                                                $max_revenue) *
                                            100
                                        : 0; ?>
                                <div style="background: #28a745; height: 16px; width: <?php echo $bar_width; ?>%; min-width: 2px; border-radius: 2px;"></div>
                            </td>
                            <td style="text-align: right; padding: 5px;">$<?php echo number_format(
                                $day["daily_revenue"],
                                2,
                            ); ?></td>
                            <td style="text-align: right; padding: 5px; color: #666;">$<?php echo number_format(
                                $day["cumulative_revenue"],
                                2,
                            ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Service Breakdown -->
            <?php if (!empty($data["services"])): ?>
            <h3 style="margin-bottom: 15px;">Revenue by Service</h3>
            <div style="margin-bottom: 30px;">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th class="text-right">Transactions</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data["services"] as $svc): ?>
                        <tr>
                            <td><?php echo h(
                                $svc["service_name"] ?: "(Unknown)",
                            ); ?></td>
                            <td class="text-right"><?php echo number_format(
                                $svc["transactions"],
                            ); ?></td>
                            <td class="text-right">$<?php echo number_format(
                                $svc["revenue"],
                                2,
                            ); ?></td>
                            <td class="text-right">
                                <?php
                                $pct =
                                    $data["mtd"]["total_revenue"] > 0
                                        ? ($svc["revenue"] /
                                                $data["mtd"]["total_revenue"]) *
                                            100
                                        : 0;
                                echo number_format($pct, 1) . "%";
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Top Customers -->
            <?php if (!empty($data["customers"])): ?>
            <h3 style="margin-bottom: 15px;">Top Customers</h3>
            <div style="margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th class="text-right">Transactions</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (
                            array_slice($data["customers"], 0, 15)
                            as $cust
                        ): ?>
                        <tr>
                            <td>
                                <?php if ($cust["customer_id"]): ?>
                                    <a href="?action=pricing_customer_edit&customer_id=<?php echo $cust[
                                        "customer_id"
                                    ]; ?>">
                                        <?php echo h(
                                            $cust["customer_name"] ?:
                                            "Customer #" . $cust["customer_id"],
                                        ); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo h(
                                        $cust["customer_name"] ?: "(Unknown)",
                                    ); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?php echo number_format(
                                $cust["transactions"],
                            ); ?></td>
                            <td class="text-right">$<?php echo number_format(
                                $cust["revenue"],
                                2,
                            ); ?></td>
                            <td class="text-right">
                                <?php
                                $pct =
                                    $data["mtd"]["total_revenue"] > 0
                                        ? ($cust["revenue"] /
                                                $data["mtd"]["total_revenue"]) *
                                            100
                                        : 0;
                                echo number_format($pct, 1) . "%";
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($data["customers"]) > 15): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666; font-style: italic;">
                                ...and <?php echo count($data["customers"]) -
                                    15; ?> more customers
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <div style="display: flex; gap: 15px;">
        <a href="?action=calendar_month&year=<?php echo $data[
            "year"
        ]; ?>&month=<?php echo $data["month"]; ?>" class="btn">View Month Checklist</a>
        <a href="?action=ingestion" class="btn">Manage Reports</a>
    </div>
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
// END PHASE 4
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
