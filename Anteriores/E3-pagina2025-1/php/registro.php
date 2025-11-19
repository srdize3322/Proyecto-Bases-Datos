<?php
session_start();
$mensaje_error = $_SESSION['error'] ?? null;
$mensaje_success = $_SESSION['success'] ?? null;
$form_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['error'], $_SESSION['success'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Registrar nuevo usuario</h1>

        <form action="procesar_registro.php" method="POST" class="formulario-grid-2x4">

            <div class="form-group">
                <label for="nombre_usuario">Nombre de usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario"
                    value="<?= htmlspecialchars($form_data['nombre_usuario'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="clave">Contraseña:</label>
                <input type="password" id="clave" name="clave" required>
            </div>

            <div class="form-group">
                <label for="repetir_clave">Repetir contraseña:</label>
                <input type="password" id="repetir_clave" name="repetir_clave" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono"
                    value="<?= htmlspecialchars($form_data['telefono'] ?? '') ?>" required
                    placeholder="Ej: +569 1234 5678">
            </div>

            <div class="form-group">
                <label for="run">RUN:</label>
                <input type="text" id="run" name="run"
                    value="<?= htmlspecialchars($form_data['run'] ?? '') ?>" required
                    placeholder="Ej: 12345678">
            </div>

            <div class="form-group">
                <label for="dv">DV:</label>
                <input type="text" id="dv" name="dv"
                    value="<?= htmlspecialchars($form_data['dv'] ?? '') ?>" required
                    placeholder="Ej: 9">
            </div>

            <div class="form-group">
                <label for="nombre_real">Nombre completo:</label>
                <input type="text" id="nombre_real" name="nombre_real"
                    value="<?= htmlspecialchars($form_data['nombre_real'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email"
                    value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
            </div>
            <div class="grid-full center-content">
                <button type="submit">Registrar</button>

                <?php if ($mensaje_error): ?>
                    <p class="error"><?= htmlspecialchars($mensaje_error) ?></p>
                <?php elseif ($mensaje_success): ?>
                    <p class="success"><?= htmlspecialchars($mensaje_success) ?></p>
                <?php endif; ?>

                <p><a href="main.php">Volver al inicio</a></p>
            </div>
        </form>
    </div>
</body>
</html>
