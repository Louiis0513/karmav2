<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
enviarHeadersCache('privado');

requiereEstudiante();
$idEstudiante = (int)$_SESSION["estudiante"]["id"];
$error = "";

$stmt = $mysqli->prepare("SELECT * FROM estudiante WHERE id_estudiante = ? LIMIT 1");
$stmt->bind_param("i", $idEstudiante);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$alumno) { unset($_SESSION["estudiante"]); header("Location: login.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar_cuenta") {
    $pass = (string)($_POST["password_confirm"] ?? "");
    if (!password_verify($pass, $alumno["password_hash"])) {
        $error = "Contraseña incorrecta: no se eliminó la cuenta.";
    } else {
        $stmt = $mysqli->prepare("DELETE FROM estudiante WHERE id_estudiante = ?");
        $stmt->bind_param("i", $idEstudiante);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION["estudiante"]);
        guardarMensaje("Tu cuenta fue eliminada. ¡Esperamos verte de nuevo en KARMA!");
        header("Location: index.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "actualizar") {
    $nombre = trim((string)($_POST["nombre"] ?? ""));
    $telefono = preg_replace('/\D+/', '', (string)($_POST["telefono"] ?? ""));
    $grado = trim((string)($_POST["grado"] ?? ""));
    $nuevaPass = (string)($_POST["nueva_password"] ?? "");

    if (mb_strlen($nombre) < 3) {
        $error = "Escribe tu nombre completo.";
    } elseif ($telefono !== "" && !telefonoValido($telefono)) {
        $error = "El teléfono debe tener 10 dígitos y empezar por 3.";
    } elseif ($nuevaPass !== "" && strlen($nuevaPass) < 8) {
        $error = "La nueva contraseña debe tener mínimo 8 caracteres.";
    } else {
        $tel = $telefono !== "" ? $telefono : null;
        $gradoVal = $grado !== "" ? $grado : null;
        if ($nuevaPass !== "") {
            $hash = password_hash($nuevaPass, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE estudiante SET nombre=?, telefono=?, grado=?, password_hash=? WHERE id_estudiante=?");
            $stmt->bind_param("ssssi", $nombre, $tel, $gradoVal, $hash, $idEstudiante);
        } else {
            $stmt = $mysqli->prepare("UPDATE estudiante SET nombre=?, telefono=?, grado=? WHERE id_estudiante=?");
            $stmt->bind_param("sssi", $nombre, $tel, $gradoVal, $idEstudiante);
        }
        $stmt->execute();
        $stmt->close();
        $_SESSION["estudiante"]["nombre"] = $nombre;
        guardarMensaje("Tu cuenta fue actualizada.");
        header("Location: mi_cuenta.php");
        exit;
    }
    $alumno["nombre"] = $nombre; $alumno["telefono"] = $telefono; $alumno["grado"] = $grado;
}

$misSolicitudes = $mysqli->query(
    "SELECT s.fecha, s.descripcion_duda, s.estado, t.nombre AS tutor, m.nombre AS materia
     FROM solicitud s
     INNER JOIN tutor t ON t.id_tutor = s.id_tutor
     INNER JOIN materia m ON m.id_materia = t.id_materia
     WHERE s.id_estudiante = $idEstudiante
     ORDER BY s.fecha DESC, s.id_solicitud DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = "Mi cuenta";
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <p class="etiqueta">Alumno</p>
        <div class="admin-titulo" style="margin-bottom:1.25rem">
            <div class="seccion-intro" style="margin:0">
                <h1>Mi cuenta</h1>
                <p><?= e($alumno["correo"]) ?> · Actualiza tus datos, revisa tus solicitudes y tus chats.</p>
            </div>
            <div class="form-acciones" style="margin:0">
                <a class="btn" href="mis_chats.php">Mis chats</a>
                <a class="btn btn-secundario" href="tutores.php">Buscar tutor</a>
            </div>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="panel-card">
            <h2 style="margin-bottom:1rem">Mis datos</h2>
            <form method="post" action="mi_cuenta.php" class="form-card" style="max-width:640px;margin:0;border:none;box-shadow:none;padding:0">
                <input type="hidden" name="accion" value="actualizar">
                <div class="form-grupo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($alumno["nombre"]) ?>">
                </div>
                <div class="form-grupo">
                    <label for="telefono">Teléfono / WhatsApp</label>
                    <input type="tel" id="telefono" name="telefono" maxlength="10" value="<?= e($alumno["telefono"] ?? "") ?>" inputmode="numeric" placeholder="3001234567">
                </div>
                <div class="form-grupo">
                    <label for="grado">Grado</label>
                    <select id="grado" name="grado">
                        <option value="">Selecciona</option>
                        <?php foreach (["6°","7°","8°","9°","10°","11°"] as $g): ?>
                            <option value="<?= $g ?>" <?= ($alumno["grado"] ?? "") === $g ? "selected" : "" ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label for="nueva_password">Nueva contraseña (opcional)</label>
                    <input type="password" id="nueva_password" name="nueva_password" minlength="8" autocomplete="new-password" placeholder="Déjala vacía si no la cambias">
                </div>
                <div class="form-acciones">
                    <button class="btn" type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>

        <div class="panel-card">
            <h2 style="margin-bottom:1rem">Mis solicitudes</h2>
            <?php if (count($misSolicitudes) === 0): ?>
                <div class="vacio">Aún no has enviado solicitudes. <a href="tutores.php">Busca un tutor</a> y escríbele por chat.</div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead><tr><th>Fecha</th><th>Tutor</th><th>Duda</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($misSolicitudes as $s): ?>
                                <tr>
                                    <td><?= e($s["fecha"]) ?></td>
                                    <td><?= e($s["tutor"]) ?><br><span class="badge"><?= e($s["materia"]) ?></span></td>
                                    <td style="max-width:260px"><?= e($s["descripcion_duda"] ?? "") ?></td>
                                    <td><span class="badge <?= $s["estado"] === 'aceptado' ? 'badge-ok' : ($s["estado"] === 'rechazado' ? 'badge-off' : 'badge-warn') ?>"><?= e(ucfirst($s["estado"])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel-card" style="border-color:rgba(179,58,90,.35)">
            <h2 style="color:var(--danger)">Eliminar mi cuenta</h2>
            <p style="margin-bottom:1rem">Se borrarán tu cuenta, tus chats y tus mensajes. Tus solicitudes quedarán sin cuenta asociada.</p>
            <form method="post" action="mi_cuenta.php" class="form-card" style="max-width:640px;margin:0;border:none;box-shadow:none;padding:0" onsubmit="return confirm('¿Seguro? Se eliminará tu cuenta y tus chats.');">
                <input type="hidden" name="accion" value="eliminar_cuenta">
                <div class="form-grupo">
                    <label for="password_confirm">Confirma con tu contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="current-password">
                </div>
                <div class="form-acciones">
                    <button class="btn btn-peligro" type="submit">Eliminar mi cuenta</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
