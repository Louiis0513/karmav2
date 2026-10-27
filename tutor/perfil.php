<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

enviarHeadersCache('privado');
requiereTutor();

$idTutor = (int)$_SESSION["tutor"]["id"];
$error = "";

$stmt = $mysqli->prepare("SELECT * FROM tutor WHERE id_tutor = ? LIMIT 1");
$stmt->bind_param("i", $idTutor);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$tutor) { unset($_SESSION["tutor"]); header("Location: ../login.php"); exit; }

// --- Eliminar mi cuenta ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar_cuenta") {
    $pass = (string)($_POST["password_confirm"] ?? "");
    if ($tutor["password_hash"] && !password_verify($pass, $tutor["password_hash"])) {
        $error = "Contraseña incorrecta: no se eliminó la cuenta.";
    } else {
        $antes = cargarTutorConMateria($mysqli, $idTutor);
        borrarFotoSiLocal($tutor["foto_url"], dirname(__DIR__));
        $stmt = $mysqli->prepare("DELETE FROM tutor WHERE id_tutor = ?");
        $stmt->bind_param("i", $idTutor);
        $stmt->execute();
        $stmt->close();
        cacheParcheTutorEliminado($mysqli, $idTutor, $antes);
        unset($_SESSION["tutor"]);
        guardarMensaje("Tu cuenta de tutor fue eliminada. ¡Gracias por haber hecho parte de KARMA!");
        header("Location: ../index.php");
        exit;
    }
}

// --- Actualizar perfil ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "actualizar") {
    $nombre = trim((string)($_POST["nombre"] ?? ""));
    $correo = trim((string)($_POST["correo"] ?? ""));
    $descripcion = trim((string)($_POST["descripcion"] ?? ""));
    $disponibilidad = trim((string)($_POST["disponibilidad"] ?? ""));
    $telefono = preg_replace('/\D+/', '', (string)($_POST["telefono"] ?? ""));
    $precio = trim((string)($_POST["precio_hora"] ?? "")) !== "" ? (float)$_POST["precio_hora"] : null;
    $modalidad = in_array($_POST["modalidad"] ?? "", ["virtual", "presencial", "ambas"], true) ? $_POST["modalidad"] : "virtual";
    $idMateria = (int)($_POST["id_materia"] ?? 0);
    $nuevaPass = (string)($_POST["nueva_password"] ?? "");

    if (mb_strlen($nombre) < 3) {
        $error = "Escribe tu nombre completo.";
    } elseif (!correoValido($correo)) {
        $error = "Escribe un correo válido.";
    } elseif (!telefonoValido($telefono)) {
        $error = "El teléfono debe tener 10 dígitos y empezar por 3.";
    } elseif ($disponibilidad === "") {
        $error = "Indica tu disponibilidad.";
    } elseif ($idMateria <= 0) {
        $error = "Selecciona una materia.";
    } elseif ($nuevaPass !== "" && strlen($nuevaPass) < 8) {
        $error = "La nueva contraseña debe tener mínimo 8 caracteres.";
    } else {
        // Correo único (otro tutor u otro alumno)
        $stmt = $mysqli->prepare(
            "SELECT id_tutor FROM tutor WHERE correo = ? AND id_tutor <> ?
             UNION SELECT id_estudiante FROM estudiante WHERE correo = ? LIMIT 1"
        );
        $stmt->bind_param("sis", $correo, $idTutor, $correo);
        $stmt->execute();
        $duplicado = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();

        if ($duplicado) {
            $error = "Ese correo ya lo usa otra cuenta.";
        } else {
            $foto = $tutor["foto_url"];
            try {
                $subida = subirImagen($_FILES["foto"] ?? [], "tutores", dirname(__DIR__));
                if ($subida !== null) {
                    borrarFotoSiLocal($foto, dirname(__DIR__));
                    $foto = $subida;
                }
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }

            if ($error === "") {
                if ($nuevaPass !== "") {
                    $hash = password_hash($nuevaPass, PASSWORD_DEFAULT);
                    $stmt = $mysqli->prepare(
                        "UPDATE tutor SET nombre=?, correo=?, descripcion=?, disponibilidad=?, precio_hora=?, modalidad=?, telefono=?, foto_url=?, id_materia=?, password_hash=? WHERE id_tutor=?"
                    );
                    $stmt->bind_param("ssssdsssisi", $nombre, $correo, $descripcion, $disponibilidad, $precio, $modalidad, $telefono, $foto, $idMateria, $hash, $idTutor);
                } else {
                    $stmt = $mysqli->prepare(
                        "UPDATE tutor SET nombre=?, correo=?, descripcion=?, disponibilidad=?, precio_hora=?, modalidad=?, telefono=?, foto_url=?, id_materia=? WHERE id_tutor=?"
                    );
                    $stmt->bind_param("ssssdsssii", $nombre, $correo, $descripcion, $disponibilidad, $precio, $modalidad, $telefono, $foto, $idMateria, $idTutor);
                }
                $stmt->execute();
                $stmt->close();
                cacheParcheTutor($mysqli, $idTutor, $tutor);
                $_SESSION["tutor"]["nombre"] = $nombre;
                $_SESSION["tutor"]["correo"] = $correo;
                guardarMensaje("Perfil actualizado correctamente.");
                header("Location: perfil.php");
                exit;
            }
            // Recargar datos escritos si hubo error de imagen
            $tutor = array_merge($tutor, compact("nombre", "correo", "descripcion", "disponibilidad", "telefono", "idMateria"));
            $tutor["precio_hora"] = $precio; $tutor["modalidad"] = $modalidad; $tutor["id_materia"] = $idMateria;
        }
    }
}

