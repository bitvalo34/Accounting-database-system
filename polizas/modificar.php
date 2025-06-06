<?php
include '../conexion.php';

if(!isset($_GET['num'])){
  header("Location: listar.php");
  exit();
}

$numPoliza = $_GET['num'];

// Obtener datos de la cabecera de la póliza
$stmt = $conexion->prepare("SELECT * FROM Polizas WHERE NumPoliza = ?");
$stmt->bind_param("i", $numPoliza);
$stmt->execute();
$result = $stmt->get_result();
$poliza = $result->fetch_assoc();

if(!$poliza){
  echo "Póliza no encontrada.";
  exit();
}

// Obtener movimientos de la póliza
$stmt2 = $conexion->prepare("SELECT * FROM DetallePoliza WHERE NumPoliza = ?");
$stmt2->bind_param("i", $numPoliza);
$stmt2->execute();
$result2 = $stmt2->get_result();
$movimientos = [];
while($row = $result2->fetch_assoc()){
  $movimientos[] = $row;
}

// Si no hay movimientos, creamos dos por defecto
if(count($movimientos) < 2){
  if(count($movimientos) == 0){
    // Movimiento 1: Debe (por defecto)
    $movimientos[] = [
      'NumCuenta' => '',
      'DebeHaber' => 'D',
      'Valor' => 0
    ];
    // Movimiento 2: Haber (por defecto)
    $movimientos[] = [
      'NumCuenta' => '',
      'DebeHaber' => 'H',
      'Valor' => 0
    ];
  } else {
    // Si hay uno, añadir uno para completar dos. Si el existente es Debe, añadir Haber, y viceversa.
    $existing = $movimientos[0];
    $newMovimiento = [
      'NumCuenta' => '',
      'DebeHaber' => ($existing['DebeHaber'] === 'D') ? 'H' : 'D',
      'Valor' => 0
    ];
    $movimientos[] = $newMovimiento;
  }
}

