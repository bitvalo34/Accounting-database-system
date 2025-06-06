<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Contabilidad</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Font Awesome (opcional) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Estilos globales -->
  <link rel="stylesheet" href="/contabilidad/css/estilos.css">
</head>
<body class="d-flex flex-column min-vh-100">
  <!-- Encabezado / Navbar -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-custom">
      <a class="navbar-brand" href="/contabilidad/index.php">Contabilidad</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon" style="color:#fff;"><i class="fas fa-bars"></i></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item"><a class="nav-link" href="/contabilidad/cuentas/listar.php">Cuentas</a></li>
          <li class="nav-item"><a class="nav-link" href="/contabilidad/polizas/listar.php">Pólizas</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-toggle="dropdown">
              Reportes
            </a>
            <div class="dropdown-menu">
              <a class="dropdown-item" href="/contabilidad/reportes/diario.php">Diario</a>
              <a class="dropdown-item" href="/contabilidad/reportes/mayor.php">Mayor</a>
              <a class="dropdown-item" href="/contabilidad/reportes/balance.php">Balance</a>
            </div>
          </li>
        </ul>
      </div>
    </nav>
  </header>

  <!-- Contenido principal -->
  <main class="flex-grow-1">
    <div class="container mt-4">
