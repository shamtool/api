<?php
// Primitive includes!
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

// Normal includes
require __DIR__ . '/Database.php';
$db = STDatabase::getInstance();

// Create Router instance
$router = new \Bramus\Router\Router();

// Define routes
$router->get('/', function() {
    global $db;

    $cnt = $db->query("SELECT COUNT(*) FROM divmapInfo")->fetch()[0];
    echo sprintf('Hello worlds! There was been currently %s recorded over the databased.', $cnt);
});

// Run it!
$router->run();
