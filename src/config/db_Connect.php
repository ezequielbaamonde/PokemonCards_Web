<?php
class DB {
    private static $connection; //Propiedad estática para almacenar la conexión a la base de datos.
    //El modificador de acceso static permite acceder a la propiedad sin necesidad de crear una instancia de la clase.

    public static function getConnection() {
        if (!self::$connection) {
            $host = 'localhost';
            $dbname = 'nombre_base_datos';
            $user = 'root';
            $pass = '';
            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                http_response_code(500); // Cambia el estado HTTP a 500 (Error interno del servidor)
                die(json_encode(['error' => $e->getMessage()]));
            }
        }

        return self::$connection;
    }

    //Función para cerrar la conexión a la base de datos. Se implementa en el controlador de la app.
    /*Se puede usar en cualquier parte de la app, pero no es necesario cerrarla explícitamente ya que
    PHP lo hace automáticamente al finalizar el script.*/
    public static function closeConnection() {
        if (self::$connection) {
            self::$connection = null;
            echo json_encode(['success' => 'Database connection closed.']);
        }
    }
}
?>