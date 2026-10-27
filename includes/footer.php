<?php
$scriptActual = $_SERVER["PHP_SELF"] ?? "";
$esAdmin = str_contains($scriptActual, "/admin/");
$enSubcarpeta = $esAdmin || str_contains($scriptActual, "/tutor/");
$prefijo = $enSubcarpeta ? "../" : "";
if ($esAdmin || str_contains($scriptActual, "/tutor/")): ?>
    </div>
<?php endif; ?>

    <footer class="pie">
        <p>&copy; <?= date("Y") ?> KARMA · Tutorías para bachillerato · Bogotá, Colombia</p>
        <?php if ($esAdmin): ?>
            <p style="margin-top:0.4rem;font-size:0.9rem;opacity:.9">
                Sesión: <?= e($_SESSION["admin"]["nombre"] ?? "Administrador") ?>
            </p>
        <?php else: ?>
            <p style="margin-top:0.4rem;font-size:0.9rem;opacity:.9">
                <a href="<?= $prefijo ?>admin/login.php">Acceso administradores</a>
            </p>
        <?php endif; ?>
    </footer>
    <script src="<?= $prefijo ?>js/app.js"></script>
</body>
</html>