$materias = obtenerMaterias($mysqli);
$tituloPagina = "Mi perfil";
require __DIR__ . "/../includes/header_tutor.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Mi cuenta de tutor</p>
        <h1>Mi perfil</h1>
        <p>Actualiza tus datos, tu foto y tu contraseña. Solo tú puedes editar tu perfil.</p>
    </div>
</div>

<?php if ($error !== ""): ?>
    <div class="alerta alerta-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel-card">
    <form method="post" action="perfil.php" enctype="multipart/form-data" class="form-card" style="max-width:720px;margin:0;border:none;box-shadow:none;padding:0">
        <input type="hidden" name="accion" value="actualizar">
        <div class="form-grupo">
            <label>Foto actual</label>
            <div class="celda-persona">
                <img class="avatar-mini" style="width:72px;height:72px" src="../<?= e(rutaFoto($tutor["foto_url"])) ?>" alt="Foto de <?= e($tutor["nombre"]) ?>">
                <span class="badge <?= (int)$tutor["verificado"] === 1 ? 'badge-ok' : 'badge-warn' ?>"><?= (int)$tutor["verificado"] === 1 ? "Verificado" : "Pendiente de verificación" ?></span>
            </div>
        </div>
        <div class="form-grupo">
            <label for="foto">Subir nueva foto</label>
            <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp" data-preview="preview-foto">
            <p class="form-ayuda">JPG, PNG o WebP, máximo 2 MB. Si no eliges archivo, se mantiene la foto actual.</p>
            <img id="preview-foto" class="avatar-mini" style="display:none;width:72px;height:72px;margin-top:.5rem" alt="Vista previa">
        </div>
        <div class="form-grupo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($tutor["nombre"]) ?>">
        </div>
        <div class="form-grupo">
            <label for="correo">Correo (para iniciar sesión)</label>
            <input type="email" id="correo" name="correo" required maxlength="150" value="<?= e($tutor["correo"] ?? "") ?>">
        </div>
        <div class="form-grupo">
            <label for="id_materia">Materia</label>
            <select id="id_materia" name="id_materia" required>
                <option value="">Selecciona</option>
                <?php foreach ($materias as $m): ?>
                    <option value="<?= (int)$m["id_materia"] ?>" <?= (int)$tutor["id_materia"] === (int)$m["id_materia"] ? "selected" : "" ?>><?= e($m["nombre"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-grupo">
            <label for="descripcion">Descripción / experiencia</label>
            <textarea id="descripcion" name="descripcion"><?= e($tutor["descripcion"] ?? "") ?></textarea>
        </div>
        <div class="form-grupo">
            <label for="disponibilidad">Disponibilidad</label>
            <input type="text" id="disponibilidad" name="disponibilidad" required maxlength="150" value="<?= e($tutor["disponibilidad"]) ?>">
        </div>
        <div class="form-grupo">
            <label for="precio_hora">Precio por hora (COP, opcional)</label>
            <input type="number" id="precio_hora" name="precio_hora" min="0" step="1000" value="<?= $tutor["precio_hora"] !== null ? e((string)(int)$tutor["precio_hora"]) : "" ?>" placeholder="25000">
        </div>
        <div class="form-grupo">
            <label for="modalidad">Modalidad</label>
            <select id="modalidad" name="modalidad">
                <option value="virtual" <?= $tutor["modalidad"] === "virtual" ? "selected" : "" ?>>Virtual</option>
                <option value="presencial" <?= $tutor["modalidad"] === "presencial" ? "selected" : "" ?>>Presencial</option>
                <option value="ambas" <?= $tutor["modalidad"] === "ambas" ? "selected" : "" ?>>Virtual y presencial</option>
            </select>
        </div>
        <div class="form-grupo">
            <label for="telefono">Teléfono WhatsApp</label>
            <input type="tel" id="telefono" name="telefono" required maxlength="10" value="<?= e($tutor["telefono"]) ?>" inputmode="numeric">
        </div>
        <div class="form-grupo">
            <label for="nueva_password">Nueva contraseña (opcional)</label>
            <input type="password" id="nueva_password" name="nueva_password" minlength="8" autocomplete="new-password" placeholder="Déjala vacía si no la cambias">
        </div>
        <div class="form-acciones">
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secundario" href="index.php">Volver al panel</a>
        </div>
    </form>
</div>

<div class="panel-card" style="border-color:rgba(179,58,90,.35)">
    <h2 style="color:var(--danger)">Zona de peligro</h2>
    <p style="margin-bottom:1rem">Eliminar tu cuenta borra tu perfil, tus chats y tus solicitudes. Esta acción no se puede deshacer.</p>
    <form method="post" action="perfil.php" class="form-card" style="max-width:720px;margin:0;border:none;box-shadow:none;padding:0" onsubmit="return confirm('¿Seguro? Se eliminará tu cuenta de tutor, tus chats y solicitudes.');">
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

<?php require __DIR__ . "/../includes/footer.php"; ?>
