<?php
$esAdmin = str_contains($_SERVER["PHP_SELF"] ?? "", "/admin/");
if ($esAdmin): ?>
    </div>
<?php endif; ?>

    <footer class="pie">
        <p>&copy; <?= date("Y") ?> KARMA · Tutorías para bachillerato · Bogotá, Colombia</p>
        <?php if (!$esAdmin): ?>
            <p style="margin-top:0.4rem;font-size:0.9rem;opacity:.9">
                <a href="admin/login.php">Acceso administradores</a>
            </p>
        <?php else: ?>
            <p style="margin-top:0.4rem;font-size:0.9rem;opacity:.9">
                Sesión: <?= e($_SESSION["admin"]["nombre"] ?? "Administrador") ?>
            </p>
        <?php endif; ?>
    </footer>
    <script src="<?= $esAdmin ? '../js/app.js' : 'js/app.js' ?>"></script>
</body>
</html>
