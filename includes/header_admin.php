<?php
$mensaje = $mensaje ?? "";
if ($mensaje === "") {
    $mensaje = obtenerMensaje();
}
$paginaActual = basename($_SERVER["PHP_SELF"] ?? "");
$adminNombre = $_SESSION["admin"]["nombre"] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? e($tituloPagina) . " · Panel KARMA" : "Panel KARMA" ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav class="nav-admin" aria-label="Administración">
            <a href="index.php" class="nav-marca">
                <img src="../img/logo.png" alt="">
                Panel KARMA
            </a>
            <div class="nav-admin-links">
                <a href="index.php" class="<?= $paginaActual === 'index.php' ? 'activo' : '' ?>">Inicio</a>
                <a href="materias.php" class="<?= $paginaActual === 'materias.php' ? 'activo' : '' ?>">Materias</a>
                <a href="tutores.php" class="<?= in_array($paginaActual, ['tutores.php', 'tutor_form.php'], true) ? 'activo' : '' ?>">Tutores</a>
                <a href="solicitudes.php" class="<?= $paginaActual === 'solicitudes.php' ? 'activo' : '' ?>">Solicitudes</a>
                <a href="../index.php" target="_blank" rel="noopener">Ver sitio</a>
                <a href="logout.php" class="nav-salir">Salir</a>
            </div>
        </nav>
    </header>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?= e($mensaje) ?></p>
    <?php endif; ?>

    <div class="admin-shell">
