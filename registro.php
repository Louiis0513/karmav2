<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

enviarHeadersCache('privado');

if (estudianteLogueado() || tutorLogueado()) {
    header("Location: index.php");
    exit;
}

$tipo = ($_GET["tipo"] ?? $_POST["tipo"] ?? "alumno") === "tutor" ? "tutor" : "alumno";
$error = "";
$datos = ["nombre" => "", "correo" => "", "telefono" => "", "grado" => "", "descripcion" => "", "disponibilidad" => "", "precio_hora" => "", "modalidad" => "virtual", "id_materia" => 0];
$materias = obtenerMaterias($mysqli);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($datos as $k => $v) { $datos[$k] = trim((string)($_POST[$k] ?? $v)); }
    $datos["telefono"] = preg_replace('/\D+/', '', $datos["telefono"]);
    $datos["id_materia"] = (int)($datos["id_materia"] ?: 0);
    $password = (string)($_POST["password"] ?? "");
    $password2 = (string)($_POST["password2"] ?? "");

    if (mb_strlen($datos["nombre"]) < 3) {
        $error = "Escribe tu nombre completo.";
    } elseif (!correoValido($datos["correo"])) {
        $error = "Escribe un correo válido.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener mínimo 8 caracteres.";
    } elseif ($password !== $password2) {
        $error = "Las contraseñas no coinciden.";
    } elseif ($datos["telefono"] !== "" && !telefonoValido($datos["telefono"])) {
        $error = "El teléfono debe tener 10 dígitos y empezar por 3.";
    } else {
        // Correo único entre alumnos y tutores
        $stmt = $mysqli->prepare("SELECT id_estudiante FROM estudiante WHERE correo = ? UNION SELECT id_tutor FROM tutor WHERE correo = ? LIMIT 1");
        $stmt->bind_param("ss", $datos["correo"], $datos["correo"]);
        $stmt->execute();
        $existe = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();

        if ($existe) {
            $error = "Ese correo ya está registrado. Inicia sesión.";
        } elseif ($tipo === "alumno") {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $tel = $datos["telefono"] !== "" ? $datos["telefono"] : null;
            $grado = $datos["grado"] !== "" ? $datos["grado"] : null;
            $stmt = $mysqli->prepare("INSERT INTO estudiante (nombre, correo, password_hash, telefono, grado) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $datos["nombre"], $datos["correo"], $hash, $tel, $grado);
            $stmt->execute();
            $_SESSION["estudiante"] = ["id" => $stmt->insert_id, "nombre" => $datos["nombre"], "correo" => $datos["correo"]];
            $stmt->close();
            session_regenerate_id(true);
            guardarMensaje("¡Bienvenido/a a KARMA, " . $datos["nombre"] . "! Ya puedes buscar tutores y chatear con ellos.");
            header("Location: tutores.php");
            exit;
        } else {
            if ($datos["id_materia"] <= 0) {
                $error = "Selecciona la materia que enseñas.";
            } elseif ($datos["telefono"] === "" || !telefonoValido($datos["telefono"])) {
                $error = "Como tutor necesitas un teléfono válido de 10 dígitos que empiece por 3.";
            } elseif ($datos["disponibilidad"] === "") {
                $error = "Indica tu disponibilidad.";
            } else {
                try {
                    $foto = subirImagen($_FILES["foto"] ?? [], "tutores", __DIR__) ?? "";
                } catch (Exception $ex) {
                    $error = $ex->getMessage();
                    $foto = "";
                }
                if ($error === "") {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $precio = $datos["precio_hora"] !== "" ? (float)$datos["precio_hora"] : null;
                    $modalidad = in_array($datos["modalidad"], ["virtual", "presencial", "ambas"], true) ? $datos["modalidad"] : "virtual";
                    $stmt = $mysqli->prepare(
                        "INSERT INTO tutor (nombre, correo, password_hash, descripcion, disponibilidad, precio_hora, modalidad, telefono, foto_url, verificado, id_materia)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)"
                    );
                    $stmt->bind_param("sssssdsssi", $datos["nombre"], $datos["correo"], $hash, $datos["descripcion"], $datos["disponibilidad"], $precio, $modalidad, $datos["telefono"], $foto, $datos["id_materia"]);
                    $stmt->execute();
                    $nuevoId = (int) $mysqli->insert_id;
                    $stmt->close();
                    cacheParcheTutor($mysqli, $nuevoId);
                    $_SESSION["tutor"] = ["id" => $nuevoId, "nombre" => $datos["nombre"], "correo" => $datos["correo"], "verificado" => 0];
                    session_regenerate_id(true);
                    guardarMensaje("¡Registro recibido! Tu perfil quedó pendiente de verificación por un administrador.");
                    header("Location: tutor/index.php");
                    exit;
                }
            }
        }
    }
}

