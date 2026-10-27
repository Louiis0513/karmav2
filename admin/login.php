<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');

if (adminLogueado()) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim((string)($_POST["correo"] ?? ""));
    $password = (string)($_POST["password"] ?? "");

    if ($correo === "" || $password === "") {
        $error = "Ingresa correo y contraseña.";
    } else {
        $stmt = $mysqli->prepare(
            "SELECT id_admin, nombre, correo, password_hash
             FROM administrador WHERE correo = ? LIMIT 1"
        );
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin["password_hash"])) {
            $_SESSION["admin"] = [
                "id" => (int)$admin["id_admin"],
                "nombre" => $admin["nombre"],
                "correo" => $admin["correo"],
            ];
            guardarMensaje("Bienvenida/o, " . $admin["nombre"] . ".");
            header("Location: index.php");
            exit;
        }

        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · Panel KARMA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <a href="../index.php" class="nav-marca">
            <img src="../img/logo.png" alt="KARMA" style="height:52px">
            KARMA
        </a>
        <h1>Panel de administración</h1>
        <p>Acceso solo para administradores verificados.</p>

        <?php if ($error !== ""): ?>
            <div class="alerta alerta-error" style="margin-top:1rem;text-align:left"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="post" action="login.php" novalidate>
                <div class="form-grupo">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" required
                           value="<?= e($_POST["correo"] ?? "admin@demo.com") ?>"
                           placeholder="admin@demo.com" autocomplete="username">
                </div>
                <div class="form-grupo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••" autocomplete="current-password">
                </div>
                <div class="form-acciones" style="justify-content:center">
                    <button class="btn" type="submit" style="width:100%">Ingresar</button>
                </div>
            </form>
        </div>

        <div class="demo-cred">
            Demo: <strong>admin@demo.com</strong> / <strong>Admin123!</strong>
        </div>
        <p style="margin-top:1rem"><a href="../index.php">← Volver al sitio</a></p>
    </div>
</body>
</html>
