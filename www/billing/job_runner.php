<?php
/**
 * Background Job Runner (CLI only)
 *
 * Launched by action_job_start() to run long operations in the background.
 * Reads job parameters from a JSON file, executes the operation, and
 * updates progress via job_progress() / job_complete() / job_fail().
 *
 * Usage:
 *   php5.6 job_runner.php --job-id=job_xxx --type=seed
 *   php5.6 job_runner.php --job-id=job_xxx --type=sync
 *   php5.6 job_runner.php --job-id=job_xxx --type=audit
 */

if (php_sapi_name() !== "cli") {
    die("CLI only");
}

$opts = getopt("", ["job-id:", "type:"]);
if (empty($opts["job-id"]) || empty($opts["type"])) {
    die("Usage: php job_runner.php --job-id=ID --type=seed|sync|audit\n");
}

$job_id = $opts["job-id"];
$type = $opts["type"];

// Bootstrap the app without routing
$_SERVER["REQUEST_METHOD"] = "GET";
define("BACKGROUND_JOB", true);
define("BACKGROUND_JOB_ID", $job_id);
require_once __DIR__ . "/control_panel.php";

$job = job_read($job_id);
if (!$job) {
    die("Job not found: $job_id\n");
}
$params = $job["params"];

try {
    switch ($type) {
        case "seed":
            require_once __DIR__ . "/admin_seed.php";
            $config = isset($GLOBALS["SEED_CONFIG"])
                ? $GLOBALS["SEED_CONFIG"]
                : $SEED_CONFIG;
            foreach ($params as $k => $v) {
                $config[$k] = $v;
            }
            $clear_first = !empty($params["clear_first"]);
            $result = run_seed($config, $clear_first);
            if ($result["success"]) {
                $msg = sprintf(
                    "Database reseeded: %d reports, %d lines (%.1f%% exact, %.1f%% small var, %.1f%% large var)",
                    $result["reports"],
                    $result["lines"],
                    ($result["matches"] / max(1, $result["lines"])) * 100,
                    ($result["small_variances"] / max(1, $result["lines"])) *
                        100,
                    ($result["large_variances"] / max(1, $result["lines"])) *
                        100
                );
            } else {
                $msg = "Seeding completed with warnings";
            }
            job_complete($job_id, $msg);
            break;

        case "sync":
            $entity = isset($params["entity"]) ? $params["entity"] : "";
            $sync_functions = [
                "customers" => "sync_customers_from_remote",
                "services" => "sync_services_from_remote",
                "discount_groups" => "sync_discount_groups_from_remote",
                "lms" => "sync_lms_from_remote",
                "cogs" => "sync_cogs_from_remote",
                "business_rules" => "sync_business_rules_from_remote",
            ];
            if ($entity !== "all" && !isset($sync_functions[$entity])) {
                job_fail($job_id, "Unknown sync entity: $entity");
                break;
            }

            if ($entity === "all") {
                // Run each sync individually with per-entity progress
                $total = count($sync_functions);
                $step = 0;
                $total_synced = 0;
                foreach ($sync_functions as $name => $func) {
                    $step++;
                    $label = ucfirst(str_replace("_", " ", $name));
                    job_progress(
                        $job_id,
                        $label,
                        $step,
                        $total,
                        "Syncing $label..."
                    );
                    $r = $func();
                    if (is_array($r) && isset($r["synced"])) {
                        $total_synced += $r["synced"];
                    } elseif (is_int($r)) {
                        $total_synced += $r;
                    }
                }
                job_complete(
                    $job_id,
                    "Synced all entities: $total_synced total records"
                );
            } else {
                $func = $sync_functions[$entity];
                $label = ucfirst(str_replace("_", " ", $entity));
                job_progress($job_id, $label, 0, 1, "Syncing $label...");
                $result = $func();
                if (is_array($result)) {
                    $msg = isset($result["message"])
                        ? $result["message"]
                        : "Synced " .
                            $result["synced"] .
                            " of " .
                            $result["total"] .
                            " records";
                } else {
                    $msg = "Synced $result records";
                }
                job_complete(
                    $job_id,
                    ucfirst(str_replace("_", " ", $entity)) . ": " . $msg
                );
            }
            break;

        case "audit":
            require_once __DIR__ . "/calculator.php";
            $report_id = isset($params["report_id"])
                ? (int) $params["report_id"]
                : 0;
            if (!$report_id) {
                job_fail($job_id, "No report ID specified");
                break;
            }

            // Check report exists
            $report = sqlite_query(
                "SELECT * FROM billing_reports WHERE id = ?",
                [$report_id]
            );
            if (empty($report)) {
                job_fail($job_id, "Report ID $report_id not found");
                break;
            }
            $report = $report[0];

            // Get all line IDs for per-line progress
            $line_rows = sqlite_query(
                "SELECT id FROM billing_report_lines WHERE report_id = ? ORDER BY id",
                [$report_id]
            );
            $total_lines = count($line_rows);
            job_progress(
                $job_id,
                "Auditing",
                0,
                $total_lines,
                "Starting audit of report $report_id ($total_lines lines)"
            );

            $matches = 0;
            $variances = 0;
            $errors = 0;
            $total_expected = 0;
            $total_actual = 0;

            foreach ($line_rows as $i => $line) {
                $line_audit = audit_billing_line($line["id"]);

                if (!empty($line_audit["errors"])) {
                    $errors++;
                } elseif ($line_audit["variance"]["is_match"]) {
                    $matches++;
                } else {
                    $variances++;
                }

                if ($line_audit["expected_revenue"] !== null) {
                    $total_expected += $line_audit["expected_revenue"];
                }
                $total_actual += $line_audit["actual_revenue"];

                // Update progress every 10 lines to avoid excessive file writes
                if (($i + 1) % 10 === 0 || $i + 1 === $total_lines) {
                    job_progress(
                        $job_id,
                        "Auditing",
                        $i + 1,
                        $total_lines,
                        "Audited " . ($i + 1) . " of $total_lines lines"
                    );
                }
            }

            $msg = sprintf(
                "Audit complete: %d lines, %d matches, %d variances, %d errors",
                $total_lines,
                $matches,
                $variances,
                $errors
            );
            job_complete($job_id, $msg);
            break;

        default:
            job_fail($job_id, "Unknown job type: $type");
            break;
    }
} catch (Exception $e) {
    job_fail($job_id, $e->getMessage());
}
