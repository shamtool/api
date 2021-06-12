<?php
// Primitive includes!
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
$dotenv->load();

// Normal includes
require __DIR__ . '/Database.php';
$db = STDatabase::getInstance();

// Create Router instance
$router = new \Bramus\Router\Router();

// Define routes
$router->get('/', function() {
    global $db;
    echo sprintf('Hello worlds! There was been currently %s recorded over the databased.',
        $db->query("SELECT COUNT(*) FROM divmapInfo"));
});

// Run it!
$router->run();
