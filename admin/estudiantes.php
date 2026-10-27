<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');

requiereAdmin();

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar") {
    $id = (int)($_POST["id_estudiante"] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM estudiante WHERE id_estudiante = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        guardarMensaje("Alumno eliminado (también se borraron sus chats).");
    }
    header("Location: estudiantes.php");
    exit;
}

$estudiantes = $mysqli->query(
    "SELECT e.*,
            (SELECT COUNT(*) FROM solicitud s WHERE s.id_estudiante = e.id_estudiante) AS solicitudes,
            (SELECT COUNT(*) FROM conversacion c WHERE c.id_estudiante = e.id_estudiante) AS chats
     FROM estudiante e
     ORDER BY e.creado_en DESC, e.nombre"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = "Alumnos";
require __DIR__ . "/../includes/header_admin.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Personas</p>
        <h1>Alumnos registrados</h1>
        <p>Cuentas de alumnos que inician sesión, solicitan tutorías y chatean con tutores.</p>
    </div>
</div>

<div class="panel-card">
    <?php if (count($estudiantes) === 0): ?>
        <div class="vacio">Todavía no hay alumnos registrados.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead><tr><th>Alumno</th><th>Grado</th><th>Registro</th><th>Solicitudes</th><th>Chats</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($estudiantes as $a): ?>
                        <tr>
                            <td><strong><?= e($a["nombre"]) ?></strong><br><span style="color:var(--muted);font-size:.9rem"><?= e($a["correo"]) ?><?= !empty($a["telefono"]) ? " · " . e($a["telefono"]) : "" ?></span></td>
                            <td><?= e($a["grado"] ?? "—") ?></td>
                            <td><?= e(date("Y-m-d", strtotime($a["creado_en"]))) ?></td>
                            <td><?= (int)$a["solicitudes"] ?></td>
                            <td><?= (int)$a["chats"] ?></td>
                            <td>
                                <form method="post" action="estudiantes.php" onsubmit="return confirm('¿Eliminar este alumno y sus chats?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_estudiante" value="<?= (int)$a["id_estudiante"] ?>">
                                    <button class="btn btn-sm btn-peligro" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
