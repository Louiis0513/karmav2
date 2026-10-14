<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

requiereAdmin();

/*
 * Pantalla 4: Gestión de tutores
 * - SELECT de todos los tutores con su materia (JOIN).
 * - Botón verificar / quitar verificación: UPDATE directo del campo verificado.
 * - Editar lleva a tutor_form.php?id=... ; eliminar hace DELETE por POST.
 */

require __DIR__ . "/../includes/header_admin.php";
?>

    <section class="seccion">
        <h2>Gestión de tutores</h2>
        <p><a href="tutor_form.php">+ Nuevo tutor</a></p>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/../includes/footer.php"; ?>
