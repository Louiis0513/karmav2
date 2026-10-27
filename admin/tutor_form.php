<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

enviarHeadersCache('privado');
requiereAdmin();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : (int)($_POST["id_tutor"] ?? 0);
$error = "";
$tutor = [
    "nombre" => "",
    "correo" => "",
    "descripcion" => "",
    "disponibilidad" => "",
    "precio_hora" => null,
    "modalidad" => "virtual",
    "telefono" => "",
    "foto_url" => "",
    "id_materia" => 0,
    "verificado" => 0,
];

if ($id > 0 && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $mysqli->prepare("SELECT * FROM tutor WHERE id_tutor = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$fila) {
        guardarMensaje("Tutor no encontrado.");
        header("Location: tutores.php");
        exit;
    }
    $tutor = $fila;
}

$materias = obtenerMaterias($mysqli);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tutor["nombre"] = trim((string)($_POST["nombre"] ?? ""));
    $tutor["descripcion"] = trim((string)($_POST["descripcion"] ?? ""));
    $tutor["disponibilidad"] = trim((string)($_POST["disponibilidad"] ?? ""));
    $tutor["telefono"] = preg_replace('/\D+/', '', (string)($_POST["telefono"] ?? ""));
    $tutor["correo"] = trim((string)($_POST["correo"] ?? ""));
    $tutor["precio_hora"] = trim((string)($_POST["precio_hora"] ?? "")) !== "" ? (float)$_POST["precio_hora"] : null;
    $tutor["modalidad"] = in_array($_POST["modalidad"] ?? "", ["virtual", "presencial", "ambas"], true) ? $_POST["modalidad"] : "virtual";
    $tutor["id_materia"] = (int)($_POST["id_materia"] ?? 0);
    $tutor["verificado"] = isset($_POST["verificado"]) ? 1 : 0;
    $nuevaPassword = (string)($_POST["password"] ?? "");
    $id = (int)($_POST["id_tutor"] ?? 0);

    if ($tutor["nombre"] === "") {
        $error = "El nombre es obligatorio.";
    } elseif ($tutor["correo"] !== "" && !correoValido($tutor["correo"])) {
        $error = "El correo del tutor no es válido.";
    } elseif ($nuevaPassword !== "" && strlen($nuevaPassword) < 8) {
        $error = "La contraseña debe tener mínimo 8 caracteres.";
    } elseif (!telefonoValido($tutor["telefono"])) {
        $error = "El teléfono debe tener 10 dígitos y empezar por 3.";
    } elseif ($tutor["disponibilidad"] === "") {
        $error = "Indica la disponibilidad.";
    } elseif ($tutor["id_materia"] <= 0) {
        $error = "Selecciona una materia.";
    } else {
        // Subida de foto (opcional): si no se elige archivo se mantiene la actual
        try {
            $fotoSubida = subirImagen($_FILES["foto"] ?? [], "tutores", dirname(__DIR__));
            if ($fotoSubida !== null) {
                if ($id > 0) {
                    $prev = $mysqli->prepare("SELECT foto_url FROM tutor WHERE id_tutor = ?");
                    $prev->bind_param("i", $id);
                    $prev->execute();
                    $filaPrev = $prev->get_result()->fetch_assoc();
                    $prev->close();
                    borrarFotoSiLocal($filaPrev["foto_url"] ?? "", dirname(__DIR__));
                }
                $tutor["foto_url"] = $fotoSubida;
            } elseif ($id > 0) {
                $prev = $mysqli->prepare("SELECT foto_url FROM tutor WHERE id_tutor = ?");
                $prev->bind_param("i", $id);
                $prev->execute();
                $filaPrev = $prev->get_result()->fetch_assoc();
                $prev->close();
                $tutor["foto_url"] = $filaPrev["foto_url"] ?? "";
            }
        } catch (Exception $ex) {
            $error = $ex->getMessage();
        }

        // Correo único entre tutores y alumnos
        if ($error === "" && $tutor["correo"] !== "") {
            $stmt = $mysqli->prepare(
                "SELECT id_tutor FROM tutor WHERE correo = ? AND id_tutor <> ? UNION SELECT id_estudiante FROM estudiante WHERE correo = ? LIMIT 1"
            );
            $stmt->bind_param("sis", $tutor["correo"], $id, $tutor["correo"]);
            $stmt->execute();
            $duplicado = (bool)$stmt->get_result()->fetch_row();
            $stmt->close();
            if ($duplicado) $error = "Ese correo ya lo usa otra cuenta.";
        }

        if ($error !== "") {
            // se queda en el formulario mostrando el error
        } elseif ($id > 0) {
            $antes = cargarTutorConMateria($mysqli, $id);
            $correoVal = $tutor["correo"] !== "" ? $tutor["correo"] : null;
            if ($nuevaPassword !== "") {
                $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare(
                    "UPDATE tutor SET nombre=?, correo=?, descripcion=?, disponibilidad=?, precio_hora=?, modalidad=?, telefono=?, foto_url=?, verificado=?, id_materia=?, password_hash=?
                     WHERE id_tutor=?"
                );
                $stmt->bind_param("ssssdsssiisi", $tutor["nombre"], $correoVal, $tutor["descripcion"], $tutor["disponibilidad"], $tutor["precio_hora"], $tutor["modalidad"], $tutor["telefono"], $tutor["foto_url"], $tutor["verificado"], $tutor["id_materia"], $hash, $id);
            } else {
                $stmt = $mysqli->prepare(
                    "UPDATE tutor SET nombre=?, correo=?, descripcion=?, disponibilidad=?, precio_hora=?, modalidad=?, telefono=?, foto_url=?, verificado=?, id_materia=?
                     WHERE id_tutor=?"
                );
                $stmt->bind_param("ssssdsssiii", $tutor["nombre"], $correoVal, $tutor["descripcion"], $tutor["disponibilidad"], $tutor["precio_hora"], $tutor["modalidad"], $tutor["telefono"], $tutor["foto_url"], $tutor["verificado"], $tutor["id_materia"], $id);
            }
            $stmt->execute();
            $stmt->close();
            cacheParcheTutor($mysqli, $id, $antes);
            guardarMensaje("Tutor actualizado.");
        } else {
            $correoVal = $tutor["correo"] !== "" ? $tutor["correo"] : null;
            $hash = $nuevaPassword !== "" ? password_hash($nuevaPassword, PASSWORD_DEFAULT) : null;
            $stmt = $mysqli->prepare(
                "INSERT INTO tutor (nombre, correo, password_hash, descripcion, disponibilidad, precio_hora, modalidad, telefono, foto_url, verificado, id_materia)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssssdsssii", $tutor["nombre"], $correoVal, $hash, $tutor["descripcion"], $tutor["disponibilidad"], $tutor["precio_hora"], $tutor["modalidad"], $tutor["telefono"], $tutor["foto_url"], $tutor["verificado"], $tutor["id_materia"]);
            $stmt->execute();
            $nuevoId = (int) $mysqli->insert_id;
            $stmt->close();
            cacheParcheTutor($mysqli, $nuevoId);
            guardarMensaje("Tutor creado.");
        }
        if ($error === "") {
            header("Location: tutores.php");
            exit;
        }
    }
}

