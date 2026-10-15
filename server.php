<?php

// Routeur du serveur de développement (`php artisan serve` l'utilise automatiquement s'il existe).
// Différence avec celui de Laravel : seuls les vrais fichiers de public/ sont servis directement.
// Les dossiers (ex. public/admin, qui contient les images envoyées) passent par Laravel :
// sinon « /admin » affiche « Not Found » au lieu d'atteindre la route correspondante.

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
