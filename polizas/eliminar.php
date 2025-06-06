<?php
include '../conexion.php';

if(isset($_GET['num'])){
  $numPoliza = $_GET['num'];
  
  // Primero, eliminar los movimientos de la póliza en DetallePoliza
  $stmt = $conexion->prepare("DELETE FROM DetallePoliza WHERE NumPoliza = ?");
  $stmt->bind_param("i", $numPoliza);
  $stmt->execute();
  
  // Luego, eliminar la cabecera en Polizas
  $stmt = $conexion->prepare("DELETE FROM Polizas WHERE NumPoliza = ?");
  $stmt->bind_param("i", $numPoliza);
  $stmt->execute();
}
header("Location: listar.php");
?>

