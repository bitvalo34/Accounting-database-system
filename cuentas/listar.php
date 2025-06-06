<?php
include '../conexion.php';

// Consulta para obtener todas las cuentas
$sql = "SELECT * FROM Cuentas";
$result = $conexion->query($sql);

// Función para mapear el tipo a su descripción
function getTipoDescripcion($tipo) {
    $map = [
        'A' => 'Activo',
        'P' => 'Pasivo',
        'C' => 'Capital',
        'I' => 'Ingresos',
        'G' => 'Gastos'
    ];
    return isset($map[$tipo]) ? $map[$tipo] : $tipo;
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listar Cuentas</title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    /* Estilo para el icono de información */
    .info-icon {
      color: #17a2b8;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h2>Listado de Cuentas</h2>
    <!-- Botón para agregar una nueva cuenta -->
    <a href="agregar.php" class="btn btn-primary mb-3">Agregar Nueva Cuenta</a>
    <!-- Tabla para listar las cuentas -->
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>NumCuenta</th>
          <th>NombreCuenta</th>
          <th>Tipo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()){ 
            $numCuenta = $row['NumCuenta'];
            // Consultar si la cuenta se ha referenciado en alguna póliza en DetallePoliza
            $sqlRef = "SELECT GROUP_CONCAT(NumPoliza SEPARATOR ', ') as polizas FROM DetallePoliza WHERE NumCuenta = $numCuenta";
            $resRef = $conexion->query($sqlRef);
            $refRow = $resRef->fetch_assoc();
            $polizasRef = $refRow['polizas'];// Contendrá los números de póliza separados por coma, si existen
            $referenciada = !empty($polizasRef); // Verdadero si la cuenta está referenciada
            // Mapear el tipo a descripción
            $tipoDesc = getTipoDescripcion($row['Tipo']);
            ?>
          <tr>
            <td><?php echo $row['NumCuenta']; ?></td>
            <td><?php echo $row['NombreCuenta']; ?></td>
            <td><?php echo $tipoDesc; ?></td>
            <td>
              <!-- Enlace para modificar la cuenta -->
              <a href="modificar.php?num=<?php echo $row['NumCuenta']; ?>" class="btn btn-warning btn-sm">Modificar</a>
              <?php if(!$referenciada){ ?>
                <!-- Enlace para eliminar la cuenta si no está referenciada -->
                <a href="eliminar.php?num=<?php echo $row['NumCuenta']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta cuenta?')">Eliminar</a>
              <?php } else { ?>
                <!-- Si está referenciada, se deshabilita el botón de eliminar y se muestra un icono informativo -->
                <button type="button" class="btn btn-danger btn-sm" disabled>Eliminar</button>
                <!-- Icono de información -->
                <i class="fas fa-info-circle info-icon" title="No se puede eliminar esta cuenta. Referenciada en póliza(s): <?php echo $polizasRef; ?>"></i>
              <?php } ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <a href="../index.php" class="btn btn-secondary">Volver al Menú</a>
  </div>
</body>
</html>
<?php include 'footer.php'; ?>
