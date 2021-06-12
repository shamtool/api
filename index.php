<?php
require __DIR__ . '/vendor/autoload.php';

// Create Router instance
$router = new \Bramus\Router\Router();

// Define routes
$router->get('/', function() {
    echo 'Hello worlds';
});

// Run it!
$router->run();
