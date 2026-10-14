<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

/*
 * Pantalla 1: Login del administrador
 * - SELECT a administrador por correo y password_verify() con password_hash.
 * - Si es correcto: guardar $_SESSION["admin"] y redirigir a index.php.
 * - Usuario de ejemplo (bd/karma.sql): admin@karma.com / admin12345
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de KARMA - Ingresar</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <section class="seccion">
        <h2>Panel de KARMA</h2>
        <p>Acceso solo para administradores. En construcción.</p>
        <p><a href="../index.php">Volver al sitio</a></p>
    </section>

</body>
</html>
