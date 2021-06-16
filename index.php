<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment vars into memory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SECRET_PASS']);

require __DIR__ . '/controller/main.php';
