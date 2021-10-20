<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/DiscordOAuth.php';

// Load environment vars into memory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SECRET_PASS']);

$provider = new DiscordOAuth(
    $_ENV['DCORD_ID'],
    $_ENV['DCORD_SECRET'],
    "http://localhost:6969/oauth2/discord.php"
);

try {
    if (!isset($_GET['code'])) {
        echo '<a href="'.$provider->getOAuthUrl().'">Login with Discord</a>';
    } else {
        $provider->exchangeAccessToken($_GET['code']);

        // Get the user object.
        $user = $provider->getBasicUser();
        printf('Hello %s#%s!', $user->username, $user->discriminator);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}