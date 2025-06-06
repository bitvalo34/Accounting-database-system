<?php
include '../conexion.php';

// Obtener la lista de cuentas
$queryCuentas = "SELECT NumCuenta, NombreCuenta FROM Cuentas ORDER BY NumCuenta";
$resCuentas = $conexion->query($queryCuentas);
$optionsCuentas = "";
while($row = $resCuentas->fetch_assoc()){
    $optionsCuentas .= "<option value='".$row['NumCuenta']."'>".$row['NumCuenta']." - ".$row['NombreCuenta']."</option>";
}

// Procesamiento del formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $numPoliza = $_POST['numPoliza'];
    $fecha = $_POST['fecha'];
    $descripcion = $_POST['descripcion'];

    // Verificar si ya existe una póliza con el mismo número
    $stmtCheck = $conexion->prepare("SELECT COUNT(*) AS count FROM Polizas WHERE NumPoliza = ?");
    $stmtCheck->bind_param("i", $numPoliza);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();
    if ($rowCheck['count'] > 0) {
        $error = "Ya existe una póliza con el número $numPoliza. Por favor, ingrese un número de póliza único.";
    }
    
    if(!isset($error)){
        $cuentas      = $_POST['cuenta'];
        $lados        = $_POST['lado'];
        $valoresDebe  = $_POST['valorDebe'];
        $valoresHaber = $_POST['valorHaber'];

           // Insertar la cabecera en Polizas
        $stmt = $conexion->prepare("INSERT INTO Polizas (NumPoliza, Fecha, Descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $numPoliza, $fecha, $descripcion);
        if(!$stmt->execute()){
          $error = "Error al insertar la póliza: " . $stmt->error;
        }
        
        // Validar totales
        $totalDebe = 0;
        $totalHaber = 0;
        foreach($lados as $i => $lado){
            $valorSinComas = str_replace(',','', ($lado === 'D') ? ($valoresDebe[$i] ?? "0") : ($valoresHaber[$i] ?? "0"));
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
            // Insertar movimientos en DetallePoliza
            for($i = 0; $i < count($cuentas); $i++){
                $numCuenta = $cuentas[$i];
                $lado = $lados[$i];
                $valorSinComas = str_replace(',','', ($lado === 'D') ? ($valoresDebe[$i] ?? "0") : ($valoresHaber[$i] ?? "0"));
                $valor = floatval($valorSinComas);
                $stmt2 = $conexion->prepare("INSERT INTO DetallePoliza (NumPoliza, NumCuenta, DebeHaber, Valor) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iisd", $numPoliza, $numCuenta, $lado, $valor);
                if(!$stmt2->execute()){
                    $error = "Error al insertar el movimiento: " . $stmt2->error;
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
  <title>Agregar Póliza</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .mensaje-validacion {
      font-weight: bold;
      color: red;
    }
    /* Contenedor global de la T: se imprime el borde horizontal solo una vez */
    #tGlobal {
      border-top: 2px solid #000;
      margin-top: 10px;
      padding-top: 10px;
    }
    .movimiento {
      margin-bottom: 10px;
    }
    /* Contenedor T para los montos: solo la línea vertical */
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
    /* Totales: se alinean justo debajo de cada columna de la T */
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
  <h2>Agregar Póliza</h2>
  <?php if(isset($error)){ echo "<div class='alert alert-danger'>$error</div>"; } ?>
  
  <form id="formPoliza" method="post">
    <!-- Cabecera -->
    <div class="form-group">
      <label for="numPoliza">Número de Póliza</label>
      <input type="number" class="form-control" name="numPoliza" id="numPoliza" required>
    </div>
    <div class="form-group">
      <label for="fecha">Fecha</label>
      <input type="date" class="form-control" name="fecha" id="fecha" required>
    </div>
    <div class="form-group">
      <label for="descripcion">Descripción</label>
      <textarea class="form-control" name="descripcion" id="descripcion" required></textarea>
    </div>

    <h3>Movimientos</h3>
    <!-- Mensaje de validación en tiempo real -->
    <div id="mensajeValidacion" class="mensaje-validacion mb-3"></div>

    <!-- Contenedor global para la T completa -->
    <div id="tGlobal">
      <div id="movimientosContainer">
        <!-- Movimiento 1: Por defecto, debe -->
        <div class="row movimiento">
          <!-- Parte izquierda: select y radios -->
          <div class="col-md-5">
            <select name="cuenta[]" class="form-control" required>
              <option value="">Seleccione Cuenta</option>
              <?php echo $optionsCuentas; ?>
            </select>
            <div class="text-center mt-2">
              <div class="form-check form-check-inline">
                <input class="form-check-input tipoMovimiento" type="radio" name="lado[0]" value="D" checked>
                <label class="form-check-label">Debe</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input tipoMovimiento" type="radio" name="lado[0]" value="H">
                <label class="form-check-label">Haber</label>
              </div>
            </div>
          </div>
          <!-- Parte derecha: T gráfica -->
          <div class="col-md-5">
            <div class="t-container">
              <div class="t-debe">
                <input type="text" name="valorDebe[]" class="form-control valorDebe" placeholder="Valor Debe">
              </div>
              <div class="t-haber">
                <!-- En este movimiento, por defecto, se oculta el input de Haber -->
                <input type="text" name="valorHaber[]" class="form-control valorHaber" placeholder="Valor Haber" value="0" style="visibility: hidden;">
              </div>
            </div>
          </div>
          <!-- Botón eliminar -->
          <div class="col-md-2 text-right">
            <button type="button" class="btn btn-danger btn-sm eliminarMovimiento" disabled>
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
        <!-- Movimiento 2: Por defecto, haber -->
        <div class="row movimiento">
          <!-- Parte izquierda: select y radios -->
          <div class="col-md-5">
            <select name="cuenta[]" class="form-control" required>
              <option value="">Seleccione Cuenta</option>
              <?php echo $optionsCuentas; ?>
            </select>
            <div class="text-center mt-2">
              <div class="form-check form-check-inline">
                <input class="form-check-input tipoMovimiento" type="radio" name="lado[1]" value="D">
                <label class="form-check-label">Debe</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input tipoMovimiento" type="radio" name="lado[1]" value="H" checked>
                <label class="form-check-label">Haber</label>
              </div>
            </div>
          </div>
          <!-- Parte derecha: T gráfica -->
          <div class="col-md-5">
            <div class="t-container">
              <div class="t-debe">
                <!-- En este movimiento, por defecto, se oculta el input de Debe -->
                <input type="text" name="valorDebe[]" class="form-control valorDebe" placeholder="Valor Debe" value="0" style="visibility: hidden;">
              </div>
              <div class="t-haber">
                <input type="text" name="valorHaber[]" class="form-control valorHaber" placeholder="Valor Haber">
              </div>
            </div>
          </div>
          <!-- Botón eliminar -->
          <div class="col-md-2 text-right">
            <button type="button" class="btn btn-danger btn-sm eliminarMovimiento" disabled>
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Totales: se alinean justo debajo de la parte derecha (T) -->
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
    <!-- Botón de Preview general: muestra la T completa (se muestran select e inputs, sin radios) -->
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

  // Función para mostrar solo el input de Debe o Haber (funcionalidad anterior)
  function ajustarInputs(radio) {
    let parentRow = radio.closest(".movimiento");
    if(radio.val() === 'D'){
      parentRow.find(".valorDebe").prop("readonly", false).css("visibility", "visible");
      parentRow.find(".valorHaber").prop("readonly", true).val("0").css("visibility", "hidden");
    } else {
      parentRow.find(".valorHaber").prop("readonly", false).css("visibility", "visible");
      parentRow.find(".valorDebe").prop("readonly", true).val("0").css("visibility", "hidden");
    }
    updateTotals();
  }

  $(document).ready(function(){
    // Inicializar cada movimiento (dos ya por defecto)
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
            <?php echo addslashes($optionsCuentas); ?>
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
      // Habilitar botón eliminar si hay más de 2 movimientos (ya que por defecto tenemos 2)
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

    // Preview general: muestra la T completa (se muestran select e inputs, pero se eliminan los radio buttons)
    $("#previewGeneral").click(function(){
      // Clonar el contenedor global #tGlobal
      let tCompleta = $("#tGlobal").clone();
      // En el clon, remover los radio buttons
      tCompleta.find(".tipoMovimiento, .form-check").remove();

      // Recorrer cada select del contenedor original y actualizar el select correspondiente en el clon
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




