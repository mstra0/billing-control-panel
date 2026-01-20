<?php
// ============================================================
// PRICE CALCULATION ENGINE
// Calculates expected prices with full audit trail
// For billing reconciliation and variance analysis
// ============================================================

require_once __DIR__ . "/data.php";

/**
 * Calculate the expected price for a billing line item
 * Returns full audit trail documenting every calculation step
 *
 * @param string $as_of_date    The report date (determines which pricing was active)
 * @param int    $customer_id   Customer ID
 * @param string $efx_code      EFX code from the billing line (maps to service)
 * @param int    $count         Transaction count (trusted input from their system)
 * @return array Audit trail with all calculation steps and final expected price
 */
function calculate_price_audit($as_of_date, $customer_id, $efx_code, $count)
{
    $audit = [
        "as_of_date" => $as_of_date,
        "customer_id" => $customer_id,
        "efx_code" => $efx_code,
        "count" => (int) $count,
        "steps" => [],
        "errors" => [],
        "expected_unit_price" => null,
        "expected_revenue" => null,
    ];

    // ----------------------------------------------------------------
    // STEP 1: Get customer info
    // ----------------------------------------------------------------
    $customer = sqlite_query(
        "SELECT c.*, dg.name as group_name
         FROM customers c
         LEFT JOIN discount_groups dg ON c.discount_group_id = dg.id
         WHERE c.id = ?",
        [$customer_id]
    );

    if (empty($customer)) {
        $audit["errors"][] = "Customer ID $customer_id not found";
        $audit["steps"][] = [
            "step" => 1,
            "name" => "customer_lookup",
            "description" => "Find customer",
            "success" => false,
            "error" => "Customer not found",
        ];
        return $audit;
    }

    $customer = $customer[0];
    $audit["customer_name"] = $customer["name"];
    $audit["steps"][] = [
        "step" => 1,
        "name" => "customer_lookup",
        "description" => "Find customer",
        "success" => true,
        "result" => [
            "customer_id" => $customer_id,
            "customer_name" => $customer["name"],
            "group_id" => $customer["discount_group_id"],
            "group_name" => $customer["group_name"],
            "status" => $customer["status"],
        ],
    ];

    // ----------------------------------------------------------------
    // STEP 2: Map EFX code to service
    // ----------------------------------------------------------------
    $service = get_service_by_efx_code($efx_code);

    if (!$service) {
        $audit["errors"][] = "EFX code '$efx_code' not mapped to any service";
        $audit["steps"][] = [
            "step" => 2,
            "name" => "service_mapping",
            "description" => "Map EFX code to service",
            "success" => false,
            "error" => "EFX code not found in transaction_types table",
            "efx_code" => $efx_code,
        ];
        return $audit;
    }

    $audit["service_id"] = $service["id"];
    $audit["service_name"] = $service["name"];
    $audit["steps"][] = [
        "step" => 2,
        "name" => "service_mapping",
        "description" => "Map EFX code to service",
        "success" => true,
        "efx_code" => $efx_code,
        "result" => [
            "service_id" => $service["id"],
            "service_name" => $service["name"],
        ],
    ];

    // ----------------------------------------------------------------
    // STEP 3: Get effective tiers (with inheritance resolution)
    // ----------------------------------------------------------------
    $tier_result = get_effective_tiers_as_of($customer_id, $service["id"], $as_of_date);

    if (empty($tier_result["tiers"])) {
        $audit["errors"][] = "No pricing tiers found for service '{$service["name"]}' as of $as_of_date";
        $audit["steps"][] = [
            "step" => 3,
            "name" => "tier_resolution",
            "description" => "Resolve pricing tiers (inheritance: customer -> group -> default)",
            "success" => false,
            "error" => "No tiers found at any level",
            "inheritance_chain" => $tier_result["inheritance_chain"],
        ];
        return $audit;
    }

    $audit["steps"][] = [
        "step" => 3,
        "name" => "tier_resolution",
        "description" => "Resolve pricing tiers (inheritance: customer -> group -> default)",
        "success" => true,
        "source" => $tier_result["source"],
        "effective_date" => $tier_result["effective_date"],
        "inheritance_chain" => $tier_result["inheritance_chain"],
        "tiers" => array_map(function ($t) {
            return [
                "volume_start" => (int) $t["volume_start"],
                "volume_end" => $t["volume_end"] ? (int) $t["volume_end"] : null,
                "price" => (float) $t["price_per_inquiry"],
            ];
        }, $tier_result["tiers"]),
    ];

    // ----------------------------------------------------------------
    // STEP 4: Find matching tier for volume
    // ----------------------------------------------------------------
    $matched_tier = null;
    $volume = (int) $count;

    foreach ($tier_result["tiers"] as $tier) {
        $start = (int) $tier["volume_start"];
        $end = $tier["volume_end"] ? (int) $tier["volume_end"] : PHP_INT_MAX;

        if ($volume >= $start && $volume <= $end) {
            $matched_tier = $tier;
            break;
        }
    }

    if (!$matched_tier) {
        // Try to find the highest tier if volume exceeds all defined tiers
        foreach ($tier_result["tiers"] as $tier) {
            if ($tier["volume_end"] === null || $tier["volume_end"] === "") {
                $matched_tier = $tier;
                break;
            }
        }
    }

    if (!$matched_tier) {
        $audit["errors"][] = "No tier matches volume of $volume";
        $audit["steps"][] = [
            "step" => 4,
            "name" => "tier_matching",
            "description" => "Find tier for volume",
            "success" => false,
            "error" => "Volume $volume doesn't match any tier range",
            "volume" => $volume,
        ];
        return $audit;
    }

    $base_price = (float) $matched_tier["price_per_inquiry"];
    $audit["steps"][] = [
        "step" => 4,
        "name" => "tier_matching",
        "description" => "Find tier for volume",
        "success" => true,
        "volume" => $volume,
        "matched_tier" => [
            "volume_start" => (int) $matched_tier["volume_start"],
            "volume_end" => $matched_tier["volume_end"] ? (int) $matched_tier["volume_end"] : "unlimited",
            "price" => $base_price,
        ],
        "base_price" => $base_price,
    ];

    // ----------------------------------------------------------------
    // STEP 5: Get escalator info and calculate adjusted price
    // ----------------------------------------------------------------
    $escalator_info = get_escalator_year_on_date($customer_id, $as_of_date);

    $escalator_pct = 0;
    $fixed_adj = 0;
    $adj_price = $base_price;

    if ($escalator_info["has_escalator"] && $escalator_info["current_year"] > 1) {
        $escalator_pct = (float) $escalator_info["escalator_percentage"];
        $fixed_adj = (float) $escalator_info["fixed_adjustment"];

        // Apply escalator: adj_price = base_price * (1 + pct/100) + fixed
        $adj_price = $base_price * (1 + $escalator_pct / 100) + $fixed_adj;
    }

    $audit["steps"][] = [
        "step" => 5,
        "name" => "escalator_calculation",
        "description" => "Apply escalator adjustments",
        "success" => true,
        "has_escalator" => $escalator_info["has_escalator"],
        "contract_start" => $escalator_info["contract_start"],
        "current_year" => $escalator_info["current_year"],
        "delay_months" => $escalator_info["delay_months"],
        "escalator_percentage" => $escalator_pct,
        "fixed_adjustment" => $fixed_adj,
        "calculation" => $escalator_info["has_escalator"] && $escalator_info["current_year"] > 1
            ? sprintf(
                "\$%.4f × (1 + %.2f%%) + \$%.4f = \$%.4f",
                $base_price,
                $escalator_pct,
                $fixed_adj,
                $adj_price
            )
            : "No escalator applied (Year 1 or no escalator configured)",
        "base_price" => $base_price,
        "adjusted_price" => $adj_price,
    ];

    // ----------------------------------------------------------------
    // STEP 6: Calculate expected revenue
    // ----------------------------------------------------------------
    $expected_revenue = $adj_price * $count;

    $audit["steps"][] = [
        "step" => 6,
        "name" => "revenue_calculation",
        "description" => "Calculate expected revenue",
        "success" => true,
        "unit_price" => $adj_price,
        "count" => $count,
        "calculation" => sprintf("\$%.4f × %d = \$%.2f", $adj_price, $count, $expected_revenue),
        "expected_revenue" => $expected_revenue,
    ];

    // ----------------------------------------------------------------
    // Final results
    // ----------------------------------------------------------------
    $audit["expected_unit_price"] = $adj_price;
    $audit["expected_revenue"] = $expected_revenue;
    $audit["base_price"] = $base_price;
    $audit["escalator_applied"] = $escalator_info["has_escalator"] && $escalator_info["current_year"] > 1;

    return $audit;
}

