<?php
require_once __DIR__ . '/../STDatabase.php';
require_once __DIR__ . '/../STRouter.php';
require_once __DIR__ . '/../STUtils.php';
require_once __DIR__ . '/../common/Map.php';

$router = STRouter::getInstance();

$router->get('/', function() {
    $db = STDatabase::getInstance();
    $cnt = $db->query("SELECT COUNT(*) FROM all_maps")->fetch()[0];
    echo sprintf('Hello worlds! There was been currently %s recorded over the databased.', $cnt);
});

$router->get('/mapinfo/(\d+)', function($mapId) use ($router) {
    $mapId = (int)$mapId;
    $map = new CommonMap();
    $map->id = $mapId;
    try {
        $map->load();
    } catch (Exception $e) {
        $router->trigger404();
        return;
    }

    header('Content-Type: application/json');
    echo json_encode($map->exportRESTObj());
});

$router->get('/mapinfo/(\d+)/xml', function($mapId) use ($router) {
    $mapId = (int)$mapId;
    $map = new CommonMap();
    $map->id = $mapId;
    try {
        $map->load();
    } catch (Exception $e) {
        $router->trigger404();
        return;
    }

    if ($map->xml == null) {
        $router->trigger404();
        return;
    }

    header('Content-Type: application/xml');
    echo $map->xml;
});

$router->get('/mapinfo/(\d+)', function($mapId) use ($router) {
    $mapId = (int)$mapId;
    $map = new CommonMap();
    $map->id = $mapId;
    try {
        $map->load();
    } catch (Exception $e) {
        $router->trigger404();
        return;
    }

    header('Content-Type: application/json');
    echo json_encode($map->exportRESTObj());
});
