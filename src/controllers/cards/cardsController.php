<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../../middlewares/JwtMiddleware.php'; // importar el middleware

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/* MÉTODO POST DE /mazos */
$app->post('/mazos', function (Request $request, Response $response) {
    $db = DB::getConnection();
    $data = $request->getParsedBody();

    $nombre = $data['nombre'] ?? null;
    $cartas = $data['cartas'] ?? [];

    if (!$nombre || !is_array($cartas)) {
        $response->getBody()->write(json_encode(["error' => 'Debe enviar un nombre y 5 id's de cartas."]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    if (count($cartas) !== 5) {
        $response->getBody()->write(json_encode(['error' => 'Debe seleccionar exactamente 5 cartas.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    if (count(array_unique($cartas)) !== 5) {
        $response->getBody()->write(json_encode(['error' => 'No puede haber cartas repetidas.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $jwt = $request->getAttribute('jwt');
    $usuarioId = $jwt->sub;

    // Verificar cantidad de mazos existentes del usuario
    $stmt = $db->prepare("SELECT COUNT(*) FROM mazo WHERE usuario_id = :usuarioId");
    $stmt->bindParam(':usuarioId', $usuarioId);
    $stmt->execute();
    $cantidad = $stmt->fetchColumn();

    if ($cantidad >= 3) {
        $response->getBody()->write(json_encode(['error' => 'El usuario ya tiene 3 mazos.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Verificar que todas las cartas existan
    $placeholders = implode(',', array_fill(0, count($cartas), '?'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM carta WHERE id IN ($placeholders)");
    $stmt->execute($cartas);
    $cartasExistentes = $stmt->fetchColumn();

    if ($cartasExistentes != 5) {
        $response->getBody()->write(json_encode(['error' => 'Alguna de las cartas no existe. Corroborar nuevamente el
                                                             id de las cartas.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Crear el mazo
    $stmt = $db->prepare("INSERT INTO mazo (usuario_id, nombre) VALUES (:usuarioId, :nombre)");
    $stmt->bindParam(':usuarioId', $usuarioId);
    $stmt->bindParam(':nombre', $nombre);

    if (!$stmt->execute()) {
        $response->getBody()->write(json_encode(['error' => 'No se pudo crear el mazo.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $mazoId = $db->lastInsertId();

    // Insertar las cartas en mazo_carta
    $stmt = $db->prepare("INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES (:mazoId, :cartaId, 'en_mazo')");
    foreach ($cartas as $cartaId) {
        $stmt->bindParam(':mazoId', $mazoId);
        $stmt->bindParam(':cartaId', $cartaId);
        $stmt->execute();
    }

    $response->getBody()->write(json_encode([
        'id_mazo' => $mazoId,
        'nombre' => $nombre
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

})->add($jwtMiddleware);

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/* Eliminar mazo */
$app->delete('/mazos/{mazo}', function (Request $request, Response $response, $args) {
    $db = DB::getConnection();
    $idMazo = $args['mazo']; // ID del mazo a eliminar

    $jwt = $request->getAttribute('jwt');
    $idUsuario = $jwt->sub;

    // Verificar que el mazo pertenezca al usuario
    $stmt = $db->prepare("SELECT * FROM mazo WHERE id = :idMazo AND usuario_id = :idUsuario");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->bindParam(':idUsuario', $idUsuario);
    $stmt->execute();
    $mazo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mazo) {
        $response->getBody()->write(json_encode(['error' => 'Mazo no encontrado o no pertenece al usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Verificar que el mazo no haya sido usado en partidas
    $stmt = $db->prepare("SELECT COUNT(*) FROM partida WHERE mazo_id = :idMazo");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->execute();
    $cantidad = $stmt->fetchColumn();

    if ($cantidad > 0) {
        $response->getBody()->write(json_encode(['error' => 'No se puede eliminar el mazo porque ya fue usado en partidas anteriores']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    // Eliminar relaciones en mazo_carta
    $stmt = $db->prepare("DELETE FROM mazo_carta WHERE mazo_id = :idMazo");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->execute();

    // Eliminar el mazo
    $stmt = $db->prepare("DELETE FROM mazo WHERE id = :idMazo");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->execute();

    $response->getBody()->write(json_encode(['mensaje' => 'Mazo eliminado correctamente']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
})->add($jwtMiddleware);

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

// Ver mazos creados por el usuario (con nombre de usuario en la URL)
$app->get('/usuarios/{usuario}/mazos', function (Request $request, Response $response, array $args) {
    $db = DB::getConnection();
    $nombreUser = $args['usuario'];

    // Extraer ID del usuario logueado desde el token
    $jwt = $request->getAttribute('jwt');
    $usuarioId = $jwt->sub;

    // Obtener el nombre real del usuario desde la base de datos
    $stmt = $db->prepare("SELECT usuario FROM usuario WHERE id = :id");
    $stmt->bindParam(':id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $nombreReal = $stmt->fetchColumn();

    // Validar que el nombre en la URL coincida con el del usuario logueado
    if (!$nombreReal || strtolower($nombreReal) !== strtolower($nombreUser)) {
        $response->getBody()->write(json_encode(['error' => 'Acceso no autorizado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // Obtener los mazos del usuario
    $stmt = $db->prepare("SELECT id, nombre FROM mazo WHERE usuario_id = :usuarioId");
    $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $mazos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($mazos)) {
        $response->getBody()->write(json_encode(['mensaje' => 'El usuario no tiene mazos creados']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode(['mazos' => $mazos]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
})->add($jwtMiddleware);

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/* Cambiar nombre de mazo */
$app->put('/mazos/{mazo}', function (Request $request, Response $response, array $args) {
    $db = DB::getConnection();
    $mazoId = (int) $args['mazo'];

    $jwt = $request->getAttribute('jwt');
    $usuarioId = $jwt->sub;

    $data = $request->getParsedBody();
    $nuevoNombre = trim($data['nombre'] ?? '');

    if ($nuevoNombre === '') {
        $response->getBody()->write(json_encode(['error' => 'El nombre del mazo no puede estar vacío']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Verificar que el mazo pertenezca al usuario logueado
    $stmt = $db->prepare("SELECT * FROM mazo WHERE id = :mazoId AND usuario_id = :usuarioId");
    $stmt->bindParam(':mazoId', $mazoId, PDO::PARAM_INT);
    $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch()) {
        $response->getBody()->write(json_encode(['error' => 'El mazo no pertenece al usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // Actualizar el nombre del mazo
    $stmt = $db->prepare("UPDATE mazo SET nombre = :nuevoNombre WHERE id = :mazoId");
    $stmt->bindParam(':nuevoNombre', $nuevoNombre);
    $stmt->bindParam(':mazoId', $mazoId);
    $stmt->execute();

    $response->getBody()->write(json_encode([
        'message' => 'Nombre del mazo actualizado correctamente',
        'mazo_id' => $mazoId,
        'nuevo_nombre' => $nuevoNombre
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
})->add($jwtMiddleware);

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/*  
    /cartas?atributo={atributo}&nombre={nombre}
    /cartas?atributo={atributo}
    /cartas?nombre={nombre}
    
Listar  las  cartas  según  los parámetros de búsqueda incluyendo los puntos de ataque. */
$app->get('/cartas', function (Request $request, Response $response) {
    $db = DB::getConnection();

    $params = $request->getQueryParams(); // Obtener los parámetros de búsqueda
    $atributo = $params['atributo'] ?? null;
    $nombre = $params['nombre'] ?? null;

    $sql = "SELECT c.id, c.nombre, c.ataque, c.ataque_nombre, c.imagen, a.nombre AS atributo 
            FROM carta c 
            INNER JOIN atributo a ON c.atributo_id = a.id 
            WHERE 1=1";

    $queryParams = []; // Inicializar un array para los parámetros de la consulta

    if ($atributo !== null) {
        $sql .= " AND a.nombre LIKE :atributo";
        $queryParams[':atributo'] = "%$atributo%";
    }

    if ($nombre !== null) {
        $sql .= " AND c.nombre LIKE :nombre";
        $queryParams[':nombre'] = "%$nombre%";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($queryParams);
    $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartas)) {
        $response->getBody()->write(json_encode(['mensaje' => 'No se encontraron cartas que coincidan con los criterios de búsqueda']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode($cartas));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});