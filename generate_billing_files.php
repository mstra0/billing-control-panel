<?php
/**
 * Generate realistic billing report files for ingestion testing
 *
 * Creates ~60 daily billing files across 2 months (Dec 2025 + Jan 2026)
 * Each file has 1000+ lines using valid customers/services from DB
 */

define("MOCK_MODE", true);

// Minimal DB bootstrap
$_sqlite_db = null;
function sqlite_db()
{
    global $_sqlite_db;
    if ($_sqlite_db === null) {
        $_sqlite_db = new SQLite3(__DIR__ . "/test_shared/control_panel.db");
    }
    return $_sqlite_db;
}

function sqlite_query($sql, $params = [])
{
    $db = sqlite_db();
    $stmt = $db->prepare($sql);
    $i = 1;
    foreach ($params as $param) {
        $stmt->bindValue($i++, $param);
    }
    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

// Get real data from database
echo "Loading data from database...\n";

$customers = sqlite_query(
    "SELECT id, name FROM customers WHERE status='active' ORDER BY id"
);
echo "Found " . count($customers) . " active customers\n";

$transaction_types = sqlite_query(
    "SELECT efx_code, display_name, service_id FROM transaction_types ORDER BY efx_code"
);
echo "Found " . count($transaction_types) . " transaction types\n";

if (empty($customers) || empty($transaction_types)) {
    die("ERROR: No customers or transaction types in database!\n");
}

// Hit codes - weighted distribution
$hit_codes = [
    "H" => 70, // Hit - 70%
    "M" => 20, // Miss - 20%
    "E" => 5, // Error - 5%
    "N" => 5, // Null - 5%
];

function weighted_random($weights)
{
    $total = array_sum($weights);
    $rand = mt_rand(1, $total);
    $cumulative = 0;
    foreach ($weights as $key => $weight) {
        $cumulative += $weight;
        if ($rand <= $cumulative) {
            return $key;
        }
    }
    return array_keys($weights)[0];
}

// Price ranges by transaction type suffix
function get_unit_cost($efx_code)
{
    // Base costs vary by type
    $base = 0.1;
    if (strpos($efx_code, "IDS") === 0) {
        $base = 0.35;
    }
    // Identity Standard
    elseif (strpos($efx_code, "VFT") === 0) {
        $base = 0.45;
    }
    // Verification Total
    elseif (strpos($efx_code, "SSE") === 0) {
        $base = 0.25;
    }
    // Social Security Express
    elseif (strpos($efx_code, "SSM") === 0) {
        $base = 0.3;
    }
    // Social Security Monitor
    elseif (strpos($efx_code, "ACS") === 0) {
        $base = 0.2;
    }
    // Account Standard
    elseif (strpos($efx_code, "FNL") === 0) {
        $base = 0.28;
    }
    // Financial Lookup
    elseif (strpos($efx_code, "VFN") === 0) {
        $base = 0.4;
    }
    // Verification Network
    elseif (strpos($efx_code, "FNC") === 0) {
        $base = 0.32;
    }
    // Financial Core
    elseif (strpos($efx_code, "LNE") === 0) {
        $base = 0.38;
    }
    // Lending Extended
    elseif (strpos($efx_code, "BKB") === 0) {
        $base = 0.15;
    }
    // Bank Basic
    elseif (strpos($efx_code, "ACB") === 0) {
        $base = 0.12;
    } // Account Basic

    // Add some variance (+/- 15%)
    $variance = $base * (mt_rand(-15, 15) / 100);
    return round($base + $variance, 4);
}

// Generate a single day's billing file
function generate_daily_file(
    $year,
    $month,
    $day,
    $customers,
    $transaction_types,
    $hit_codes
) {
    $lines = [];
    $billing_id_counter = 1;

    // Each customer gets some transactions
    foreach ($customers as $customer) {
        // Random number of transaction types this customer uses (12-20)
        $num_types = mt_rand(12, min(20, count($transaction_types)));
        $selected_types = array_rand($transaction_types, $num_types);
        if (!is_array($selected_types)) {
            $selected_types = [$selected_types];
        }

        foreach ($selected_types as $type_idx) {
            $tx_type = $transaction_types[$type_idx];

            // Transaction count varies by customer size and randomness
            // Base: 50-500 transactions, with some outliers
            $base_count = mt_rand(50, 500);

            // Some customers are high-volume (10% chance)
            if (mt_rand(1, 10) === 1) {
                $base_count = mt_rand(1000, 5000);
            }

            // Daily variance (+/- 30%)
            $variance = $base_count * (mt_rand(-30, 30) / 100);
            $count = max(10, (int) ($base_count + $variance));

            $hit_code = weighted_random($hit_codes);
            $unit_cost = get_unit_cost($tx_type["efx_code"]);
            $revenue = round($count * $unit_cost, 2);

            $billing_id = sprintf(
                "B%04d%02d%02d%05d",
                $year,
                $month,
                $day,
                $billing_id_counter++
            );

            $lines[] = [
                "y" => $year,
                "m" => $month,
                "cust_id" => $customer["id"],
                "cust_name" => $customer["name"],
                "hit_code" => $hit_code,
                "tran_displayname" => $tx_type["display_name"],
                "actual_unit_cost" => $unit_cost,
                "count" => $count,
                "revenue" => $revenue,
                "EFX_code" => $tx_type["efx_code"],
                "billing_id" => $billing_id,
            ];
        }
    }

    return $lines;
}

// Create output directories
$generated_dir = __DIR__ . "/test_shared/generated";
$archive_dir = __DIR__ . "/test_shared/archive";

if (!is_dir($generated_dir)) {
    mkdir($generated_dir, 0755, true);
}
if (!is_dir($archive_dir)) {
    mkdir($archive_dir, 0755, true);
}

// Clear existing DataX files
foreach (glob($generated_dir . "/DataX_*.csv") as $file) {
    unlink($file);
}
foreach (glob($archive_dir . "/DataX_*.csv") as $file) {
    unlink($file);
}

echo "\nGenerating billing files...\n";

$total_files = 0;
$total_lines = 0;

// Generate for December 2025 (days 1-31)
for ($day = 1; $day <= 31; $day++) {
    $lines = generate_daily_file(
        2025,
        12,
        $day,
        $customers,
        $transaction_types,
        $hit_codes
    );

    $filename = sprintf("DataX_2025_12_%d_humanreadable.csv", $day);
    $filepath = $archive_dir . "/" . $filename;

    $csv =
        "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    foreach ($lines as $line) {
        $csv .=
            implode(",", [
                $line["y"],
                $line["m"],
                $line["cust_id"],
                '"' . str_replace('"', '""', $line["cust_name"]) . '"',
                $line["hit_code"],
                '"' . $line["tran_displayname"] . '"',
                $line["actual_unit_cost"],
                $line["count"],
                $line["revenue"],
                $line["EFX_code"],
                $line["billing_id"],
            ]) . "\n";
    }

    file_put_contents($filepath, $csv);
    $total_files++;
    $total_lines += count($lines);
    echo "  Created $filename (" . count($lines) . " lines)\n";
}

