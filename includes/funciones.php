<?php
require_once __DIR__ . '/cache.php';

function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function enviarHeadersCache(string $modo = 'privado'): void
{
    if (headers_sent()) {
        return;
    }
    if ($modo === 'publico') {
        // HTML breve; la fuente de verdad del directorio es el caché de archivos (TTL 4 h + parche al editar).
        header('Cache-Control: public, max-age=120, stale-while-revalidate=60');
        header('Vary: Cookie');
    } else {
        header('Cache-Control: no-store, private');
    }
}

/* ——— Lecturas cacheadas (hosting compartido) ——— */

/** Materias simples (filtros / selects). */
function obtenerMaterias(mysqli $db): array
{
    $cached = cacheGet('materias');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query('SELECT id_materia, nombre FROM materia ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
    cacheSet('materias', $rows, CACHE_TTL_MATERIAS);
    return $rows;
}

/** Home: materias con imagen, descripción y conteo de tutores verificados. */
function obtenerMateriasHome(mysqli $db): array
{
    $cached = cacheGet('materias_home');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query(
        'SELECT m.id_materia, m.nombre, m.imagen, m.descripcion, COUNT(t.id_tutor) AS tutores
         FROM materia m
         LEFT JOIN tutor t ON t.id_materia = m.id_materia AND t.verificado = 1
         GROUP BY m.id_materia, m.nombre, m.imagen, m.descripcion
         ORDER BY m.nombre'
    )->fetch_all(MYSQLI_ASSOC);
    cacheSet('materias_home', $rows, CACHE_TTL_MATERIAS);
    return $rows;
}

/**
 * Directorio de tutores verificados. Cachea sin búsqueda de texto (materia).
 * @return list<array<string,mixed>>
 */
function obtenerTutores(mysqli $db, int $idMateria = 0, string $q = ''): array
{
    $q = trim($q);
    $usarCache = ($q === '');
    $cacheKey = 'tutores_' . max(0, $idMateria);
    if ($usarCache) {
        $cached = cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $sql = 'SELECT t.id_tutor, t.nombre, t.descripcion, t.disponibilidad, t.precio_hora, t.modalidad, t.foto_url,
                   m.id_materia, m.nombre AS materia
            FROM tutor t
            INNER JOIN materia m ON m.id_materia = t.id_materia
            WHERE t.verificado = 1';
    $types = '';
    $params = [];
    if ($idMateria > 0) {
        $sql .= ' AND t.id_materia = ?';
        $types .= 'i';
        $params[] = $idMateria;
    }
    if ($q !== '') {
        $sql .= ' AND (t.nombre LIKE ? OR t.descripcion LIKE ? OR m.nombre LIKE ?)';
        $types .= 'sss';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY t.nombre';
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($usarCache) {
        cacheSet($cacheKey, $rows, CACHE_TTL_DEFAULT);
    }
    return $rows;
}

/** Carga tutor + materia (verificado o no). */
function cargarTutorConMateria(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT t.id_tutor, t.nombre, t.descripcion, t.disponibilidad, t.precio_hora, t.modalidad,
                t.telefono, t.foto_url, t.verificado, t.id_materia, m.nombre AS materia
         FROM tutor t
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE t.id_tutor = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Ficha pública: solo tutor verificado. */
function obtenerTutorVerificado(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $key = 'tutor_' . $id;
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    if ($cached === false) {
        return null;
    }
    $row = cargarTutorConMateria($db, $id);
    if ($row && (int) ($row['verificado'] ?? 0) === 1) {
        cacheSet($key, $row, CACHE_TTL_TUTOR);
        return $row;
    }
    cacheSet($key, false, CACHE_TTL_NEGATIVO);
    return null;
}

/* ——— Parche de caché (write-through): sin wipe total ——— */

function tutorEntraEnListado(array $tutor, int $idMateriaFiltro): bool
{
    if ((int) ($tutor['verificado'] ?? 0) !== 1) {
        return false;
    }
    if ($idMateriaFiltro > 0 && (int) ($tutor['id_materia'] ?? 0) !== $idMateriaFiltro) {
        return false;
    }
    return true;
}

/** Fila compacta para listados (sin teléfono). */
function tutorFilaListado(array $tutor): array
{
    return [
        'id_tutor' => (int) ($tutor['id_tutor'] ?? 0),
        'nombre' => (string) ($tutor['nombre'] ?? ''),
        'descripcion' => $tutor['descripcion'] ?? null,
        'disponibilidad' => (string) ($tutor['disponibilidad'] ?? ''),
        'precio_hora' => $tutor['precio_hora'] ?? null,
        'modalidad' => (string) ($tutor['modalidad'] ?? 'virtual'),
        'foto_url' => $tutor['foto_url'] ?? null,
        'id_materia' => (int) ($tutor['id_materia'] ?? 0),
        'materia' => (string) ($tutor['materia'] ?? ''),
    ];
}

function cacheListaQuitarTutor(array $lista, int $idTutor): array
{
    $out = [];
    foreach ($lista as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((int) ($row['id_tutor'] ?? 0) === $idTutor) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

function cacheListaUpsertTutor(array $lista, array $tutor): array
{
    $id = (int) ($tutor['id_tutor'] ?? 0);
    $lista = cacheListaQuitarTutor($lista, $id);
    $lista[] = tutorFilaListado($tutor);
    usort($lista, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    });
    return $lista;
}

/**
 * Actualiza conteo de una materia dentro de materias_home (1 query pequeña).
 * Si el home no está en caché, no hace nada (lazy).
 */
function cacheParcheMateriasHomeFila(mysqli $db, int $idMateria): void
{
    $idMateria = (int) $idMateria;
    if ($idMateria <= 0) {
        return;
    }
    $peek = cachePeek('materias_home');
    if ($peek === null || !is_array($peek['data'])) {
        return;
    }
    $stmt = $db->prepare(
        'SELECT m.id_materia, m.nombre, m.imagen, m.descripcion, COUNT(t.id_tutor) AS tutores
         FROM materia m
         LEFT JOIN tutor t ON t.id_materia = m.id_materia AND t.verificado = 1
         WHERE m.id_materia = ?
         GROUP BY m.id_materia, m.nombre, m.imagen, m.descripcion'
    );
    $stmt->bind_param('i', $idMateria);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $home = $peek['data'];
    $encontrado = false;
    foreach ($home as $i => $row) {
        if ((int) ($row['id_materia'] ?? 0) === $idMateria) {
            if ($fila) {
                $home[$i] = $fila;
            } else {
                unset($home[$i]);
                $home = array_values($home);
            }
            $encontrado = true;
            break;
        }
    }
    if (!$encontrado && $fila) {
        $home[] = $fila;
        usort($home, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
        });
    }
    cachePatch('materias_home', $home, CACHE_TTL_MATERIAS);
}

/**
 * Parchea ficha + listados existentes + home tras create/update/verificar.
 * Una sola lectura del tutor; no regenera listados desde MySQL.
 */
function cacheParcheTutor(mysqli $db, int $idTutor, ?array $antes = null): void
{
    $idTutor = (int) $idTutor;
    if ($idTutor <= 0) {
        return;
    }
    $ahora = cargarTutorConMateria($db, $idTutor);
    $matsAfectadas = [];
    if ($antes && !empty($antes['id_materia'])) {
        $matsAfectadas[(int) $antes['id_materia']] = true;
    }
    if ($ahora && !empty($ahora['id_materia'])) {
        $matsAfectadas[(int) $ahora['id_materia']] = true;
    }

    if ($ahora && (int) ($ahora['verificado'] ?? 0) === 1) {
        cachePatch('tutor_' . $idTutor, $ahora, CACHE_TTL_TUTOR);
    } else {
        cachePatch('tutor_' . $idTutor, false, CACHE_TTL_NEGATIVO);
    }

    foreach (cacheKeysByPrefix('tutores_') as $key) {
        if (!preg_match('/^tutores_(\d+)$/', $key, $m)) {
            continue;
        }
        $idMatFiltro = (int) $m[1];
        cachePatchIfPresent($key, static function ($data) use ($ahora, $idTutor, $idMatFiltro) {
            if (!is_array($data)) {
                return $data;
            }
            $data = cacheListaQuitarTutor($data, $idTutor);
            if ($ahora && tutorEntraEnListado($ahora, $idMatFiltro)) {
                $data = cacheListaUpsertTutor($data, $ahora);
            }
            return $data;
        });
    }

    foreach (array_keys($matsAfectadas) as $idMat) {
        cacheParcheMateriasHomeFila($db, (int) $idMat);
    }
}

/** Elimina tutor del caché (tras DELETE). $antes = fila previa con id_materia. */
function cacheParcheTutorEliminado(mysqli $db, int $idTutor, ?array $antes = null): void
{
    $idTutor = (int) $idTutor;
    cacheForget('tutor_' . $idTutor);
    foreach (cacheKeysByPrefix('tutores_') as $key) {
        cachePatchIfPresent($key, static function ($data) use ($idTutor) {
            return is_array($data) ? cacheListaQuitarTutor($data, $idTutor) : $data;
        });
    }
    $idMat = (int) ($antes['id_materia'] ?? 0);
    if ($idMat > 0) {
        cacheParcheMateriasHomeFila($db, $idMat);
    } else {
        cacheForget('materias_home');
    }
}

/**
 * Tras create/edit/delete de materia: refresca listados.
 * Parchea nombre de materia en tutores/fichas ya cacheados (sin wipe).
 */
function cacheParcheMaterias(mysqli $db, ?int $idMatRenombrada = null, ?string $nombreNuevo = null, bool $eliminada = false): void
{
    $rows = $db->query('SELECT id_materia, nombre FROM materia ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
    cachePatch('materias', $rows, CACHE_TTL_MATERIAS);

    $home = $db->query(
        'SELECT m.id_materia, m.nombre, m.imagen, m.descripcion, COUNT(t.id_tutor) AS tutores
         FROM materia m
         LEFT JOIN tutor t ON t.id_materia = m.id_materia AND t.verificado = 1
         GROUP BY m.id_materia, m.nombre, m.imagen, m.descripcion
         ORDER BY m.nombre'
    )->fetch_all(MYSQLI_ASSOC);
    cachePatch('materias_home', $home, CACHE_TTL_MATERIAS);

    if ($idMatRenombrada && $nombreNuevo !== null && !$eliminada) {
        $id = (int) $idMatRenombrada;
        $nom = (string) $nombreNuevo;
        foreach (cacheKeysByPrefix('tutores_') as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (!is_array($data)) {
                    return $data;
                }
                foreach ($data as $i => $row) {
                    if (is_array($row) && (int) ($row['id_materia'] ?? 0) === $id) {
                        $data[$i]['materia'] = $nom;
                    }
                }
                return $data;
            });
        }
        foreach (cacheKeysByPrefix('tutor_') as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (is_array($data) && (int) ($data['id_materia'] ?? 0) === $id) {
                    $data['materia'] = $nom;
                }
                return $data;
            });
        }
    }

    if ($eliminada && $idMatRenombrada) {
        $id = (int) $idMatRenombrada;
        cacheForget('tutores_' . $id);
    }
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

function estudianteLogueado() {
    return isset($_SESSION["estudiante"]);
}

function tutorLogueado() {
    return isset($_SESSION["tutor"]);
}

function requiereEstudiante() {
    if (!estudianteLogueado()) {
        guardarMensaje("Inicia sesión como alumno para continuar.");
        header("Location: login.php");
        exit;
    }
}

function requiereTutor($base = "../") {
    if (!tutorLogueado()) {
        guardarMensaje("Inicia sesión como tutor para continuar.");
        header("Location: " . $base . "login.php");
        exit;
    }
}

/** Devuelve ['tipo'=>'estudiante'|'tutor','id'=>int,'nombre'=>...] o null */
function usuarioChat() {
    if (estudianteLogueado()) {
        return ["tipo" => "estudiante", "id" => (int)$_SESSION["estudiante"]["id"], "nombre" => $_SESSION["estudiante"]["nombre"]];
    }
    if (tutorLogueado()) {
        return ["tipo" => "tutor", "id" => (int)$_SESSION["tutor"]["id"], "nombre" => $_SESSION["tutor"]["nombre"]];
    }
    return null;
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

function correoValido($correo) {
    return (bool)filter_var((string)$correo, FILTER_VALIDATE_EMAIL);
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

function formatoPrecio($precio) {
    if ($precio === null || $precio === "" || (float)$precio <= 0) {
        return "A acordar";
    }
    return "$" . number_format((float)$precio, 0, ",", ".") . " / hora";
}

function etiquetaModalidad($m) {
    return ["virtual" => "Virtual", "presencial" => "Presencial", "ambas" => "Virtual y presencial"][$m] ?? "Virtual";
}

/**
 * Sube una imagen a img/<carpeta>/ y devuelve la ruta relativa (ej: img/tutores/abc.webp)
 * o null si no se subió archivo. Lanza Exception con mensaje si es inválida.
 * $raiz = ruta absoluta a la raíz del proyecto KARMA.
 */
function subirImagen(array $file, $carpeta, $raiz) {
    if (!isset($file["error"]) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("No se pudo subir la imagen. Inténtalo de nuevo.");
    }
    if ($file["size"] > 2 * 1024 * 1024) {
        throw new Exception("La imagen debe pesar máximo 2 MB.");
    }
    $tipos = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    $mime = mime_content_type($file["tmp_name"]);
    if (!isset($tipos[$mime])) {
        throw new Exception("Formato no permitido. Usa JPG, PNG o WebP.");
    }
    $dir = rtrim($raiz, "/") . "/img/" . $carpeta;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $nombre = $carpeta . "_" . bin2hex(random_bytes(8)) . "." . $tipos[$mime];
    if (!move_uploaded_file($file["tmp_name"], $dir . "/" . $nombre)) {
        throw new Exception("No se pudo guardar la imagen en el servidor.");
    }
    return "img/" . $carpeta . "/" . $nombre;
}

function borrarFotoSiLocal($ruta, $raiz) {
    $ruta = trim((string)$ruta);
    if ($ruta === "" || !str_starts_with($ruta, "img/tutores/")) {
        return;
    }
    // No borrar las fotos demo sembradas
    $demo = ["andres.jpg","camila.jpg","julian.jpg","valentina.jpg","daniel.jpg","sofia.jpg","mateo.jpg","laura.jpg"];
    if (in_array(basename($ruta), $demo, true)) {
        return;
    }
    $abs = rtrim($raiz, "/") . "/" . $ruta;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

/** Verifica que la conversación exista y el usuario sea participante. Devuelve fila o null */
function conversacionParaUsuario($mysqli, $idConversacion, array $usuario) {
    $stmt = $mysqli->prepare(
        "SELECT c.*, e.nombre AS nombre_estudiante, t.nombre AS nombre_tutor, t.foto_url AS foto_tutor, m.nombre AS materia
         FROM conversacion c
         INNER JOIN estudiante e ON e.id_estudiante = c.id_estudiante
         INNER JOIN tutor t ON t.id_tutor = c.id_tutor
         INNER JOIN materia m ON m.id_materia = t.id_materia
         WHERE c.id_conversacion = ? LIMIT 1"
    );
    $stmt->bind_param("i", $idConversacion);
    $stmt->execute();
    $conv = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$conv) return null;
    if ($usuario["tipo"] === "estudiante" && (int)$conv["id_estudiante"] !== $usuario["id"]) return null;
    if ($usuario["tipo"] === "tutor" && (int)$conv["id_tutor"] !== $usuario["id"]) return null;
    return $conv;
}

function mensajesNoLeidos($mysqli, array $usuario) {
    if ($usuario["tipo"] === "estudiante") {
        $stmt = $mysqli->prepare(
            "SELECT COUNT(*) FROM mensaje msg
             INNER JOIN conversacion c ON c.id_conversacion = msg.id_conversacion
             WHERE c.id_estudiante = ? AND msg.remitente_tipo = 'tutor' AND msg.leido = 0"
        );
    } else {
        $stmt = $mysqli->prepare(
            "SELECT COUNT(*) FROM mensaje msg
             INNER JOIN conversacion c ON c.id_conversacion = msg.id_conversacion
             WHERE c.id_tutor = ? AND msg.remitente_tipo = 'estudiante' AND msg.leido = 0"
        );
    }
    $stmt->bind_param("i", $usuario["id"]);
    $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}
