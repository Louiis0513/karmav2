<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
enviarHeadersCache('privado');

if (estudianteLogueado()) { header("Location: tutores.php"); exit; }
if (tutorLogueado()) { header("Location: tutor/index.php"); exit; }

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim((string)($_POST["correo"] ?? ""));
    $password = (string)($_POST["password"] ?? "");

    if (!correoValido($correo) || $password === "") {
        $error = "Ingresa un correo válido y tu contraseña.";
    } else {
        // Primero alumnos, luego tutores
        $stmt = $mysqli->prepare("SELECT id_estudiante, nombre, correo, password_hash FROM estudiante WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $alumno = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($alumno && password_verify($password, $alumno["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["estudiante"] = ["id" => (int)$alumno["id_estudiante"], "nombre" => $alumno["nombre"], "correo" => $alumno["correo"]];
            guardarMensaje("Hola de nuevo, " . $alumno["nombre"] . ".");
            header("Location: tutores.php");
            exit;
        }

        $stmt = $mysqli->prepare("SELECT id_tutor, nombre, correo, password_hash, verificado FROM tutor WHERE correo = ? AND password_hash IS NOT NULL LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $tutor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($tutor && password_verify($password, $tutor["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["tutor"] = ["id" => (int)$tutor["id_tutor"], "nombre" => $tutor["nombre"], "correo" => $tutor["correo"], "verificado" => (int)$tutor["verificado"]];
            guardarMensaje("Hola de nuevo, " . $tutor["nombre"] . ".");
            header("Location: tutor/index.php");
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
    <title>Ingresar · KARMA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <a href="index.php" class="nav-marca">
            <img src="img/logo.png" alt="KARMA" style="height:52px">
            KARMA
        </a>
        <h1>Ingresar</h1>
        <p>Acceso para alumnos y tutores registrados.</p>

        <?php if ($error !== ""): ?>
            <div class="alerta alerta-error" style="margin-top:1rem;text-align:left"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="post" action="login.php" novalidate>
                <div class="form-grupo">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" required value="<?= e($_POST["correo"] ?? "") ?>" placeholder="tu@correo.com" autocomplete="username">
                </div>
                <div class="form-grupo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                </div>
                <div class="form-acciones" style="justify-content:center">
                    <button class="btn" type="submit" style="width:100%">Ingresar</button>
                </div>
            </form>
        </div>

        <div class="demo-cred">
            Demo alumno: <strong>alumno@demo.com</strong> / <strong>Alumno123!</strong><br>
            Demo tutor: <strong>andres@demo.com</strong> / <strong>Tutor123!</strong>
        </div>
        <p style="margin-top:1rem">¿No tienes cuenta? <a href="registro.php">Regístrate</a> · <a href="registro.php?tipo=tutor">Quiero ser tutor</a></p>
        <p style="margin-top:.5rem"><a href="index.php">← Volver al sitio</a></p>
    </div>
</body>
</html>
