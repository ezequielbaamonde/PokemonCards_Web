<?php

/* DEPENDENCIAS IMPORTADAS DEL PSR de SLIM */
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
// Permitir peticiones desde tu frontend (ajusta el dominio según sea necesario)
header("Access-Control-Allow-Origin: http://localhost:5173");
// Permitir métodos HTTP específicos
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// Permitir encabezados personalizados, como Authorization (JWT) si es necesario
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Permitir que las cookies o encabezados de autenticación se envíen (opcional)
header("Access-Control-Allow-Credentials: true");


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
