<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

requiereAdmin();

/*
 * Pantalla 3: Gestión de materias
 * - SELECT de todas las materias.
 * - INSERT (nueva), UPDATE (editar) y DELETE (eliminar), siempre por POST.
 * - Una materia con tutores asignados no se puede eliminar (llave foránea).
 */

require __DIR__ . "/../includes/header_admin.php";
?>

    <section class="seccion">
        <h2>Gestión de materias</h2>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/../includes/footer.php"; ?>
