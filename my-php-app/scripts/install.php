<?php
require_once __DIR__ . '/../src/App/Installer.php';

use App\Installer;

$installer = new Installer();

echo "===========================================\n";
echo "   PHP Application Universal Installer    \n";
echo "===========================================\n\n";

// Collect configuration
$config = [];

echo "Database Configuration:\n";
echo "-----------------------\n";

// Database driver
echo "Select database driver (mysql/pgsql/sqlite) [mysql]: ";
$config['db_driver'] = trim(fgets(STDIN)) ?: 'mysql';

if ($config['db_driver'] !== 'sqlite') {
    // Database host
    echo "Enter database host [localhost]: ";
    $config['db_host'] = trim(fgets(STDIN)) ?: 'localhost';

    // Database port
    $defaultPort = $config['db_driver'] === 'pgsql' ? '5432' : '3306';
    echo "Enter database port [$defaultPort]: ";
    $config['db_port'] = trim(fgets(STDIN)) ?: $defaultPort;

    // Database name
    echo "Enter database name: ";
    $config['db_name'] = trim(fgets(STDIN));

    // Database user
    echo "Enter database username: ";
    $config['db_user'] = trim(fgets(STDIN));

    // Database password
    echo "Enter database password: ";
    $config['db_pass'] = trim(fgets(STDIN));
} else {
    // For SQLite, just need the database file path
    echo "Enter SQLite database file path [database.sqlite]: ";
    $config['db_name'] = trim(fgets(STDIN)) ?: 'database.sqlite';
    $config['db_user'] = '';
    $config['db_pass'] = '';
}

echo "\n";

// Application settings
echo "Application Configuration:\n";
echo "--------------------------\n";

echo "Enter application environment (local/production) [production]: ";
$config['app_env'] = trim(fgets(STDIN)) ?: 'production';

echo "Enable debug mode? (yes/no) [no]: ";
$debugInput = trim(fgets(STDIN)) ?: 'no';
$config['app_debug'] = strtolower($debugInput) === 'yes' ? 'true' : 'false';

echo "\n";

// Optional database schema file
echo "Database Schema:\n";
echo "----------------\n";
echo "Enter path to SQL schema file (leave empty to skip): ";
$schemaFile = trim(fgets(STDIN));
if (!empty($schemaFile)) {
    $config['schema_file'] = $schemaFile;
}

echo "\n";

// Run the installation
if ($installer->runInstallation($config)) {
    echo "\n===========================================\n";
    echo "  Installation completed successfully!    \n";
    echo "===========================================\n\n";
    echo "Next steps:\n";
    echo "1. Review the .env file and adjust settings as needed\n";
    echo "2. Delete the installation files for security:\n";
    echo "   - scripts/install.php\n";
    echo "   - src/Views/install.php\n";
    echo "3. Configure your web server to point to src/public/\n";
    echo "4. Access your application\n\n";
    exit(0);
} else {
    echo "\n===========================================\n";
    echo "      Installation failed!               \n";
    echo "===========================================\n\n";
    echo "Please check the errors above and try again.\n\n";
    exit(1);
}
