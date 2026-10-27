<?php
$mensaje = $mensaje ?? "";
if ($mensaje === "") {
    $mensaje = obtenerMensaje();
}
$paginaActual = basename($_SERVER["PHP_SELF"] ?? "");
$tutorNombre = $_SESSION["tutor"]["nombre"] ?? "Tutor";
$verificadoTutor = (int)($_SESSION["tutor"]["verificado"] ?? 0) === 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($tituloPagina) ? e($tituloPagina) . " · Mi panel KARMA" : "Mi panel KARMA" ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav class="nav-admin" aria-label="Panel del tutor">
            <a href="index.php" class="nav-marca">
                <img src="../img/logo.png" alt="">
                Mi panel · KARMA
            </a>
            <div class="nav-admin-links">
                <a href="index.php" class="<?= $paginaActual === 'index.php' ? 'activo' : '' ?>">Inicio</a>
                <a href="perfil.php" class="<?= $paginaActual === 'perfil.php' ? 'activo' : '' ?>">Mi perfil</a>
                <a href="../mis_chats.php">Mis chats</a>
                <a href="solicitudes.php" class="<?= $paginaActual === 'solicitudes.php' ? 'activo' : '' ?>">Solicitudes</a>
                <a href="../index.php" target="_blank" rel="noopener">Ver sitio</a>
                <a href="../logout.php" class="nav-salir">Salir</a>
            </div>
        </nav>
    </header>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?= e($mensaje) ?></p>
    <?php endif; ?>

    <?php if (!$verificadoTutor): ?>
        <p class="mensaje mensaje-error">Tu perfil está <strong>pendiente de verificación</strong>. Todavía no aparece en el sitio público hasta que un administrador lo apruebe.</p>
    <?php endif; ?>

    <div class="admin-shell">
