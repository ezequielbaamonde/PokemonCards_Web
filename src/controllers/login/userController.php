<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

// POST: Login de usuario
$app->post('/login', function (Request $request, Response $response) {
    $db = DB::getConnection(); // Conexión a la base de datos.
    $params = $request->getParsedBody(); // Obtiene los datos enviados en el cuerpo de la solicitud.
    // Extrae los parámetros de la solicitud
    //name = $params['nombre'] ?? null;
    $username = $params['usuario'] ?? null;
    $name = $params['nombre'] ?? null;
    $password = $params['password'] ?? null;

    // Validación de datos
    if (!$username || !$password || !$name) {
        $response->getBody()->write(json_encode(['error' => 'El usuario, el nombre y la clave son requeridos']));
        //return $response->withStatus(400);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $stmt = $db->prepare("SELECT * FROM usuario WHERE usuario = :username and nombre = :name"); // Prepara la consulta SQL para buscar el usuario por nombre y contraseña.
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':name', $name); // Vincula el parámetro :name a la variable $name.
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Busca el usuario en la base de datos.

    // Verifica si el usuario existe y si la contraseña es correcta usando password_verify - Genera TOKEN
    if ($user && password_verify($password, $user['password'])) {
        $secretKey = "1983temandealab";
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 hora

        //Establece la zona horaria a Buenos Aires
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $expirationDateTime = date('Y-m-d H:i:s', $expirationTime); //Convertimos a formato MySQL
    
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $user['id'],
            'username' => $user['usuario']
        ];
    
        $jwt = JWT::encode($payload, $secretKey, 'HS256'); // Clave secreta para codificar el token.
    
        // Guardamos el token y la expiración
        $stmt = $db->prepare("UPDATE usuario SET token = :token, vencimiento_token = :expiration WHERE id = :userId");
        $stmt->bindParam(':token', $jwt);
        $stmt->bindParam(':expiration', $expirationDateTime); // 🕒 Guardamos formato legible
        $stmt->bindParam(':userId', $user['id']);
        $stmt->execute();
    
        $response->getBody()->write(json_encode([
            'token' => $jwt,
            'message' => 'Login successful'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }else{
        $response->getBody()->write(json_encode(['error' => 'Invalid username or password']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
});

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

// POST: Crea un nuevo usuario.
$app->post('/registro', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $name = $data['nombre'] ?? '';
    $password = $data['password'] ?? '';

    /* Validación del nombre de usuario: debe tener entre 6 y 20 caracteres alfanuméricos preg_match es una
    funcion de PHP que permite realizar una búsqueda de patrones en una cadena de texto. En este caso, se
    está buscando una cadena que contenga solo letras y números, con una longitud de entre 6 y 20 caracteres.
    Retorna 1 si se encuentra una coincidencia, 0 si no se encuentra ninguna coincidencia y false si ocurre
    un error.*/
    
    if (!preg_match('/^[a-zA-Z0-9]{6,20}$/', $username)) {
        $response->getBody()->write(json_encode(['error' => 'El nombre de usuario debe tener entre 6 y 20 caracteres alfanuméricos.']));
        return $response->withStatus(400);
    }

    if (strlen($name) < 6 || strlen($name) > 20) {
        $response->getBody()->write(json_encode(['error' => 'El nombre debe tener entre 6 y 20 caracteres.']));
        return $response->withStatus(400);
    }

    // Validación de la clave
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $response->getBody()->write(json_encode(['error' => 'La clave debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y caracteres especiales.']));
        return $response->withStatus(400);
    }

    $db = DB::getConnection(); // Obtener la conexión a la base de datos

    // Verificar si el nombre de usuario ya está en uso
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = :username"); // Utilizamos COUNT(*) para verificar si el nombre de usuario ya está en uso antes de intentar crear un nuevo usuario.
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->fetchColumn() > 0) { //el fetchColumn() devuelve la primera columna de la primera fila del conjunto de resultados.
        $response->getBody()->write(json_encode(['error' => 'El nombre de usuario ya está en uso.']));
        return $response->withStatus(400);
    }
    // Insertar el nuevo usuario
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Hashear la contraseña antes de almacenarla en la base de datos.
    $stmt = $db->prepare("INSERT INTO usuario (nombre, usuario, password) VALUES (:nombre, :username, :password)");
    $stmt->bindParam(':nombre', $name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(['message' => 'Usuario creado exitosamente.']));
        return $response->withStatus(201); // Código de estado 201 Created indica que el recurso fue creado exitosamente.
    } else {
        $response->getBody()->write(json_encode(['error' => 'Error al crear el usuario.']));
        return $response->withStatus(500); // Código de estado 500 Internal Server Error indica que hubo un error en el servidor al procesar la solicitud.
    }
});

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

//Valida TOKEN retornado tras LOGIN.
require_once __DIR__ . '/../../middlewares/JwtMiddleware.php'; // importar el middleware

$app->get('/perfil', function (Request $request, Response $response) {
    try {
        $user = $request->getAttribute('jwt'); // decodifica el token JWT
        $response->getBody()->write(json_encode([ // devuelve el token decodificado
            'mensaje' => 'Bienvenido ' . $user->username,
            'id' => $user->sub // id del usuario
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([  // devuelve un error si no se puede decodificar el token
            'error' => 'Error al procesar la solicitud',
            'mensaje' => $e->getMessage() // mensaje de error
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($jwtMiddleware); // agrega el middleware JWT a la ruta /perfil

/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

// PUT: Actualiza el nombre de usuario y la contraseña de un usuario logueado | Valida token.
$app->put('/usuarios/{usuario}', function (Request $request, Response $response, array $args) {
    $usernameParam = $args['usuario'];
    
    $data = $request->getParsedBody();
    $newUsername = $data['nombre'] ?? null;
    $newPassword = $data['password'] ?? null;

    // 1. Obtener y validar el token
    $jwt = $request->getAttribute('jwt');

    // 2. Validar que el USUARIO en el token coincida con el de la URL
    if ($jwt->username !== $usernameParam) {
        $response->getBody()->write(json_encode(['error' => 'No autorizado para modificar este usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // 3. Validar entrada
    if (!$newUsername || !$newPassword) {
        $response->getBody()->write(json_encode(['error' => 'Nombre y contraseña son obligatorios']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    if (strlen($newUsername) < 6 || strlen($newUsername) > 20) {
        $response->getBody()->write(json_encode(['error' => 'El nombre debe tener entre 6 y 20 caracteres.']));
        return $response->withStatus(400);
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
        $response->getBody()->write(json_encode(['error' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y caracteres especiales']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // 4. Actualizar en la base de datos
    $db = DB::getConnection();

    // Verificar si el usuario existe
    $stmt = $db->prepare("SELECT * FROM usuario WHERE usuario = :username");
    $stmt->bindParam(':username', $usernameParam);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE usuario SET nombre = :newUsername, password = :newPassword WHERE usuario = :oldUsername");
    $stmt->bindParam(':newUsername', $newUsername);
    $stmt->bindParam(':newPassword', $hashedPassword);
    $stmt->bindParam(':oldUsername', $usernameParam);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode([
            'message' => 'Usuario actualizado exitosamente',
            'nuevo_nombre' => $newUsername,
            'nueva_password' => $newPassword
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['error' => 'Error al actualizar el usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($jwtMiddleware); // Agrega el middleware JWT a la ruta /usuarios/{usuario}


/*-----------------------------------------------------------------------*/
/*-----------------------------------------------------------------------*/

// GET: Obtener información del usuario logueado | Valida token.
$app->get('/usuarios/{usuario}', function (Request $request, Response $response, array $args) {
    $usernameParam = $args['usuario'];

    // 1. Obtener y validar el token
    $jwt = $request->getAttribute('jwt');

    // 2. Validar que el USUARIO en el token coincida con el de la URL
    if ($jwt->username !== $usernameParam) {
        $response->getBody()->write(json_encode(['error' => 'No autorizado para acceder a este usuario']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // 3. Obtener información del usuario desde la base de datos
    $db = DB::getConnection();
    $stmt = $db->prepare("SELECT id, nombre, usuario FROM usuario WHERE usuario = :username");
    $stmt->bindParam(':username', $usernameParam);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // 4. Retornar la información del usuario
    $response->getBody()->write(json_encode($user));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
})->add($jwtMiddleware); // Agrega el middleware JWT a la ruta /usuarios/{usuario}
