<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

requiereAdmin();

/*
 * Pantalla 6: Bandeja de solicitudes
 * - SELECT a solicitud con su tutor y materia, ORDER BY fecha DESC.
 * - Mostrar también el medio (formulario o WhatsApp). Solo lectura.
 */

require __DIR__ . "/../includes/header_admin.php";
?>

    <section class="seccion">
        <h2>Bandeja de solicitudes</h2>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/../includes/footer.php"; ?>
