<!-- Elimina una cuenta de la base de datos -->
<?php
include '../conexion.php'; // Se incluye el archivo de conexión para acceder a la base de datos

// Se verifica si se recibió el parámetro 'num' por GET (número de cuenta)
if (isset($_GET['num'])) {
    $numCuenta = $_GET['num']; // Se asigna el número de cuenta a una variable

    // Se prepara la sentencia SQL para eliminar la cuenta con el número especificado
    $stmt = $conexion->prepare("DELETE FROM Cuentas WHERE NumCuenta = ?");
    $stmt->bind_param("i", $numCuenta); // Se vincula el parámetro, indicando que es un entero ("i")
    $stmt->execute(); // Se ejecuta la sentencia
}

// Una vez eliminado (o si no se recibió el parámetro), se redirecciona a la página de listado
header("Location: listar.php");
?>
