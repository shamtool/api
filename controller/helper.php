<?php
require_once __DIR__ . '/../STRouter.php';
require_once __DIR__ . '/../STUtils.php';
require_once __DIR__ . '/../common/Map.php';

$router = STRouter::getInstance();

$router->before('GET|POST', '/helper(/.*)?', function() use ($router) {
    // TODO: Temporarily restrict using secret password
    if (($_REQUEST['secret_pass'] ?? null) != $_ENV['SECRET_PASS']) {
        $router->trigger403(true);
    }
});

$router->mount('/helper', function() use ($router) {
    $router->get('/divinity/addMap', function() use ($router) {
        /** @var DivinityMap */
        $div_map = null;
        try {
            $common_map = new CommonMap();
            // Common props
            $props = [
                'mapCode'  => STUtils::queryToMapCode($_REQUEST, "mapCode", false),
                'author'   => STUtils::queryToString($_REQUEST, "author"),
                'xml'      => STUtils::queryToString($_REQUEST, "xml"),
                'wind'     => STUtils::queryToFloat($_REQUEST, "wind"),
                'gravity'  => STUtils::queryToFloat($_REQUEST, "gravity"),
                'mgoc'     => STUtils::queryToFloat($_REQUEST, "mgoc"),
                'imageUrl' => STUtils::queryToString($_REQUEST, "imageUrl")
            ];
            foreach ($props as $prop => $val) {
                $common_map->update($prop, $val);
            }

            $div_map = new DivinityMap($common_map);
            $props = [
                'mapCode'  => STUtils::queryToMapCode($_REQUEST, "mapCode", false),
                'author'   => STUtils::queryToString($_REQUEST, "author"),
                'xml'      => STUtils::queryToString($_REQUEST, "xml"),
                'wind'     => STUtils::queryToFloat($_REQUEST, "wind"),
                'gravity'  => STUtils::queryToFloat($_REQUEST, "gravity"),
                'mgoc'     => STUtils::queryToFloat($_REQUEST, "mgoc"),
                'imageUrl' => STUtils::queryToString($_REQUEST, "imageUrl")
            ];
            foreach ($props as $prop => $val) {
                $div_map->update($prop, $val);
            }

            // Save both common and divinity
            $div_map->save();
        } catch (Exception $e) {
            $jsonArray = array();
            $jsonArray['status'] = "403";
            $jsonArray['status_text'] = $e->getMessage();

            header('{$_SERVER["SERVER_PROTOCOL"]} 403 Bad Request');
            header('Content-Type: application/json');
            echo json_encode($jsonArray);
            exit(400);
        }

        header('Content-Type: application/json');
        echo json_encode(['id' => $div_map->commonMap->id]);
    });

    $router->get('/spiritual/addMap', function() {
        echo 'todo: adds spr';
    });
});
