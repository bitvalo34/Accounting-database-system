<?php
include '../conexion.php';

// Mapeo para convertir la inicial del tipo a su nombre completo
$tipoCompleto = [
    'A' => 'Activo',
    'P' => 'Pasivo',
    'C' => 'Capital',
    'I' => 'Ingresos',
    'G' => 'Gastos'
];

// Si no se especifica la cuenta, mostramos un formulario para ingresarla mediante un <select>
if(!isset($_GET['cuenta'])) {
    // Obtener todas las cuentas disponibles
    $sqlCuentas = "SELECT NumCuenta, NombreCuenta FROM Cuentas ORDER BY NumCuenta";
    $resCuentas = $conexion->query($sqlCuentas);
    $cuentasOptions = "";
    while($row = $resCuentas->fetch_assoc()){
        $cuentasOptions .= "<option value='{$row['NumCuenta']}'>{$row['NumCuenta']} - {$row['NombreCuenta']}</option>";
    }
    ?>
    <?php include 'header.php'; ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Reporte de Mayor - Seleccionar Cuenta</title>
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body>
      <div class="container mt-5">
        <h2>Reporte de Mayor</h2>
        <div class="alert alert-warning">
          Debe especificar la cuenta.
        </div>
        <form method="get" action="mayor.php" class="form-inline">
          <div class="form-group mb-2">
            <label for="cuenta" class="mr-2">Seleccione la Cuenta:</label>
            <select class="form-control" id="cuenta" name="cuenta" required>
              <option value="">Seleccione Cuenta</option>
              <?php echo $cuentasOptions; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary mb-2 ml-2">Generar Reporte</button>
        </form>
        <a href="../index.php" class="btn btn-secondary mt-3">Volver al Menú</a>
      </div>
    </body>
    </html>
    <?php include 'footer.php'; ?>
    <?php
    exit();
}

// Se tiene el parámetro "cuenta"
$numCuenta = $_GET['cuenta'];

// Obtener nombre y tipo de cuenta
$stmt = $conexion->prepare("SELECT NombreCuenta, Tipo FROM Cuentas WHERE NumCuenta = ?");
$stmt->bind_param("i", $numCuenta);
$stmt->execute();
$result = $stmt->get_result();
$cuentaInfo = $result->fetch_assoc();
if(!$cuentaInfo){
    echo "Cuenta no encontrada.";
    exit();
}

// Convertir el tipo a su nombre completo
$tipoNombre = isset($tipoCompleto[$cuentaInfo['Tipo']]) ? $tipoCompleto[$cuentaInfo['Tipo']] : $cuentaInfo['Tipo'];

// Consulta para obtener los movimientos de la cuenta
$sql = "SELECT p.NumPoliza, p.Fecha, p.Descripcion, dp.DebeHaber, dp.Valor 
        FROM Polizas p 
        JOIN DetallePoliza dp ON p.NumPoliza = dp.NumPoliza 
        WHERE dp.NumCuenta = ?
        ORDER BY p.Fecha, p.NumPoliza";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $numCuenta);
$stmt->execute();
$result = $stmt->get_result();

$totalDebe = 0;
$totalHaber = 0;
$movimientos = [];
while($row = $result->fetch_assoc()){
    $movimientos[] = $row;
    if($row['DebeHaber'] == 'D'){
        $totalDebe += $row['Valor'];
    } else {
        $totalHaber += $row['Valor'];
    }
}

// Calcular saldo global
$saldoGlobal = $totalDebe - $totalHaber;
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Mayor</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
      h3 { margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h2 class="mb-4">EMPRESA XXXX - REPORTE DE MAYOR</h2>
    <h3>Cuenta: <?php echo $cuentaInfo['NombreCuenta']; ?> (<?php echo $tipoNombre; ?>)</h3>
    <table class="table table-bordered">
      <thead class="thead-light">
        <tr>
          <th>Póliza</th>
          <th>Fecha</th>
          <th>Descripción</th>
          <th class="text-right">Debe</th>
          <th class="text-right">Haber</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($movimientos as $mov): 
                $debe = ($mov['DebeHaber'] == 'D') ? "Q " . number_format($mov['Valor'], 2, '.', ',') : "";
                $haber = ($mov['DebeHaber'] == 'H') ? "Q " . number_format($mov['Valor'], 2, '.', ',') : "";
        ?>
          <tr>
            <td>#<?php echo $mov['NumPoliza']; ?></td>
            <td><?php echo date("d/m/Y", strtotime($mov['Fecha'])); ?></td>
            <td><?php echo $mov['Descripcion']; ?></td>
            <td class="text-right"><?php echo $debe; ?></td>
            <td class="text-right"><?php echo $haber; ?></td>
          </tr>
        <?php endforeach; ?>
        <tr class="font-weight-bold">
          <td colspan="3">Totales</td>
          <td class="text-right"><?php echo ($totalDebe > 0) ? "Q " . number_format($totalDebe, 2, '.', ',') : ""; ?></td>
          <td class="text-right"><?php echo ($totalHaber > 0) ? "Q " . number_format($totalHaber, 2, '.', ',') : ""; ?></td>
        </tr>
        <tr class="font-weight-bold">
          <td colspan="3">Saldo</td>
          <?php if($saldoGlobal > 0): ?>
            <td class="text-right"><?php echo "Q " . number_format($saldoGlobal, 2, '.', ','); ?></td>
            <td class="text-right"></td>
          <?php elseif($saldoGlobal < 0): ?>
            <td class="text-right"></td>
            <td class="text-right"><?php echo "Q " . number_format(abs($saldoGlobal), 2, '.', ','); ?></td>
          <?php else: ?>
            <td colspan="2" class="text-right"></td>
          <?php endif; ?>
        </tr>
      </tbody>
    </table>
    
    <!-- Enlace para descargar en PDF (implementado en descargar_pdf.php) -->
    <a href="descargar_pdf.php?reporte=mayor&cuenta=<?php echo $numCuenta; ?>" class="btn btn-success mt-3">Descargar PDF</a>
    <a href="../index.php" class="btn btn-secondary mt-3">Volver al Menú</a>
  </div>
</body>
</html>
<?php include 'footer.php'; ?>



