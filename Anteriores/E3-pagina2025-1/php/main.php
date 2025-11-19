<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?error=Debes iniciar sesión primero');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></h1>
        <h2>¿Qué deseas hacer?</h2>

        <div class="menu">
            <a href="crear_viaje.php" class="btn">Crear Viaje</a>
            <a href="desplegar_viaje.php" class="btn">Ver Información del Viaje</a>
            <a href="consulta.php" class="btn">Consulta inestructurada</a>
            <a href="cerrar_sesion.php" class="btn btn-secondary">Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>
