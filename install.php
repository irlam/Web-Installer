<?php
// filepath: install.php
// Simple log helper
$__LOG_FILE = __DIR__ . '/logs.txt';
if (!file_exists($__LOG_FILE)) {
    @file_put_contents($__LOG_FILE, "[" . date('c') . "] Installer log created\n", FILE_APPEND);
}
function installer_log($msg) {
    global $__LOG_FILE;
    @file_put_contents($__LOG_FILE, '[' . date('c') . "] " . $msg . "\n", FILE_APPEND);
}

// Download logs endpoint (force download of logs.txt)
if (isset($_GET['download']) && $_GET['download'] === 'logs') {
    $path = $__LOG_FILE;
    if (is_file($path) && is_readable($path)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="logs.txt"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        echo 'Log file not found.';
    }
    exit;
}

// Download install summary endpoint (install_result.json)
if (isset($_GET['download']) && $_GET['download'] === 'summary') {
    $path = __DIR__ . '/install_result.json';
    if (is_file($path) && is_readable($path)) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="install_result.json"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        echo 'Install summary not found. Run installation first.';
    }
    exit;
}

// Load Composer autoloader if present
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    installer_log('Composer autoload loaded.');
}

// Load core classes without Composer autoload
require_once __DIR__ . '/src/App/Installer.php';
installer_log('Installer.php loaded.');

// Proactively include Database to avoid class not found on some hosts
if (!class_exists('Config\\Database')) {
    $dbPath = __DIR__ . '/src/Config/Database.php';
    installer_log('Attempting to include Database.php at: ' . $dbPath);
    if (file_exists($dbPath)) {
        require_once $dbPath;
        installer_log('Database.php included. class_exists(Config\\Database)=' . (class_exists('Config\\Database') ? 'yes' : 'no'));
    } else {
        installer_log('Missing Database.php at ' . $dbPath);
    }
}
$installer = new \App\Installer();
installer_log('Installer instantiated. PHP ' . PHP_VERSION);
$errors = [];

