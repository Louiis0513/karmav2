<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');

requiereTutor();
$idTutor = (int)$_SESSION["tutor"]["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idSolicitud = (int)($_POST["id_solicitud"] ?? 0);
    $estado = (string)($_POST["estado"] ?? "");
    $validos = ["pendiente", "contactado", "aceptado", "rechazado", "finalizado"];
    if ($idSolicitud > 0 && in_array($estado, $validos, true)) {
        $stmt = $mysqli->prepare("UPDATE solicitud SET estado = ? WHERE id_solicitud = ? AND id_tutor = ?");
        $stmt->bind_param("sii", $estado, $idSolicitud, $idTutor);
        $stmt->execute();
        $stmt->close();
        guardarMensaje("Estado de la solicitud actualizado.");
    }
    header("Location: solicitudes.php");
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT s.*, e.nombre AS nombre_alumno_cuenta
     FROM solicitud s
     LEFT JOIN estudiante e ON e.id_estudiante = s.id_estudiante
     WHERE s.id_tutor = ?
     ORDER BY s.fecha DESC, s.id_solicitud DESC"
);
$stmt->bind_param("i", $idTutor);
$stmt->execute();
$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$tituloPagina = "Solicitudes recibidas";
require __DIR__ . "/../includes/header_tutor.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Bandeja</p>
        <h1>Solicitudes recibidas</h1>
        <p>Actualiza el estado para que el alumno y el equipo de KARMA sepan en qué va cada tutoría.</p>
    </div>
    <a class="btn btn-secundario" href="../mis_chats.php">Ir a mis chats</a>
</div>

<div class="panel-card">
    <?php if (count($solicitudes) === 0): ?>
        <div class="vacio">No tienes solicitudes todavía.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Alumno</th><th>Duda</th><th>Medio</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td><?= e($s["fecha"]) ?></td>
                            <td><strong><?= e($s["nombre_estudiante"]) ?></strong><br><span style="color:var(--muted);font-size:.9rem"><?= e($s["telefono_estudiante"]) ?></span></td>
                            <td style="max-width:260px"><?= e($s["descripcion_duda"] ?? "") ?></td>
                            <td><span class="badge"><?= e(ucfirst($s["medio"])) ?></span></td>
                            <td>
                                <form method="post" action="solicitudes.php" style="display:flex;gap:.4rem;flex-wrap:wrap">
                                    <input type="hidden" name="id_solicitud" value="<?= (int)$s["id_solicitud"] ?>">
                                    <select name="estado">
                                        <?php foreach (["pendiente","contactado","aceptado","rechazado","finalizado"] as $est): ?>
                                            <option value="<?= $est ?>" <?= $s["estado"] === $est ? "selected" : "" ?>><?= ucfirst($est) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm" type="submit">Guardar</button>
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
