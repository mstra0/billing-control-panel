<?php
/**
 * QA Wrapper - Common QA page functionality
 *
 * Provides shared rendering functions for QA test pages.
 * Each test file only needs to define its specific demo content.
 */

/**
 * Render a standard QA test page
 *
 * @param string $title Page title
 * @param string $description Short description
 * @param array $test_results Results from running tests
 * @param string $test_output Raw test output
 * @param string $demo_content HTML content for the demo section
 * @param string $theme_color Primary color (default purple)
 * @param bool $is_critical Whether this is a critical/money test
 */
function render_qa_page($title, $description, $test_results, $test_output, $demo_content = '', $theme_color = '#667eea', $is_critical = false)
{
    $passed = isset($test_results['passed']) ? $test_results['passed'] : 0;
    $failed = isset($test_results['failed']) ? $test_results['failed'] : 0;
    $total = $passed + $failed;
    $status = ($failed === 0) ? 'PASS' : 'FAIL';
    $status_color = ($failed === 0) ? '#28a745' : '#dc3545';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QA: <?php echo htmlspecialchars($title); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }

        header {
            background: linear-gradient(135deg, <?php echo $theme_color; ?> 0%, <?php echo $is_critical ? '#c82333' : '#764ba2'; ?> 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        header h1 { font-size: 2em; margin-bottom: 10px; }
        header p { opacity: 0.9; }
        .critical-badge {
            display: inline-block;
            background: #ffc107;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .nav { margin-bottom: 20px; }
        .nav a {
            display: inline-block;
            padding: 10px 20px;
            background: <?php echo $theme_color; ?>;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .nav a:hover { opacity: 0.9; }

        .status-badge {
            display: inline-block;
            padding: 10px 30px;
            font-size: 1.5em;
            font-weight: bold;
            color: white;
            background: <?php echo $status_color; ?>;
            border-radius: 50px;
            margin: 10px 0;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-box .number { font-size: 2.5em; font-weight: bold; }
        .stat-box.passed .number { color: #28a745; }
        .stat-box.failed .number { color: #dc3545; }
        .stat-box .label { color: #666; text-transform: uppercase; font-size: 0.8em; }

        section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        section h2 {
            color: <?php echo $theme_color; ?>;
            border-bottom: 2px solid <?php echo $theme_color; ?>;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .demo-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
        }
        .demo-box h3 { color: #495057; margin-bottom: 15px; }

        .test-output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.9em;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

        .code-example {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85em;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; font-weight: 600; }

        .try-it {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
        }
        .try-it h4 { color: #0066cc; margin-bottom: 10px; }
        .try-it select, .try-it input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }
        .try-it button {
            padding: 8px 20px;
            background: <?php echo $theme_color; ?>;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="nav">
            <a href="../qa_dashboard.php">Back to QA Dashboard</a>
            <a href="?run=1">Re-run Tests</a>
        </nav>

        <header>
            <?php if ($is_critical): ?>
            <div class="critical-badge">CRITICAL - MONEY CALCULATIONS</div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p><?php echo htmlspecialchars($description); ?></p>
            <div class="status-badge"><?php echo $status; ?></div>
        </header>

        <div class="stats">
            <div class="stat-box passed">
                <div class="number"><?php echo $passed; ?></div>
                <div class="label">Tests Passed</div>
            </div>
            <div class="stat-box failed">
                <div class="number"><?php echo $failed; ?></div>
                <div class="label">Tests Failed</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo $total; ?></div>
                <div class="label">Total Tests</div>
            </div>
        </div>

        <?php if (!empty($demo_content)): ?>
        <section>
            <h2>Live Demo</h2>
            <?php echo $demo_content; ?>
        </section>
        <?php endif; ?>

        <section>
            <h2>Test Output</h2>
            <p>Raw output from running all <?php echo $total; ?> tests:</p>
            <div class="test-output"><?php echo htmlspecialchars($test_output); ?></div>
        </section>

        <?php if (!empty($test_results['errors'])): ?>
        <section>
            <h2>Failed Tests</h2>
            <table>
                <tr>
                    <th>Test</th>
                    <th>Expected</th>
                    <th>Actual</th>
                </tr>
                <?php foreach ($test_results['errors'] as $error): ?>
                <tr>
                    <td><?php echo htmlspecialchars($error['test']); ?></td>
                    <td><?php echo htmlspecialchars($error['expected']); ?></td>
                    <td><?php echo htmlspecialchars($error['actual']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>
        <?php endif; ?>

        <footer style="text-align: center; padding: 20px; color: #666;">
            <p>QA Test Page | Last run: <?php echo date('Y-m-d H:i:s'); ?></p>
        </footer>
    </div>
</body>
</html>
<?php
}

/**
 * Generate simple demo HTML
 */
function demo_box($title, $content)
{
    return '<div class="demo-box"><h3>' . htmlspecialchars($title) . '</h3>' . $content . '</div>';
}

/**
 * Generate a code example block
 */
function code_example($code)
{
    return '<pre class="code-example">' . htmlspecialchars($code) . '</pre>';
}
