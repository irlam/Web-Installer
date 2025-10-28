<?php

namespace App;

// Best-effort include of Database class for hosts without autoloaders
if (!class_exists('Config\\Database')) {
    $dbBootstrap = __DIR__ . '/../Config/Database.php';
    if (file_exists($dbBootstrap)) {
        require_once $dbBootstrap;
    }
}

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
    private $domain;
    private $subdomains;

    public function __construct()
    {
        $this->checkRequirements();
    }

    public function setCredentials($dbHost, $dbName, $dbUser, $dbPass)
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
    }

    public function setDomain($domain, $subdomains = [])
    {
        $this->domain = $domain;
        $this->subdomains = $subdomains;
    }

    public function promptForCredentials()
    {
        echo "Please enter your SQL username: ";
        $this->dbUser = trim(fgets(STDIN));

        echo "Please enter your SQL password: ";
        $this->dbPass = trim(fgets(STDIN));
    }

    private function checkRequirements()
    {
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            throw new \Exception("PHP 8.0+ required.");
        }
        if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            throw new \Exception("PDO and PDO MySQL extensions required.");
        }
        if (!is_writable(__DIR__ . '/../..')) {
            throw new \Exception("Directory not writable.");
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
                
                // Sanitize database name to prevent SQL injection
                $sanitizedDbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
                
                // Try to create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$sanitizedDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
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
            $logFile = dirname(__DIR__, 2) . '/logs.txt';
            @file_put_contents($logFile, '[' . date('c') . "] runInstallation start\n", FILE_APPEND);
    }

    /**
     * Generate environment file from template
                @error_log('[Installer] Extracting ZIP at ' . $zipPath . "\n", 3, $logFile);
                $this->extractZip($zipPath, __DIR__ . '/../..');
    public function generateEnvironmentFile($config)
    {
        $baseDir = dirname(__DIR__, 2);
        $envExample = $baseDir . '/.env.example';
        $envFile = $baseDir . '/.env';

                error_log('[Installer] Checking Database class include at: ' . $dbFile . "\n", 3, $logFile);
            return false;
        }

        $content = file_get_contents($envExample);

        // Replace placeholders with actual values
        $replacements = [
            'DB_HOST=127.0.0.1' => 'DB_HOST=' . ($config['db_host'] ?? '127.0.0.1'),
            'DB_PORT=3306' => 'DB_PORT=' . ($config['db_port'] ?? '3306'),
                error_log('[Installer] ' . $msg . "\n", 3, $logFile);
            'DB_USERNAME=your_username' => 'DB_USERNAME=' . ($config['db_user'] ?? ''),
            'DB_PASSWORD=your_password' => 'DB_PASSWORD=' . ($config['db_pass'] ?? ''),
            'DB_CONNECTION=mysql' => 'DB_CONNECTION=' . ($config['db_driver'] ?? 'mysql'),
            error_log('[Installer] Database connection object created' . "\n", 3, $logFile);
            'YOUR_APP_KEY_HERE' => base64_encode(random_bytes(32)), // Replace just the placeholder part, keeping 'base64:' prefix
            'APP_ENV=local' => 'APP_ENV=' . ($config['app_env'] ?? 'production'),
            'APP_DEBUG=true' => 'APP_DEBUG=' . ($config['app_debug'] ?? 'false'),
        ];
                error_log('[Installer] Importing schema from ' . $dbSchemaPath . "\n", 3, $logFile);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

            error_log('[Installer] Updating files for domain ' . $this->domain . ' with subdomains ' . implode(',', (array)$this->subdomains) . "\n", 3, $logFile);
        if (file_put_contents($envFile, $content) === false) {
            $this->errors[] = "Failed to create .env file";
            return false;
        }

            error_log('[Installer] runInstallation success' . "\n", 3, $logFile);
        // Set appropriate permissions
        chmod($envFile, 0600);
            $logFile = dirname(__DIR__, 2) . '/logs.txt';
            error_log('[Installer] Exception: ' . $e->getMessage() . "\n", 3, $logFile);

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

        // Prevent directory traversal attacks
        $realPath = realpath($sqlFile);
        if ($realPath === false || !file_exists($realPath)) {
            $this->warnings[] = "Database schema file not found: $sqlFile";
            return true;
        }

        // Ensure the file is readable and has .sql extension
        if (!is_readable($realPath) || pathinfo($realPath, PATHINFO_EXTENSION) !== 'sql') {
            $this->errors[] = "Invalid schema file: must be a readable .sql file";
            return false;
        }

        try {
            $dsn = "{$this->dbDriver}:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
            $pdo = new \PDO($dsn, $this->dbUser, $this->dbPass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = file_get_contents($realPath);
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
        // Database name is always required
        if (empty($config['db_name'])) {
            $this->errors[] = "Required field missing: db_name";
        }
        
        // For non-SQLite databases, we need host and user
        if (($config['db_driver'] ?? 'mysql') !== 'sqlite') {
            if (empty($config['db_host'])) {
                $this->errors[] = "Required field missing: db_host";
            }
            if (empty($config['db_user'])) {
                $this->errors[] = "Required field missing: db_user";
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
        // Warn user to manually delete installation files for security
        $this->warnings[] = "Remember to delete installation files: scripts/install.php and src/Views/install.php";
        
        return true;
    }

    /**
     * Web-based installation process
     */
    public function runInstallation()
    {
        try {
            // Extract website ZIP
            $zipPath = __DIR__ . '/../../packages/website.zip';
            if (file_exists($zipPath)) {
                $this->extractZip($zipPath, __DIR__ . '/../..');
            }

            // Connect to DB
            // Ensure Database class is available even without Composer autoload
            if (!class_exists('Config\\Database')) {
                $dbFile = __DIR__ . '/../Config/Database.php';
                $logFile = dirname(__DIR__, 2) . '/installer.log';
                error_log('[Installer] Checking Database class include at: ' . $dbFile . "\n", 3, $logFile);
                if (file_exists($dbFile)) {
                    require_once $dbFile;
                    error_log('[Installer] Database.php included. class_exists(Config\\Database)=' . (class_exists('Config\\Database') ? 'yes' : 'no') . "\n", 3, $logFile);
                } else {
                    error_log('[Installer] Database.php file not found at: ' . $dbFile . "\n", 3, $logFile);
                }
            }
            if (!class_exists('Config\\Database')) {
                $msg = 'Config\\Database class not found. Expected file at src/Config/Database.php.';
                error_log('[Installer] ' . $msg . "\n", 3, dirname(__DIR__, 2) . '/installer.log');
                throw new \Exception($msg);
            }
            $db = new \Config\Database($this->dbHost, $this->dbName, $this->dbUser, $this->dbPass);

            // Import DB from extracted database/schema.sql if exists
            $dbSchemaPath = __DIR__ . '/../../database/schema.sql';
            if (file_exists($dbSchemaPath)) {
                $sql = file_get_contents($dbSchemaPath);
                $db->getConnection()->exec($sql);
            }

            // Update files for domain
            $this->updateFiles(__DIR__ . '/../..', $this->domain, $this->subdomains);

            // Generate default user file
            $this->generateDefaultUserFile();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function extractZip($zipPath, $extractTo)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractTo);
            $zip->close();
        } else {
            throw new \Exception('Failed to extract ZIP file.');
        }
    }

    private function updateFiles($dir, $domain, $subdomains)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && !in_array($file->getExtension(), ['jpg', 'png', 'gif', 'zip', 'tar', 'sql'])) {
                $content = file_get_contents($file->getPathname());
                $original = $content;

                // Replace placeholders
                $content = str_replace('__DOMAIN__', $domain, $content);

                // Handle subdomains
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

    private function generateDefaultUserFile()
    {
        $content = "Default Admin User:\nUsername: admin\nPassword: defaultpass123\nEmail: admin@example.com\n";
        file_put_contents(__DIR__ . '/../../database/default_user.txt', $content);
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