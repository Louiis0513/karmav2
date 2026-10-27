<?php
session_start();
require_once __DIR__ . "/includes/funciones.php";
enviarHeadersCache('privado');
unset($_SESSION["estudiante"], $_SESSION["tutor"]);
if (empty($_SESSION)) {
    session_destroy();
}
header("Location: index.php");
exit;
