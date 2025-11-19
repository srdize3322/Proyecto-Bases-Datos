<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?error=Debes iniciar sesión');
    exit();
}
$mensaje = $_GET['mensaje'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Viaje</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Crear nuevo viaje</h1>
        <form action="procesar_crear_viaje.php" method="POST" class="formulario">
            <label for="nombre">Nombre del viaje:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required></textarea>

            <label for="fecha_inicio">Fecha de inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required>

            <label for="fecha_fin">Fecha de término:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>

            <label for="ciudad">Ciudad destino:</label>
            <input type="text" id="ciudad" name="ciudad" required>

            <label for="organizador">Organizador (usuario):</label>
            <input type="text" id="organizador" name="organizador" required>

            <!-- Cuando se crea el viaje, se debe calcular el precio total y puntaje obtenido por este y luego sumarle ese puntaje al usuario  -->

            <button type="submit">Crear viaje</button>
        </form>

        <?php if ($mensaje): ?>
            <p class="success"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <p><a href="main.php">Volver al inicio</a></p>
    </div>
</body>
</html>
