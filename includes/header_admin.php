<?php
$mensaje = $mensaje ?? "";
if ($mensaje === "") {
    $mensaje = obtenerMensaje();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de KARMA</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

    <header>
        <nav class="nav">
            <a href="index.php">Panel principal</a>
            <a href="materias.php">Gestión de materias</a>
            <a href="tutores.php">Gestión de tutores</a>
            <a href="solicitudes.php">Bandeja de solicitudes</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?= e($mensaje) ?></p>
    <?php endif; ?>
