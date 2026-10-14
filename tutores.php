<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

/*
 * Pantalla 8: Listado de tutores (usuario)
 * - SELECT a tutor + materia, solo los que tienen verificado = 1.
 * - Filtro por materia con GET: tutores.php?materia=2
 * - Cada tutor enlaza a su perfil: tutor.php?id=...
 */

require __DIR__ . "/includes/header.php";
?>

    <section class="seccion">
        <h2>Listado de tutores</h2>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/includes/footer.php"; ?>
