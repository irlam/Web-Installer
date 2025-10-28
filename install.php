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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    $subdomains = array_map('trim', explode(',', $_POST['subdomains'] ?? ''));
    $subdomains = array_filter($subdomains);

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');

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
                echo '</ul></div>';
                echo '</div>';
                echo '<h3 style="margin:10px 0;">Next steps</h3>';
                echo '<ol>';
                echo '<li><strong>Delete installers</strong>: remove <code>install.php</code>, <code>index.html</code> (if present), and <code>database/default_user.txt</code>.</li>';
                echo '<li><strong>Secure files</strong>: ensure files are read-only for the web user where appropriate.</li>';
                echo '<li><strong>Configure SSL</strong>: ensure your domain/subdomain resolves correctly and TLS is configured in your hosting panel.</li>';
                echo '<li><strong>Optional</strong>: remove <code>packages/website.zip</code> after verification.</li>';
                echo '</ol>';
                echo '<div style="display:flex;gap:10px;">';
                echo '<a href="./" style="background:#2e7d32;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">Open site</a>';
                echo '<a href="logs.txt" style="background:#455a64;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;">View logs</a>';
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
        $pkgZip = __DIR__ . '/packages/website.zip';
        $checks['packages/website.zip present'] = file_exists($pkgZip);
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
            <button type="submit">Install Website</button>
        </form>
    </div>
</body>
</html>