$tituloPagina = $id > 0 ? "Editar tutor" : "Nuevo tutor";
require __DIR__ . "/../includes/header_admin.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Formulario</p>
        <h1><?= $id > 0 ? "Editar tutor" : "Nuevo tutor" ?></h1>
        <p><a href="tutores.php">‹ Volver a Gestión de tutores</a></p>
    </div>
</div>

<?php if ($error !== ""): ?>
    <div class="alerta alerta-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (count($materias) === 0): ?>
    <div class="alerta alerta-info">Primero crea al menos una materia. <a href="materias.php">Ir a materias</a></div>
<?php else: ?>
    <div class="panel-card">
        <form method="post" enctype="multipart/form-data" action="tutor_form.php<?= $id > 0 ? '?id=' . $id : '' ?>" class="form-card" style="max-width:720px;margin:0;border:none;box-shadow:none;padding:0">
            <?php if ($id > 0): ?>
                <input type="hidden" name="id_tutor" value="<?= $id ?>">
            <?php endif; ?>
            <div class="form-grupo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($tutor["nombre"]) ?>">
            </div>
            <div class="form-grupo">
                <label for="id_materia">Materia</label>
                <select id="id_materia" name="id_materia" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= (int)$m["id_materia"] ?>" <?= (int)$tutor["id_materia"] === (int)$m["id_materia"] ? "selected" : "" ?>>
                            <?= e($m["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label for="descripcion">Descripción / experiencia</label>
                <textarea id="descripcion" name="descripcion"><?= e($tutor["descripcion"] ?? "") ?></textarea>
            </div>
            <div class="form-grupo">
                <label for="disponibilidad">Disponibilidad</label>
                <input type="text" id="disponibilidad" name="disponibilidad" required maxlength="150"
                       value="<?= e($tutor["disponibilidad"]) ?>" placeholder="Lunes a viernes, 3 a 6 p.m.">
            </div>
            <div class="form-grupo">
                <label for="telefono">Teléfono WhatsApp</label>
                <input type="tel" id="telefono" name="telefono" required maxlength="10"
                       value="<?= e($tutor["telefono"]) ?>" placeholder="3001234567" inputmode="numeric">
            </div>
            <div class="form-grupo">
                <label for="correo">Correo para login del tutor (opcional)</label>
                <input type="email" id="correo" name="correo" maxlength="150"
                       value="<?= e($tutor["correo"] ?? "") ?>" placeholder="tutor@correo.com">
                <p class="form-ayuda">Con correo y contraseña el tutor podrá ingresar a su panel, editar su perfil y responder chats.</p>
            </div>
            <div class="form-grupo">
                <label for="password">Contraseña <?= $id > 0 ? "(dejar vacía para no cambiarla)" : "(opcional)" ?></label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
            </div>
            <div class="form-grupo">
                <label for="precio_hora">Precio por hora (COP, opcional)</label>
                <input type="number" id="precio_hora" name="precio_hora" min="0" step="1000"
                       value="<?= $tutor["precio_hora"] !== null && $tutor["precio_hora"] !== "" ? e((string)(int)$tutor["precio_hora"]) : "" ?>" placeholder="25000">
            </div>
            <div class="form-grupo">
                <label for="modalidad">Modalidad</label>
                <select id="modalidad" name="modalidad">
                    <option value="virtual" <?= ($tutor["modalidad"] ?? "virtual") === "virtual" ? "selected" : "" ?>>Virtual</option>
                    <option value="presencial" <?= ($tutor["modalidad"] ?? "") === "presencial" ? "selected" : "" ?>>Presencial</option>
                    <option value="ambas" <?= ($tutor["modalidad"] ?? "") === "ambas" ? "selected" : "" ?>>Virtual y presencial</option>
                </select>
            </div>
            <div class="form-grupo">
                <label>Foto actual</label>
                <div class="celda-persona">
                    <img class="avatar-mini" style="width:72px;height:72px" src="../<?= e(rutaFoto($tutor["foto_url"] ?? "")) ?>" alt="">
                    <span style="color:var(--muted);font-size:.9rem"><?= e($tutor["foto_url"] ?? "") ?: "Sin foto (se usa imagen por defecto)" ?></span>
                </div>
            </div>
            <div class="form-grupo">
                <label for="foto">Subir foto del tutor</label>
                <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp" data-preview="preview-foto">
                <p class="form-ayuda">JPG, PNG o WebP, máximo 2 MB. Si no eliges archivo, se mantiene la foto actual.</p>
                <img id="preview-foto" class="avatar-mini" style="display:none;width:72px;height:72px;margin-top:.5rem" alt="Vista previa">
            </div>
            <div class="form-grupo">
                <label>
                    <input type="checkbox" name="verificado" value="1" <?= (int)$tutor["verificado"] === 1 ? "checked" : "" ?>>
                    Tutor verificado (visible en el sitio público)
                </label>
            </div>
            <div class="form-acciones">
                <button class="btn" type="submit"><?= $id > 0 ? "Guardar cambios" : "Crear tutor" ?></button>
                <a class="btn btn-secundario" href="tutores.php">Cancelar</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . "/../includes/footer.php"; ?>
