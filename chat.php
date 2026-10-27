<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
enviarHeadersCache('privado');

$usuario = usuarioChat();
if (!$usuario) {
    guardarMensaje("Inicia sesión como alumno para chatear con un tutor.");
    header("Location: login.php");
    exit;
}

// Alumno inicia chat desde el perfil del tutor: chat.php?tutor=ID&tema=...
if (isset($_GET["tutor"]) && $usuario["tipo"] === "estudiante") {
    $idTutor = (int)$_GET["tutor"];
    $stmt = $mysqli->prepare("SELECT id_tutor, nombre, verificado FROM tutor WHERE id_tutor = ? LIMIT 1");
    $stmt->bind_param("i", $idTutor);
    $stmt->execute();
    $tutorFila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tutorFila || (int)$tutorFila["verificado"] !== 1) {
        guardarMensaje("Ese tutor no está disponible.");
        header("Location: tutores.php");
        exit;
    }

    $temaInicial = trim((string)($_GET["tema"] ?? ""));
    $stmt = $mysqli->prepare("INSERT IGNORE INTO conversacion (id_estudiante, id_tutor, tema) VALUES (?, ?, ?)");
    $temaVal = $temaInicial !== "" ? $temaInicial : null;
    $stmt->bind_param("iis", $usuario["id"], $idTutor, $temaVal);
    $stmt->execute();
    $idConv = $stmt->insert_id;
    $stmt->close();

    if ($idConv === 0) {
        $stmt = $mysqli->prepare("SELECT id_conversacion FROM conversacion WHERE id_estudiante = ? AND id_tutor = ? LIMIT 1");
        $stmt->bind_param("ii", $usuario["id"], $idTutor);
        $stmt->execute();
        $idConv = (int)$stmt->get_result()->fetch_assoc()["id_conversacion"];
        $stmt->close();
    } elseif ($temaInicial !== "") {
        // Mensaje inicial con el tema
        $stmt = $mysqli->prepare("INSERT INTO mensaje (id_conversacion, remitente_tipo, id_remitente, contenido) VALUES (?, 'estudiante', ?, ?)");
        $msg = "Hola " . $tutorFila["nombre"] . ", te escribo desde KARMA. Necesito ayuda con: " . $temaInicial;
        $stmt->bind_param("iis", $idConv, $usuario["id"], $msg);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: chat.php?id=" . $idConv);
    exit;
}

$idConv = (int)($_GET["id"] ?? 0);
$conv = $idConv > 0 ? conversacionParaUsuario($mysqli, $idConv, $usuario) : null;
if (!$conv) {
    guardarMensaje("Conversación no encontrada.");
    header("Location: mis_chats.php");
    exit;
}

// Guardar acuerdo (tema / fecha / modalidad / estado)
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "acuerdo") {
    $tema = trim((string)($_POST["tema"] ?? ""));
    $fechaProp = trim((string)($_POST["fecha_propuesta"] ?? ""));
    $modalidad = in_array($_POST["modalidad"] ?? "", ["virtual", "presencial"], true) ? $_POST["modalidad"] : null;
    $estado = in_array($_POST["estado"] ?? "", ["abierta", "acordada", "cerrada"], true) ? $_POST["estado"] : "abierta";
    $stmt = $mysqli->prepare("UPDATE conversacion SET tema = ?, fecha_propuesta = ?, modalidad = ?, estado = ? WHERE id_conversacion = ?");
    $stmt->bind_param("ssssi", $tema, $fechaProp, $modalidad, $estado, $idConv);
    $stmt->execute();
    $stmt->close();
    guardarMensaje($estado === "acordada" ? "¡Tutoría acordada! Quedó registrado el tema y el horario." : "Acuerdo actualizado.");
    header("Location: chat.php?id=" . $idConv);
    exit;
}

