<?php
require_once __DIR__ . '/../STDatabase.php';
require_once __DIR__ . '/../STRouter.php';

$db = STDatabase::getInstance();
$router = STRouter::getInstance();

$router->get('/', function() use ($db) {
    $cnt = $db->query("SELECT COUNT(*) FROM all_maps")->fetch()[0];
    echo sprintf('Hello worlds! There was been currently %s recorded over the databased.', $cnt);
});
