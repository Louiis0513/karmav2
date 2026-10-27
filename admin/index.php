<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');

requiereAdmin();

$stats = [
    "materias" => contador($mysqli, "SELECT COUNT(*) FROM materia"),
    "tutores" => contador($mysqli, "SELECT COUNT(*) FROM tutor"),
    "verificados" => contador($mysqli, "SELECT COUNT(*) FROM tutor WHERE verificado = 1"),
    "solicitudes" => contador($mysqli, "SELECT COUNT(*) FROM solicitud"),
    "pendientes" => contador($mysqli, "SELECT COUNT(*) FROM tutor WHERE verificado = 0"),
    "estudiantes" => contador($mysqli, "SELECT COUNT(*) FROM estudiante"),
];

$recientes = $mysqli->query(
    "SELECT s.id_solicitud, s.nombre_estudiante, s.fecha, s.medio, t.nombre AS tutor
     FROM solicitud s
     INNER JOIN tutor t ON t.id_tutor = s.id_tutor
     ORDER BY s.fecha DESC, s.id_solicitud DESC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$tituloPagina = "Panel principal";
require __DIR__ . "/../includes/header_admin.php";
?>

<div class="admin-titulo">
    <div>
        <p class="etiqueta">Dashboard</p>
        <h1>Panel principal</h1>
        <p>Resumen de KARMA. Gestiona materias, verifica tutores y revisa solicitudes.</p>
    </div>
    <div class="form-acciones" style="margin:0">
        <a class="btn" href="tutor_form.php">+ Nuevo tutor</a>
        <a class="btn btn-secundario" href="solicitudes.php">Ver solicitudes</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><strong><?= $stats["materias"] ?></strong><span>Materias</span></div>
    <div class="stat-card"><strong><?= $stats["tutores"] ?></strong><span>Tutores registrados</span></div>
    <div class="stat-card"><strong><?= $stats["verificados"] ?></strong><span>Tutores verificados</span></div>
    <div class="stat-card"><strong><?= $stats["solicitudes"] ?></strong><span>Solicitudes</span></div>
</div>

<?php if ($stats["pendientes"] > 0): ?>
    <div class="alerta alerta-info">Hay <strong><?= $stats["pendientes"] ?></strong> tutor(es) pendiente(s) de verificación. <a href="tutores.php?filtro=pendientes">Revisarlos ahora</a> · Alumnos registrados: <strong><?= $stats["estudiantes"] ?></strong></div>
<?php endif; ?>

<div class="panel-card">
    <h2 style="margin-bottom:1rem">Últimas solicitudes</h2>
    <?php if (count($recientes) === 0): ?>
        <div class="vacio">Todavía no hay solicitudes.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Tutor</th>
                        <th>Fecha</th>
                        <th>Medio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recientes as $s): ?>
                        <tr>
                            <td><?= e($s["nombre_estudiante"]) ?></td>
                            <td><?= e($s["tutor"]) ?></td>
                            <td><?= e($s["fecha"]) ?></td>
                            <td>
                                <span class="badge <?= $s["medio"] === "whatsapp" ? "badge-ok" : "" ?>">
                                    <?= e(ucfirst($s["medio"])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
