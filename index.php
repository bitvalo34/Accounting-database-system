<?php include 'header.php'; ?>
<!-- Se incluye el encabezado común, que contiene la cabecera y la barra de navegación -->

<!-- Jumbotron de bienvenida -->
<div class="jumbotron text-center">
    <!-- Título principal con estilo de display y negrita -->
    <h1 class="display-4 font-weight-bold">Bienvenido a Contabilidad</h1>
    <!-- Línea horizontal personalizada para separar el título del contenido -->
    <hr class="my-4" style="border-top: 2px solid #00aaff; width: 50%;">
    <p>Utiliza el menú para navegar a las distintas secciones.</p>
    <!-- Botones de acción para gestionar cuentas y pólizas -->
    <a class="btn btn-custom btn-lg" href="cuentas/listar.php" role="button">Gestionar Cuentas</a>
    <a class="btn btn-custom btn-lg" href="polizas/listar.php" role="button">Gestionar Pólizas</a>
</div>

<!-- Sección principal de contenido -->
<div class="container">
    <div class="row">
        <!-- Tarjeta para la sección de Cuentas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-custom text-white">
                    Cuentas
                </div>
                <div class="card-body">
                    <p class="card-text">Agrega, modifica y elimina cuentas para llevar el control financiero.</p>
                    <a href="cuentas/listar.php" class="btn btn-custom">Ver Cuentas</a>
                </div>
            </div>
        </div>
        <!-- Tarjeta para la sección de Pólizas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-custom text-white">
                    Pólizas
                </div>
                <div class="card-body">
                    <p class="card-text">Registra y administra las pólizas contables de tu empresa.</p>
                    <a href="polizas/listar.php" class="btn btn-custom">Ver Pólizas</a>
                </div>
            </div>
        </div>
        <!-- Tarjeta para la sección de Reportes -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-custom text-white">
                    Reportes
                </div>
                <div class="card-body">
                    <p class="card-text">Genera reportes de diario, balance y mayor para analizar tu información financiera.</p>
                    <a href="reportes/diario.php" class="btn btn-custom">Ver Reportes</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>

