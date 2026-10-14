<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

// Pantalla 7: las materias salen de la tabla materia, con cuántos tutores verificados tiene cada una.
$resultado = $mysqli->query(
    "SELECT m.id_materia, m.nombre, COUNT(t.id_tutor) AS tutores
     FROM materia m
     LEFT JOIN tutor t ON t.id_materia = m.id_materia AND t.verificado = 1
     GROUP BY m.id_materia, m.nombre
     ORDER BY m.nombre"
);
$materias = $resultado->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>

<main class="portada">

    <section id="inicio" class="portada-hero">
        <div class="portada-hero-texto">
            <p class="portada-etiqueta">Tutorías para estudiantes de bachillerato</p>
            <h1>Aprender juntos es <span class="resaltado">más fácil</span></h1>
            <p>¿Te cuesta una materia? En KARMA encuentras tutores verificados
            que te explican de forma sencilla, personalizada y económica.</p>

            <form action="tutores.php" method="get" class="portada-buscador">
                <input type="text" name="buscar" placeholder="¿Qué necesitas reforzar? Ej: álgebra">
                <button type="submit">Buscar tutor</button>
            </form>

            <?php if (count($materias) > 0): ?>
                <div class="portada-materias-rapidas">
                    <?php foreach ($materias as $m): ?>
                        <a href="tutores.php?materia=<?= $m["id_materia"] ?>"><?= e($m["nombre"]) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="portada-hero-imagen">
            <span class="destello destello-grande"></span>
            <span class="circulo circulo-verde"></span>
            <img src="img/estudiantes.jpg" alt="Estudiantes estudiando juntos alrededor de una mesa">
            <span class="destello destello-pequeno"></span>
        </div>
    </section>

    <section class="portada-pasos">
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
                <p>Contáctalo por WhatsApp o deja tu solicitud con tu duda y tu teléfono.</p>
            </div>
        </div>
    </section>

    <section id="materias" class="portada-materias">
        <h2>Materias disponibles</h2>
        <?php if (count($materias) === 0): ?>
            <p>Pronto tendremos materias disponibles.</p>
        <?php else: ?>
            <div class="portada-materias-lista">
                <?php foreach ($materias as $m): ?>
                    <a href="tutores.php?materia=<?= $m["id_materia"] ?>" class="portada-materia">
                        <h3><?= e($m["nombre"]) ?></h3>
                        <p>
                            <?= $m["tutores"] ?>
                            <?= $m["tutores"] == 1 ? "tutor verificado" : "tutores verificados" ?>
                        </p>
                        <span>Ver tutores →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="portada-confianza">
        <img src="img/escribiendo.jpg" alt="Estudiante tomando apuntes en un cuaderno">
        <div>
            <h2>¿Por qué KARMA?</h2>
            <div class="portada-razon">
                <h3>Tutores verificados</h3>
                <p>Antes de aparecer en KARMA, el administrador revisa los datos de cada tutor.</p>
            </div>
            <div class="portada-razon">
                <h3>Contacto directo</h3>
                <p>Hablas con tu tutor por WhatsApp, sin intermediarios.</p>
            </div>
            <div class="portada-razon">
                <h3>Tú acuerdas el horario y el precio</h3>
                <p>Se ponen de acuerdo directamente, sin pagos dentro de la página.</p>
            </div>
        </div>
    </section>

    <section id="nosotros" class="portada-nosotros">
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

        <p id="ubicacion" class="portada-ubicacion">Bogotá, Colombia · Ficha 3260309</p>
    </section>

</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
