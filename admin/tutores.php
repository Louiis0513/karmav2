<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

enviarHeadersCache('privado');
requiereAdmin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id = (int)($_POST["id_tutor"] ?? 0);

    if ($accion === "verificar" && $id > 0) {
        $antes = cargarTutorConMateria($mysqli, $id);
        $valor = (int)($_POST["verificado"] ?? 0) === 1 ? 1 : 0;
        $stmt = $mysqli->prepare("UPDATE tutor SET verificado = ? WHERE id_tutor = ?");
        $stmt->bind_param("ii", $valor, $id);
        $stmt->execute();
        $stmt->close();
        cacheParcheTutor($mysqli, $id, $antes);
        guardarMensaje($valor === 1 ? "Tutor verificado." : "Se quitó la verificación.");
        header("Location: tutores.php");
        exit;
    }

    if ($accion === "eliminar" && $id > 0) {
        $antes = cargarTutorConMateria($mysqli, $id);
        $stmt = $mysqli->prepare("DELETE FROM tutor WHERE id_tutor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        cacheParcheTutorEliminado($mysqli, $id, $antes);
        guardarMensaje("Tutor eliminado.");
        header("Location: tutores.php");
        exit;
    }
}

$filtro = $_GET["filtro"] ?? "todos";
$where = "";
if ($filtro === "pendientes") $where = "WHERE t.verificado = 0";
if ($filtro === "verificados") $where = "WHERE t.verificado = 1";
if (!in_array($filtro, ["pendientes", "verificados"], true)) $filtro = "todos";

$tutores = $mysqli->query(
    "SELECT t.*, m.nombre AS materia
     FROM tutor t
     INNER JOIN materia m ON m.id_materia = t.id_materia
     $where
     ORDER BY t.verificado ASC, t.nombre"
)->fetch_all(MYSQLI_ASSOC);
$pendientes = contador($mysqli, "SELECT COUNT(*) FROM tutor WHERE verificado = 0");

$tituloPagina = "Tutores";
require __DIR__ . "/../includes/header_admin.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Personas</p>
        <h1>Gestión de tutores</h1>
        <p>Verifica perfiles, edita información o elimina tutores. Solo los verificados aparecen en el sitio público.</p>
    </div>
    <a class="btn" href="tutor_form.php">+ Nuevo tutor</a>
</div>

<div class="filtros" style="margin-bottom:1.25rem">
    <a href="tutores.php" class="<?= $filtro === 'todos' ? 'activo' : '' ?>">Todos</a>
    <a href="tutores.php?filtro=pendientes" class="<?= $filtro === 'pendientes' ? 'activo' : '' ?>">Pendientes de verificación<?= $pendientes > 0 ? " ($pendientes)" : "" ?></a>
    <a href="tutores.php?filtro=verificados" class="<?= $filtro === 'verificados' ? 'activo' : '' ?>">Verificados</a>
</div>

<div class="panel-card">
    <?php if (count($tutores) === 0): ?>
        <div class="vacio">No hay tutores registrados. <a href="tutor_form.php">Crear el primero</a>.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Tutor</th>
                        <th>Materia</th>
                        <th>Disponibilidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tutores as $t): ?>
                        <tr>
                            <td>
                                <div class="celda-persona">
                                    <img class="avatar-mini" src="../<?= e(rutaFoto($t["foto_url"])) ?>" alt="">
                                    <div>
                                        <strong><?= e($t["nombre"]) ?></strong><br>
                                        <span style="color:var(--muted);font-size:0.9rem"><?= e($t["telefono"]) ?><?= !empty($t["correo"]) ? " · " . e($t["correo"]) : " · Sin login" ?></span><br>
                                        <span style="color:var(--muted);font-size:0.85rem"><?= e(formatoPrecio($t["precio_hora"] ?? null)) ?> · <?= e(etiquetaModalidad($t["modalidad"] ?? "virtual")) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($t["materia"]) ?></td>
                            <td><?= e($t["disponibilidad"]) ?></td>
                            <td>
                                <?php if ((int)$t["verificado"] === 1): ?>
                                    <span class="badge badge-ok">Verificado</span>
                                <?php else: ?>
                                    <span class="badge badge-warn">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acciones-fila">
                                    <a class="btn btn-sm btn-secundario" href="tutor_form.php?id=<?= (int)$t["id_tutor"] ?>">Editar</a>
                                    <form method="post" action="tutores.php">
                                        <input type="hidden" name="accion" value="verificar">
                                        <input type="hidden" name="id_tutor" value="<?= (int)$t["id_tutor"] ?>">
                                        <input type="hidden" name="verificado" value="<?= (int)$t["verificado"] === 1 ? 0 : 1 ?>">
                                        <button class="btn btn-sm <?= (int)$t["verificado"] === 1 ? 'btn-secundario' : 'btn-menta' ?>" type="submit">
                                            <?= (int)$t["verificado"] === 1 ? "Quitar verif." : "Verificar" ?>
                                        </button>
                                    </form>
                                    <form method="post" action="tutores.php" onsubmit="return confirm('¿Eliminar este tutor y sus solicitudes?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_tutor" value="<?= (int)$t["id_tutor"] ?>">
                                        <button class="btn btn-sm btn-peligro" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
