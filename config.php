<?php

ini_set('session.cookie_httponly', 1); // empêche JS (donc XSS) de lire le cookie
ini_set('session.cookie_secure', 1);   // cookie envoyé seulement en HTTPS
ini_set('session.cookie_samesite', 'Strict'); // protection CSRF

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