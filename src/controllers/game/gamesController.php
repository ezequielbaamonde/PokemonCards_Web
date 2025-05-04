<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../../middlewares/JwtMiddleware.php'; // importar el middleware

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/*MÉTODO POST DE /PARTIDAS*/
$app->post('/partidas', function (Request $request, Response $response) {
    $db = DB::getConnection();

    $data = $request->getParsedBody();
    $idMazo = $data['id_mazo'] ?? null;

    if (!$idMazo) {
        $response->getBody()->write(json_encode(['error' => 'El ID del mazo es obligatorio']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $jwt = $request->getAttribute('jwt'); // Obtener el JWT del middleware
    $idUsuario = $jwt->sub; // Obtener el ID del usuario desde el JWT

    // Verificar que el mazo pertenece al usuario
    $stmt = $db->prepare("SELECT * FROM mazo WHERE id = :idMazo AND usuario_id = :idUsuario");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->bindParam(':idUsuario', $idUsuario);
    $stmt->execute();
    $mazo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mazo) {
        $response->getBody()->write(json_encode(['error' => 'El mazo no pertenece al usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    $fechaCreacion = (new DateTime('now', new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y-m-d H:i:s');
    $estado = 'en_curso';
    $now = '-';

    // Crear la partida
    $stmt = $db->prepare("INSERT INTO partida (usuario_id, el_usuario, fecha, mazo_id, estado) VALUES (:idUsuario, :elUsuario, :fecha, :idMazo, :estado)");
    $stmt->bindParam(':idUsuario', $idUsuario);
    $stmt->bindParam(':elUsuario', $now); // Empató, perdió o ganó | Principalmente nada.
    $stmt->bindParam(':fecha', $fechaCreacion);
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->bindParam(':estado', $estado);

    if (!$stmt->execute()) {
        $response->getBody()->write(json_encode([
            'error' => 'Error al crear la partida',
            'detalles' => $stmt->errorInfo()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $idPartida = $db->lastInsertId(); // Obtener el ID de la partida creada

    // Actualizar estado de las cartas en mazo_carta
    $stmt = $db->prepare("UPDATE mazo_carta SET estado = 'en_mano' WHERE mazo_id = :idMazo");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->execute();

    // Actualizar estado de las cartas en mazo_carta del servidor
    $stmt = $db->prepare("
        UPDATE mazo_carta
        SET estado = 'en_mano'
        WHERE mazo_id IN (SELECT id FROM mazo WHERE usuario_id = 1)
    ");
    $stmt->execute();


    // Buscamos las cartas asociadas al mazo
    //mc es un alias para mazo_carta y c es un alias para carta
    // Se hace un INNER JOIN para obtener las cartas que están en el mazo
    $stmt = $db->prepare("
        SELECT c.*
        FROM mazo_carta mc
        INNER JOIN carta c ON mc.carta_id = c.id
        WHERE mc.mazo_id = :idMazo
    ");
    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->execute();
    $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode([
        'message' => 'Partida creada exitosamente',
        'id_partida' => $idPartida,
        'cartas' => $cartas
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
})->add($jwtMiddleware); // Middleware para verificar el JWT y que el usuario se haya logeado correctamente

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/* MÉTODO POST DE /jugadas */
$app->post('/jugadas', function (Request $request, Response $response) {
    $db = DB::getConnection();
    $data = $request->getParsedBody();
    $idPartida = $data['id_partida'] ?? null;
    $idCartaJugador = $data['id_carta'] ?? null;

    if (!$idPartida || !$idCartaJugador) {
        $response->getBody()->write(json_encode(['error' => 'ID de partida y carta son obligatorios']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $jwt = $request->getAttribute('jwt');
    $idUsuario = $jwt->sub;

    $stmt = $db->prepare("
        SELECT p.id, p.usuario_id, p.mazo_id
        FROM partida p
        WHERE p.id = :idPartida AND p.usuario_id = :idUsuario AND p.estado = 'en_curso'
    ");
    $stmt->bindParam(':idPartida', $idPartida);
    $stmt->bindParam(':idUsuario', $idUsuario);
    $stmt->execute();
    $partida = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partida) {
        $response->getBody()->write(json_encode(['error' => 'Partida no encontrada o no válida']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $stmt = $db->prepare("
        SELECT mc.carta_id, c.ataque, c.atributo_id
        FROM mazo_carta mc
        JOIN carta c ON mc.carta_id = c.id
        WHERE mc.carta_id = :idCartaJugador AND mc.mazo_id = :idMazo AND mc.estado = 'en_mano'
    ");
    $stmt->bindParam(':idCartaJugador', $idCartaJugador);
    $stmt->bindParam(':idMazo', $partida['mazo_id']);
    $stmt->execute();
    $cartaJugador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartaJugador) {
        $response->getBody()->write(json_encode(['error' => 'La carta no pertenece al mazo o ya fue usada']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $idCartaServidor = jugadaServidor();

    $stmt = $db->prepare("
        SELECT c.id, c.ataque, c.atributo_id
        FROM carta c
        WHERE c.id = :idCartaServidor
    ");
    $stmt->bindParam(':idCartaServidor', $idCartaServidor);
    $stmt->execute();
    $cartaServidor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Determinar el resultado de la jugada utilizando la función determinarResultado
    $datosResultado = determinarResultado($cartaJugador, $cartaServidor);

    // Para guardar en la BD
    $resultadoFinal = $datosResultado['resultado'];
    
    // Para devolver en el JSON
    $fuerzaJugador = $datosResultado['fuerza_jugador'];
    $fuerzaServidor = $datosResultado['fuerza_servidor'];

    $stmt = $db->prepare("
        INSERT INTO jugada (partida_id, carta_id_a, carta_id_b, el_usuario)
        VALUES (:idPartida, :idCartaJugador, :idCartaServidor, :resultado)
    ");
    $stmt->bindParam(':idPartida', $idPartida);
    $stmt->bindParam(':idCartaJugador', $idCartaJugador);
    $stmt->bindParam(':idCartaServidor', $idCartaServidor);
    $stmt->bindParam(':resultado', $resultadoFinal);
    $stmt->execute();

    // Actualizar el estado de la carta del jugador a 'descartado'
    $stmt = $db->prepare("
        UPDATE mazo_carta 
        SET estado = 'descartado' 
        WHERE carta_id = :idCartaJugador AND mazo_id = :mazoId
    ");
    $stmt->bindParam(':idCartaJugador', $idCartaJugador);
    $stmt->bindParam(':mazoId', $partida['mazo_id']);

    $stmt->execute();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM jugada WHERE partida_id = :idPartida
    ");
    $stmt->bindParam(':idPartida', $idPartida);
    $stmt->execute();
    $cantidadJugadas = (int) $stmt->fetchColumn();

    $ganadas = 0;
    $perdidas = 0;

    // Si se han jugado 5 rondas, contar las ganadas y perdidas
    if ($cantidadJugadas == 5) {
        $stmt = $db->prepare("
            SELECT el_usuario FROM jugada WHERE partida_id = :idPartida
        ");
        $stmt->bindParam(':idPartida', $idPartida);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($resultados as $res) {
            if ($res === 'gano') $ganadas++;
            if ($res === 'perdio') $perdidas++;
        }
    }

    //El round redondea a 2 decimales
    $data = [
        'carta_servidor' => $cartaServidor,
        'fuerza_usuario' => round($fuerzaJugador, 2),
        'fuerza_servidor' => round($fuerzaServidor, 2)
    ];

    // Si se han jugado 5 rondas, determinar el resultado final
    if ($cantidadJugadas == 5) {
        if ($ganadas > $perdidas) {
            $data['resultado_final'] = 'Usuario ganó la partida';
            $resultadoUsuario = 'gano';
        } elseif ($ganadas < $perdidas) {
            $data['resultado_final'] = 'Servidor ganó la partida';
            $resultadoUsuario = 'perdio';
        } else {
            $data['resultado_final'] = 'La partida terminó en empate';
            $resultadoUsuario = 'empato';
        }
        
        // Actualizar resultado de y de la partida el usuario
        $estadoFinal = 'finalizada';
        $stmt = $db->prepare("
            UPDATE partida 
            SET estado = :estadoFinal, el_usuario = :resultadoUsuario 
            WHERE id = :idPartida
        ");

        $stmt->bindParam(':estadoFinal', $estadoFinal);
        $stmt->bindParam(':resultadoUsuario', $resultadoUsuario);
        $stmt->bindParam(':idPartida', $idPartida);
        $stmt->execute();

        // Reiniciar el mazo del servidor
        $stmt = $db->prepare("
            UPDATE mazo_carta 
            SET estado = 'en_mazo' 
            WHERE mazo_id IN (SELECT id FROM mazo WHERE usuario_id = 1)
        ");
        $stmt->execute();
    }

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

}) -> add($jwtMiddleware); // Middleware para verificar el JWT y que el usuario se haya logeado correctamente

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/* Índica los  atributos  de  las  cartas que quedan en mano del usuario.  */
$app->get('/usuarios/{usuario}/partidas/{partida}/cartas', function (Request $request, Response $response, array $args) {
    $db = DB::getConnection();

    $idUsuario = (int) $args['usuario']; //id usuario
    $idPartida = (int) $args['partida'];

    // Validar que el usuario en el token sea el mismo del path o que sea el servidor
    $jwt = $request->getAttribute('jwt');
    
    if ($jwt->sub !== $idUsuario && $jwt->sub !== 1) {
        $response->getBody()->write(json_encode(['error' => 'Acceso no autorizado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // Obtener el mazo usado por el usuario en la partida
    $stmt = $db->prepare("SELECT mazo_id FROM partida WHERE id = :idPartida AND usuario_id = :idUsuario");
    $stmt->bindParam(':idPartida', $idPartida);
    $stmt->bindParam(':idUsuario', $idUsuario);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        $response->getBody()->write(json_encode(['error' => 'Partida no encontrada o no pertenece al usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $idMazo = $result['mazo_id'];

    // Obtener los atributos de las cartas en mano
    //El distinct es para eliminar las filas donde se repite el atributo.
    $stmt = $db->prepare("
        SELECT DISTINCT a.*
        FROM mazo_carta mc
        JOIN carta c ON mc.carta_id = c.id
        JOIN atributo a ON c.atributo_id = a.id
        WHERE mc.mazo_id = :idMazo AND mc.estado = 'en_mano'
    ");

    $stmt->bindParam(':idMazo', $idMazo);
    $stmt->execute();
    $atributos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode(['atributos' => $atributos]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
})->add($jwtMiddleware);

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

/* Estadisticas  */
$app->get('/estadistica', function (Request $request, Response $response) {
    $db = DB::getConnection();

    $sql = "
        SELECT 
            u.nombre AS usuario,
            COUNT(*) AS total_partidas,
            SUM(CASE WHEN p.el_usuario = 'gano' THEN 1 ELSE 0 END) AS partidas_ganadas,
            SUM(CASE WHEN p.el_usuario = 'perdio' THEN 1 ELSE 0 END) AS partidas_perdidas,
            SUM(CASE WHEN p.el_usuario = 'empato' THEN 1 ELSE 0 END) AS partidas_empatadas
        FROM usuario u
        JOIN partida p ON p.usuario_id = u.id
        GROUP BY u.id, u.nombre
        ORDER BY usuario
    ";

    $stmt = $db->query($sql);
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($estadisticas));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});