<?php

namespace App;

class Installer
{
    private $dbUser;
    private $dbPass;

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

    public function getDbUser()
    {
        return $this->dbUser;
    }

    public function getDbPass()
    {
        return $this->dbPass;
    }
}