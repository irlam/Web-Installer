<?php

namespace App;

use Config\Database;

class Installer
{
    private $dbUser;
    private $dbPass;
    private $dbHost;
    private $dbName;
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

    public function runInstallation()
    {
        try {
            // Connect to DB
            $db = new Database($this->dbHost, $this->dbName, $this->dbUser, $this->dbPass);

            // Run schema
            $schemaPath = __DIR__ . '/../../sql/schema.sql';
            if (file_exists($schemaPath)) {
                $sql = file_get_contents($schemaPath);
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

    private function updateFiles($dir, $domain, $subdomains)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
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

    // Legacy methods for CLI
    public function promptForCredentials()
    {
        echo "Please enter your SQL username: ";
        $this->dbUser = trim(fgets(STDIN));

        echo "Please enter your SQL password: ";
        $this->dbPass = trim(fgets(STDIN));
    }

    public function getDbUser()
    {
        return $this->dbUser;
    }

    public function getDbPass()
    {
        return $this->dbPass;
    }
}