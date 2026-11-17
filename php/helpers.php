<?php
// Funciones compartidas: sesión, seguridad (CSRF), consultas preparadas y textos.
if (session_status() === PHP_SESSION_NONE) session_start();

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok() {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

// Consultas preparadas: devuelven filas ([] si falla) o true/false para escrituras
function q_all($c, $sql, $types = '', $params = []) {
    $st = mysqli_prepare($c, $sql);
    if (!$st) return [];
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    if (!mysqli_stmt_execute($st)) return [];
    $r = mysqli_stmt_get_result($st);
    return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
}
function q_one($c, $sql, $types = '', $params = []) {
    $a = q_all($c, $sql, $types, $params);
    return $a[0] ?? null;
}
function q_run($c, $sql, $types = '', $params = []) {
    $st = mysqli_prepare($c, $sql);
    if (!$st) return false;
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    return mysqli_stmt_execute($st);
}

function hace($fecha) {
    $t = strtotime((string) $fecha);
    if (!$t) return '';
    $d = time() - $t;
    if ($d < 60) return 'hace un momento';
    if ($d < 3600) return 'hace ' . floor($d / 60) . ' min';
    if ($d < 86400) return 'hace ' . floor($d / 3600) . ' h';
    if ($d < 2592000) return 'hace ' . floor($d / 86400) . ' d';
    return date('d/m/Y', $t);
}

function inicial($nombre) {
    $n = trim((string) $nombre);
    return $n === '' ? '?' : mb_strtoupper(mb_substr($n, 0, 1, 'UTF-8'), 'UTF-8');
}

// "María Fernanda López" -> "María L."  (privacidad en el muro público)
function nombre_publico($nombre) {
    $p = preg_split('/\s+/', trim((string) $nombre));
    if (count($p) < 2) return $p[0] ?? '';
    return $p[0] . ' ' . mb_strtoupper(mb_substr(end($p), 0, 1, 'UTF-8'), 'UTF-8') . '.';
}

function json_out($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
