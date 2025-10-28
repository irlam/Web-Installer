<?php
// filepath: public/install.php
// Simple PHP installer for domain migration
// Run via browser: http://yourhost/install.php

// Requirements check
$errors = [];
if (version_compare(PHP_VERSION, '8.0', '<')) {
    $errors[] = 'PHP 8.0+ required.';
}
if (!extension_loaded('mysqli')) {
    $errors[] = 'mysqli extension required.';
}
if (!is_writable(__DIR__)) {
    $errors[] = 'Directory not writable.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain'] ?? '');
    $subdomains = array_map('trim', explode(',', $_POST['subdomains'] ?? ''));
    $subdomains = array_filter($subdomains); // Remove empties

    if (empty($domain) || !filter_var("http://$domain", FILTER_VALIDATE_URL)) {
        $errors[] = 'Valid primary domain required.';
    }

    if (empty($errors)) {
        // Update files
        updateFiles(__DIR__, $domain, $subdomains);
        echo "<p>Installation complete! Domain updated to $domain. Delete install.php for security.</p>";
        exit;
    }
}

function updateFiles($dir, $domain, $subdomains) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && !in_array($file->getExtension(), ['jpg', 'png', 'gif', 'zip', 'tar'])) {
            $content = file_get_contents($file->getPathname());
            $original = $content;

            // Replace placeholders
            $content = str_replace('__DOMAIN__', $domain, $content);

            // Handle subdomains (e.g., replace example.com with www.example.com if www in subdomains)
            foreach ($subdomains as $sub) {
                $content = str_replace("https://$domain", "https://$sub.$domain", $content);
                $content = str_replace("http://$domain", "http://$sub.$domain", $content);
            }

            if ($content !== $original) {
                file_put_contents($file->getPathname(), $content);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Installer</title></head>
<body>
    <h1>Site Installer</h1>
    <?php if ($errors): ?>
        <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
    <?php endif; ?>
    <form method="post">
        <label>Primary Domain (e.g., example.com): <input name="domain" required></label><br>
        <label>Subdomains (comma-separated, e.g., www,api): <input name="subdomains"></label><br>
        <button type="submit">Install</button>
    </form>
</body>
</html>