<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

enviarHeadersCache('publico');

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0) {
    guardarMensaje("Tutor no encontrado.");
    header("Location: tutores.php");
    exit;
}

$tutor = obtenerTutorVerificado($mysqli, $id);

if (!$tutor) {
    guardarMensaje("Ese tutor no está disponible o no está verificado.");
    header("Location: tutores.php");
    exit;
}

// Registrar interés por WhatsApp (medio informativo)
if (isset($_GET["wa"]) && $_GET["wa"] === "1") {
    $stmt = $mysqli->prepare(
        "INSERT INTO solicitud (nombre_estudiante, telefono_estudiante, descripcion_duda, fecha, medio, id_tutor)
         VALUES ('Contacto WhatsApp', '3000000000', 'El estudiante abrió WhatsApp desde el perfil.', CURDATE(), 'whatsapp', ?)"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $msg = "Hola " . $tutor["nombre"] . ", te escribo desde KARMA. Me interesa una tutoría de " . $tutor["materia"] . ".";
    header("Location: " . whatsappUrl($tutor["telefono"], $msg));
    exit;
}

$tituloPagina = $tutor["nombre"];
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <p class="etiqueta"><a href="tutores.php">← Volver a tutores</a></p>
        <article class="perfil-tutor">
            <div class="perfil-foto">
                <img src="<?= e(rutaFoto($tutor["foto_url"])) ?>" alt="Foto de <?= e($tutor["nombre"]) ?>">
            </div>
            <div>
                <span class="badge"><?= e($tutor["materia"]) ?></span>
                <h1 style="margin:0.6rem 0 0.75rem"><?= e($tutor["nombre"]) ?></h1>
                <p><?= nl2br(e($tutor["descripcion"] ?? "Tutor verificado en KARMA.")) ?></p>

                <div class="perfil-meta">
                    <div>
                        <strong>Disponibilidad</strong>
                        <?= e($tutor["disponibilidad"]) ?>
                    </div>
                    <div>
                        <strong>Materia</strong>
                        <?= e($tutor["materia"]) ?>
                    </div>
                    <div>
                        <strong>Precio</strong>
                        <?= e(formatoPrecio($tutor["precio_hora"] ?? null)) ?>
                    </div>
                    <div>
                        <strong>Modalidad</strong>
                        <?= e(etiquetaModalidad($tutor["modalidad"] ?? "virtual")) ?>
                    </div>
                    <div>
                        <strong>Estado</strong>
                        <span class="badge badge-ok">Verificado</span>
                    </div>
                </div>

                <div class="perfil-acciones">
                    <?php if (estudianteLogueado()): ?>
                        <a class="btn" href="chat.php?tutor=<?= (int)$tutor["id_tutor"] ?>">💬 Chatear y acordar tema</a>
                    <?php elseif (tutorLogueado()): ?>
                        <a class="btn" href="mis_chats.php">Ir a mis chats</a>
                    <?php else: ?>
                        <a class="btn" href="login.php">💬 Ingresa para chatear</a>
                    <?php endif; ?>
                    <a class="btn btn-wa" href="tutor.php?id=<?= (int)$tutor["id_tutor"] ?>&wa=1">Contactar por WhatsApp</a>
                    <a class="btn" href="solicitud.php?id_tutor=<?= (int)$tutor["id_tutor"] ?>">Dejar solicitud</a>
                    <a class="btn btn-secundario" href="tutores.php">Ver más tutores</a>
                </div>
            </div>
        </article>
    </div>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
