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

/** Helper function to populate common map object from query parameters. */
function updateCommonMapFromReq($common_map, $map_code) {
    $props = [
        'mapCode'  => $map_code,
        'author'   => STUtils::queryToString($_REQUEST, "author"),
        'xml'      => STUtils::queryToString($_REQUEST, "xml"),
        'wind'     => STUtils::queryToFloat($_REQUEST, "wind"),
        'gravity'  => STUtils::queryToFloat($_REQUEST, "gravity"),
        'mgoc'     => STUtils::queryToFloat($_REQUEST, "mgoc"),
        'imageUrl' => STUtils::queryToString($_REQUEST, "imageUrl"),
    ];
    foreach ($props as $prop => $val) {
        $common_map->update($prop, $val);
    }
}

$router->mount('/helper', function() use ($router) {
    $router->get('/divinity/addMap', function() use ($router) {
        $common_map = new CommonMap();
        $div_map = new DivinityMap($common_map);
        try {
            // Check if the mapcode exists
            $map_code = STUtils::queryToMapCode($_REQUEST, "mapCode", false);
            if (($id = CommonMap::findIdByMapCode($map_code)) != null) {
                error_log($id);
                // Map exists, check if it exists as a divinity map
                $common_map->id = $id;
                if ($div_map->idExists())
                    throw new Exception("The mapCode supplied ({$map_code}) already exists as Divinity.");
                else
                    $common_map->load();
            } else {
                // Map doesn't exist, set it up with common props
                updateCommonMapFromReq($common_map, $map_code);
            }

            $props = [
                'difficulty'  => STUtils::queryToInt($_REQUEST, "difficulty", false, 1, 7),
                'category'    => STUtils::queryToInt($_REQUEST, "category", false, 1, 2),
                'cage'        => STUtils::queryToBool($_REQUEST, "cage"),
                'noAnchor'    => STUtils::queryToBool($_REQUEST, "noAnchor"),
                'noMotor'     => STUtils::queryToBool($_REQUEST, "noMotor"),
                'water'       => STUtils::queryToBool($_REQUEST, "water"),
                'timer'       => STUtils::queryToBool($_REQUEST, "timer"),
                'noBalloon'   => STUtils::queryToBool($_REQUEST, "noBalloon"),
                'opportunist' => STUtils::queryToBool($_REQUEST, "opportunist"),
            ];
            foreach ($props as $prop => $val) {
                $div_map->update($prop, $val);
            }

            // Save both common and divinity
            $div_map->save();
        } catch (Exception $e) {
            $jsonArray = array();
            $jsonArray['status'] = "400";
            $jsonArray['status_text'] = $e->getMessage();

            header("{$_SERVER['SERVER_PROTOCOL']} 403 Bad Request");
            header('Content-Type: application/json');
            echo json_encode($jsonArray);
            exit(400);
        }

        header('Content-Type: application/json');
        echo json_encode(['id' => $div_map->commonMap->id]);
    });

    $router->get('/spiritual/addMap', function() {
        $common_map = new CommonMap();
        $spi_map = new SpiritualMap($common_map);
        try {
            // Check if the mapcode exists
            $map_code = STUtils::queryToMapCode($_REQUEST, "mapCode", false);
            if (($id = CommonMap::findIdByMapCode($map_code)) != null) {
                error_log($id);
                // Map exists, check if it exists as a spiritual map
                $common_map->id = $id;
                if ($spi_map->idExists())
                    throw new Exception("The mapCode supplied ({$map_code}) already exists as Spiritual.");
                else
                    $common_map->load();
            } else {
                // Map doesn't exist, set it up with common props
                updateCommonMapFromReq($common_map, $map_code);
            }

            $props = [
                'difficulty' => STUtils::queryToInt($_REQUEST, "difficulty", false, 1, 10),
                'cage'       => STUtils::queryToBool($_REQUEST, "cage"),
                'noAnchor'   => STUtils::queryToBool($_REQUEST, "noAnchor"),
                'noMotor'    => STUtils::queryToBool($_REQUEST, "noMotor"),
                'water'      => STUtils::queryToBool($_REQUEST, "water"),
                'timer'      => STUtils::queryToBool($_REQUEST, "timer"),
                'noB'        => STUtils::queryToBool($_REQUEST, "noB"),
            ];
            foreach ($props as $prop => $val) {
                $spi_map->update($prop, $val);
            }

            // Save both common and spiritual
            $spi_map->save();
        } catch (Exception $e) {
            $jsonArray = array();
            $jsonArray['status'] = "400";
            $jsonArray['status_text'] = $e->getMessage();

            header("{$_SERVER['SERVER_PROTOCOL']} 403 Bad Request");
            header('Content-Type: application/json');
            echo json_encode($jsonArray);
            exit(400);
        }

        header('Content-Type: application/json');
        echo json_encode(['id' => $spi_map->commonMap->id]);
    });
});
