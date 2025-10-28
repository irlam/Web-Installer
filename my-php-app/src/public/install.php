<?php
// filepath: src/public/install.php
require_once '../App/Installer.php';

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
    <title>Installer</title>
</head>
<body>
    <h1>Site Installer</h1>
    <?php if ($errors): ?>
        <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
    <?php endif; ?>
    <form method="post">
        <label>Primary Domain (e.g., example.com): <input name="domain" required></label><br>
        <label>Subdomains (comma-separated, e.g., www,api): <input name="subdomains"></label><br>
        <label>DB Host: <input name="db_host" value="localhost"></label><br>
        <label>DB Name: <input name="db_name" required></label><br>
        <label>DB User: <input name="db_user" required></label><br>
        <label>DB Pass: <input type="password" name="db_pass" required></label><br>
        <button type="submit">Install</button>
    </form>
</body>
</html>