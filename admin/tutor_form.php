<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

requiereAdmin();

/*
 * Pantalla 5: Formulario de tutor
 * - Sin id: crear (INSERT). Con ?id=...: cargar el tutor y editar (UPDATE).
 * - La materia se elige de un <select> que viene de SELECT a materia.
 * - Validar: nombre obligatorio, teléfono de 10 dígitos que empieza por 3.
 */

require __DIR__ . "/../includes/header_admin.php";
?>

    <section class="seccion">
        <h2>Formulario de tutor</h2>
        <p><a href="tutores.php">‹ Volver a Gestión de tutores</a></p>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/../includes/footer.php"; ?>
