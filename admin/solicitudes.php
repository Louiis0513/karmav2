<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');

requiereAdmin();

$solicitudes = $mysqli->query(
    "SELECT s.id_solicitud, s.nombre_estudiante, s.telefono_estudiante, s.descripcion_duda,
            s.fecha, s.medio, s.estado, t.nombre AS tutor, m.nombre AS materia
     FROM solicitud s
     INNER JOIN tutor t ON t.id_tutor = s.id_tutor
     INNER JOIN materia m ON m.id_materia = t.id_materia
     ORDER BY s.fecha DESC, s.id_solicitud DESC"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = "Solicitudes";
require __DIR__ . "/../includes/header_admin.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Bandeja</p>
        <h1>Solicitudes de tutoría</h1>
        <p>Listado de contactos recibidos por formulario o WhatsApp. El tutor actualiza el estado desde su panel.</p>
    </div>
</div>

<div class="panel-card">
    <?php if (count($solicitudes) === 0): ?>
        <div class="vacio">No hay solicitudes todavía.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Tutor / Materia</th>
                        <th>Duda</th>
                        <th>Medio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td><?= e($s["fecha"]) ?></td>
                            <td>
                                <strong><?= e($s["nombre_estudiante"]) ?></strong><br>
                                <span style="color:var(--muted);font-size:0.9rem"><?= e($s["telefono_estudiante"]) ?></span>
                            </td>
                            <td>
                                <?= e($s["tutor"]) ?><br>
                                <span class="badge"><?= e($s["materia"]) ?></span>
                            </td>
                            <td style="max-width:280px"><?= e($s["descripcion_duda"] ?? "") ?></td>
                            <td>
                                <span class="badge <?= $s["medio"] === "whatsapp" ? "badge-ok" : "" ?>">
                                    <?= e(ucfirst($s["medio"])) ?>
                                </span>
                            </td>
                            <td><span class="badge <?= ($s["estado"] ?? "pendiente") === "aceptado" ? "badge-ok" : "badge-warn" ?>"><?= e(ucfirst($s["estado"] ?? "pendiente")) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
