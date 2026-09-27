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
    $client->setAccessType("offline");
    $client->setPrompt("consent");
    $state = bin2hex(random_bytes(32));
    $_SESSION["oauth_state"]=$state;
    $client->setState($state);
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit();
?>