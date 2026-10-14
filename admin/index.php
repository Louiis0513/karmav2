<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

requiereAdmin();

/*
 * Pantalla 2: Panel principal
 * - Totales con SELECT COUNT(*): materias, tutores, tutores verificados y solicitudes.
 */

require __DIR__ . "/../includes/header_admin.php";
?>

    <section class="seccion">
        <h2>Panel principal</h2>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/../includes/footer.php"; ?>
