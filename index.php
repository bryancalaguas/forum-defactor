<?php
declare(strict_types=1);


// Front controller: the ONLY public PHP entry point.


// When using PHP's built-in server, let it serve real files (CSS, images) directly.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}


require dirname(__DIR__) . '/vendor/autoload.php';


use App\Session;
use Bramus\Router\Router;


Session::start();


$router = new Router();
(require dirname(__DIR__) . '/config/routes.php')($router);
$router->run();