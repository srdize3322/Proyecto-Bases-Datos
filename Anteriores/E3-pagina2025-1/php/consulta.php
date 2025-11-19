<?php
session_start();
require_once 'utils.php';

// Tablas válidas en el sistema
$tablas = [
    'agenda', 'habitacion', 'participante', 'avion', 'reserva',
    'airbnb', 'bus', 'tren', 'review', 'persona', 'panorama',
    'usuario', 'transporte', 'seguro', 'empleado', 'hospedaje', 'hotel'
];

// Variables de apoyo
$tablaSel = $_POST['tabla'] ?? '';
$columnaSel = trim($_POST['columna'] ?? '');
$whereCampo = trim($_POST['where_campo'] ?? '');
$whereValor = trim($_POST['where_valor'] ?? '');

$resultados = [];
$error = '';

// Aquí debe escribir la lógica de la consulta y ejecución

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta Inestructurada Guiada</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Consulta Inestructurada Guiada</h1>

    <form method="POST" class="formulario">
        <div class="form-group">
            <label for="columna">Columna a mostrar (SELECT):</label>
            <input type="text" name="columna" id="columna" required value="<?= htmlspecialchars($columnaSel) ?>">
        </div>

        <div class="form-group">
            <label for="tabla">Tabla (FROM):</label>
            <select name="tabla" id="tabla" required>
                <option value="">-- Seleccionar tabla --</option>
                <?php foreach ($tablas as $tabla): ?>
                    <option value="<?= $tabla ?>" <?= $tablaSel === $tabla ? 'selected' : '' ?>><?= $tabla ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="where_campo">Campo para filtrar (WHERE):</label>
            <input type="text" name="where_campo" id="where_campo" value="<?= htmlspecialchars($whereCampo) ?>">
        </div>

        <div class="form-group">
            <label for="where_valor">Valor:</label>
            <input type="text" name="where_valor" id="where_valor" value="<?= htmlspecialchars($whereValor) ?>">
        </div>

        <button type="submit">Ejecutar</button>
        <p><a href="main.php">Volver al inicio</a></p>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if (!empty($resultados)): ?>
        <table class="tabla-estandar">
            <thead>
                <tr>
                    <?php foreach (array_keys($resultados[0]) as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                            <td><?= htmlspecialchars($valor) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
