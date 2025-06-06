<?php
include '../conexion.php'; // Se incluye el archivo de conexión a la base de datos

// Se verifica si el formulario fue enviado vía POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Se capturan los datos enviados por el formulario
    $numCuenta = $_POST['numCuenta'];
    $nombreCuenta = $_POST['nombreCuenta'];
    $tipo = $_POST['tipo'];

    // Se verifica si ya existe una cuenta con el mismo número para proteger la integridad de la llave primaria
    $stmtCheck = $conexion->prepare("SELECT COUNT(*) AS count FROM Cuentas WHERE NumCuenta = ?");
    $stmtCheck->bind_param("i", $numCuenta);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();
    // Si la cuenta ya existe, se asigna un mensaje de error
    if ($rowCheck['count'] > 0) {
        $error = "Ya existe una cuenta con el número $numCuenta. Por favor, ingrese un número de cuenta único.";
    }

    // Si no hay error, se procede a insertar la nueva cuenta
    if(!isset($error)){
        $stmt = $conexion->prepare("INSERT INTO Cuentas (NumCuenta, NombreCuenta, Tipo) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $numCuenta, $nombreCuenta, $tipo);
        // Si la inserción fue exitosa, se redirige a la página de listar
        if ($stmt->execute()) {
            header("Location: listar.php");
        // Si hubo un error en la inserción, se asigna un mensaje de error
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>

<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cuenta</title>
    <!-- Enlace a la hoja de estilos personalizada -->
    <link rel="stylesheet" href="../css/estilos.css">
    <!-- Enlace a la hoja de estilos de Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
  <div class="container">
      <h2>Agregar Cuenta</h2>
      <!-- Se muestra un mensaje de error en caso de haberlo -->
      <?php if(isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
      <!-- Formulario para agregar una nueva cuenta -->
      <form method="post" action="">
          <div class="form-group">
              <label for="numCuenta">Número de Cuenta</label>
              <!-- Input numérico para el número de cuenta (campo obligatorio) -->
              <input type="number" class="form-control" id="numCuenta" name="numCuenta" required>
          </div>
          <div class="form-group">
              <label for="nombreCuenta">Nombre de Cuenta</label>
              <!-- Input de texto para el nombre de la cuenta (campo obligatorio) -->
              <input type="text" class="form-control" id="nombreCuenta" name="nombreCuenta" required>
          </div>
          <div class="form-group">
            <label for="tipo">Tipo</label>
            <!-- Select para elegir el tipo de cuenta (campo obligatorio) -->
            <select class="form-control" id="tipo" name="tipo" required>
                <option value="">Seleccione un tipo</option>
                <option value="A">Activo</option>
                <option value="P">Pasivo</option>
                <option value="C">Capital</option>
                <option value="I">Ingresos</option>
                <option value="G">Gastos</option>
            </select>
           </div>
          <button type="submit" class="btn btn-primary">Agregar</button>
      </form>
  </div>
</body>
</html>
<?php include 'footer.php'; ?>
