<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
enviarHeadersCache('privado');

$usuario = usuarioChat();
if (!$usuario) {
    guardarMensaje("Inicia sesión para ver tus chats.");
    header("Location: login.php");
    exit;
}

if ($usuario["tipo"] === "estudiante") {
    $stmt = $mysqli->prepare(
        "SELECT c.*, t.nombre AS otro_nombre, t.foto_url AS otro_foto, m.nombre AS materia,
                (SELECT contenido FROM mensaje WHERE id_conversacion = c.id_conversacion ORDER BY id_mensaje DESC LIMIT 1) AS ultimo_mensaje,
                (SELECT creado_en FROM mensaje WHERE id_conversacion = c.id_conversacion ORDER BY id_mensaje DESC LIMIT 1) AS ultimo_en,
                (SELECT COUNT(*) FROM mensaje WHERE id_conversacion = c.id_conversacion AND remitente_tipo = 'tutor' AND leido = 0) AS sin_leer
         FROM conversacion c
         INNER JOIN tutor t ON t.id_tutor = c.id_tutor
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE c.id_estudiante = ?
         ORDER BY ultimo_en DESC, c.creado_en DESC"
    );
} else {
    $stmt = $mysqli->prepare(
        "SELECT c.*, e.nombre AS otro_nombre, NULL AS otro_foto, m.nombre AS materia,
                (SELECT contenido FROM mensaje WHERE id_conversacion = c.id_conversacion ORDER BY id_mensaje DESC LIMIT 1) AS ultimo_mensaje,
                (SELECT creado_en FROM mensaje WHERE id_conversacion = c.id_conversacion ORDER BY id_mensaje DESC LIMIT 1) AS ultimo_en,
                (SELECT COUNT(*) FROM mensaje WHERE id_conversacion = c.id_conversacion AND remitente_tipo = 'estudiante' AND leido = 0) AS sin_leer
         FROM conversacion c
         INNER JOIN estudiante e ON e.id_estudiante = c.id_estudiante
         INNER JOIN tutor t ON t.id_tutor = c.id_tutor
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE c.id_tutor = ?
         ORDER BY ultimo_en DESC, c.creado_en DESC"
    );
}
$stmt->bind_param("i", $usuario["id"]);
$stmt->execute();
$chats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$tituloPagina = "Mis chats";
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <p class="etiqueta"><?= $usuario["tipo"] === "tutor" ? "Tutor" : "Alumno" ?></p>
        <div class="admin-titulo" style="margin-bottom:1.25rem">
            <div class="seccion-intro" style="margin:0">
                <h1>Mis chats</h1>
                <p>Conversaciones para acordar temas, horarios y modalidad de la tutoría. Los mensajes nuevos aparecen al entrar a cada conversación.</p>
            </div>
            <?php if ($usuario["tipo"] === "tutor"): ?>
                <a class="btn btn-secundario" href="tutor/index.php">Volver a mi panel</a>
            <?php else: ?>
                <a class="btn btn-secundario" href="tutores.php">Buscar tutor</a>
            <?php endif; ?>
        </div>

        <?php if (count($chats) === 0): ?>
            <div class="vacio">
                <?php if ($usuario["tipo"] === "estudiante"): ?>
                    Aún no tienes chats. <a href="tutores.php">Elige un tutor</a> y pulsa «Chatear».
                <?php else: ?>
                    Aún no tienes chats. Cuando un alumno te escriba, aparecerá aquí.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="lista-chats">
                <?php foreach ($chats as $c): ?>
                    <a class="chat-item" href="chat.php?id=<?= (int)$c["id_conversacion"] ?>">
                        <img class="avatar-mini" style="width:52px;height:52px" src="<?= e(rutaFoto($c["otro_foto"] ?? "")) ?>" alt="">
                        <span class="chat-item-texto">
                            <strong><?= e($c["otro_nombre"]) ?> · <?= e($c["materia"]) ?></strong>
                            <span><?= e($c["tema"] ? "Tema: " . $c["tema"] . " — " : "") ?><?= e($c["ultimo_mensaje"] ?? "Sin mensajes todavía") ?></span>
                        </span>
                        <span class="chat-item-meta">
                            <?php if ((int)$c["sin_leer"] > 0): ?><span class="badge badge-ok"><?= (int)$c["sin_leer"] ?> nuevo(s)</span><?php endif; ?>
                            <span class="badge <?= $c["estado"] === 'acordada' ? 'badge-ok' : 'badge-warn' ?>"><?= e(ucfirst($c["estado"])) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
