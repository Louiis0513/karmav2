<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

/*
 * Pantalla 9: Perfil del tutor (usuario)
 * - Recibe el id por GET: tutor.php?id=1
 * - SELECT de un solo tutor verificado con su materia (consulta preparada).
 * - Botón "Contactar por WhatsApp" (https://wa.me/57 + telefono)
 *   y enlace a solicitud.php?id_tutor=...
 */

require __DIR__ . "/includes/header.php";
?>

    <section class="seccion">
        <h2>Perfil del tutor</h2>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/includes/footer.php"; ?>