/**
 * Audit a specific billing report line
 * Compares expected price (calculated) vs actual price (from CSV)
 *
 * @param int $line_id The billing_report_lines.id to audit
 * @return array Full audit with comparison and variance analysis
 */
function audit_billing_line($line_id)
{
    // Get the line item
    $line = sqlite_query(
        "SELECT brl.*, br.report_date, br.report_type
         FROM billing_report_lines brl
         JOIN billing_reports br ON brl.report_id = br.id
         WHERE brl.id = ?",
        [$line_id]
    );

    if (empty($line)) {
        return [
            "success" => false,
            "error" => "Billing line ID $line_id not found",
        ];
    }

    $line = $line[0];

    // Calculate expected price
    $audit = calculate_price_audit(
        $line["report_date"],
        $line["customer_id"],
        $line["efx_code"],
        $line["count"]
    );

    // Add actual values from CSV
    $audit["actual_unit_price"] = (float) $line["actual_unit_cost"];
    $audit["actual_revenue"] = (float) $line["revenue"];

    // Add line metadata
    $audit["line_id"] = $line_id;
    $audit["report_id"] = $line["report_id"];
    $audit["report_date"] = $line["report_date"];
    $audit["report_type"] = $line["report_type"];
    $audit["hit_code"] = $line["hit_code"];
    $audit["tran_displayname"] = $line["tran_displayname"];
    $audit["billing_id"] = $line["billing_id"];

    // Calculate variance
    if ($audit["expected_unit_price"] !== null) {
        $audit["variance"] = [
            "unit_price" => round($audit["actual_unit_price"] - $audit["expected_unit_price"], 6),
            "revenue" => round($audit["actual_revenue"] - $audit["expected_revenue"], 2),
            "unit_price_pct" => $audit["expected_unit_price"] > 0
                ? round((($audit["actual_unit_price"] - $audit["expected_unit_price"]) / $audit["expected_unit_price"]) * 100, 4)
                : null,
        ];

        // Determine if variance is within acceptable threshold (e.g., rounding errors)
        $threshold = 0.0001; // $0.0001 tolerance for unit price
        $audit["variance"]["is_match"] = abs($audit["variance"]["unit_price"]) <= $threshold;
        $audit["variance"]["status"] = $audit["variance"]["is_match"] ? "MATCH" : "VARIANCE";
    } else {
        $audit["variance"] = [
            "unit_price" => null,
            "revenue" => null,
            "unit_price_pct" => null,
            "is_match" => false,
            "status" => "ERROR",
        ];
    }

    return $audit;
}

