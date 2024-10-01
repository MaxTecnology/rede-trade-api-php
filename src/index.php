<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if (empty($_REQUEST['debug'])) header('content-type: application/json; charset=utf-8');

set_time_limit(20);
date_default_timezone_set('America/Sao_Paulo');

if (!empty($_REQUEST['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require __DIR__ . "/Core/bootstrap.php";

use Core\App;
use Core\Router;
use Core\Env;
use Core\Model;

Env::load();

$app = new App();
$router = new Router();
$model = new Model();

$app->build();
$router->build();

echo json_encode([
    "next" => true,
    "message" => "Api",
    "payload" => [],
]);