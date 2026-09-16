<?php
    require_once 'vendor/autoload.php';
    session_start();
    $client = new Google\Client();
    $client->setAuthConfig('client_secret.json');
    $client->setRedirectUri( 'http://localhost/seoautomation/callback.php' );
    $client->addScope('openid');
    $client->addScope('email');
    $client->addScope('profile');
    $client->addScope(Google\Service\SearchConsole::WEBMASTERS_READONLY);
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit();
?>