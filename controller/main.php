<?php
require_once __DIR__ . '/../STRouter.php';

$router = STRouter::getInstance();

// Define routes
$router->set400(function() {
    $jsonArray = array();
    $jsonArray['status'] = "400";
    $jsonArray['status_text'] = "Bad Request";

    header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode($jsonArray);
});

$router->set403(function() {
    $jsonArray = array();
    $jsonArray['status'] = "403";
    $jsonArray['status_text'] = "You do not have the privileges to use this API function.";

    header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
    header('Content-Type: application/json');
    echo json_encode($jsonArray);
});

$router->set404(function() {
    $jsonArray = array();
    $jsonArray['status'] = "404";
    $jsonArray['status_text'] = "Invalid API function or parameter.";

    header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
    header('Content-Type: application/json');
    echo json_encode($jsonArray);
});

// Include route definitions
require __DIR__ . '/public.php';
require __DIR__ . '/helper.php';

// Run it
$router->run();

