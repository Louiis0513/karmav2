<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');

requiereTutor();

$idTutor = (int)$_SESSION["tutor"]["id"];
$stmt = $mysqli->prepare(
    "SELECT t.*, m.nombre AS materia FROM tutor t INNER JOIN materia m ON m.id_materia = t.id_materia WHERE t.id_tutor = ? LIMIT 1"
);
$stmt->bind_param("i", $idTutor);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tutor) {
    unset($_SESSION["tutor"]);
    header("Location: ../login.php");
    exit;
}
// Refresca estado de verificación en sesión
$_SESSION["tutor"]["verificado"] = (int)$tutor["verificado"];
$_SESSION["tutor"]["nombre"] = $tutor["nombre"];

$stats = [
    "chats" => contador($mysqli, "SELECT COUNT(*) FROM conversacion WHERE id_tutor = $idTutor"),
    "solicitudes" => contador($mysqli, "SELECT COUNT(*) FROM solicitud WHERE id_tutor = $idTutor"),
    "sin_leer" => mensajesNoLeidos($mysqli, ["tipo" => "tutor", "id" => $idTutor]),
];

$recientes = $mysqli->query(
    "SELECT s.nombre_estudiante, s.telefono_estudiante, s.descripcion_duda, s.fecha, s.estado
     FROM solicitud s WHERE s.id_tutor = $idTutor ORDER BY s.fecha DESC, s.id_solicitud DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = "Mi panel";
require __DIR__ . "/../includes/header_tutor.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Panel del tutor</p>
        <h1>Hola, <?= e($tutor["nombre"]) ?></h1>
        <p>
            Estado:
            <?php if ((int)$tutor["verificado"] === 1): ?>
                <span class="badge badge-ok">Verificado · visible en el sitio</span>
            <?php else: ?>
                <span class="badge badge-warn">Pendiente de verificación</span>
            <?php endif; ?>
            · <?= e($tutor["materia"]) ?> · <?= e(etiquetaModalidad($tutor["modalidad"])) ?> · <?= e(formatoPrecio($tutor["precio_hora"])) ?>
        </p>
    </div>
    <div class="form-acciones" style="margin:0">
        <a class="btn" href="perfil.php">Editar mi perfil</a>
        <?php if ((int)$tutor["verificado"] === 1): ?>
            <a class="btn btn-secundario" href="../tutor.php?id=<?= $idTutor ?>" target="_blank" rel="noopener">Ver mi perfil público</a>
        <?php endif; ?>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><strong><?= $stats["chats"] ?></strong><span>Conversaciones</span></div>
    <div class="stat-card"><strong><?= $stats["sin_leer"] ?></strong><span>Mensajes sin leer</span></div>
    <div class="stat-card"><strong><?= $stats["solicitudes"] ?></strong><span>Solicitudes recibidas</span></div>
    <div class="stat-card"><strong><?= (int)$tutor["verificado"] === 1 ? "Activo" : "En revisión" ?></strong><span>Estado del perfil</span></div>
</div>

<div class="panel-card">
    <h2 style="margin-bottom:1rem">Últimas solicitudes</h2>
    <?php if (count($recientes) === 0): ?>
        <div class="vacio">Todavía no tienes solicitudes. Cuando un alumno te contacte por el formulario, aparecerán aquí y en tus chats.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Alumno</th><th>Duda</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($recientes as $s): ?>
                        <tr>
                            <td><?= e($s["fecha"]) ?></td>
                            <td><strong><?= e($s["nombre_estudiante"]) ?></strong><br><span style="color:var(--muted);font-size:.9rem"><?= e($s["telefono_estudiante"]) ?></span></td>
                            <td style="max-width:280px"><?= e($s["descripcion_duda"] ?? "") ?></td>
                            <td><span class="badge"><?= e(ucfirst($s["estado"])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:1rem"><a class="btn btn-sm btn-secundario" href="solicitudes.php">Ver todas y actualizar estado</a>
        <a class="btn btn-sm" href="../mis_chats.php">Ir a mis chats</a></p>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
