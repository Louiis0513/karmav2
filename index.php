<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

enviarHeadersCache('publico');

$materias = obtenerMateriasHome($mysqli);
$totalTutores = 0;
foreach ($materias as $m) {
    $totalTutores += (int) ($m['tutores'] ?? 0);
}
$totalMaterias = count($materias);
$totalSolicitudes = contador($mysqli, "SELECT COUNT(*) FROM solicitud");

$tituloPagina = "Inicio";
require __DIR__ . "/includes/header.php";
?>

<main class="portada">

    <section id="inicio" class="portada-hero">
        <div class="portada-hero-texto">
            <p class="etiqueta portada-etiqueta">Tutorías para estudiantes de bachillerato</p>
            <h1>Aprender juntos es <span class="resaltado">más fácil</span></h1>
            <p>¿Te cuesta una materia? En KARMA encuentras tutores verificados
            que te explican de forma sencilla, personalizada y cercana.</p>

            <form action="tutores.php" method="get" class="portada-buscador" role="search">
                <input type="text" name="buscar" placeholder="¿Qué necesitas reforzar? Ej: álgebra" aria-label="Buscar materia o tutor">
                <button type="submit">Buscar tutor</button>
            </form>

            <?php if (count($materias) > 0): ?>
                <div class="portada-materias-rapidas">
                    <?php foreach ($materias as $m): ?>
                        <a href="tutores.php?materia=<?= (int)$m["id_materia"] ?>"><?= e($m["nombre"]) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="stats-strip">
                <div class="stat-pill"><strong><?= $totalTutores ?></strong><span>tutores verificados</span></div>
                <div class="stat-pill"><strong><?= $totalMaterias ?></strong><span>materias activas</span></div>
                <div class="stat-pill"><strong><?= $totalSolicitudes ?></strong><span>solicitudes</span></div>
            </div>
        </div>

        <div class="portada-hero-imagen">
            <span class="destello destello-grande" aria-hidden="true"></span>
            <span class="circulo circulo-verde" aria-hidden="true"></span>
            <img src="img/hero-estudio.jpg" alt="Estudiantes colaborando en una tutoría">
            <span class="destello destello-pequeno" aria-hidden="true"></span>
        </div>
    </section>

    <section class="portada-pasos">
        <p class="etiqueta">Paso a paso</p>
        <h2>¿Cómo funciona?</h2>
        <div class="portada-pasos-lista">
            <div class="portada-paso">
                <span class="portada-paso-numero">01</span>
                <h3>Elige la materia</h3>
                <p>Busca por tema o toca la materia en la que necesitas refuerzo.</p>
            </div>
            <div class="portada-paso">
                <span class="portada-paso-numero">02</span>
                <h3>Conoce al tutor</h3>
                <p>Revisa su perfil, su experiencia y los horarios en que puede ayudarte.</p>
            </div>
            <div class="portada-paso">
                <span class="portada-paso-numero">03</span>
                <h3>Escríbele</h3>
                <p>Regístrate como alumno, abre el chat con tu tutor y acuerda tema, horario y modalidad. También puedes usar WhatsApp.</p>
            </div>
        </div>
    </section>

    <section id="materias" class="portada-materias">
        <p class="etiqueta">Catálogo</p>
        <h2>Materias disponibles</h2>
        <?php if (count($materias) === 0): ?>
            <div class="vacio">Pronto tendremos materias disponibles.</div>
        <?php else: ?>
            <div class="portada-materias-lista">
                <?php foreach ($materias as $m): ?>
                    <a href="tutores.php?materia=<?= (int)$m["id_materia"] ?>" class="portada-materia card-materia">
                        <div class="card-materia-media">
                            <img src="<?= e(rutaFoto($m["imagen"] ?? "", "img/estudiantes.jpg")) ?>" alt="<?= e($m["nombre"]) ?>">
                        </div>
                        <div class="card-materia-body">
                            <h3><?= e($m["nombre"]) ?></h3>
                            <p><?= e($m["descripcion"] ?? "") ?></p>
                            <p class="meta">
                                <?= (int)$m["tutores"] ?>
                                <?= (int)$m["tutores"] === 1 ? "tutor verificado" : "tutores verificados" ?>
                            </p>
                            <span class="card-enlace">Ver tutores →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="portada-confianza">
        <img src="img/confianza.jpg" alt="Estudiante tomando apuntes en una tutoría">
        <div>
            <p class="etiqueta" style="color:#b9ef9c">Confianza</p>
            <h2>¿Por qué KARMA?</h2>
            <div class="portada-razon">
                <h3>Tutores verificados</h3>
                <p>Antes de aparecer en KARMA, el administrador revisa los datos de cada tutor.</p>
            </div>
            <div class="portada-razon">
                <h3>Contacto directo</h3>
                <p>Hablas con tu tutor por WhatsApp, sin intermediarios ni comisiones ocultas.</p>
            </div>
            <div class="portada-razon">
                <h3>Tú acuerdas horario y precio</h3>
                <p>Se ponen de acuerdo directamente, sin pagos dentro de la página.</p>
            </div>
        </div>
    </section>

    <section id="nosotros" class="portada-nosotros">
        <p class="etiqueta">Equipo</p>
        <h2>Nosotros</h2>
        <p>KARMA es un proyecto creado para facilitar el aprendizaje
        entre estudiantes. Buscamos que pedir ayuda académica sea
        más accesible y cómodo para todos.</p>

        <ul class="portada-equipo">
            <li>Maria Celeste Ruiz Rodriguez</li>
            <li>Maria Jose Perez Lombana</li>
            <li>Laura Katherine Benavides Peña</li>
            <li>Angie Carolina Castilla Ovalle</li>
            <li>Maria Jose Alvarez Garcia</li>
        </ul>

        <div class="form-acciones" style="justify-content:center;margin-top:1.5rem">
            <a class="btn" href="registro.php">Registrarme como alumno</a>
            <a class="btn btn-secundario" href="registro.php?tipo=tutor">Quiero ser tutor</a>
        </div>

        <p id="ubicacion" class="portada-ubicacion">Bogotá, Colombia · Ficha 3260309</p>
    </section>

</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
