<?php
 /* DEPENDENCIAS IMPORTADAS DEL PSR de SLIM */
 use Psr\Http\Message\ResponseInterface as Response;
 use Psr\Http\Message\ServerRequestInterface as Request;
 use Slim\Factory\AppFactory;
 include_once __DIR__ . '/../config/db_Connect.php'; //Incluye el archivo de conexión a la base de datos.
 $conn = DB::getConnection(); //Llama a la función getConnection() de la clase DB para obtener la conexión a la base de datos.
?>

<!DOCTYPE html>
<html lang="en-es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokemon Web Cards</title>
    <link rel="stylesheet" type="text/css" href="styles/style.css"> <!-- Enlaza el archivo CSS para el estilo de la página. -->
</head>
<body>
    <h1><u>Bienvenido a la Web de Pokemon</u></h1>
    <h2>Listado de cartas pokemonm</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Ataque</th>
            <th>Ataque Nombre</th>
        </tr>
        <?php
        
        // Realiza una consulta a la base de datos para obtener los datos de las cartas pokemon.
        $stmt = $conn->prepare("SELECT id, nombre, ataque, ataque_nombre FROM pokemon"); //Prepara la consulta SQL.
        $stmt->execute(); //Ejecuta la consulta SQL.
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC); //Obtiene todos los resultados de la consulta en un array asociativo.
        // Recorre los resultados y los muestra en la tabla.
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>"; //Escapa los caracteres especiales para evitar inyecciones XSS.
            echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ataque']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ataque_nombre']) . "</td>";
            echo "</tr>";
        }
        ?>
</body>
</html>
<? $conn = DB::closeConnection(); //Cierra la conexión a la base de datos. ?>
