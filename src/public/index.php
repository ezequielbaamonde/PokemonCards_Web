<?php
/* DEPENDENCIAS IMPORTADAS DEL PSR de SLIM */
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php'; //COLOCAR PUNTOS PARA DIRIGIRME AL DIRECTORIO VENDOR.

$app = AppFactory::create(); //Crea la app (El Core)

$app->get('/', function (Request $request, Response $response, $args) { //El string del argumento es el LOCALHOST de la APP.
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->run(); //Corre la APP.
/* No es necesario cerrar la etiqueta PHP para ejecutar este codigo */

