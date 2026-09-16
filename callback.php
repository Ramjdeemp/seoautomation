<?php
require_once 'vendor/autoload.php';
session_start();
$client = new Google\Client();
$client->setAuthConfig('client_secret.json');
$client->setRedirectUri( 'http://localhost/seoautomation/callback.php' );
$client->addScope('openid');
$client->addScope('email');
$client->addScope('profile');
$client->addScope(
    Google\Service\SearchConsole::WEBMASTERS_READONLY
);
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if(isset($token['error'])){
        die('Error fetching access token: ' .  $token['error_description']);
    }    
    $_SESSION['authusertoken'] = $token;
    header('Location: dashboard.html');
    exit();
} else {
    echo "No authorization code found. Please log in first.";
}
exit();
?>