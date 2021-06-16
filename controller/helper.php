<?php
require_once __DIR__ . '/../STDatabase.php';
require_once __DIR__ . '/../STRouter.php';

$db = STDatabase::getInstance();
$router = STRouter::getInstance();

$router->before('GET|POST', '/helper(/.*)?', function() use ($router) {
    // TODO: Temporarily restrict using secret password
    if (($_REQUEST['secret_pass'] ?? null) != $_ENV['SECRET_PASS']) {
        $router->trigger403(true);
    }
});

$router->mount('/helper', function() use ($router) {
    $router->get('/divinity/addMap', function() {
        echo 'todo: adds div';
    });

    $router->get('/spiritual/addMap', function() {
        echo 'todo: adds spr';
    });
});
