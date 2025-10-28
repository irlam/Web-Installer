<?php
// filepath: install.php
require_once 'src/App/Installer.php';

use App\Installer;

$installer = new Installer();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    $subdomains = array_map('trim', explode(',', $_POST['subdomains'] ?? ''));
    $subdomains = array_filter($subdomains);

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');

    if (empty($domain) || !filter_var("http://$domain", FILTER_VALIDATE_URL)) {
        $errors[] = 'Valid primary domain required.';
    }
    if (empty($dbName) || empty($dbUser) || empty($dbPass)) {
        $errors[] = 'DB details required.';
    }

    if (empty($errors)) {
        try {
            $installer->setCredentials($dbHost, $dbName, $dbUser, $dbPass);
            $installer->setDomain($domain, $subdomains);
            if ($installer->runInstallation()) {
                echo "<p>Installation complete! Default user added. Delete install.php and database/default_user.txt for security.</p>";
                exit;
            } else {
                $errors[] = 'Installation failed.';
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
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
        <?php if ($errors): ?>
            <div class="error">
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="domain">Primary Domain</label>
                <input type="text" id="domain" name="domain" placeholder="e.g., example.com" required>
                <div class="help">Enter your main domain name (without http:// or https://). This is the primary URL for your site.</div>
            </div>
            <div class="form-group">
                <label for="subdomains">Subdomains (Optional)</label>
                <input type="text" id="subdomains" name="subdomains" placeholder="e.g., www,api,blog">
                <div class="help">Comma-separated list of subdomains (e.g., www, api). These will be configured alongside the primary domain.</div>
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