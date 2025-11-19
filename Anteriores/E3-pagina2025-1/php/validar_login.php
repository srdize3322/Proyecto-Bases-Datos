<?php
session_start();
require_once 'utils.php';

$usuario = $_POST['usuario'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

$db = conectarBD();

$query = "INSERTE SU CONSULTA SQL AQUI";
$stmt = $db->prepare($query);
$stmt->bindParam(':usuario', $usuario);
$stmt->bindParam(':contrasena', $contrasena);
$stmt->execute();

$resultado = $stmt->fetch();

if ($resultado) {
    $_SESSION['usuario'] = $usuario;
    header('Location: main.php');
    exit();
} else {
    header('Location: index.php?error=Usuario no existe o contrasena errónea');
    exit();
}
?>
