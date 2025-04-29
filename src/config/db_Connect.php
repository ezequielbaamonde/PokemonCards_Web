<?php
class DB {
    private static $connection; //Propiedad estática para almacenar la conexión a la base de datos.
    //El modificador de acceso static permite acceder a la propiedad sin necesidad de crear una instancia de la clase.

    public static function getConnection() {
        if (!self::$connection) { /*self se usa dentro de una clase para
            hacer referencia a elementos estáticos (métodos o propiedades) de esa misma clase.*/
            //Almaceno los parametros de mi conexión por PDO
            $host = 'localhost';
            $dbname = 'seminario_php_use';
            $user = 'root';
            $pass = '';
            //intento conexión
            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo json_encode(['success' => 'Connected to the database successfully.']);
            } catch (PDOException $e) {
                die(json_encode(['error' => $e->getMessage()])); //Sino, arroja mensaje por excepción
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