<?php
include '../conexion.php';

// Mapeo de tipos a su descripción completa y el orden deseado
$tiposOrden = [
    'A' => 'Activo',
    'P' => 'Pasivo',
    'C' => 'Capital',
    'I' => 'Ingresos',
    'G' => 'Gastos'
];

// Consulta para agrupar los movimientos por cuenta
$sql = "SELECT dp.NumCuenta, 
               SUM(IF(dp.DebeHaber='D', dp.Valor, 0)) AS Debe,
               SUM(IF(dp.DebeHaber='H', dp.Valor, 0)) AS Haber,
               c.NombreCuenta,
               c.Tipo
        FROM DetallePoliza dp
        JOIN Cuentas c ON dp.NumCuenta = c.NumCuenta
        GROUP BY dp.NumCuenta, c.NombreCuenta, c.Tipo
        ORDER BY FIELD(c.Tipo, 'A', 'P', 'C', 'I', 'G'), dp.NumCuenta";
$result = $conexion->query($sql);

// Agrupar por tipo
$balancePorTipo = [];
while($row = $result->fetch_assoc()){
    $tipo = $row['Tipo'];
    if(!isset($balancePorTipo[$tipo])){
        $balancePorTipo[$tipo] = [];
    }
    $balancePorTipo[$tipo][] = $row;
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Balance</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
      h3 { margin-top: 20px; }
      /* Encabezado superior con fondo distinto */
      thead.titulo {
          background-color: #003366;
          color: #fff;
      }
      .subtotal-row {
          background-color: #e9ecef;
          font-weight: bold;
      }
      .text-right {
          text-align: right;
      }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h2>EMPRESA XXXX - BALANCE</h2>
    <table class="table table-bordered">
      <thead class="titulo">
        <tr>
          <th>Número</th>
          <th>Cuenta</th>
          <th>Debe</th>
          <th>Haber</th>
          <th>Saldo Debe</th>
          <th>Saldo Haber</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        // Variables generales para totales
        $totalDebeGeneral = 0;
        $totalHaberGeneral = 0;
        $totalSaldoDebeGeneral = 0;
        $totalSaldoHaberGeneral = 0;
        
        // Recorrer cada tipo, en el orden deseado
        foreach($tiposOrden as $tipoCode => $tipoDesc):
          if(isset($balancePorTipo[$tipoCode])):
            // Imprimir fila de subtítulo de clasificación
            ?>
            <tr class="subtotal-row">
              <td colspan="6"><?php echo $tipoDesc; ?></td>
            </tr>
            <?php
            // Recorrer cada cuenta de ese tipo
            foreach($balancePorTipo[$tipoCode] as $row):
              // Calcular saldo: si Debe > Haber, saldo va en Debe; si Haber > Debe, en Haber.
              $saldo = $row['Debe'] - $row['Haber'];
              $saldoDebe = $saldo > 0 ? $saldo : 0;
              $saldoHaber = $saldo < 0 ? abs($saldo) : 0;
              
              // Acumular totales generales
              $totalDebeGeneral += $row['Debe'];
              $totalHaberGeneral += $row['Haber'];
              $totalSaldoDebeGeneral += $saldoDebe;
              $totalSaldoHaberGeneral += $saldoHaber;
              ?>
              <tr>
                <td><?php echo $row['NumCuenta']; ?></td>
                <td><?php echo $row['NombreCuenta']; ?> <small>(<?php echo $tipoDesc; ?>)</small></td>
                <td class="text-right"><?php echo ($row['Debe'] > 0) ? "Q " . number_format($row['Debe'], 2, '.', ',') : ""; ?></td>
                <td class="text-right"><?php echo ($row['Haber'] > 0) ? "Q " . number_format($row['Haber'], 2, '.', ',') : ""; ?></td>
                <td class="text-right"><?php echo ($saldoDebe > 0) ? "Q " . number_format($saldoDebe, 2, '.', ',') : ""; ?></td>
                <td class="text-right"><?php echo ($saldoHaber > 0) ? "Q " . number_format($saldoHaber, 2, '.', ',') : ""; ?></td>
              </tr>
            <?php 
            endforeach;
          endif;
        endforeach;
        ?>
        <!-- Fila de totales generales -->
        <tr class="font-weight-bold">
          <td colspan="2">Totales</td>
          <td class="text-right"><?php echo ($totalDebeGeneral > 0) ? "Q " . number_format($totalDebeGeneral, 2, '.', ',') : ""; ?></td>
          <td class="text-right"><?php echo ($totalHaberGeneral > 0) ? "Q " . number_format($totalHaberGeneral, 2, '.', ',') : ""; ?></td>
          <td class="text-right"><?php echo ($totalSaldoDebeGeneral > 0) ? "Q " . number_format($totalSaldoDebeGeneral, 2, '.', ',') : ""; ?></td>
          <td class="text-right"><?php echo ($totalSaldoHaberGeneral > 0) ? "Q " . number_format($totalSaldoHaberGeneral, 2, '.', ',') : ""; ?></td>
        </tr>
      </tbody>
    </table>
    
    <!-- Enlace para descargar en PDF (implementado en descargar_pdf.php) -->
    <a href="descargar_pdf.php?reporte=balance" class="btn btn-success mt-3">Descargar PDF</a>
    <a href="../index.php" class="btn btn-secondary mt-3">Volver al Menú</a>
  </div>
</body>
</html>
<?php include 'footer.php'; ?>



