<?php
require_once __DIR__ . '/../STDatabase.php';
require_once __DIR__ . '/../STRouter.php';
require_once __DIR__ . '/../STUtils.php';

$db = STDatabase::getInstance();
$router = STRouter::getInstance();

$router->before('GET|POST', '/helper(/.*)?', function() use ($router) {
    // TODO: Temporarily restrict using secret password
    if (($_REQUEST['secret_pass'] ?? null) != $_ENV['SECRET_PASS']) {
        $router->trigger403(true);
    }
});

$router->mount('/helper', function() use ($router) {
    $router->get('/divinity/addMap', function() use ($router) {
        $map = new CommonMap();
        try {
            $map->mapCode = STUtils::parseMapCode(STUtils::queryToString($_REQUEST, "mapCode", false));
            // TODO: ..
        } catch (Exception $e) {
            $jsonArray = array();
            $jsonArray['status'] = "400";
            $jsonArray['status_text'] = $e->getMessage();

            header('{$_SERVER["SERVER_PROTOCOL"]} 403 Bad Request');
            header('Content-Type: application/json');
            echo json_encode($jsonArray);
            exit(400);
        }

        try {
            $map->load();
        } catch (Exception $e) {
            $router->trigger404();
            return;
        }
    
        header('Content-Type: application/json');
        echo json_encode($map->exportRESTObj());
    });

    $router->get('/spiritual/addMap', function() {
        echo 'todo: adds spr';
    });
});