/**
 * Audit all lines in a billing report
 * Returns summary with variance statistics
 *
 * @param int $report_id The billing_reports.id to audit
 * @return array Summary with per-line audits and statistics
 */
function audit_billing_report($report_id)
{
    $report = sqlite_query("SELECT * FROM billing_reports WHERE id = ?", [$report_id]);

    if (empty($report)) {
        return [
            "success" => false,
            "error" => "Report ID $report_id not found",
        ];
    }

    $report = $report[0];
    $lines = sqlite_query(
        "SELECT id FROM billing_report_lines WHERE report_id = ? ORDER BY id",
        [$report_id]
    );

    $results = [
        "report_id" => $report_id,
        "report_date" => $report["report_date"],
        "report_type" => $report["report_type"],
        "total_lines" => count($lines),
        "matches" => 0,
        "variances" => 0,
        "errors" => 0,
        "total_expected_revenue" => 0,
        "total_actual_revenue" => 0,
        "total_variance" => 0,
        "lines" => [],
    ];

    foreach ($lines as $line) {
        $audit = audit_billing_line($line["id"]);
        $results["lines"][] = $audit;

        if (!empty($audit["errors"])) {
            $results["errors"]++;
        } elseif ($audit["variance"]["is_match"]) {
            $results["matches"]++;
        } else {
            $results["variances"]++;
        }

        if ($audit["expected_revenue"] !== null) {
            $results["total_expected_revenue"] += $audit["expected_revenue"];
        }
        $results["total_actual_revenue"] += $audit["actual_revenue"];
    }

    $results["total_variance"] = $results["total_actual_revenue"] - $results["total_expected_revenue"];

    return $results;
}

