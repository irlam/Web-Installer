<?php
require_once '../src/App/Installer.php';

$installer = new Installer();

// Check PHP version
if (!$installer->checkPhpVersion()) {
    die("PHP version is not compatible. Please ensure you are running PHP 7.4 or higher.");
}

// Prompt for SQL user credentials
$sqlUser = readline("Enter SQL username: ");
$sqlPassword = readline("Enter SQL password: ");

// Set credentials in the installer
$installer->setSqlCredentials($sqlUser, $sqlPassword);

// Run the installation process
if ($installer->runInstallation()) {
    echo "Installation completed successfully!";
} else {
    echo "Installation failed. Please check the logs for more details.";
}
?>