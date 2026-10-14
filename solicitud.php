<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

/*
 * Pantalla 10: Solicitud de tutoría (usuario)
 * - Formulario: nombre, teléfono, descripción de la duda y tutor.
 * - Al recibir el POST: validar (teléfono de 10 dígitos que empieza por 3),
 *   INSERT en solicitud con la fecha de hoy y medio = 'formulario',
 *   mensaje de confirmación y redirección (PRG).
 */

require __DIR__ . "/includes/header.php";
?>

    <section class="seccion">
        <h2>Solicitud de tutoría</h2>
        <p>En construcción.</p>
    </section>

<?php require __DIR__ . "/includes/footer.php"; ?>
