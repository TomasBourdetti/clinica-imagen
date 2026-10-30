<?php
// Núcleo del panel: sesión, permisos, seguridad (CSRF) y diseño común
require __DIR__ . '/../db.php';

const MODULOS = ['pacientes' => 'Pacientes', 'citas' => 'Agenda de citas', 'personal' => 'Personal',
                 'sucursales' => 'Sucursales y servicios', 'resultados' => 'Resultados e imágenes', 'roles' => 'Roles y permisos'];
const ROLES_STAFF = ['admin', 'recepcionista', 'profesional', 'tecnico'];

if (empty($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit; }
$st = $pdo->prepare('SELECT id_usuario, nombre_completo, rol FROM usuarios WHERE id_usuario = ? AND activo = 1');
$st->execute([$_SESSION['id_usuario']]);
$yo = $st->fetch();
if (!$yo || $yo['rol'] === 'paciente') { header('Location: ../perfil.php'); exit; }

if ($yo['rol'] === 'admin') $permitidos = array_keys(MODULOS);       // el admin ve todo
else { $p = $pdo->prepare('SELECT modulo FROM permiso WHERE rol = ?'); $p->execute([$yo['rol']]); $permitidos = $p->fetchAll(PDO::FETCH_COLUMN); }

function exigir($modulo) {
    global $permitidos;
    if (!in_array($modulo, $permitidos)) { http_response_code(403); die('No tienes permiso para esta sección. <a href="index.php">Volver</a>'); }
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf() { return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">'; }
function post() {   // true si llegó un POST con token válido
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return false;
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Solicitud no válida.');
    return true;
}
function msg($t, $tipo = 'ok') { return $t ? '<div class="' . $tipo . '">' . e($t) . '</div>' : ''; }
function bd($estado) { return '<span class="badge e-' . e(preg_replace('/\W/', '', strtolower($estado))) . '">' . e($estado) . '</span>'; }
function btn($accion, $campos, $txt, $cls = '', $conf = '') {   // botón mini con su formulario
    $h = '<form method="post" class="mini"' . ($conf ? ' onsubmit="return confirm(\'' . $conf . '\')"' : '') . '>' . csrf()
       . '<input type="hidden" name="accion" value="' . e($accion) . '">';
    foreach ($campos as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return $h . '<button class="' . $cls . '">' . e($txt) . '</button></form>';
}
function validarUsuario($d, $pdo, $idExcluir = 0, $pedirClave = true) {
    $err = [];
    if (trim($d['nombre_completo'] ?? '') === '') $err[] = 'Falta el nombre.';
    if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $err[] = 'Email no válido.';
    if (!preg_match('/^\d{' . LARGO_CEDULA . '}$/', $d['cedula'] ?? '')) $err[] = 'La cédula debe tener ' . LARGO_CEDULA . ' números.';
    $t = trim($d['telefono'] ?? '');
    if ($t !== '' && !preg_match('/^\d{' . LARGO_TELEFONO . '}$/', $t)) $err[] = 'El teléfono debe tener ' . LARGO_TELEFONO . ' números.';
    if ($fe = errorFechaNacimiento($d['fecha_nacimiento'] ?? '')) $err[] = $fe;
    if ($pedirClave && strlen($d['contrasena'] ?? '') < 8) $err[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (!$err) {
        $q = $pdo->prepare('SELECT 1 FROM usuarios WHERE (email = ? OR cedula = ?) AND id_usuario <> ?');
        $q->execute([$d['email'], $d['cedula'], $idExcluir]);
        if ($q->fetch()) $err[] = 'Ya existe un usuario con ese email o cédula.';
    }
    return $err;
}
function insertarUsuario($pdo, $d, $rol) {
    $pdo->prepare('INSERT INTO usuarios (nombre_completo, email, cedula, contrasena, rol, fecha_nacimiento, telefono) VALUES (?,?,?,?,?,?,?)')
        ->execute([trim($d['nombre_completo']), trim($d['email']), $d['cedula'], password_hash($d['contrasena'], PASSWORD_DEFAULT),
                   $rol, ($d['fecha_nacimiento'] ?? '') ?: null, trim($d['telefono'] ?? '')]);
    return $pdo->lastInsertId();
}
function top($titulo) {
    global $yo, $permitidos;
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
       . e($titulo) . ' · Panel</title><link rel="stylesheet" href="admin.css?v=' . filemtime(__DIR__ . '/admin.css') . '"></head><body><div class="app"><nav class="lado"><h2>Clínica Imagen</h2><small>'
       . e($yo['nombre_completo']) . ' · ' . e($yo['rol']) . '</small><a href="index.php">Inicio</a>';
    foreach (MODULOS as $k => $n) if (in_array($k, $permitidos)) echo '<a href="' . $k . '.php">' . e($n) . '</a>';
    echo '<a href="../perfil.php">Mi perfil</a><a href="../index.php" style="background:#38bdf8;color:#111827;text-align:center;font-weight:bold;margin-top:6px">🏠 Ir al inicio</a><a href="../logout.php">Cerrar sesión</a></nav><main><h1>' . e($titulo) . '</h1>';
}
function pie() { echo '</main></div></body></html>'; }
