<?php
require_once('../tcpdf/tcpdf.php');
include '../conexion.php';

// Recibir parámetro de reporte y otros posibles parámetros
$reporte = isset($_GET['reporte']) ? $_GET['reporte'] : '';
$html = '';
$titulo = '';

// Mapeo para convertir la inicial del tipo a su nombre completo (para mayor)
$tipoCompleto = [
    'A' => 'Activo',
    'P' => 'Pasivo',
    'C' => 'Capital',
    'I' => 'Ingresos',
    'G' => 'Gastos'
];

switch($reporte){
    case 'diario':
        // Reporte Diario
        $fechaFiltro = isset($_GET['fecha']) ? $_GET['fecha'] : '';
        $where = $fechaFiltro ? "WHERE p.Fecha = '$fechaFiltro'" : "";
        $titulo = "EMPRESA XXXX - REPORTE DE DIARIO";
        
        $sql = "SELECT p.NumPoliza, p.Fecha, p.Descripcion, dp.NumCuenta, dp.DebeHaber, dp.Valor 
                FROM Polizas p 
                JOIN DetallePoliza dp ON p.NumPoliza = dp.NumPoliza 
                $where
                ORDER BY p.Fecha, p.NumPoliza";
        $result = $conexion->query($sql);
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
            $polizas[$numPoliza]['movimientos'][] = [
                'NumCuenta' => $row['NumCuenta'],
                'DebeHaber' => $row['DebeHaber'],
                'Valor' => $row['Valor']
            ];
        }
        
        $html .= "<h2 style='text-align:center;'>$titulo</h2>";
        if($fechaFiltro){
            $html .= "<p><strong>Fecha:</strong> " . date("d/m/Y", strtotime($fechaFiltro)) . "</p>";
        }
        if(empty($polizas)){
            $html .= "<p>No se encontraron pólizas para la fecha indicada.</p>";
        } else {
            foreach ($polizas as $numPoliza => $datos){
                $html .= "<h3>Póliza: $numPoliza</h3>";
                $html .= "<p>Fecha: " . date("d/m/Y", strtotime($datos['Fecha'])) . " - Descripción: " . $datos['Descripcion'] . "</p>";
                
                // Tabla: 3 columnas -> Cuenta, Debe, Haber
                $html .= "<table border='1' cellpadding='4' cellspacing='0'>
                            <thead style='background-color:#f2f2f2;'>
                                <tr>
                                    <th>Cuenta</th>
                                    <th style='text-align:right;'>Debe</th>
                                    <th style='text-align:right;'>Haber</th>
                                </tr>
                            </thead>
                            <tbody>";
                $totalDebe = 0;
                $totalHaber = 0;
                foreach ($datos['movimientos'] as $mov){
                    $debe = ($mov['DebeHaber'] == 'D') ? "Q " . number_format($mov['Valor'], 2, '.', ',') : "";
                    $haber = ($mov['DebeHaber'] == 'H') ? "Q " . number_format($mov['Valor'], 2, '.', ',') : "";
                    $totalDebe += ($mov['DebeHaber'] == 'D') ? $mov['Valor'] : 0;
                    $totalHaber += ($mov['DebeHaber'] == 'H') ? $mov['Valor'] : 0;
                    
                    $html .= "<tr>
                                <td>{$mov['NumCuenta']}</td>
                                <td style='text-align:right;'>$debe</td>
                                <td style='text-align:right;'>$haber</td>
                              </tr>";
                }
                
                // Fila de totales
                $html .= "<tr style='font-weight:bold;'>
                            <!-- Dejamos la primera celda en blanco y unimos 1 celda más para alinear 'Totales' debajo de 'Cuenta' -->
                            <td colspan='1' style='text-align:right;'>Totales</td>
                            <td style='text-align:right;'>Q " . number_format($totalDebe, 2, '.', ',') . "</td>
                            <td style='text-align:right;'>Q " . number_format($totalHaber, 2, '.', ',') . "</td>
                          </tr>";
                $html .= "</tbody></table><br>";
            }
        }
        break;
        
        case 'balance':
            // Reporte de Balance
            $titulo = "EMPRESA XXXX - BALANCE";
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
            
            $html .= "<h2 style='text-align:center;'>$titulo</h2>";
            // Tabla normal de Balance -> 6 columnas
            $html .= "<table border='1' cellpadding='4' cellspacing='0'>
                        <thead style='background-color:#f2f2f2;'>
                            <tr>
                                <th>Número</th>
                                <th>Cuenta</th>
                                <th style='text-align:right;'>Debe</th>
                                <th style='text-align:right;'>Haber</th>
                                <th style='text-align:right;'>Saldo Debe</th>
                                <th style='text-align:right;'>Saldo Haber</th>
                            </tr>
                        </thead>
                        <tbody>";
            
            $totalDebeGeneral = 0;
            $totalHaberGeneral = 0;
            $totalSaldoDebeGeneral = 0;
            $totalSaldoHaberGeneral = 0;
            
            $tiposOrden = [
                'A' => 'Activo',
                'P' => 'Pasivo',
                'C' => 'Capital',
                'I' => 'Ingresos',
                'G' => 'Gastos'
            ];
            
            foreach($tiposOrden as $tipoCode => $tipoDesc){
                if(isset($balancePorTipo[$tipoCode])){
                    // Subtítulo
                    $html .= "<tr style='background-color:#e9ecef; font-weight:bold;'>
                                <td colspan='6'>$tipoDesc</td>
                              </tr>";
                    
                    // Filas normales: 6 celdas
                    foreach($balancePorTipo[$tipoCode] as $row){
                        $saldo = $row['Debe'] - $row['Haber'];
                        $saldoDebe = ($saldo > 0) ? $saldo : 0;
                        $saldoHaber = ($saldo < 0) ? abs($saldo) : 0;
                        
                        $totalDebeGeneral += $row['Debe'];
                        $totalHaberGeneral += $row['Haber'];
                        $totalSaldoDebeGeneral += $saldoDebe;
                        $totalSaldoHaberGeneral += $saldoHaber;
                        
                        $html .= "<tr>
                                    <td style='text-align:center;'>{$row['NumCuenta']}</td>
                                    <td>{$row['NombreCuenta']} ($tipoDesc)</td>
                                    <td style='text-align:right;'>" . ($row['Debe'] > 0 ? "Q " . number_format($row['Debe'], 2, '.', ',') : "") . "</td>
                                    <td style='text-align:right;'>" . ($row['Haber'] > 0 ? "Q " . number_format($row['Haber'], 2, '.', ',') : "") . "</td>
                                    <td style='text-align:right;'>" . ($saldoDebe > 0 ? "Q " . number_format($saldoDebe, 2, '.', ',') : "") . "</td>
                                    <td style='text-align:right;'>" . ($saldoHaber > 0 ? "Q " . number_format($saldoHaber, 2, '.', ',') : "") . "</td>
                                  </tr>";
                    }
                }
            }
            
            // Fila de totales -> Se agrega UNA celda en blanco adicional 
            // (7 celdas en total), en lugar de las 6 normales
            // 1) col 1 -> colspan="2" con "Totales"
            // 2) col 2 -> celdablancca
            // 3) col 3 -> Debe
            // 4) col 4 -> Haber
            // 5) col 5 -> Saldo Debe
            // 6) col 6 -> Saldo Haber
            
            $html .= "<tr style='font-weight:bold;'>";
            $html .= "<td colspan='2' style='text-align:right;'>Totales</td>";
            
            // Celda en blanco (la extra)
            $html .= "<td></td>";
            
            // Las 3 celdas restantes con los totales
            $html .= "<td style='text-align:right;'>" . ($totalDebeGeneral > 0 ? "Q " . number_format($totalDebeGeneral, 2, '.', ',') : "") . "</td>";
            $html .= "<td style='text-align:right;'>" . ($totalHaberGeneral > 0 ? "Q " . number_format($totalHaberGeneral, 2, '.', ',') : "") . "</td>";
            $html .= "<td style='text-align:right;'>" . ($totalSaldoDebeGeneral > 0 ? "Q " . number_format($totalSaldoDebeGeneral, 2, '.', ',') : "") . "</td>";
            $html .= "<td style='text-align:right;'>" . ($totalSaldoHaberGeneral > 0 ? "Q " . number_format($totalSaldoHaberGeneral, 2, '.', ',') : "") . "</td>";
            $html .= "</tr>";
            
            $html .= "</tbody></table>";
            break;
            
        case 'mayor':
            // Reporte de Mayor
            if(!isset($_GET['cuenta'])){
                die("Debe especificar la cuenta. Ejemplo: descargar_pdf.php?reporte=mayor&cuenta=101");
            }
            $numCuenta = $_GET['cuenta'];
            
            // Obtener información de la cuenta
            $stmt = $conexion->prepare("SELECT NombreCuenta, Tipo FROM Cuentas WHERE NumCuenta = ?");
            $stmt->bind_param("i", $numCuenta);
            $stmt->execute();
            $result = $stmt->get_result();
            $cuentaInfo = $result->fetch_assoc();
            if(!$cuentaInfo){
                die("Cuenta no encontrada.");
            }
            
            $titulo = "EMPRESA XXXX - MAYOR";
            $tipoNombre = isset($tipoCompleto[$cuentaInfo['Tipo']]) ? $tipoCompleto[$cuentaInfo['Tipo']] : $cuentaInfo['Tipo'];
            
            $html .= "<h2 style='text-align:center;'>$titulo</h2>";
            $html .= "<h3>Cuenta: " . $cuentaInfo['NombreCuenta'] . " (" . $tipoNombre . ")</h3>";
            
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
            
            // Tabla normal de Mayor -> 5 columnas
            $html .= "<table border='1' cellpadding='4' cellspacing='0'>
                        <thead style='background-color:#f2f2f2;'>
                            <tr>
                                <th>Póliza</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th style='text-align:right;'>Debe</th>
                                <th style='text-align:right;'>Haber</th>
                            </tr>
                        </thead>
                        <tbody>";
            while($row = $result->fetch_assoc()){
                $debe = ($row['DebeHaber'] == 'D') ? "Q " . number_format($row['Valor'], 2, '.', ',') : "";
                $haber = ($row['DebeHaber'] == 'H') ? "Q " . number_format($row['Valor'], 2, '.', ',') : "";
                $totalDebe += ($row['DebeHaber'] == 'D') ? $row['Valor'] : 0;
                $totalHaber += ($row['DebeHaber'] == 'H') ? $row['Valor'] : 0;
                
                $html .= "<tr>
                            <td>#{$row['NumPoliza']}</td>
                            <td>" . date("d/m/Y", strtotime($row['Fecha'])) . "</td>
                            <td>{$row['Descripcion']}</td>
                            <td style='text-align:right;'>$debe</td>
                            <td style='text-align:right;'>$haber</td>
                          </tr>";
            }
            
            // Fila de totales -> 7 celdas en lugar de 5:
            //  - colspan="3" con 'Totales'
            //  - 2 celdas en blanco
            //  - 1 celda Debe
            //  - 1 celda Haber
            $html .= "<tr style='font-weight:bold;'>
                        <td colspan='3' style='text-align:right;'>Totales</td>
                        <td></td>
                        <td></td>
                        <td style='text-align:right;'>Q " . number_format($totalDebe, 2, '.', ',') . "</td>
                        <td style='text-align:right;'>Q " . number_format($totalHaber, 2, '.', ',') . "</td>
                      </tr>";
            
            // Saldo -> misma lógica (7 celdas)
            $saldoGlobal = $totalDebe - $totalHaber;
            $html .= "<tr style='font-weight:bold;'>
                        <td colspan='3' style='text-align:right;'>Saldo</td>
                        <td></td>
                        <td></td>";
            if($saldoGlobal > 0){
                // Saldo Debe
                $html .= "<td style='text-align:right;'>Q " . number_format($saldoGlobal, 2, '.', ',') . " (Saldo Debe)</td>";
                $html .= "<td style='text-align:right;'></td>";
            } elseif($saldoGlobal < 0){
                // Saldo Haber
                $html .= "<td style='text-align:right;'></td>";
                $html .= "<td style='text-align:right;'>Q " . number_format(abs($saldoGlobal), 2, '.', ',') . " (Saldo Haber)</td>";
            } else {
                // Saldo 0
                $html .= "<td colspan='2' style='text-align:right;'>Q 0.00</td>";
            }
            $html .= "</tr>";
            
            $html .= "</tbody></table>";
            break;
            
        default:
            die("Reporte no especificado.");
    }
    
    // Crear nuevo documento PDF
    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Información del documento
    $pdf->SetCreator('Sistema Contabilidad');
    $pdf->SetAuthor('Tu Nombre o Empresa');
    $pdf->SetTitle($titulo);
    $pdf->SetSubject($titulo);
    
    // Márgenes
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Eliminar cabecera y pie de página predeterminados
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Agregar una página
    $pdf->AddPage();
    
    // Escribir el contenido HTML en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Salida del PDF
    $pdf->Output('reporte.pdf', 'I');