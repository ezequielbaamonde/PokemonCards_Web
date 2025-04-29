<?php
$sv = "localhost"; //Nombre del SV.
$user = "root";
$pass = ""; //Contraseña de la base de datos.
$db = "seminario_php"; //Nombre de la base de datos.
$conn = new mysqli($sv, $user, $pass, $db); //Crea la conexión a la base de datos.

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); //Si no se conecta, muestra el error.
} else {
    echo "Connected successfully"; //Si se conecta, muestra el mensaje de conexión exitosa.
}
?>