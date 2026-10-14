<?php
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function adminLogueado() {
    return isset($_SESSION["admin"]);
}

// Todas las pantallas de admin/ la llaman al inicio: sin sesión, vuelven al login.
function requiereAdmin() {
    if (!adminLogueado()) {
        header("Location: login.php");
        exit;
    }
}

// Mensaje "flash": se guarda antes de redirigir y se muestra una sola vez.
function guardarMensaje($texto) {
    $_SESSION["mensaje"] = $texto;
}

function obtenerMensaje() {
    $mensaje = $_SESSION["mensaje"] ?? "";
    unset($_SESSION["mensaje"]);
    return $mensaje;
}
