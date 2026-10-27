<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

enviarHeadersCache('privado');

$idTutor = isset($_GET["id_tutor"]) ? (int)$_GET["id_tutor"] : (int)($_POST["id_tutor"] ?? 0);
$error = "";

$tutores = obtenerTutores($mysqli);

$tutorSeleccionado = null;
foreach ($tutores as $t) {
    if ((int)$t["id_tutor"] === $idTutor) {
        $tutorSeleccionado = $t;
        break;
    }
}

$alumnoSesion = null;
if (estudianteLogueado()) {
    $stmtA = $mysqli->prepare("SELECT nombre, telefono FROM estudiante WHERE id_estudiante = ? LIMIT 1");
    $stmtA->bind_param("i", $_SESSION["estudiante"]["id"]);
    $stmtA->execute();
    $alumnoSesion = $stmtA->get_result()->fetch_assoc();
    $stmtA->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim((string)($_POST["nombre_estudiante"] ?? ""));
    $telefono = preg_replace('/\D+/', '', (string)($_POST["telefono_estudiante"] ?? ""));
    $duda = trim((string)($_POST["descripcion_duda"] ?? ""));
    $idTutor = (int)($_POST["id_tutor"] ?? 0);

    if ($nombre === "" || mb_strlen($nombre) < 3) {
        $error = "Escribe tu nombre completo.";
    } elseif (!telefonoValido($telefono)) {
        $error = "El teléfono debe tener 10 dígitos y empezar por 3.";
    } elseif ($duda === "") {
        $error = "Cuéntanos brevemente tu duda.";
    } elseif ($idTutor <= 0) {
        $error = "Selecciona un tutor.";
    } else {
        $check = $mysqli->prepare("SELECT id_tutor FROM tutor WHERE id_tutor = ? AND verificado = 1 LIMIT 1");
        $check->bind_param("i", $idTutor);
        $check->execute();
        $ok = (bool)$check->get_result()->fetch_assoc();
        $check->close();

        if (!$ok) {
            $error = "El tutor seleccionado no está disponible.";
        } else {
            $idEstudiante = estudianteLogueado() ? (int)$_SESSION["estudiante"]["id"] : null;
            $stmt = $mysqli->prepare(
                "INSERT INTO solicitud (id_estudiante, nombre_estudiante, telefono_estudiante, descripcion_duda, fecha, medio, id_tutor)
                 VALUES (?, ?, ?, ?, CURDATE(), 'formulario', ?)"
            );
            $stmt->bind_param("isssi", $idEstudiante, $nombre, $telefono, $duda, $idTutor);
            $stmt->execute();
            $stmt->close();

            guardarMensaje("¡Solicitud enviada! El equipo o el tutor te contactarán pronto.");
            header("Location: tutor.php?id=" . $idTutor);
            exit;
        }
    }
}

$tituloPagina = "Solicitud de tutoría";
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <div class="form-card">
            <p class="etiqueta">Contacto</p>
            <h1>Solicitud de tutoría</h1>
            <p style="margin-bottom:1.25rem">Déjanos tus datos y la duda. Te responderán por teléfono o WhatsApp.</p>

            <?php if ($error !== ""): ?>
                <div class="alerta alerta-error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if (count($tutores) === 0): ?>
                <div class="alerta alerta-info">Aún no hay tutores verificados para solicitar.</div>
            <?php else: ?>
                <form method="post" action="solicitud.php" novalidate>
                    <div class="form-grupo">
                        <label for="id_tutor">Tutor</label>
                        <select name="id_tutor" id="id_tutor" required>
                            <option value="">Selecciona un tutor</option>
                            <?php foreach ($tutores as $t): ?>
                                <option value="<?= (int)$t["id_tutor"] ?>" <?= $idTutor === (int)$t["id_tutor"] ? "selected" : "" ?>>
                                    <?= e($t["nombre"]) ?> — <?= e($t["materia"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label for="nombre_estudiante">Tu nombre</label>
                        <input type="text" id="nombre_estudiante" name="nombre_estudiante" required maxlength="100"
                               value="<?= e($_POST["nombre_estudiante"] ?? ($alumnoSesion["nombre"] ?? "")) ?>" placeholder="Ej: Mariana López">
                    </div>
                    <div class="form-grupo">
                        <label for="telefono_estudiante">Teléfono / WhatsApp</label>
                        <input type="tel" id="telefono_estudiante" name="telefono_estudiante" required maxlength="10"
                               value="<?= e($_POST["telefono_estudiante"] ?? ($alumnoSesion["telefono"] ?? "")) ?>" placeholder="3001234567" inputmode="numeric">
                        <p class="form-ayuda">10 dígitos, empieza por 3.</p>
                    </div>
                    <div class="form-grupo">
                        <label for="descripcion_duda">¿Qué necesitas reforzar?</label>
                        <textarea id="descripcion_duda" name="descripcion_duda" required
                                  placeholder="Ej: factorización y ecuaciones para el parcial del viernes"><?= e($_POST["descripcion_duda"] ?? "") ?></textarea>
                    </div>
                    <div class="form-acciones">
                        <button class="btn" type="submit">Enviar solicitud</button>
                        <?php if ($tutorSeleccionado): ?>
                            <a class="btn btn-secundario" href="tutor.php?id=<?= (int)$tutorSeleccionado["id_tutor"] ?>">Volver al perfil</a>
                        <?php else: ?>
                            <a class="btn btn-secundario" href="tutores.php">Ver tutores</a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
