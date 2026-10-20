<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

requiereAdmin();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : (int)($_POST["id_tutor"] ?? 0);
$error = "";
$tutor = [
    "nombre" => "",
    "descripcion" => "",
    "disponibilidad" => "",
    "telefono" => "",
    "foto_url" => "img/tutores/andres.jpg",
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

$materias = $mysqli->query("SELECT id_materia, nombre FROM materia ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tutor["nombre"] = trim((string)($_POST["nombre"] ?? ""));
    $tutor["descripcion"] = trim((string)($_POST["descripcion"] ?? ""));
    $tutor["disponibilidad"] = trim((string)($_POST["disponibilidad"] ?? ""));
    $tutor["telefono"] = preg_replace('/\D+/', '', (string)($_POST["telefono"] ?? ""));
    $tutor["foto_url"] = trim((string)($_POST["foto_url"] ?? ""));
    $tutor["id_materia"] = (int)($_POST["id_materia"] ?? 0);
    $tutor["verificado"] = isset($_POST["verificado"]) ? 1 : 0;
    $id = (int)($_POST["id_tutor"] ?? 0);

    if ($tutor["nombre"] === "") {
        $error = "El nombre es obligatorio.";
    } elseif (!telefonoValido($tutor["telefono"])) {
        $error = "El teléfono debe tener 10 dígitos y empezar por 3.";
    } elseif ($tutor["disponibilidad"] === "") {
        $error = "Indica la disponibilidad.";
    } elseif ($tutor["id_materia"] <= 0) {
        $error = "Selecciona una materia.";
    } else {
        if ($id > 0) {
            $stmt = $mysqli->prepare(
                "UPDATE tutor SET nombre=?, descripcion=?, disponibilidad=?, telefono=?, foto_url=?, verificado=?, id_materia=?
                 WHERE id_tutor=?"
            );
            $stmt->bind_param(
                "sssssiii",
                $tutor["nombre"],
                $tutor["descripcion"],
                $tutor["disponibilidad"],
                $tutor["telefono"],
                $tutor["foto_url"],
                $tutor["verificado"],
                $tutor["id_materia"],
                $id
            );
            $stmt->execute();
            $stmt->close();
            guardarMensaje("Tutor actualizado.");
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO tutor (nombre, descripcion, disponibilidad, telefono, foto_url, verificado, id_materia)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssssii",
                $tutor["nombre"],
                $tutor["descripcion"],
                $tutor["disponibilidad"],
                $tutor["telefono"],
                $tutor["foto_url"],
                $tutor["verificado"],
                $tutor["id_materia"]
            );
            $stmt->execute();
            $stmt->close();
            guardarMensaje("Tutor creado.");
        }
        header("Location: tutores.php");
        exit;
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
        <form method="post" action="tutor_form.php<?= $id > 0 ? '?id=' . $id : '' ?>" class="form-card" style="max-width:720px;margin:0;border:none;box-shadow:none;padding:0">
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
                <label for="foto_url">Foto (ruta)</label>
                <input type="text" id="foto_url" name="foto_url" maxlength="255"
                       value="<?= e($tutor["foto_url"] ?? "") ?>" placeholder="img/tutores/nombre.jpg">
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
