<?php
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_set_cookie_params([
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$client = new Google\Client();
$client->setAuthConfig('client_secret.json');
$client->setRedirectUri('http://localhost/seoautomation/callback.php');
$client->addScope('openid');
$client->addScope('email');
$client->addScope('profile');
$client->addScope(Google\Service\SearchConsole::WEBMASTERS_READONLY);

if (!isset($_GET['code'])) {
    die("Authorization failed");
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    die("OAuth error");
}

// --- SECURE ENCRYPTION MATCHING SEOAUTOMATOR.PHP ---
$rawPassphrase = $_ENV['APP_ENCRYPTION_KEY'];
$encryptionKey = hash('sha256', $rawPassphrase, true);
$tokenString = json_encode($token);

$iv = random_bytes(12);
$ciphertext = openssl_encrypt($tokenString, 'aes-256-gcm', $encryptionKey, OPENSSL_RAW_DATA, $iv, $tag);

// Save using the exact keys seoautomator.php expects
$_SESSION['authusertoken_cipher'] = $ciphertext;
$_SESSION['authusertoken_iv'] = bin2hex($iv);
$_SESSION['authusertoken_tag'] = bin2hex($tag);
// ---------------------------------------------------

header("Location: dashboard.html");
exit();
?>