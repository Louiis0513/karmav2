<?php
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function adminLogueado() {
    return isset($_SESSION["admin"]);
}

function requiereAdmin() {
    if (!adminLogueado()) {
        header("Location: login.php");
        exit;
    }
}

function guardarMensaje($texto) {
    $_SESSION["mensaje"] = $texto;
}

function obtenerMensaje() {
    $mensaje = $_SESSION["mensaje"] ?? "";
    unset($_SESSION["mensaje"]);
    return $mensaje;
}

function telefonoValido($telefono) {
    return (bool)preg_match('/^3\d{9}$/', (string)$telefono);
}

function rutaFoto($ruta, $fallback = "img/estudiantes.jpg") {
    $ruta = trim((string)$ruta);
    if ($ruta === "") {
        return $fallback;
    }
    return $ruta;
}

function whatsappUrl($telefono, $mensaje = "") {
    $url = "https://wa.me/57" . preg_replace('/\D+/', '', (string)$telefono);
    if ($mensaje !== "") {
        $url .= "?text=" . rawurlencode($mensaje);
    }
    return $url;
}

function contador($mysqli, $sql) {
    $resultado = $mysqli->query($sql);
    $fila = $resultado->fetch_row();
    return (int)($fila[0] ?? 0);
}
