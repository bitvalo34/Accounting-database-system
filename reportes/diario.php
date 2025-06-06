<?php
include '../conexion.php';

// Filtrado por fecha si se envía el parámetro
$fechaFiltro = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$where = $fechaFiltro ? "WHERE p.Fecha = '$fechaFiltro'" : "";

// Consulta para obtener las pólizas, sus movimientos y la información de la cuenta
$sql = "SELECT p.NumPoliza, p.Fecha, p.Descripcion, 
               dp.NumCuenta, c.NombreCuenta, dp.DebeHaber, dp.Valor 
        FROM Polizas p 
        JOIN DetallePoliza dp ON p.NumPoliza = dp.NumPoliza 
        LEFT JOIN Cuentas c ON dp.NumCuenta = c.NumCuenta
        $where
        ORDER BY p.Fecha, p.NumPoliza";
$result = $conexion->query($sql);

// Agrupar resultados por póliza
$polizas = [];
while ($row = $result->fetch_assoc()){
    $numPoliza = $row['NumPoliza'];
    if(!isset($polizas[$numPoliza])){
        $polizas[$numPoliza] = [
            'Fecha' => $row['Fecha'],
            'Descripcion' => $row['Descripcion'],
            'movimientos' => []
        ];
    }
    // Guardar también el nombre de la cuenta
    $polizas[$numPoliza]['movimientos'][] = [
        'NumCuenta' => $row['NumCuenta'],
        'NombreCuenta' => $row['NombreCuenta'],
        'DebeHaber' => $row['DebeHaber'],
        'Valor' => $row['Valor']
    ];
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Diario</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
      h4 { margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h2 class="mb-4">EMPRESA XXXX - REPORTE DE DIARIO</h2>
    <!-- Formulario para filtrar por fecha -->
    <form method="get" class="form-inline mb-4">
      <label for="fecha" class="mr-2">Filtrar por Fecha:</label>
      <input type="date" class="form-control mr-2" id="fecha" name="fecha" value="<?php echo $fechaFiltro; ?>">
      <button type="submit" class="btn btn-primary mr-2">Filtrar</button>
      <a href="diario.php" class="btn btn-secondary">Limpiar Filtro</a>
    </form>
    
    <?php if(empty($polizas)): ?>
      <div class="alert alert-info">No se encontraron pólizas para la fecha indicada.</div>
    <?php else: ?>
      <?php foreach ($polizas as $numPoliza => $datos): 
                $totalDebe = 0;
                $totalHaber = 0;
      ?>
        <h4>Póliza: <?php echo $numPoliza; ?></h4>
        <p>
          Fecha: <?php echo date("d/m/Y", strtotime($datos['Fecha'])); ?> &nbsp;&nbsp; 
          Descripción: <?php echo $datos['Descripcion']; ?>
        </p>
        <table class="table table-bordered">
          <thead class="thead-light">
            <tr>
              <th>Cuenta</th>
              <th>Debe</th>
              <th>Haber</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($datos['movimientos'] as $mov): 
                      $debe = ($mov['DebeHaber'] == 'D') ? "Q " . number_format($mov['Valor'], 2, '.', ',') : "";
                      $haber = ($mov['DebeHaber'] == 'H') ? "Q " . number_format($mov['Valor'], 2, '.', ',') : "";
                      $totalDebe += ($mov['DebeHaber'] == 'D') ? $mov['Valor'] : 0;
                      $totalHaber += ($mov['DebeHaber'] == 'H') ? $mov['Valor'] : 0;
                      // Mostrar en la columna Cuenta: número - nombre
                      $cuentaTexto = $mov['NumCuenta'];
                      if(!empty($mov['NombreCuenta'])){
                          $cuentaTexto .= " - " . $mov['NombreCuenta'];
                      }
            ?>
              <tr>
                <td><?php echo $cuentaTexto; ?></td>
                <td class="text-right"><?php echo $debe; ?></td>
                <td class="text-right"><?php echo $haber; ?></td>
              </tr>
            <?php endforeach; ?>
            <tr class="font-weight-bold">
              <td>Totales</td>
              <td class="text-right"><?php echo "Q " . number_format($totalDebe, 2, '.', ','); ?></td>
              <td class="text-right"><?php echo "Q " . number_format($totalHaber, 2, '.', ','); ?></td>
            </tr>
          </tbody>
        </table>
      <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Enlace para descargar en PDF (implementado en descargar_pdf.php) -->
    <a href="descargar_pdf.php?reporte=diario<?php echo $fechaFiltro ? '&fecha=' . $fechaFiltro : ''; ?>" class="btn btn-success mt-3">Descargar PDF</a>
    <a href="../index.php" class="btn btn-secondary mt-3">Volver al Menú</a>
  </div>
</body>
</html>
<?php include 'footer.php'; ?>