// Generate for January 2026 (days 1-31)
for ($day = 1; $day <= 31; $day++) {
    $lines = generate_daily_file(
        2026,
        1,
        $day,
        $customers,
        $transaction_types,
        $hit_codes
    );

    $filename = sprintf("DataX_2026_01_%d_humanreadable.csv", $day);

    // Put first 15 days in archive (already processed), rest in generated (ready to import)
    if ($day <= 15) {
        $filepath = $archive_dir . "/" . $filename;
    } else {
        $filepath = $generated_dir . "/" . $filename;
    }

    $csv =
        "y,m,cust_id,cust_name,hit_code,tran_displayname,actual_unit_cost,count,revenue,EFX_code,billing_id\n";
    foreach ($lines as $line) {
        $csv .=
            implode(",", [
                $line["y"],
                $line["m"],
                $line["cust_id"],
                '"' . str_replace('"', '""', $line["cust_name"]) . '"',
                $line["hit_code"],
                '"' . $line["tran_displayname"] . '"',
                $line["actual_unit_cost"],
                $line["count"],
                $line["revenue"],
                $line["EFX_code"],
                $line["billing_id"],
            ]) . "\n";
    }

    file_put_contents($filepath, $csv);
    $total_files++;
    $total_lines += count($lines);
    echo "  Created $filename (" . count($lines) . " lines)\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total files created: $total_files\n";
echo "Total lines: $total_lines\n";
echo "Average lines per file: " . round($total_lines / $total_files) . "\n";
echo "\nFiles in generated/ (ready for import): " .
    count(glob($generated_dir . "/DataX_*.csv")) .
    "\n";
echo "Files in archive/ (historical): " .
    count(glob($archive_dir . "/DataX_*.csv")) .
    "\n";
