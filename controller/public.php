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

$router->get('/health', function() {
    exit(200);
});

$router->get('/mapinfo', function() {
    $result = [];
    try {
        $skip = STUtils::queryToInt($_REQUEST, 'skip', true, 0) ?? 0;
        $limit = STUtils::queryToInt($_REQUEST, 'limit', true, 1, 60) ?? 20;

        $db = STDatabase::getInstance();
        // Skip and limit are guaranteed to be safe integers, no need for prepare
        $stmt = $db->query("SELECT id FROM all_maps LIMIT {$skip}, {$limit}");
        if (!$stmt) throw new Exception("Could not execute SQL statement.");

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $mapId = $row[0];
            $map = new CommonMap();
            $map->id = $mapId;
            $map->load();

            $result[] = $map->exportRESTObj();
        }
    } catch (Exception $e) {
        $jsonArray = array();
        $jsonArray['status'] = "400";
        $jsonArray['status_text'] = $e->getMessage();

        header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode($jsonArray);
        exit(400);
    }

    header('Content-Type: application/json');
    echo json_encode(['result' => $result]);
});

$router->get('/mapinfo/count', function() {
    $ret = null;

    try {
        $db = STDatabase::getInstance();
        $stmt = $db->query("SELECT 
            (SELECT COUNT(*) FROM all_maps) AS total,
            (SELECT COUNT(*) FROM all_maps a INNER JOIN mapdb_spiritual b ON a.id = b.id) AS totalSpiritual,
            (SELECT COUNT(*) FROM all_maps a INNER JOIN mapdb_divinity b ON a.id = b.id) AS totalDivinity;");
        if (!$stmt) throw new Exception("Could not execute SQL statement.");

        $ret = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $jsonArray = array();
        $jsonArray['status'] = "400";
        $jsonArray['status_text'] = $e->getMessage();

        header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode($jsonArray);
        exit(400);
    }

    header('Content-Type: application/json');
    echo json_encode($ret, JSON_NUMERIC_CHECK);
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

$router->get('/divinity/mapinfo/(\d+)', function($mapId) use ($router) {
    $mapId = (int)$mapId;
    $divmap = new DivinityMap(new CommonMap($mapId));
    try {
        $divmap->load();
    } catch (Exception $e) {
        $router->trigger404();
        return;
    }

    header('Content-Type: application/json');
    echo json_encode($divmap->exportRESTObj());
});

$router->get('/spiritual/mapinfo/(\d+)', function($mapId) use ($router) {
    $mapId = (int)$mapId;
    $sprmap = new SpiritualMap(new CommonMap($mapId));
    try {
        $sprmap->load();
    } catch (Exception $e) {
        $router->trigger404();
        return;
    }

    header('Content-Type: application/json');
    echo json_encode($sprmap->exportRESTObj());
});
