<?php

namespace App;

class Application
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../Config/Database.php';
    }

    public function run()
    {
        // Initialize application logic here
        // For example, you could set up routing or handle requests
    }

    public function getConfig()
    {
        return $this->config;
    }
}