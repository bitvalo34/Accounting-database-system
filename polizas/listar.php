<?php
include '../conexion.php';

// Consulta para obtener todas las pólizas
$sql = "SELECT * FROM Polizas ORDER BY NumPoliza";
$result = $conexion->query($sql);

// Función para convertir DebeHaber a palabra completa
function getDebeHaber($dh) {
    return ($dh == 'D') ? 'Debe' : (($dh == 'H') ? 'Haber' : $dh);
}
?>
<?php include 'header.php'; ?>

    <h2>Listado de Pólizas</h2>
    <a href="agregar.php" class="btn btn-primary mb-3">Agregar Nueva Póliza</a>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>NumPoliza</th>
          <th>Fecha</th>
          <th>Descripción</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        while($row = $result->fetch_assoc()){ 
          $numPoliza = $row['NumPoliza'];
          ?>
          <tr>
            <td><?php echo $numPoliza; ?></td>
            <td><?php echo date("d/m/Y", strtotime($row['Fecha'])); ?></td>
            <td><?php echo $row['Descripcion']; ?></td>
            <td>
              <!-- Botón toggle: siempre muestra "Más detalles" -->
              <button class="btn btn-info btn-sm expandDetalle" data-target="#detalle_<?php echo $numPoliza; ?>">Más detalles</button>
              <a href="modificar.php?num=<?php echo $numPoliza; ?>" class="btn btn-warning btn-sm">Modificar</a>
              <a href="eliminar.php?num=<?php echo $numPoliza; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta póliza?')">Eliminar</a>
            </td>
          </tr>
          <!-- Fila oculta para detalles de la póliza -->
          <tr class="collapse detalle-row" id="detalle_<?php echo $numPoliza; ?>">
            <td colspan="4">
              <?php
              // Obtener movimientos para esta póliza
              $stmtDet = $conexion->prepare("SELECT dp.*, c.NombreCuenta FROM DetallePoliza dp LEFT JOIN Cuentas c ON dp.NumCuenta = c.NumCuenta WHERE dp.NumPoliza = ?");
              $stmtDet->bind_param("i", $numPoliza);
              $stmtDet->execute();
              $resultDet = $stmtDet->get_result();
              ?>
              <table class="table table-sm table-bordered detalle-table">
                <thead>
                  <tr>
                    <th>Cuenta</th>
                    <th>Debe/Haber</th>
                    <th>Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  while($det = $resultDet->fetch_assoc()){
                    // Combina el número y el nombre de la cuenta
                    $cuentaTexto = $det['NumCuenta'];
                    if(!empty($det['NombreCuenta'])){
                        $cuentaTexto .= " - " . $det['NombreCuenta'];
                    }
                    // Agrega el símbolo de quetzales en Valor
                    $valorFormateado = "Q " . number_format($det['Valor'], 2, '.', ',');
                    ?>
                    <tr>
                      <td><?php echo $cuentaTexto; ?></td>
                      <td><?php echo getDebeHaber($det['DebeHaber']); ?></td>
                      <td style="text-align:right;"><?php echo $valorFormateado; ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
              <!-- Botón para ocultar detalles -->
              <button class="btn btn-secondary btn-sm cerrar-detalle" type="button" data-target="#detalle_<?php echo $numPoliza; ?>">Ocultar detalles</button>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <a href="../index.php" class="btn btn-secondary">Volver al Menú</a>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script>
     // Cuando se haga clic en "Más detalles", solo se expande la fila
     $(document).on('click', '.expandDetalle', function(){
        var target = $(this).attr('data-target');
        $(target).collapse('show');
     });
  
     // Manejar el botón para cerrar detalles: al hacer clic se oculta la fila de detalles.
     $(document).on('click', '.cerrar-detalle', function(){
        var target = $(this).attr('data-target');
        $(target).collapse('hide');
     });
  </script>

<?php include 'footer.php'; ?>



