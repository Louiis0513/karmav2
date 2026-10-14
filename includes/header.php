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
    <title>KARMA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header>
        <nav class="nav nav-publica">
            <a href="index.php" class="nav-marca">
                <img src="img/logo.png" alt="Logo de KARMA">
                KARMA
            </a>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <a href="tutores.php">Tutores</a>
                <a href="index.php#nosotros">Nosotros</a>
                <a href="index.php#ubicacion">Ubicacion</a>
            </div>
        </nav>
    </header>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?= e($mensaje) ?></p>
    <?php endif; ?>
