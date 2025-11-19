<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?error=Debes iniciar sesión');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Viaje</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Detalles de Viaje</h1>
        <!-- Aquí se mostrarán los detalles del viaje -->
        <!-- La idea es que rellenen con lo solicitado en la sección 2.3 del enunciado -->
        <!-- Apoyate con los estilos css para que se vea bonito :) -->
    </div>
</body>
</html>