// Procesamiento del formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  // Actualizar cabecera de la póliza
  $fecha = $_POST['fecha'];
  $descripcion = $_POST['descripcion'];
  $stmt = $conexion->prepare("UPDATE Polizas SET Fecha = ?, Descripcion = ? WHERE NumPoliza = ?");
  $stmt->bind_param("ssi", $fecha, $descripcion, $numPoliza);
  if(!$stmt->execute()){
    $error = "Error al actualizar la póliza: " . $stmt->error;
  }
  
  if(!isset($error)){
    // Recibir arrays del formulario
    $cuentas      = $_POST['cuenta'];
    $lados        = $_POST['lado'];
    $valoresDebe  = $_POST['valorDebe'];
    $valoresHaber = $_POST['valorHaber'];
    
    // Validar totales
    $totalDebe = 0;
    $totalHaber = 0;
    foreach($lados as $i => $lado){
      $valorSinComas = str_replace(',', '', ($lado === 'D') ? ($valoresDebe[$i] ?? "0") : ($valoresHaber[$i] ?? "0"));
      $valor = floatval($valorSinComas);
      if($lado === 'D'){
        $totalDebe += $valor;
      } else {
        $totalHaber += $valor;
      }
    }
    if($totalDebe !== $totalHaber){
      $error = "Error: Los totales no cuadran (Debe: ".number_format($totalDebe,2)." / Haber: ".number_format($totalHaber,2).").";
    } else {
      // Eliminar los movimientos actuales de DetallePoliza
      $conexion->query("DELETE FROM DetallePoliza WHERE NumPoliza = $numPoliza");
      
      // Insertar movimientos actualizados
      for($i = 0; $i < count($cuentas); $i++){
        $numCuenta = $cuentas[$i];
        $lado = $lados[$i];
        $valorSinComas = str_replace(',', '', ($lado === 'D') ? ($valoresDebe[$i] ?? "0") : ($valoresHaber[$i] ?? "0"));
        $valor = floatval($valorSinComas);
        $stmt3 = $conexion->prepare("INSERT INTO DetallePoliza (NumPoliza, NumCuenta, DebeHaber, Valor) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("iisd", $numPoliza, $numCuenta, $lado, $valor);
        if(!$stmt3->execute()){
          $error = "Error al insertar el movimiento: " . $stmt3->error;
          break;
        }
      }
    }
  }
  if(!isset($error)){
    header("Location: listar.php");
    exit();
  }
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Modificar Póliza</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .mensaje-validacion {
      font-weight: bold;
      color: red;
    }
    /* Contenedor global de la T: borde horizontal único */
    #tGlobal {
      border-top: 2px solid #000;
      margin-top: 10px;
      padding-top: 10px;
    }
    .movimiento {
      margin-bottom: 10px;
    }
    /* Contenedor T para los montos: solo la línea vertical, extendida al 100% */
    .t-container {
      position: relative;
      display: flex;
      align-items: stretch;
      min-height: 50px;
    }
    .t-container::before {
      content: "";
      position: absolute;
      left: 50%;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #000;
    }
    .t-debe, .t-haber {
      width: 50%;
      padding: 0 10px;
    }
    /* Totales: alineados justo debajo de la parte derecha */
    #totales {
      margin-top: 10px;
      border-top: 1px solid #000;
      padding-top: 10px;
    }
    #totales .col-6 {
      padding: 0 10px;
    }
    /* Evitar ingreso de letras en montos */
    .valorDebe, .valorHaber {
      ime-mode: disabled;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2>Modificar Póliza</h2>
  <?php if(isset($error)){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
  <form id="formPoliza" method="post">
    <!-- Cabecera: Se muestra el número de póliza de solo lectura -->
    <div class="form-group">
      <label for="numPoliza">Número de Póliza</label>
      <input type="number" class="form-control" name="numPoliza" id="numPoliza" value="<?php echo $poliza['NumPoliza']; ?>" readonly>
    </div>
    <div class="form-group">
      <label for="fecha">Fecha</label>
      <input type="date" class="form-control" name="fecha" id="fecha" value="<?php echo $poliza['Fecha']; ?>" required>
    </div>
    <div class="form-group">
      <label for="descripcion">Descripción</label>
      <textarea class="form-control" name="descripcion" id="descripcion" required><?php echo $poliza['Descripcion']; ?></textarea>
    </div>

    <h3>Movimientos</h3>
    <!-- Mensaje de validación en tiempo real -->
    <div id="mensajeValidacion" class="mensaje-validacion mb-3"></div>

    <!-- Contenedor global para la T completa -->
    <div id="tGlobal">
      <div id="movimientosContainer">
        <?php foreach($movimientos as $i => $mov){ 
            // Para cada movimiento, definimos el comportamiento según el lado actual.
            $lado = $mov['DebeHaber'];
            ?>
          <div class="row movimiento">
            <!-- Parte izquierda: select y radios -->
            <div class="col-md-5">
              <select name="cuenta[]" class="form-control" required>
                <option value="">Seleccione Cuenta</option>
                <?php 
                  // Se consulta nuevamente (o puedes usar un arreglo precargado) para generar las opciones
                  $sql2 = "SELECT NumCuenta, NombreCuenta FROM Cuentas ORDER BY NumCuenta";
                  $res2 = $conexion->query($sql2);
                  while($row2 = $res2->fetch_assoc()){
                      $selected = ($row2['NumCuenta'] == $mov['NumCuenta']) ? 'selected' : '';
                      echo "<option value='{$row2['NumCuenta']}' $selected>{$row2['NumCuenta']} - {$row2['NombreCuenta']}</option>";
                  }
                ?>
              </select>
              <div class="text-center mt-2">
                <div class="form-check form-check-inline">
                  <input class="form-check-input tipoMovimiento" type="radio" name="lado[<?php echo $i; ?>]" value="D" <?php echo ($lado==='D') ? 'checked' : ''; ?>>
                  <label class="form-check-label">Debe</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input tipoMovimiento" type="radio" name="lado[<?php echo $i; ?>]" value="H" <?php echo ($lado==='H') ? 'checked' : ''; ?>>
                  <label class="form-check-label">Haber</label>
                </div>
              </div>
            </div>
            <!-- Parte derecha: T gráfica (montos) -->
            <div class="col-md-5">
              <div class="t-container">
                <div class="t-debe">
                  <?php if($lado==='D'){ ?>
                    <input type="text" name="valorDebe[]" class="form-control valorDebe" placeholder="Valor Debe" value="<?php echo number_format($mov['Valor'],2,'.',','); ?>">
                  <?php } else { ?>
                    <input type="text" name="valorDebe[]" class="form-control valorDebe" placeholder="Valor Debe" value="0" style="visibility: hidden;">
                  <?php } ?>
                </div>
                <div class="t-haber">
                  <?php if($lado==='H'){ ?>
                    <input type="text" name="valorHaber[]" class="form-control valorHaber" placeholder="Valor Haber" value="<?php echo number_format($mov['Valor'],2,'.',','); ?>">
                  <?php } else { ?>
                    <input type="text" name="valorHaber[]" class="form-control valorHaber" placeholder="Valor Haber" value="0" style="visibility: hidden;">
                  <?php } ?>
                </div>
              </div>
            </div>
            <!-- Botón de eliminar -->
            <div class="col-md-2 text-right">
              <button type="button" class="btn btn-danger btn-sm eliminarMovimiento" <?php echo ($i < 2) ? 'disabled' : ''; ?>>
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        <?php } ?>
      </div>
      <!-- Totales: alineados justo debajo de la parte derecha -->
      <div id="totales" class="row">
        <div class="col-md-5"></div>
        <div class="col-md-5">
          <div class="row">
            <div class="col-6 text-right"><strong>Total Debe:</strong> <span id="totalDebe">0.00</span></div>
            <div class="col-6 text-right"><strong>Total Haber:</strong> <span id="totalHaber">0.00</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Botón para agregar nuevos movimientos -->
    <button type="button" id="agregarMovimiento" class="btn btn-secondary mt-3 mb-3">
      <i class="fas fa-plus"></i> Agregar Movimiento
    </button>
    <!-- Botón de Preview general: muestra la T completa (select e inputs, sin radio buttons) -->
    <button type="button" id="previewGeneral" class="btn btn-info mt-3 mb-3">
      <i class="fas fa-search-plus"></i> Preview T Completa
    </button>
    <br>
    <button type="submit" class="btn btn-primary">Guardar Póliza</button>
    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>

<!-- Modal Preview (para la T completa) -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewModalLabel">Preview de la T Completa</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="previewContent"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-custom" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
  // Función para formatear números
  function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  // Actualizar totales y validación en tiempo real
  function updateTotals() {
    let totalDebe = 0, totalHaber = 0;
    $(".movimiento").each(function(){
      let debe = parseFloat($(this).find(".valorDebe").val().replace(/,/g, "")) || 0;
      let haber = parseFloat($(this).find(".valorHaber").val().replace(/,/g, "")) || 0;
      totalDebe += debe;
      totalHaber += haber;
    });
    $("#totalDebe").text(formatNumber(totalDebe));
    $("#totalHaber").text(formatNumber(totalHaber));

    // Validación en tiempo real
    if($(".movimiento").length >= 1){
      if(totalDebe !== totalHaber){
        $("#mensajeValidacion").text("Los totales no cuadran (Debe: " + formatNumber(totalDebe) + " / Haber: " + formatNumber(totalHaber) + ").");
      } else {
        $("#mensajeValidacion").text("");
      }
    }
  }

  // Restringir ingreso a dígitos y punto
  $(document).on('keypress', '.valorDebe, .valorHaber', function (e) {
    let charCode = (typeof e.which == "number") ? e.which : e.keyCode;
    if (charCode && charCode !== 8 && charCode !== 46 && (charCode < 48 || charCode > 57)) {
      e.preventDefault();
    }
  });

  // Función para mostrar solo el input de Debe o Haber
  function ajustarInputs(radio) {
    let parentRow = radio.closest(".movimiento");
    if(radio.val() === 'D'){
      let haberVal = parentRow.find(".valorHaber").val();
      if(haberVal && haberVal !== "0") {
        parentRow.find(".valorDebe").val(haberVal);
      }
      parentRow.find(".valorDebe").prop("readonly", false).css("visibility", "visible");
      parentRow.find(".valorHaber").prop("readonly", true).val("0").css("visibility", "hidden");
    } else {
      let debeVal = parentRow.find(".valorDebe").val();
      if(debeVal && debeVal !== "0") {
        parentRow.find(".valorHaber").val(debeVal);
      }
      parentRow.find(".valorHaber").prop("readonly", false).css("visibility", "visible");
      parentRow.find(".valorDebe").prop("readonly", true).val("0").css("visibility", "hidden");
    }
    updateTotals();
  }

  $(document).ready(function(){
    // Inicializar cada movimiento
    $(".movimiento").each(function(){
      let selected = $(this).find(".tipoMovimiento:checked");
      ajustarInputs(selected);
    });

    // Al cambiar el radio
    $(document).on("change", ".tipoMovimiento", function(){
      ajustarInputs($(this));
    });

    // Formatear montos al perder el foco
    $(document).on("blur", ".valorDebe, .valorHaber", function(){
      let val = $(this).val().replace(/,/g, "");
      if(val !== ""){
        let num = parseFloat(val);
        if(!isNaN(num)){
          $(this).val(formatNumber(num));
        }
      }
      updateTotals();
    });

    // Quitar formato al enfocar
    $(document).on("focus", ".valorDebe, .valorHaber", function(){
      let val = $(this).val();
      $(this).val(val.replace(/,/g, ""));
    });

    // Agregar nuevo movimiento
    $("#agregarMovimiento").click(function(){
      let indice = $("#movimientosContainer .movimiento").length;
      let row = `
      <div class="row movimiento">
        <div class="col-md-5">
          <select name="cuenta[]" class="form-control" required>
            <option value="">Seleccione Cuenta</option>
                <?php 
                  // Se consulta nuevamente (o puedes usar un arreglo precargado) para generar las opciones
                  $sql2 = "SELECT NumCuenta, NombreCuenta FROM Cuentas ORDER BY NumCuenta";
                  $res2 = $conexion->query($sql2);
                  while($row2 = $res2->fetch_assoc()){
                      $selected = ($row2['NumCuenta'] == $mov['NumCuenta']) ? 'selected' : '';
                      echo "<option value='{$row2['NumCuenta']}' $selected>{$row2['NumCuenta']} - {$row2['NombreCuenta']}</option>";
                  }
                ?>
          </select>
          <div class="text-center mt-2">
            <div class="form-check form-check-inline">
              <input class="form-check-input tipoMovimiento" type="radio" name="lado[${indice}]" value="D" checked>
              <label class="form-check-label">Debe</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input tipoMovimiento" type="radio" name="lado[${indice}]" value="H">
              <label class="form-check-label">Haber</label>
            </div>
          </div>
        </div>
        <div class="col-md-5">
          <div class="t-container">
            <div class="t-debe">
              <input type="text" name="valorDebe[]" class="form-control valorDebe" placeholder="Valor Debe">
            </div>
            <div class="t-haber">
              <input type="text" name="valorHaber[]" class="form-control valorHaber" placeholder="Valor Haber">
            </div>
          </div>
        </div>
        <div class="col-md-2 text-right">
          <button type="button" class="btn btn-danger btn-sm eliminarMovimiento">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
      </div>`;
      $("#movimientosContainer").append(row);
      let nuevaFila = $("#movimientosContainer .movimiento").last();
      let selectedRadio = nuevaFila.find(".tipoMovimiento:checked");
      ajustarInputs(selectedRadio);
      // Habilitar botón eliminar si hay más de 2 movimientos
      if($("#movimientosContainer .movimiento").length > 2){
        $(".eliminarMovimiento").prop("disabled", false);
      }
    });

    // Eliminar movimiento
    $(document).on("click", ".eliminarMovimiento", function(){
      $(this).closest(".movimiento").remove();
      updateTotals();
      if($("#movimientosContainer .movimiento").length < 3){
        $(".eliminarMovimiento").prop("disabled", true);
      }
    });

    // Preview general: muestra la T completa (se muestran select e inputs, sin radio buttons)
    $("#previewGeneral").click(function(){
      // Clonar el contenedor global #tGlobal
      let tCompleta = $("#tGlobal").clone();
      // En el clon, remover los radio buttons (los elementos .tipoMovimiento y sus contenedores .form-check)
      tCompleta.find(".tipoMovimiento, .form-check").remove();
      // Recorrer cada select del contenedor original para copiar el valor seleccionado al clon
      $("#tGlobal select").each(function(index) {
        let currentVal = $(this).val();
        tCompleta.find("select").eq(index).val(currentVal);
      });
      // Deshabilitar inputs
      tCompleta.find("input, select, textarea").prop("disabled", true);
      $("#previewContent").html(tCompleta);
      $("#previewModal").modal("show");
    });

    updateTotals();

    // Validación final al enviar el formulario
    $("#formPoliza").submit(function(e){
      let totalDebe = 0, totalHaber = 0;
      $(".movimiento").each(function(){
        totalDebe += parseFloat($(this).find(".valorDebe").val().replace(/,/g, "")) || 0;
        totalHaber += parseFloat($(this).find(".valorHaber").val().replace(/,/g, "")) || 0;
      });
      if(totalDebe !== totalHaber){
        e.preventDefault();
        alert("No se puede guardar la póliza. Los totales de Debe y Haber deben ser iguales.");
      }
    });
  });
</script>
</body>
</html>
<?php include 'footer.php'; ?>
