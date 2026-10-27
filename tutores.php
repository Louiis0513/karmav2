<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

enviarHeadersCache('publico');

$materiaId = isset($_GET["materia"]) ? (int)$_GET["materia"] : 0;
$buscar = trim((string)($_GET["buscar"] ?? ""));

$materias = obtenerMaterias($mysqli);
$tutores = obtenerTutores($mysqli, $materiaId, $buscar);

$tituloPagina = "Tutores";
require __DIR__ . "/includes/header.php";
?>

<main class="seccion">
    <div class="contenedor">
        <p class="etiqueta">Directorio</p>
        <div class="admin-titulo" style="margin-bottom:1.25rem">
            <div class="seccion-intro" style="margin:0">
                <h1>Tutores verificados</h1>
                <p>Elige un perfil, revisa disponibilidad y escríbele directo. Filtra por materia o busca por nombre.</p>
            </div>
        </div>

        <div class="toolbar">
            <div class="filtros">
                <a href="tutores.php" class="<?= $materiaId === 0 && $buscar === '' ? 'activo' : '' ?>">Todas</a>
                <?php foreach ($materias as $m): ?>
                    <a href="tutores.php?materia=<?= (int)$m["id_materia"] ?>"
                       class="<?= $materiaId === (int)$m["id_materia"] ? 'activo' : '' ?>">
                        <?= e($m["nombre"]) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <form class="buscador-inline" method="get" action="tutores.php">
                <?php if ($materiaId > 0): ?>
                    <input type="hidden" name="materia" value="<?= $materiaId ?>">
                <?php endif; ?>
                <input type="search" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar tutor o tema">
                <button class="btn btn-sm" type="submit">Buscar</button>
            </form>
        </div>

        <?php if (count($tutores) === 0): ?>
            <div class="vacio">
                No hay tutores con esos filtros. Prueba otra materia o limpia la búsqueda.
            </div>
        <?php else: ?>
            <div class="grid-tutores">
                <?php foreach ($tutores as $t): ?>
                    <a class="card-tutor" href="tutor.php?id=<?= (int)$t["id_tutor"] ?>">
                        <div class="card-tutor-media">
                            <img src="<?= e(rutaFoto($t["foto_url"])) ?>" alt="Foto de <?= e($t["nombre"]) ?>">
                        </div>
                        <div class="card-tutor-body">
                            <span class="badge"><?= e($t["materia"]) ?></span>
                            <h3 style="margin-top:0.55rem"><?= e($t["nombre"]) ?></h3>
                            <p><?= e($t["descripcion"] ?? "") ?></p>
                            <p class="meta"><?= e($t["disponibilidad"]) ?></p>
                            <p class="meta"><?= e(formatoPrecio($t["precio_hora"] ?? null)) ?> · <?= e(etiquetaModalidad($t["modalidad"] ?? "virtual")) ?></p>
                            <span class="card-enlace">Ver perfil →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
