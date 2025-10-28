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
            // Structured result for success UI
            $result = [
                'timestamp' => date('c'),
                'php_version' => PHP_VERSION,
                'domain' => (string)$this->domain,
                'subdomains' => (array)$this->subdomains,
                'zipExtracted' => false,
                'schemaImported' => false,
                'filesChanged' => 0,
                'defaultUserFile' => null,
                'zipFile' => null,
                'schemaFile' => null,
                'envCreated' => false,
                'configCreated' => false,
                'configFilesUpdated' => 0,
            ];
            // Extract website ZIP
            $zipPath = __DIR__ . '/../../packages/website.zip';
            if (!file_exists($zipPath)) {
                // Fallback: first .zip in packages directory
                $pkgDir = __DIR__ . '/../../packages';
                if (is_dir($pkgDir)) {
                    foreach (scandir($pkgDir) as $f) {
                        if ($f === '.' || $f === '..') continue;
                        if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'zip') {
                            $zipPath = $pkgDir . '/' . $f;
                            break;
                        }
                    }
                }
            }
            if (file_exists($zipPath)) {
                $this->extractZip($zipPath, __DIR__ . '/../..');
                $result['zipExtracted'] = true;
                $result['zipFile'] = basename($zipPath);
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
            if (!file_exists($dbSchemaPath)) {
                // Fallback: first .sql in database directory
                $dbDir = __DIR__ . '/../../database';
                if (is_dir($dbDir)) {
                    foreach (scandir($dbDir) as $f) {
                        if ($f === '.' || $f === '..') continue;
                        if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'sql') {
                            $dbSchemaPath = $dbDir . '/' . $f;
                            break;
                        }
                    }
                }
            }
            if (file_exists($dbSchemaPath)) {
                $sql = file_get_contents($dbSchemaPath);
                $db->getConnection()->exec($sql);
                $result['schemaImported'] = true;
                $result['schemaFile'] = basename($dbSchemaPath);
            }

            // Update files for domain
            $result['filesChanged'] = $this->updateFiles(__DIR__ . '/../..', $this->domain, $this->subdomains);

            // Generate .env or config.php if possible, and update existing config patterns
            $baseDir = dirname(__DIR__, 2);
            $gen = $this->generateAppConfig($baseDir);
            if ($gen['envCreated']) { $result['envCreated'] = true; }
            if ($gen['configCreated']) { $result['configCreated'] = true; }
            $result['configFilesUpdated'] = $this->updateConfigurationPatterns($baseDir);

            // Generate default user file
            $this->generateDefaultUserFile();
            $result['defaultUserFile'] = realpath(__DIR__ . '/../../database/default_user.txt');

            // Write summary for UI
            @file_put_contents(dirname(__DIR__, 2) . '/install_result.json', json_encode($result, JSON_PRETTY_PRINT));
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
        $changed = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && !in_array($file->getExtension(), ['jpg', 'png', 'gif', 'zip', 'tar', 'sql'])) {
                $content = file_get_contents($file->getPathname());
                $original = $content;

                // Replace placeholders
                $content = str_replace('__DOMAIN__', $domain, $content);

                // Replace common example URLs with the chosen domain (assume https)
                $appUrl = 'https://' . $domain;
                $content = str_replace('https://example.com', $appUrl, $content);
                $content = str_replace('http://example.com', $appUrl, $content);

                // Handle subdomains
                foreach ($subdomains as $sub) {
                    $content = str_replace("https://$domain", "https://$sub.$domain", $content);
                    $content = str_replace("http://$domain", "http://$sub.$domain", $content);
                }

                if ($content !== $original) {
                    file_put_contents($file->getPathname(), $content);
                    $changed++;
                }
            }
        }
        return $changed;
    }

    /**
     * Generate application configuration files from inputs
     * - If .env.example exists, create .env with DB and APP_URL
     * - Else, if config.sample.php exists, create config.php replacing tokens
     */
    private function generateAppConfig($baseDir)
    {
        $result = ['envCreated' => false, 'configCreated' => false];
        $appUrl = 'https://' . $this->domain;
        $envExample = $baseDir . '/.env.example';
        $envFile = $baseDir . '/.env';
        if (file_exists($envExample) && !file_exists($envFile)) {
            $content = file_get_contents($envExample);
            // Replace or append keys
            $pairs = [
                'APP_URL' => $appUrl,
                'DB_HOST' => $this->dbHost,
                'DB_DATABASE' => $this->dbName,
                'DB_NAME' => $this->dbName, // Some templates use DB_NAME
                'DB_USERNAME' => $this->dbUser,
                'DB_USER' => $this->dbUser,
                'DB_PASSWORD' => $this->dbPass,
                'DB_PORT' => '3306',
            ];
            foreach ($pairs as $key => $val) {
                if (preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
                    $content = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $val, $content);
                } else {
                    $content .= "\n$key=$val";
                }
            }
            // Set APP_KEY if placeholder present
            if (preg_match('/^APP_KEY=\s*$/m', $content) || strpos($content, 'APP_KEY=') === false) {
                $content .= "\nAPP_KEY=base64:" . base64_encode(random_bytes(32));
            }
            @file_put_contents($envFile, $content);
            if (file_exists($envFile)) { $result['envCreated'] = true; }
        }

        // Generate config.php from config.sample.php tokens if applicable
        $sample = $baseDir . '/config.sample.php';
        $target = $baseDir . '/config.php';
    if (file_exists($sample) && !file_exists($target)) {
            $content = file_get_contents($sample);
            $repl = [
                '__APP_URL__' => $appUrl,
                '__DB_HOST__' => $this->dbHost,
                '__DB_NAME__' => $this->dbName,
                '__DB_USER__' => $this->dbUser,
                '__DB_PASS__' => $this->dbPass,
            ];
            $content = strtr($content, $repl);
            @file_put_contents($target, $content);
            if (file_exists($target)) { $result['configCreated'] = true; }
        }

        return $result;
    }

    /**
     * Update common configuration patterns across text files
     */
    private function updateConfigurationPatterns($baseDir)
    {
        $changed = 0;
        $appUrl = 'https://' . $this->domain;

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico','zip','tar','gz','bz2','xz','7z','pdf','woff','woff2','ttf','otf'])) continue;

            $path = $file->getPathname();
            // Skip common dependency directories
            if (strpos($path, '/vendor/') !== false || strpos($path, '/node_modules/') !== false) continue;
            $isEnv = basename($path) === '.env';
            $isPhp = $ext === 'php';
            $isConfigish = stripos($path, '/config') !== false || preg_match('/(^|\/)config\.(php|ini|json|yaml|yml)$/i', $path);
            $isJson = $ext === 'json';
            $isYaml = ($ext === 'yaml' || $ext === 'yml');

            $content = file_get_contents($path);
            $orig = $content;

            // .env style
            if ($isEnv) {
                $content = preg_replace('/^APP_URL=.*/m', 'APP_URL=' . $appUrl, $content);
                $content = preg_replace('/^DB_HOST=.*/m', 'DB_HOST=' . $this->dbHost, $content);
                $content = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=' . $this->dbName, $content);
                $content = preg_replace('/^DB_NAME=.*/m', 'DB_NAME=' . $this->dbName, $content);
                $content = preg_replace('/^DB_USERNAME=.*/m', 'DB_USERNAME=' . $this->dbUser, $content);
                $content = preg_replace('/^DB_USER=.*/m', 'DB_USER=' . $this->dbUser, $content);
                $content = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD=' . $this->dbPass, $content);
            }

            // PHP define('DB_*', '...')
            if ($isPhp) {
                $re = [
                    '/define\([\"\']DB_HOST[\"\'],\s*[\"\'][^\"\']*[\"\']\)/' => "define('DB_HOST','{$this->dbHost}')",
                    '/define\([\"\']DB_NAME[\"\'],\s*[\"\'][^\"\']*[\"\']\)/' => "define('DB_NAME','{$this->dbName}')",
                    '/define\([\"\']DB_DATABASE[\"\'],\s*[\"\'][^\"\']*[\"\']\)/' => "define('DB_DATABASE','{$this->dbName}')",
                    '/define\([\"\']DB_USER(NAME)?[\"\'],\s*[\"\'][^\"\']*[\"\']\)/' => "define('DB_USERNAME','{$this->dbUser}')",
                    '/define\([\"\']DB_PASSWORD[\"\'],\s*[\"\'][^\"\']*[\"\']\)/' => "define('DB_PASSWORD','{$this->dbPass}')",
                    '/define\([\"\']APP_URL[\"\'],\s*[\"\'][^\"\']*[\"\']\)/' => "define('APP_URL','{$appUrl}')",
                ];
                foreach ($re as $pattern => $rep) {
                    $content = preg_replace($pattern, $rep, $content);
                }
            }

            // Array-style config updates only for config-ish files
            if ($isPhp && $isConfigish) {
                $re2 = [
                    '/([\"\']db_host[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbHost}'",
                    '/([\"\']host[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbHost}'",
                    '/([\"\']db_name[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbName}'",
                    '/([\"\']database(name)?[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbName}'",
                    '/([\"\']db_user(name)?[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbUser}'",
                    '/([\"\']username[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbUser}'",
                    '/([\"\']db_pass(word)?[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbPass}'",
                    '/([\"\']password[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$this->dbPass}'",
                    '/([\"\']app_url[\"\']\s*=>\s*)[\"\'][^\"\']*[\"\']/' => "$1'{$appUrl}'",
                ];
                foreach ($re2 as $pattern => $rep) {
                    $content = preg_replace($pattern, $rep, $content);
                }
            }

            // JSON config files: decode, update known keys recursively, re-encode
            if ($isJson) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $keysMap = [
                        'db_host' => $this->dbHost,
                        'host' => $this->dbHost,
                        'db_name' => $this->dbName,
                        'database' => $this->dbName,
                        'db_user' => $this->dbUser,
                        'username' => $this->dbUser,
                        'user' => $this->dbUser,
                        'db_password' => $this->dbPass,
                        'password' => $this->dbPass,
                        'db_pass' => $this->dbPass,
                        'app_url' => $appUrl,
                        'url' => $appUrl,
                    ];
                    $before = json_encode($data);
                    $update = function (&$arr) use (&$update, $keysMap) {
                        foreach ($arr as $k => &$v) {
                            if (is_array($v)) {
                                $update($v);
                            } else {
                                $lk = strtolower((string)$k);
                                if (isset($keysMap[$lk])) {
                                    $v = $keysMap[$lk];
                                }
                            }
                        }
                    };
                    $update($data);
                    $after = json_encode($data);
                    if ($after !== $before) {
                        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    }
                }
            }

            // YAML config files: targeted line replacements for common keys
            if ($isYaml) {
                // Replace top-level or indented key: value
                $replYaml = [
                    // Top-level
                    '/^(\s*)db_host:\s*.*/mi' => "$1db_host: {$this->dbHost}",
                    '/^(\s*)host:\s*.*/mi' => "$1host: {$this->dbHost}",
                    '/^(\s*)db_name:\s*.*/mi' => "$1db_name: {$this->dbName}",
                    '/^(\s*)database:\s*.*/mi' => "$1database: {$this->dbName}",
                    '/^(\s*)db_user(name)?:\s*.*/mi' => "$1db_user: {$this->dbUser}",
                    '/^(\s*)user(name)?:\s*.*/mi' => "$1username: {$this->dbUser}",
                    '/^(\s*)db_pass(word)?:\s*.*/mi' => "$1db_password: {$this->dbPass}",
                    '/^(\s*)password:\s*.*/mi' => "$1password: {$this->dbPass}",
                    '/^(\s*)app_url:\s*.*/mi' => "$1app_url: {$appUrl}",
                    '/^(\s*)url:\s*.*/mi' => "$1url: {$appUrl}",
                ];
                foreach ($replYaml as $pattern => $rep) {
                    $content = preg_replace($pattern, $rep, $content);
                }
            }

            if ($content !== $orig) {
                @file_put_contents($path, $content);
                $changed++;
            }
        }
        return $changed;
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