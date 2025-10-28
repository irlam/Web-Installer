<?php

namespace App;

class Installer
{
    private $dbUser;
    private $dbPass;
    private $dbHost = 'localhost';
    private $dbPort = 3306;
    private $dbName;
    private $dbDriver = 'mysql';
    private $errors = [];
    private $warnings = [];
    private $config = [];

    public function __construct()
    {
        $this->checkPhpVersion();
    }

    public function promptForCredentials()
    {
        echo "Please enter your SQL username: ";
        $this->dbUser = trim(fgets(STDIN));

        echo "Please enter your SQL password: ";
        $this->dbPass = trim(fgets(STDIN));
    }

    private function checkPhpVersion()
    {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            die("PHP version 7.4 or higher is required. Current version: " . PHP_VERSION . "\n");
        }
    }

    /**
     * Comprehensive system requirements check
     */
    public function checkSystemRequirements()
    {
        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '8.0', '>='),
            'mysqli' => extension_loaded('mysqli'),
            'pdo' => extension_loaded('pdo'),
            'json' => extension_loaded('json'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'curl' => extension_loaded('curl'),
            'fileinfo' => extension_loaded('fileinfo'),
            'tokenizer' => extension_loaded('tokenizer'),
        ];

        $passed = true;
        foreach ($requirements as $requirement => $status) {
            if (!$status) {
                $this->errors[] = "Required: $requirement is not available";
                $passed = false;
            }
        }

        return $passed;
    }

    /**
     * Check and create necessary directories
     */
    public function setupDirectories()
    {
        $directories = [
            'storage/cache',
            'storage/logs',
            'storage/sessions',
            'storage/uploads',
            'bootstrap/cache',
        ];

        $baseDir = dirname(__DIR__, 2);

        foreach ($directories as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            if (!file_exists($fullPath)) {
                if (!mkdir($fullPath, 0755, true)) {
                    $this->errors[] = "Failed to create directory: $dir";
                    return false;
                }
            }

            if (!is_writable($fullPath)) {
                $this->warnings[] = "Directory not writable: $dir";
            }
        }

        return true;
    }

    /**
     * Verify file permissions
     */
    public function checkPermissions()
    {
        $baseDir = dirname(__DIR__, 2);
        $paths = [
            'storage',
            'bootstrap/cache',
            '.env',
        ];

        foreach ($paths as $path) {
            $fullPath = $baseDir . '/' . $path;
            if (file_exists($fullPath) && !is_writable($fullPath)) {
                $this->warnings[] = "Path not writable: $path";
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection($host, $user, $pass, $dbName, $driver = 'mysql', $port = 3306)
    {
        try {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                $pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
                
                // Try to create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                return true;
            } elseif ($driver === 'pgsql') {
                $dsn = "pgsql:host=$host;port=$port";
                $pdo = new \PDO($dsn, $user, $pass);
                return true;
            } elseif ($driver === 'sqlite') {
                $pdo = new \PDO("sqlite:$dbName");
                return true;
            }
        } catch (\PDOException $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
            return false;
        }

        return false;
    }

    /**
     * Generate secure application key
     */
    public function generateSecurityKey()
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * Generate environment file from template
     */
    public function generateEnvironmentFile($config)
    {
        $baseDir = dirname(__DIR__, 2);
        $envExample = $baseDir . '/.env.example';
        $envFile = $baseDir . '/.env';

        if (!file_exists($envExample)) {
            $this->errors[] = ".env.example file not found";
            return false;
        }

        $content = file_get_contents($envExample);

        // Replace placeholders with actual values
        $replacements = [
            'DB_HOST=127.0.0.1' => 'DB_HOST=' . ($config['db_host'] ?? '127.0.0.1'),
            'DB_PORT=3306' => 'DB_PORT=' . ($config['db_port'] ?? '3306'),
            'DB_DATABASE=your_database_name' => 'DB_DATABASE=' . ($config['db_name'] ?? ''),
            'DB_USERNAME=your_username' => 'DB_USERNAME=' . ($config['db_user'] ?? ''),
            'DB_PASSWORD=your_password' => 'DB_PASSWORD=' . ($config['db_pass'] ?? ''),
            'DB_CONNECTION=mysql' => 'DB_CONNECTION=' . ($config['db_driver'] ?? 'mysql'),
            'APP_KEY=base64:YOUR_APP_KEY_HERE' => 'APP_KEY=' . $this->generateSecurityKey(),
            'APP_ENV=local' => 'APP_ENV=' . ($config['app_env'] ?? 'production'),
            'APP_DEBUG=true' => 'APP_DEBUG=' . ($config['app_debug'] ?? 'false'),
        ];

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        if (file_put_contents($envFile, $content) === false) {
            $this->errors[] = "Failed to create .env file";
            return false;
        }

        // Set appropriate permissions
        chmod($envFile, 0600);

        return true;
    }

    /**
     * Run database migrations/schema setup
     */
    public function setupDatabaseSchema($sqlFile = null)
    {
        if (!$sqlFile) {
            return true; // No schema file provided
        }

        if (!file_exists($sqlFile)) {
            $this->warnings[] = "Database schema file not found: $sqlFile";
            return true;
        }

        try {
            $dsn = "{$this->dbDriver}:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
            $pdo = new \PDO($dsn, $this->dbUser, $this->dbPass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);

            return true;
        } catch (\PDOException $e) {
            $this->errors[] = "Failed to setup database schema: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Generate .htaccess for Apache
     */
    public function generateHtaccess()
    {
        $baseDir = dirname(__DIR__, 2);
        $htaccessPath = $baseDir . '/src/public/.htaccess';

        $htaccessContent = <<<HTACCESS
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS;

        if (file_put_contents($htaccessPath, $htaccessContent) === false) {
            $this->warnings[] = "Failed to create .htaccess file";
            return false;
        }

        return true;
    }

    /**
     * Validate configuration values
     */
    public function validateConfiguration($config)
    {
        $required = ['db_host', 'db_name', 'db_user'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                $this->errors[] = "Required field missing: $field";
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Perform rollback on installation failure
     */
    public function rollback()
    {
        $baseDir = dirname(__DIR__, 2);
        $envFile = $baseDir . '/.env';

        if (file_exists($envFile)) {
            unlink($envFile);
        }

        // Clean up created directories if empty
        $directories = [
            'storage/cache',
            'storage/logs',
            'storage/sessions',
            'storage/uploads',
        ];

        foreach (array_reverse($directories) as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            if (file_exists($fullPath) && is_dir($fullPath)) {
                @rmdir($fullPath); // Only removes if empty
            }
        }
    }

    /**
     * Post-installation cleanup
     */
    public function cleanup()
    {
        $baseDir = dirname(__DIR__, 2);
        $installScript = $baseDir . '/scripts/install.php';
        $installView = $baseDir . '/src/Views/install.php';

        // Optionally remove installation files
        $this->warnings[] = "Remember to delete installation files: scripts/install.php and src/Views/install.php";
        
        return true;
    }

    /**
     * Complete installation process
     */
    public function runInstallation($config)
    {
        echo "Starting installation...\n\n";

        // Step 1: Check system requirements
        echo "Checking system requirements...\n";
        if (!$this->checkSystemRequirements()) {
            $this->displayErrors();
            return false;
        }
        echo "✓ System requirements met\n\n";

        // Step 2: Validate configuration
        echo "Validating configuration...\n";
        if (!$this->validateConfiguration($config)) {
            $this->displayErrors();
            return false;
        }
        echo "✓ Configuration valid\n\n";

        // Step 3: Test database connection
        echo "Testing database connection...\n";
        if (!$this->testDatabaseConnection(
            $config['db_host'] ?? 'localhost',
            $config['db_user'],
            $config['db_pass'] ?? '',
            $config['db_name'],
            $config['db_driver'] ?? 'mysql',
            $config['db_port'] ?? 3306
        )) {
            $this->displayErrors();
            $this->rollback();
            return false;
        }
        echo "✓ Database connection successful\n\n";

        // Step 4: Setup directories
        echo "Setting up directories...\n";
        if (!$this->setupDirectories()) {
            $this->displayErrors();
            $this->rollback();
            return false;
        }
        echo "✓ Directories created\n\n";

        // Step 5: Generate environment file
        echo "Generating environment file...\n";
        if (!$this->generateEnvironmentFile($config)) {
            $this->displayErrors();
            $this->rollback();
            return false;
        }
        echo "✓ Environment file created\n\n";

        // Step 6: Setup database schema (if provided)
        if (!empty($config['schema_file'])) {
            echo "Setting up database schema...\n";
            $this->dbHost = $config['db_host'] ?? 'localhost';
            $this->dbName = $config['db_name'];
            $this->dbUser = $config['db_user'];
            $this->dbPass = $config['db_pass'] ?? '';
            $this->dbDriver = $config['db_driver'] ?? 'mysql';
            
            if (!$this->setupDatabaseSchema($config['schema_file'])) {
                $this->displayErrors();
                $this->rollback();
                return false;
            }
            echo "✓ Database schema created\n\n";
        }

        // Step 7: Generate .htaccess
        echo "Generating .htaccess file...\n";
        $this->generateHtaccess();
        echo "✓ .htaccess file created\n\n";

        // Step 8: Check permissions
        echo "Checking file permissions...\n";
        $this->checkPermissions();
        echo "✓ Permissions checked\n\n";

        // Display warnings if any
        $this->displayWarnings();

        // Step 9: Cleanup
        $this->cleanup();

        echo "\n✓ Installation completed successfully!\n\n";
        return true;
    }

    /**
     * Display errors
     */
    private function displayErrors()
    {
        if (!empty($this->errors)) {
            echo "\n❌ Errors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
    }

    /**
     * Display warnings
     */
    private function displayWarnings()
    {
        if (!empty($this->warnings)) {
            echo "\n⚠ Warnings:\n";
            foreach ($this->warnings as $warning) {
                echo "  - $warning\n";
            }
        }
    }

    public function getDbUser()
    {
        return $this->dbUser;
    }

    public function getDbPass()
    {
        return $this->dbPass;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }
}