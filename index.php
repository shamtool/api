<?php
// Primitive includes!
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SECRET_PASS']);

// Normal includes
require __DIR__ . '/Database.php';
$db = STDatabase::getInstance();

// Create Router instance
$router = new \Bramus\Router\Router();

// Define routes
$router->set404(function() {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');

    $jsonArray = array();
    $jsonArray['status'] = "404";
    $jsonArray['status_text'] = "Invalid API function.";

    echo json_encode($jsonArray);
});

$router->before('GET|POST', '/helper(/.*)?', function() use ($router) {
    // TODO: Temporarily restrict using secret password
    if ($_REQUEST['secret_pass'] != $_ENV['SECRET_PASS']) {
        $jsonArray = array();
        $jsonArray['status'] = "403";
        $jsonArray['status_text'] = "You do not have the privileges to use this API function.";

        header('Content-Type: application/json');
        echo json_encode($jsonArray);
        exit(403);
    }
});

$router->mount('/helper', function() use ($router) {
    $router->get('/add/divinity', function() {
        echo 'todo: adds div';
    });

    $router->get('/add/spiritual', function() {
        echo 'todo: adds spr';
    });
});

$router->get('/', function() use ($db) {
    $cnt = $db->query("SELECT COUNT(*) FROM all_maps")->fetch()[0];
    echo sprintf('Hello worlds! There was been currently %s recorded over the databased.', $cnt);
});

// Run it!
$router->run();
