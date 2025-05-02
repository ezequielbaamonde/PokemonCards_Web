<?php

/* DEPENDENCIAS IMPORTADAS DEL PSR de SLIM */
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;


require __DIR__ . '/../../vendor/autoload.php'; //COLOCAR PUNTOS PARA DIRIGIRME AL DIRECTORIO VENDOR.
require_once __DIR__ . '/../config/db_Connect.php'; //Conexión a la base de datos.

$app = AppFactory::create(); //Crea la app (El Core)

// Add routing and body parsing middleware
$app->addRoutingMiddleware();

/*Facilita la extracción de datos en formato json del body de cada service con $data = $request->getParsedBody()*/
$app->addBodyParsingMiddleware();

/*Manejo de errores detallado en la app.*/
$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Hello World'); //Escribe en el body de la respuesta;
    return $response;
});

require_once __DIR__ . '/../routes/routes.php'; //Importa las rutas de la app.

$app->run(); //Corre la APP.
