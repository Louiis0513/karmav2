<?php
session_start();
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');
session_destroy();

header("Location: login.php");
exit;