/**
 * Format an audit trail as LaTeX for display
 * Returns string suitable for MathJax rendering
 *
 * @param array $audit The audit trail from calculate_price_audit or audit_billing_line
 * @return string LaTeX formatted calculation
 */
function format_audit_as_latex($audit)
{
    $latex = "";

    // Find key values from steps
    $base_price = null;
    $matched_tier = null;
    $escalator_pct = 0;
    $fixed_adj = 0;
    $adj_price = null;
    $count = $audit["count"];

    foreach ($audit["steps"] as $step) {
        if ($step["name"] === "tier_matching" && $step["success"]) {
            $base_price = $step["base_price"];
            $matched_tier = $step["matched_tier"];
        }
        if ($step["name"] === "escalator_calculation" && $step["success"]) {
            $escalator_pct = $step["escalator_percentage"];
            $fixed_adj = $step["fixed_adjustment"];
            $adj_price = $step["adjusted_price"];
        }
    }

    if ($base_price === null) {
        return "\\text{Error: Could not determine base price}";
    }

    // Build LaTeX
    $latex .= "\\begin{aligned}\n";

    // Tier selection
    $latex .= sprintf(
        "\\text{Base Price (Tier %s-%s)} &= \\$%.4f \\\\\n",
        number_format($matched_tier["volume_start"]),
        is_numeric($matched_tier["volume_end"]) ? number_format($matched_tier["volume_end"]) : "\\infty",
        $base_price
    );

    // Escalator calculation
    if ($escalator_pct > 0 || $fixed_adj != 0) {
        $latex .= "\\\\\n";
        $latex .= "\\text{Escalator Adjustment:} \\\\\n";

        if ($escalator_pct > 0 && $fixed_adj != 0) {
            $latex .= sprintf(
                "\\text{Adjusted Price} &= \\$%.4f \\times (1 + %.2f\\%%) + \\$%.4f \\\\\n",
                $base_price,
                $escalator_pct,
                $fixed_adj
            );
            $latex .= sprintf(
                "&= \\$%.4f \\times %.4f + \\$%.4f \\\\\n",
                $base_price,
                1 + $escalator_pct / 100,
                $fixed_adj
            );
        } elseif ($escalator_pct > 0) {
            $latex .= sprintf(
                "\\text{Adjusted Price} &= \\$%.4f \\times (1 + %.2f\\%%) \\\\\n",
                $base_price,
                $escalator_pct
            );
            $latex .= sprintf(
                "&= \\$%.4f \\times %.4f \\\\\n",
                $base_price,
                1 + $escalator_pct / 100
            );
        } else {
            $latex .= sprintf(
                "\\text{Adjusted Price} &= \\$%.4f + \\$%.4f \\\\\n",
                $base_price,
                $fixed_adj
            );
        }

        $latex .= sprintf("&= \\$%.4f \\\\\n", $adj_price);
    } else {
        $latex .= sprintf("\\text{Adjusted Price} &= \\$%.4f \\text{ (no escalator)} \\\\\n", $base_price);
        $adj_price = $base_price;
    }

    // Revenue calculation
    $latex .= "\\\\\n";
    $latex .= sprintf(
        "\\text{Expected Revenue} &= \\$%.4f \\times %s \\\\\n",
        $adj_price,
        number_format($count)
    );
    $latex .= sprintf("&= \\boxed{\\$%.2f}\n", $adj_price * $count);

    $latex .= "\\end{aligned}";

    return $latex;
}