// Enviar mensaje (formulario normal; el JS lo hace por AJAX sin recargar)
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "enviar") {
    $contenido = trim((string)($_POST["contenido"] ?? ""));
    if ($contenido !== "" && mb_strlen($contenido) <= 2000) {
        $stmt = $mysqli->prepare("INSERT INTO mensaje (id_conversacion, remitente_tipo, id_remitente, contenido) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $idConv, $usuario["tipo"], $usuario["id"], $contenido);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: chat.php?id=" . $idConv);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM mensaje WHERE id_conversacion = ? ORDER BY id_mensaje ASC");
$stmt->bind_param("i", $idConv);
$stmt->execute();
$mensajes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$otroTipo = $usuario["tipo"] === "estudiante" ? "tutor" : "estudiante";
$stmt = $mysqli->prepare("UPDATE mensaje SET leido = 1 WHERE id_conversacion = ? AND remitente_tipo = ? AND leido = 0");
$stmt->bind_param("is", $idConv, $otroTipo);
$stmt->execute();
$stmt->close();

$esEstudiante = $usuario["tipo"] === "estudiante";
$otroNombre = $esEstudiante ? $conv["nombre_tutor"] : $conv["nombre_estudiante"];
$tituloPagina = "Chat con " . $otroNombre;
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <p class="etiqueta"><a href="mis_chats.php">← Mis chats</a></p>

        <div class="chat-layout">
            <section class="panel-card chat-ventana">
                <div class="chat-cabecera">
                    <img class="avatar-mini" style="width:52px;height:52px" src="<?= e(rutaFoto($conv["foto_tutor"])) ?>" alt="">
                    <div style="flex:1">
                        <h1 style="font-size:1.35rem"><?= e($otroNombre) ?></h1>
                        <p><?= e($conv["materia"]) ?> · <span class="badge <?= $conv["estado"] === 'acordada' ? 'badge-ok' : 'badge-warn' ?>"><?= e(ucfirst($conv["estado"])) ?></span></p>
                    </div>
                    <a class="btn btn-sm btn-secundario" href="chat.php?id=<?= $idConv ?>">↻ Ver mensajes nuevos</a>
                </div>

                <div class="chat-mensajes" id="chat-mensajes">
                    <?php if (count($mensajes) === 0): ?>
                        <div class="vacio" id="chat-vacio">Todavía no hay mensajes. Escribe el primero: cuéntale tu tema y cuándo podrías tener la tutoría.</div>
                    <?php endif; ?>
                    <?php foreach ($mensajes as $msg): ?>
                        <?php $mio = $msg["remitente_tipo"] === $usuario["tipo"]; ?>
                        <div class="burbuja <?= $mio ? 'mia' : 'suya' ?>" data-id="<?= (int)$msg["id_mensaje"] ?>">
                            <p><?= nl2br(e($msg["contenido"])) ?></p>
                            <span><?= e(date("d/m H:i", strtotime($msg["creado_en"]))) ?><?= $mio ? " · " . ((int)$msg["leido"] === 1 ? "Leído" : "Enviado") : "" ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post" action="chat.php?id=<?= $idConv ?>" class="chat-form" id="chat-form">
                    <input type="hidden" name="accion" value="enviar">
                    <textarea name="contenido" id="chat-input" rows="2" maxlength="2000" required placeholder="Escribe tu mensaje... Ej: Necesito repasar factorización, ¿puedes el viernes a las 4?"></textarea>
                    <button class="btn" type="submit">Enviar</button>
                </form>
                <p class="form-ayuda">Los mensajes nuevos se ven al pulsar «Ver mensajes nuevos» o al volver a entrar a la conversación. Sin procesos en segundo plano, para no cargar el servidor. No compartas datos bancarios; acuerden aquí tema, horario y modalidad.</p>
            </section>

            <aside class="panel-card chat-acuerdo">
                <h2 style="margin-bottom:.75rem">Acuerdo de la tutoría</h2>
                <p style="margin-bottom:1rem">Registren aquí lo pactado en el chat para que quede claro para los dos.</p>
                <form method="post" action="chat.php?id=<?= $idConv ?>">
                    <input type="hidden" name="accion" value="acuerdo">
                    <div class="form-grupo">
                        <label for="tema">Tema a trabajar</label>
                        <input type="text" id="tema" name="tema" maxlength="255" value="<?= e($conv["tema"] ?? "") ?>" placeholder="Ej: Factorización y ecuaciones cuadráticas">
                    </div>
                    <div class="form-grupo">
                        <label for="fecha_propuesta">Fecha y hora acordada</label>
                        <input type="text" id="fecha_propuesta" name="fecha_propuesta" maxlength="100" value="<?= e($conv["fecha_propuesta"] ?? "") ?>" placeholder="Ej: Viernes 4:00 p.m.">
                    </div>
                    <div class="form-grupo">
                        <label for="modalidad">Modalidad</label>
                        <select id="modalidad" name="modalidad">
                            <option value="">Sin definir</option>
                            <option value="virtual" <?= ($conv["modalidad"] ?? "") === "virtual" ? "selected" : "" ?>>Virtual</option>
                            <option value="presencial" <?= ($conv["modalidad"] ?? "") === "presencial" ? "selected" : "" ?>>Presencial</option>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="abierta" <?= $conv["estado"] === "abierta" ? "selected" : "" ?>>Abierta (negociando)</option>
                            <option value="acordada" <?= $conv["estado"] === "acordada" ? "selected" : "" ?>>Acordada</option>
                            <option value="cerrada" <?= $conv["estado"] === "cerrada" ? "selected" : "" ?>>Cerrada</option>
                        </select>
                    </div>
                    <div class="form-acciones">
                        <button class="btn btn-menta" type="submit">Guardar acuerdo</button>
                    </div>
                </form>
                <?php if (!$esEstudiante): ?>
                    <p class="form-ayuda" style="margin-top:1rem">También puedes contactar al alumno por WhatsApp si te dejó solicitud con su teléfono.</p>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<script>
    // Solo al cargar la vista: bajar al último mensaje. Sin temporizadores ni peticiones en segundo plano.
    (function () {
        var caja = document.getElementById("chat-mensajes");
        if (caja) caja.scrollTop = caja.scrollHeight;
    })();
</script>

<?php require __DIR__ . "/includes/footer.php"; ?>