$tituloPagina = "Registrarse";
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <div class="form-card" style="max-width:640px">
            <p class="etiqueta">Únete a KARMA</p>
            <h1>Crear cuenta</h1>
            <p style="margin-bottom:1.25rem">Regístrate como alumno para contactar tutores, o como tutor para ofrecer tutorías (tu perfil se publica cuando un administrador lo verifique).</p>

            <div class="filtros" style="margin-bottom:1.25rem">
                <a href="registro.php?tipo=alumno" class="<?= $tipo === 'alumno' ? 'activo' : '' ?>">Soy alumno</a>
                <a href="registro.php?tipo=tutor" class="<?= $tipo === 'tutor' ? 'activo' : '' ?>">Soy tutor</a>
            </div>

            <?php if ($error !== ""): ?>
                <div class="alerta alerta-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="registro.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                <div class="form-grupo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($datos["nombre"]) ?>">
                </div>
                <div class="form-grupo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" required maxlength="150" value="<?= e($datos["correo"]) ?>" autocomplete="email">
                </div>
                <div class="form-grupo">
                    <label for="telefono">Teléfono / WhatsApp <?= $tipo === 'tutor' ? '' : '(opcional)' ?></label>
                    <input type="tel" id="telefono" name="telefono" maxlength="10" value="<?= e($datos["telefono"]) ?>" placeholder="3001234567" inputmode="numeric">
                </div>

                <?php if ($tipo === "alumno"): ?>
                    <div class="form-grupo">
                        <label for="grado">Grado (opcional)</label>
                        <select id="grado" name="grado">
                            <option value="">Selecciona</option>
                            <?php foreach (["6°","7°","8°","9°","10°","11°"] as $g): ?>
                                <option value="<?= $g ?>" <?= $datos["grado"] === $g ? "selected" : "" ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-grupo">
                        <label for="id_materia">Materia que enseñas</label>
                        <select id="id_materia" name="id_materia" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($materias as $m): ?>
                                <option value="<?= (int)$m["id_materia"] ?>" <?= (int)$datos["id_materia"] === (int)$m["id_materia"] ? "selected" : "" ?>><?= e($m["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label for="descripcion">Descripción / experiencia</label>
                        <textarea id="descripcion" name="descripcion" placeholder="Estudio, experiencia, cómo explicas..."><?= e($datos["descripcion"]) ?></textarea>
                    </div>
                    <div class="form-grupo">
                        <label for="disponibilidad">Disponibilidad</label>
                        <input type="text" id="disponibilidad" name="disponibilidad" required maxlength="150" value="<?= e($datos["disponibilidad"]) ?>" placeholder="Lunes a viernes, 3 a 6 p.m.">
                    </div>
                    <div class="form-grupo">
                        <label for="precio_hora">Precio por hora (COP, opcional)</label>
                        <input type="number" id="precio_hora" name="precio_hora" min="0" step="1000" value="<?= e($datos["precio_hora"]) ?>" placeholder="25000">
                    </div>
                    <div class="form-grupo">
                        <label for="modalidad">Modalidad</label>
                        <select id="modalidad" name="modalidad">
                            <option value="virtual" <?= $datos["modalidad"] === "virtual" ? "selected" : "" ?>>Virtual</option>
                            <option value="presencial" <?= $datos["modalidad"] === "presencial" ? "selected" : "" ?>>Presencial</option>
                            <option value="ambas" <?= $datos["modalidad"] === "ambas" ? "selected" : "" ?>>Virtual y presencial</option>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label for="foto">Foto de perfil</label>
                        <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp" data-preview="preview-foto">
                        <p class="form-ayuda">JPG, PNG o WebP, máximo 2 MB. Puedes subirla después desde tu panel.</p>
                        <img id="preview-foto" class="avatar-mini" style="display:none;width:72px;height:72px;margin-top:.5rem" alt="Vista previa">
                    </div>
                <?php endif; ?>

                <div class="form-grupo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                </div>
                <div class="form-grupo">
                    <label for="password2">Confirmar contraseña</label>
                    <input type="password" id="password2" name="password2" required autocomplete="new-password">
                </div>
                <div class="form-acciones">
                    <button class="btn" type="submit"><?= $tipo === "tutor" ? "Registrarme como tutor" : "Crear mi cuenta" ?></button>
                    <a class="btn btn-secundario" href="login.php">Ya tengo cuenta</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