// Optional: post-install cleanup to remove installer files
if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['cleanup'])) {
    $removed = [];
    $failed = [];
    $selected = isset($_POST['delete']) && is_array($_POST['delete']) ? $_POST['delete'] : [];
    $map = [
        'install.php' => __FILE__,
        'index.html' => __DIR__ . '/index.html',
        'database/default_user.txt' => __DIR__ . '/database/default_user.txt',
        'packages/website.zip' => __DIR__ . '/packages/website.zip',
        'install_result.json' => __DIR__ . '/install_result.json',
        'logs.txt' => __DIR__ . '/logs.txt',
        'site_test.php' => __DIR__ . '/site_test.php',
    ];

    foreach ($selected as $rel) {
        if (!isset($map[$rel])) continue;
        $path = $map[$rel];
        if (file_exists($path)) {
            // Try to delete; if delete of current file (install.php), it still completes execution
            if (@unlink($path)) {
                $removed[] = $rel;
                installer_log('Cleanup: removed ' . $rel);
            } else {
                // Fallback to rename
                $backup = $path . '.bak-' . time();
                if (@rename($path, $backup)) {
                    $removed[] = $rel . ' (renamed to ' . basename($backup) . ')';
                    installer_log('Cleanup: renamed ' . $rel . ' to ' . basename($backup));
                } else {
                    $failed[] = $rel;
                    installer_log('Cleanup: failed to remove ' . $rel);
                }
            }
        }
    }

    // Render cleanup result panel
    echo '<div style="max-width:700px;margin:20px auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:20px;">';
    echo '<h2 style="margin-top:0;color:#2e7d32;">🧹 Cleanup Complete</h2>';
    if ($removed) {
        echo '<p>Removed:</p><ul>'; foreach ($removed as $r) { echo '<li>' . htmlspecialchars($r) . '</li>'; } echo '</ul>';
    }
    if ($failed) {
        echo '<p style="color:#c62828;">Could not remove:</p><ul>'; foreach ($failed as $f) { echo '<li>' . htmlspecialchars($f) . '</li>'; } echo '</ul>';
        echo '<p>Please delete these files manually via your hosting file manager or FTP.</p>';
    }
    // If a site index exists, suggest opening it
    $siteIndex = null;
    foreach (['index.php','index.html'] as $candidate) {
        if (file_exists(__DIR__ . '/' . $candidate)) { $siteIndex = './' . $candidate; break; }
    }
    echo '<div style="display:flex;gap:10px;flex-wrap:wrap;">';
    if ($siteIndex) {
        echo '<a href="' . htmlspecialchars($siteIndex) . '" style="background:#2e7d32;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Open site</a>';
    } else {
        echo '<a href="./" style="background:#2e7d32;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Go to site root</a>';
    }
    echo '<a href="logs.txt" style="background:#455a64;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">View logs</a>';
    echo '<a href="install.php?download=logs" style="background:#1565c0;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Download logs</a>';
    echo '<a href="install.php?download=summary" style="background:#6a1b9a;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Download install summary</a>';
    echo '</div>';
    echo '</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    $subdomains = array_map('trim', explode(',', $_POST['subdomains'] ?? ''));
    $subdomains = array_filter($subdomains);

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $createTest = isset($_POST['create_test_page']);

    installer_log('POST received. domain=' . $domain . ' subdomains=' . implode(',', $subdomains) . ' db_host=' . $dbHost . ' db_name=' . $dbName . ' user=' . $dbUser);

    if (empty($domain) || !preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
        $errors[] = 'Valid primary domain required (e.g., example.com, no https://).';
    }
    if (empty($dbName) || empty($dbUser) || empty($dbPass)) {
        $errors[] = 'DB details required.';
    }

    if (empty($errors)) {
        try {
            if (!class_exists('Config\\Database')) {
                throw new \Exception('Internal error: Database class not loaded. Ensure src/Config/Database.php exists and permissions allow reading it.');
            }
            $installer->setCredentials($dbHost, $dbName, $dbUser, $dbPass);
            $installer->setDomain($domain, $subdomains);
            $installer->setCreateTestPage($createTest);
            if ($installer->runInstallation()) {
                // Load result summary if available
                $summary = [];
                $summaryPath = __DIR__ . '/install_result.json';
                if (file_exists($summaryPath)) {
                    $json = file_get_contents($summaryPath);
                    $summary = json_decode($json, true) ?: [];
                }

                installer_log('Installation completed successfully.');

                // Render success panel
                echo '<div style="max-width:700px;margin:20px auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:20px;">';
                echo '<h2 style="margin-top:0;color:#2e7d32;">✅ Installation Complete</h2>';
                echo '<p>Your website has been installed successfully. Review the summary below and complete the final steps.</p>';
                echo '<div style="display:flex;gap:20px;flex-wrap:wrap;">';
                echo '<div style="flex:1 1 280px;"><h3 style="margin:10px 0;">Environment</h3><ul>';
                echo '<li>PHP: ' . htmlspecialchars(PHP_VERSION) . '</li>';
                echo '<li>Domain: ' . htmlspecialchars($summary['domain'] ?? $domain) . '</li>';
                $subs = isset($summary['subdomains']) ? implode(',', (array)$summary['subdomains']) : implode(',', $subdomains);
                echo '<li>Subdomains: ' . ($subs ? htmlspecialchars($subs) : '—') . '</li>';
                echo '<li>DB Host: ' . htmlspecialchars($dbHost) . '</li>';
                echo '<li>DB Name: ' . htmlspecialchars($dbName) . '</li>';
                echo '</ul></div>';
                echo '<div style="flex:1 1 280px;"><h3 style="margin:10px 0;">Actions</h3><ul>';
                $ok = function($b){ return $b ? '✅' : '⚠️'; };
                echo '<li>Extracted package: ' . $ok(($summary['zipExtracted'] ?? false)) . '</li>';
                echo '<li>Imported schema: ' . $ok(($summary['schemaImported'] ?? false)) . '</li>';
                $filesChanged = (int)($summary['filesChanged'] ?? 0);
                echo '<li>Files updated: ' . htmlspecialchars((string)$filesChanged) . '</li>';
                $duf = $summary['defaultUserFile'] ?? __DIR__ . '/database/default_user.txt';
                echo '<li>Default user file: ' . (file_exists($duf) ? '✅ ' . htmlspecialchars(basename($duf)) : '⚠️ not found') . '</li>';
                echo '<li>.env created: ' . $ok(($summary['envCreated'] ?? false)) . '</li>';
                echo '<li>config.php created: ' . $ok(($summary['configCreated'] ?? false)) . '</li>';
                $cfgUpd = (int)($summary['configFilesUpdated'] ?? 0);
                echo '<li>Config files updated: ' . htmlspecialchars((string)$cfgUpd) . '</li>';
                if (!empty($summary['zipFile'])) { echo '<li>Package used: ' . htmlspecialchars($summary['zipFile']) . '</li>'; }
                if (!empty($summary['schemaFile'])) { echo '<li>Schema used: ' . htmlspecialchars($summary['schemaFile']) . '</li>'; }
                if (!empty($summary['testPage'])) { echo '<li>Test page: ' . htmlspecialchars($summary['testPage']) . '</li>'; }
                echo '</ul></div>';
                echo '</div>';
                echo '<h3 style="margin:10px 0;">Next steps</h3>';
                echo '<ol>';
                echo '<li><strong>Delete installers</strong>: remove <code>install.php</code>, <code>index.html</code> (if present), and <code>database/default_user.txt</code>.</li>';
                echo '<li><strong>Secure files</strong>: ensure files are read-only for the web user where appropriate.</li>';
                echo '<li><strong>Configure SSL</strong>: ensure your domain/subdomain resolves correctly and TLS is configured in your hosting panel.</li>';
                echo '<li><strong>Optional</strong>: remove <code>packages/website.zip</code> after verification.</li>';
                echo '</ol>';
                echo '<div style="display:flex;gap:10px;flex-wrap:wrap;">';
                echo '<a href="./" style="background:#2e7d32;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Open site</a>';
                if (!empty($summary['testPage']) && file_exists(__DIR__ . '/' . basename($summary['testPage']))) {
                    echo '<a href="' . htmlspecialchars(basename($summary['testPage'])) . '" style="background:#00897b;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Open test page</a>';
                }
                echo '<a href="logs.txt" style="background:#455a64;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">View logs</a>';
                echo '<a href="install.php?download=logs" style="background:#1565c0;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Download logs</a>';
                echo '<a href="install.php?download=summary" style="background:#6a1b9a;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Download install summary</a>';
                echo '</div>';
                // Cleanup form to remove installer files and launch site
                echo '<div style="margin-top:16px;padding:12px;border:1px solid #eee;border-radius:8px;background:#fafafa;">';
                echo '<h3 style="margin:10px 0;">Secure and launch your site</h3>';
                echo '<form method="post" style="display:grid;gap:6px;">';
                echo '<input type="hidden" name="cleanup" value="1">';
                echo '<label><input type="checkbox" name="delete[]" value="install.php" checked> Delete install.php (this installer)</label>';
                echo '<label><input type="checkbox" name="delete[]" value="index.html" checked> Delete index.html (installer landing)</label>';
                echo '<label><input type="checkbox" name="delete[]" value="database/default_user.txt" checked> Delete database/default_user.txt</label>';
                echo '<label><input type="checkbox" name="delete[]" value="packages/website.zip"> Delete packages/website.zip (optional)</label>';
                echo '<label><input type="checkbox" name="delete[]" value="install_result.json"> Delete install_result.json (optional)</label>';
                echo '<label><input type="checkbox" name="delete[]" value="logs.txt"> Delete logs.txt (optional)</label>';
                echo '<label><input type="checkbox" name="delete[]" value="site_test.php" checked> Delete site_test.php (test landing)</label>';
                echo '<div><button type="submit" style="background:#d32f2f;color:#fff;padding:10px 16px;border:none;border-radius:6px;cursor:pointer;">Secure and launch site</button></div>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
                exit;
            } else {
                $errors[] = 'Installation failed. Check logs for details (logs.txt).';
                installer_log('Installation returned false.');
            }
        } catch (\Exception $e) {
            installer_log('Exception: ' . $e->getMessage());
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Installer</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .intro { margin-bottom: 20px; font-size: 16px; color: #666; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .help { font-size: 14px; color: #777; margin-top: 5px; }
        .error { color: red; font-weight: bold; }
        button { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #218838; }
        ul { list-style: none; padding: 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Website Installer</h1>
        <div class="intro">
            <p>Welcome! This installer will set up your website on a new domain or hosting. It will extract the website files from <code>packages/website.zip</code>, configure the database from the included schema, update files with your domain details, and add a default admin user. Follow the steps below and provide the required information.</p>
            <p><strong>Note:</strong> Ensure your hosting supports PHP 8.0+ and MySQL. Back up your data before proceeding. <em>Current PHP version: <?php echo PHP_VERSION; ?></em></p>
        </div>
        <?php
        // Preflight checks (displayed on GET and POST)
        $checks = [];
        $checks['PHP >= 8.0'] = version_compare(PHP_VERSION, '8.0', '>=');
        $checks['PDO extension'] = extension_loaded('pdo');
        $checks['PDO MySQL'] = extension_loaded('pdo_mysql');
    $checks['ZipArchive'] = class_exists('ZipArchive');
    $checks['SPL Iterators'] = class_exists('RecursiveIteratorIterator') && class_exists('RecursiveDirectoryIterator');
        $dbPath = __DIR__ . '/src/Config/Database.php';
        $checks['Database.php exists'] = file_exists($dbPath);
        $checks['Database.php readable'] = is_readable($dbPath);
        // Try to load Database.php if not loaded
        if (!class_exists('Config\\Database') && file_exists($dbPath)) {
            require_once $dbPath;
        }
        $checks['Config\\Database available'] = class_exists('Config\\Database');
        // Discover package zip (website.zip or first .zip in packages)
        $pkgZip = __DIR__ . '/packages/website.zip';
        $pkgUse = null;
        if (file_exists($pkgZip)) {
            $pkgUse = $pkgZip;
        } else {
            $pkgDir = __DIR__ . '/packages';
            if (is_dir($pkgDir)) {
                foreach (scandir($pkgDir) as $f) {
                    if ($f === '.' || $f === '..') continue;
                    if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'zip') { $pkgUse = $pkgDir . '/' . $f; break; }
                }
            }
        }
        $checks['Install package (zip) found'] = $pkgUse !== null;

        // Discover schema sql (schema.sql or first .sql in database)
        $schema = __DIR__ . '/database/schema.sql';
        $schemaUse = null;
        if (file_exists($schema)) {
            $schemaUse = $schema;
        } else {
            $dbDir = __DIR__ . '/database';
            if (is_dir($dbDir)) {
                foreach (scandir($dbDir) as $f) {
                    if ($f === '.' || $f === '..') continue;
                    if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'sql') { $schemaUse = $dbDir . '/' . $f; break; }
                }
            }
        }
        $checks['Database schema (.sql) found'] = $schemaUse !== null;
        // Log preflight summary once per request
        installer_log('Preflight: ' . json_encode($checks));
        ?>
        <div class="form-group" style="background:#fafafa;border:1px solid #eee;padding:10px;border-radius:6px;">
            <label>Preflight Checks</label>
            <ul style="margin:0;">
                <?php foreach ($checks as $label => $ok): ?>
                    <li style="color: <?php echo $ok ? '#2e7d32' : '#c62828'; ?>;">
                        <?php echo $ok ? '✓' : '✗'; ?> <?php echo htmlspecialchars($label); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (!$checks['Config\\Database available']): ?>
                <div class="help" style="color:#c62828;">
                    Database class not loaded. Ensure the file exists at <code>src/Config/Database.php</code> and is readable. The namespace should be <code>namespace Config;</code> and the class name <code>Database</code>.
                </div>
            <?php endif; ?>
            <?php if (!empty($pkgUse) || !empty($schemaUse)): ?>
                <div class="help" style="margin-top:8px;">
                    <?php if (!empty($pkgUse)): ?><div>Using package: <code><?php echo htmlspecialchars(basename($pkgUse)); ?></code></div><?php endif; ?>
                    <?php if (!empty($schemaUse)): ?><div>Using schema: <code><?php echo htmlspecialchars(basename($schemaUse)); ?></code></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($errors): ?>
            <div class="error">
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="domain">Primary Domain</label>
                <input type="text" id="domain" name="domain" placeholder="e.g., example.com" required>
                <div class="help">Enter your main domain name (e.g., example.com, without https:// or www).</div>
            </div>
            <div class="form-group">
                <label for="subdomains">Subdomains (Optional)</label>
                <input type="text" id="subdomains" name="subdomains" placeholder="e.g., www,api,blog">
                <div class="help">Comma-separated list of subdomains (e.g., www,api). If using a subdomain as primary, enter the base domain here and the subdomain below.</div>
            </div>
            <div class="form-group">
                <label for="db_host">Database Host</label>
                <input type="text" id="db_host" name="db_host" value="localhost" required>
                <div class="help">Usually 'localhost' for most hosting providers. Check your hosting control panel if unsure.</div>
            </div>
            <div class="form-group">
                <label for="db_name">Database Name</label>
                <input type="text" id="db_name" name="db_name" placeholder="e.g., myapp_db" required>
                <div class="help">Name of the MySQL database to create or use. Create it in your hosting panel first if required.</div>
            </div>
            <div class="form-group">
                <label for="db_user">Database Username</label>
                <input type="text" id="db_user" name="db_user" placeholder="e.g., db_user" required>
                <div class="help">Your MySQL username with privileges to create tables and insert data.</div>
            </div>
            <div class="form-group">
                <label for="db_pass">Database Password</label>
                <input type="password" id="db_pass" name="db_pass" required>
                <div class="help">Password for the database user. Keep it secure and note it down.</div>
            </div>
            <div class="form-group" style="background:#fafafa;border:1px dashed #ccc;padding:10px;border-radius:6px;">
                <label><input type="checkbox" name="create_test_page" value="1" checked> Create test page (site_test.php) to verify PHP extensions and DB connection</label>
                <div class="help">Recommended during testing. The test page will be offered for deletion on the success screen.</div>
            </div>
            <button type="submit">Install Website</button>
        </form>
    </div>
</body>
</html>