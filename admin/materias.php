<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

enviarHeadersCache('privado');
requiereAdmin();

$error = "";
$editando = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear" || $accion === "editar") {
        $nombre = trim((string)($_POST["nombre"] ?? ""));
        $descripcion = trim((string)($_POST["descripcion"] ?? ""));
        $imagen = trim((string)($_POST["imagen"] ?? ""));
        $id = (int)($_POST["id_materia"] ?? 0);

        if ($nombre === "") {
            $error = "El nombre de la materia es obligatorio.";
        } elseif ($accion === "crear") {
            $stmt = $mysqli->prepare("INSERT INTO materia (nombre, descripcion, imagen) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nombre, $descripcion, $imagen);
            try {
                $stmt->execute();
                cacheParcheMaterias($mysqli);
                guardarMensaje("Materia creada correctamente.");
            } catch (mysqli_sql_exception $e) {
                $error = "No se pudo crear. ¿El nombre ya existe?";
            }
            $stmt->close();
            if ($error === "") {
                header("Location: materias.php");
                exit;
            }
        } else {
            $stmt = $mysqli->prepare("UPDATE materia SET nombre = ?, descripcion = ?, imagen = ? WHERE id_materia = ?");
            $stmt->bind_param("sssi", $nombre, $descripcion, $imagen, $id);
            try {
                $stmt->execute();
                cacheParcheMaterias($mysqli, $id, $nombre, false);
                guardarMensaje("Materia actualizada.");
            } catch (mysqli_sql_exception $e) {
                $error = "No se pudo actualizar la materia.";
            }
            $stmt->close();
            if ($error === "") {
                header("Location: materias.php");
                exit;
            }
        }
    }

    if ($accion === "eliminar") {
        $id = (int)($_POST["id_materia"] ?? 0);
        $stmt = $mysqli->prepare("DELETE FROM materia WHERE id_materia = ?");
        $stmt->bind_param("i", $id);
        try {
            $stmt->execute();
            cacheParcheMaterias($mysqli, $id, null, true);
            guardarMensaje("Materia eliminada.");
        } catch (mysqli_sql_exception $e) {
            guardarMensaje("No se puede eliminar: tiene tutores asignados.");
        }
        $stmt->close();
        header("Location: materias.php");
        exit;
    }
}

if (isset($_GET["editar"])) {
    $id = (int)$_GET["editar"];
    $stmt = $mysqli->prepare("SELECT * FROM materia WHERE id_materia = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editando = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$materias = $mysqli->query(
    "SELECT m.*, COUNT(t.id_tutor) AS tutores
     FROM materia m
     LEFT JOIN tutor t ON t.id_materia = m.id_materia
     GROUP BY m.id_materia
     ORDER BY m.nombre"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = "Materias";
require __DIR__ . "/../includes/header_admin.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Catálogo</p>
        <h1>Gestión de materias</h1>
        <p>Crea, edita o elimina materias. No se puede borrar una materia con tutores asignados.</p>
    </div>
</div>

<?php if ($error !== ""): ?>
    <div class="alerta alerta-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel-card">
    <h2 style="margin-bottom:1rem"><?= $editando ? "Editar materia" : "Nueva materia" ?></h2>
    <form method="post" action="materias.php" class="form-card" style="max-width:none;padding:0;border:none;box-shadow:none">
        <input type="hidden" name="accion" value="<?= $editando ? "editar" : "crear" ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id_materia" value="<?= (int)$editando["id_materia"] ?>">
        <?php endif; ?>
        <div class="form-grupo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required maxlength="100"
                   value="<?= e($editando["nombre"] ?? ($_POST["nombre"] ?? "")) ?>">
        </div>
        <div class="form-grupo">
            <label for="descripcion">Descripción corta</label>
            <input type="text" id="descripcion" name="descripcion" maxlength="255"
                   value="<?= e($editando["descripcion"] ?? ($_POST["descripcion"] ?? "")) ?>">
        </div>
        <div class="form-grupo">
            <label for="imagen">Ruta de imagen</label>
            <input type="text" id="imagen" name="imagen" maxlength="255"
                   value="<?= e($editando["imagen"] ?? ($_POST["imagen"] ?? "img/materias/matematicas.jpg")) ?>"
                   placeholder="img/materias/nombre.jpg">
            <p class="form-ayuda">Ejemplo: img/materias/ingles.jpg</p>
        </div>
        <div class="form-acciones">
            <button class="btn" type="submit"><?= $editando ? "Guardar cambios" : "Crear materia" ?></button>
            <?php if ($editando): ?>
                <a class="btn btn-secundario" href="materias.php">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel-card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Descripción</th>
                    <th>Tutores</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materias as $m): ?>
                    <tr>
                        <td>
                            <div class="celda-persona">
                                <img class="avatar-mini" src="../<?= e(rutaFoto($m["imagen"] ?? "", "img/estudiantes.jpg")) ?>" alt="">
                                <strong><?= e($m["nombre"]) ?></strong>
                            </div>
                        </td>
                        <td><?= e($m["descripcion"] ?? "—") ?></td>
                        <td><?= (int)$m["tutores"] ?></td>
                        <td>
                            <div class="acciones-fila">
                                <a class="btn btn-sm btn-secundario" href="materias.php?editar=<?= (int)$m["id_materia"] ?>">Editar</a>
                                <form method="post" action="materias.php" onsubmit="return confirm('¿Eliminar esta materia?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_materia" value="<?= (int)$m["id_materia"] ?>">
                                    <button class="btn btn-sm btn-peligro" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
