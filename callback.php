<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
session_set_cookie_params([
    'path' => '/',
    'domain' => '',           // Current domain
    'secure' => false,        // Set to TRUE when you deploy to a live HTTPS server!
    'httponly' => true,       // CRITICAL: Blocks JavaScript/XSS access
    'samesite' => 'Lax'    // CRITICAL: Blocks CSRF attacks
]);
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
if (!isset($_GET['code'])) {
    http_response_code(400);
    die("Authorization failed");
}
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    die("OAuth error");
}
$rawPassphrase = $_ENV['APP_ENCRYPTION_KEY'];
$encryptionKey = hash('sha256', $rawPassphrase, true);
$ivlength = openssl_cipher_iv_length('aes-256-gcm');
$iv = openssl_random_pseudo_bytes($ivlength);
$tokenString = json_encode($token);
$encryptedToken = openssl_encrypt($tokenString, 'aes-256-gcm', $encryptionKey, 0, $iv, $tag);
$_SESSION['authusertoken_cipher'] = $encryptedToken;
$_SESSION['authusertoken_iv'] = bin2hex($iv);
$_SESSION['authusertoken_tag'] = bin2hex($tag);
header("Location: dashboard.html");
exit(); 
?>