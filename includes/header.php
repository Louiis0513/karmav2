<?php
$mensaje = $mensaje ?? "";
if ($mensaje === "") {
    $mensaje = obtenerMensaje();
}
$paginaActual = basename($_SERVER["PHP_SELF"] ?? "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? e($tituloPagina) . " · KARMA" : "KARMA · Tutorías" ?></title>
    <meta name="description" content="KARMA conecta estudiantes de bachillerato con tutores verificados en Bogotá.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="nav-publica" aria-label="Principal">
            <a href="index.php" class="nav-marca">
                <img src="img/logo.png" alt="Logo de KARMA">
                KARMA
            </a>
            <button class="nav-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-links">
                <a href="index.php" class="<?= $paginaActual === 'index.php' ? 'activo' : '' ?>">Inicio</a>
                <a href="tutores.php" class="<?= in_array($paginaActual, ['tutores.php', 'tutor.php', 'solicitud.php'], true) ? 'activo' : '' ?>">Tutores</a>
                <a href="index.php#materias">Materias</a>
                <a href="index.php#nosotros">Nosotros</a>
                <a href="tutores.php" class="nav-cta">Buscar tutor</a>
            </div>
        </nav>
    </header>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?= e($mensaje) ?></p>
    <?php endif; ?>
