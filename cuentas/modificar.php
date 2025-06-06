<?php
include '../conexion.php'; // Se incluye la conexión a la base de datos
// Verifica que se haya pasado el parámetro 'num' para identificar la cuenta a modificar
if(!isset($_GET['num'])){
  header("Location: listar.php");
  exit();
}
$numCuenta = $_GET['num'];

// Se obtienen los datos de la cuenta a modificar
$stmt = $conexion->prepare("SELECT * FROM Cuentas WHERE NumCuenta = ?");
$stmt->bind_param("i", $numCuenta);
$stmt->execute();
$result = $stmt->get_result();
$cuenta = $result->fetch_assoc();

// Si no se encontró la cuenta, se muestra un mensaje y se detiene la ejecución
if(!$cuenta){
  echo "Cuenta no encontrada.";
  exit();
}

// Procesamiento del formulario al enviarse los datos modificados
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $nombreCuenta = $_POST['nombreCuenta'];
  $tipo = $_POST['tipo'];
  
  $stmt = $conexion->prepare("UPDATE Cuentas SET NombreCuenta = ?, Tipo = ? WHERE NumCuenta = ?");
  $stmt->bind_param("ssi", $nombreCuenta, $tipo, $numCuenta);
  // Se ejecuta la actualización y se redirige a la página de listado si tiene éxito,
  // o se asigna un mensaje de error en caso contrario
  if($stmt->execute()){
    header("Location: listar.php");
  } else {
    $error = "Error al modificar: " . $stmt->error;
  }
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Modificar Cuenta</title>
  <!-- Enlaces a estilos globales y Bootstrap -->
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-5">
    <h2>Modificar Cuenta</h2>
    <?php if(isset($error)){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
    <!-- Formulario para modificar la cuenta -->
    <form method="post">
      <div class="form-group">
        <label>Número de Cuenta</label>
       <!-- El número de cuenta se muestra en un input deshabilitado para evitar cambios -->
        <input type="number" class="form-control" value="<?php echo $cuenta['NumCuenta']; ?>" disabled>
      </div>
      <div class="form-group">
        <label>Nombre de Cuenta</label>
        <input type="text" class="form-control" name="nombreCuenta" value="<?php echo $cuenta['NombreCuenta']; ?>" required>
      </div>
      <div class="form-group">
        <label for="tipo">Tipo</label>
        <!-- Select para modificar el tipo de cuenta, con la opción actual preseleccionada -->
        <select class="form-control" id="tipo" name="tipo" required>
            <option value="">Seleccione un tipo</option>
            <option value="A" <?php if($cuenta['Tipo'] == 'A') echo 'selected'; ?>>Activo</option>
            <option value="P" <?php if($cuenta['Tipo'] == 'P') echo 'selected'; ?>>Pasivo</option>
            <option value="C" <?php if($cuenta['Tipo'] == 'C') echo 'selected'; ?>>Capital</option>
            <option value="I" <?php if($cuenta['Tipo'] == 'I') echo 'selected'; ?>>Ingresos</option>
            <option value="G" <?php if($cuenta['Tipo'] == 'G') echo 'selected'; ?>>Gastos</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Modificar</button>
      <a href="listar.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>
<?php include 'footer.php'; ?>