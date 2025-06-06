<?php
// Definición de las variables de conexión a la base de datos
$host = "localhost";       // Servidor de la base de datos (normalmente 'localhost')
$usuario = "root";         // Nombre de usuario para acceder a MySQL
$contrasena = "";          // Contraseña para el usuario (en este caso, vacía)
$basedatos = "CONTABILIDAD"; // Nombre de la base de datos a la que se conectará

// Creación de una nueva conexión MySQLi utilizando las variables definidas
$conexion = new mysqli($host, $usuario, $contrasena, $basedatos);

// Verificar si se produjo algún error durante la conexión
if ($conexion->connect_error) {
    // Si hay un error, se detiene la ejecución del script y se muestra el mensaje de error
    die("Error de conexión: " . $conexion->connect_error);
}
?>

