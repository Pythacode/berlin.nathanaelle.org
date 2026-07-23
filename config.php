<?php


require_once $_SERVER['DOCUMENT_ROOT'] . "/res/php/mail.php";

function envoyerMailErreur($message) {
    $to = 'contact@nathanaelle.org';
    $subject = '[ERREUR 500] berlin.nathanaelle.org';
    $body = $message . "<br><br>URL : " . ($_SERVER['REQUEST_URI'] ?? 'N/A')
          . "<br>Date : " . date('Y-m-d H:i:s');
    send_mail($body, [], $subject, $to, "alertes");
}

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        envoyerMailErreur($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/error_pages/500.php');
        exit;
    }
});

set_exception_handler(function($e) {
    envoyerMailErreur($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/error_pages/500.php');
    exit;
});

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Game\GameServer;
use App\Game\GameManager;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT']);

$DB_HOST = $_ENV["DB_HOST"];
$DB_NAME = $_ENV["DB_NAME"];
$DB_USER = $_ENV["DB_USER"];
$DB_PASS = $_ENV["DB_PASS"];
$DB_PORT = $_ENV["DB_PORT"];

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
$conn->set_charset('utf8mb4');
            
if($conn->connect_error){
    die('Erreur : ' .$conn->connect_error);
}