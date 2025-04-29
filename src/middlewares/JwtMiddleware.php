<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response;

$jwtMiddleware = function ($request, $handler) {
    // Obtiene el encabezado Authorization de la solicitud
    $authHeader = $request->getHeaderLine('Authorization'); 

    // Verifica si el encabezado Authorization está presente y comienza con "Bearer "
    // Si no está presente, devuelve un error 401 (No autorizado)
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => 'Token requerido']));
        return $response->withStatus(401);
    }

    $token = str_replace('Bearer ', '', $authHeader); // Extrae el token del encabezado
    $secretKey = "1983temandealab"; // Clave secreta para decodificar el token (debe ser la misma que se usó para codificarlo)

    try {
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        $request = $request->withAttribute('jwt', $decoded);
        return $handler->handle($request);
    } catch (Exception $e) {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Token inválido',
            'detalle' => $e->getMessage() // <-- Mostramos el motivo exacto del error
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
    /*catch (Exception $e) {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => 'Token inválido']));
        return $response->withStatus(401);
    }*/
};