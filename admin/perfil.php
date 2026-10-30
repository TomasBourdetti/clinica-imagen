<?php
require 'db.php';
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
$id = $_SESSION['id_usuario'];
$mensaje = '';

$st = $pdo->prepare('SELECT u.*, p.tipo_de_profesional FROM usuarios u
                     LEFT JOIN profesional p ON p.id_profesional = u.id_usuario WHERE u.id_usuario = ?');
$st->execute([$id]);
$u = $st->fetch();
if (!$u) { session_destroy(); header('Location: login.php'); exit; }

$esPaciente = $u['rol'] === 'paciente';
$esProf     = $u['rol'] === 'profesional';

// Menú lateral del panel para el personal (para no perderlo al abrir "Mi perfil")
$menu = [];
if ($u['rol'] !== 'paciente') {
    $mods = ['pacientes' => 'Pacientes', 'citas' => 'Agenda de citas', 'personal' => 'Personal',
             'sucursales' => 'Sucursales y servicios', 'resultados' => 'Resultados e imágenes', 'roles' => 'Roles y permisos'];
    if ($u['rol'] === 'admin') $perm = array_keys($mods);
    else { $q = $pdo->prepare('SELECT modulo FROM permiso WHERE rol = ?'); $q->execute([$u['rol']]); $perm = $q->fetchAll(PDO::FETCH_COLUMN); }
    foreach ($mods as $k => $n) if (in_array($k, $perm)) $menu[$k] = $n;
}

// ---------- Acciones: cancelar (paciente) / posponer (profesional) ----------
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $idc = (int)($_POST['id_cita'] ?? 0);
    $vivas = "estado IN ('pendiente','confirmada','pospuesta') AND TIMESTAMP(fecha, hora_inicio) > NOW()";

    if ($_POST['accion'] === 'cancelar' && $esPaciente) {
        $n = $pdo->prepare("UPDATE cita SET estado = 'cancelada' WHERE id_cita = ? AND id_paciente = ? AND $vivas");
        $n->execute([$idc, $id]);
        $mensaje = $n->rowCount() ? 'La cita fue cancelada.' : 'No se pudo cancelar esa cita.';

    } elseif ($_POST['accion'] === 'posponer' && $esProf) {
        $q = $pdo->prepare("SELECT * FROM cita WHERE id_cita = ? AND id_profesional = ? AND $vivas");
        $q->execute([$idc, $id]);
        $cita = $q->fetch();
        $ini = DateTime::createFromFormat('Y-m-d H:i', ($_POST['fecha'] ?? '') . ' ' . ($_POST['hora'] ?? ''));

        if (!$cita) {
            $error = 'No se pudo posponer esa cita.';
        } elseif (!$ini || $ini->format('Y-m-d') !== $_POST['fecha']) {
            $error = 'Fecha u hora no válidas.';
        } else {
            $min = (strtotime($cita['hora_fin']) - strtotime($cita['hora_inicio'])) / 60;   // mantiene la duración
            $fin = (clone $ini)->modify("+$min minutes");
            if ($ini < new DateTime()) {
                $error = 'La nueva fecha debe ser futura.';
            } elseif ($ini->format('H:i') < HORA_APERTURA || $fin->format('H:i') > HORA_CIERRE) {
                $error = 'El horario de atención es de ' . HORA_APERTURA . ' a ' . HORA_CIERRE . '.';
            } else {
                $d = $ini->format('Y-m-d'); $i = $ini->format('H:i:s'); $f = $fin->format('H:i:s');
                foreach (['id_profesional', 'id_paciente'] as $col) {
                    $o = $pdo->prepare("SELECT 1 FROM cita WHERE $col = ? AND id_cita <> ? AND fecha = ?
                                        AND estado <> 'cancelada' AND hora_inicio < ? AND hora_fin > ?");
                    $o->execute([$cita[$col], $idc, $d, $f, $i]);
                    if ($o->fetch()) {
                        $error = $col === 'id_profesional' ? 'Ya tienes otra cita en ese horario.' : 'El paciente ya tiene otra cita en ese horario.';
                        break;
                    }
                }
                if (!$error) {
                    $pdo->prepare("UPDATE cita SET fecha = ?, hora_inicio = ?, hora_fin = ?, estado = 'pospuesta' WHERE id_cita = ?")
                        ->execute([$d, $i, $f, $idc]);
                    $mensaje = 'La cita fue pospuesta.';
                }
            }
        }
    }
}

// ---------- Citas (una sola consulta; cambia según el rol) ----------
$citas = [];
if ($esPaciente || $esProf) {
    $yo   = $esPaciente ? 'c.id_paciente'    : 'c.id_profesional';  // mi columna
    $otro = $esPaciente ? 'c.id_profesional' : 'c.id_paciente';     // la otra persona
    $q = $pdo->prepare("SELECT c.id_cita, c.fecha, c.hora_inicio, c.hora_fin, c.estado,
                               e.nombre AS estudio, s.nombre AS sucursal,
                               uo.id_usuario AS id_otro, uo.nombre_completo AS persona, uo.cedula, uo.telefono,
                               hc.tratamiento, hc.nota
                        FROM cita c
                        JOIN estudio e   ON e.id_estudio  = c.id_estudio
                        JOIN sucursal s  ON s.id_sucursal = c.id_sucursal
                        JOIN usuarios uo ON uo.id_usuario = $otro
                        LEFT JOIN historial_clinico hc ON hc.id_hc = c.id_hc
                        WHERE $yo = ?
                        ORDER BY c.fecha, c.hora_inicio");
    $q->execute([$id]);
    $citas = $q->fetchAll();
}

// Clasifica: próximas (activas y futuras) / historial; agrupa pacientes del profesional
$ahora = new DateTime();
$proximas = $historial = $pacientes = [];
foreach ($citas as $c) {
    $dt = new DateTime($c['fecha'] . ' ' . $c['hora_inicio']);
    $activa = !in_array(strtolower($c['estado']), ['cancelada', 'realizada']);
    if ($activa && $dt >= $ahora) $proximas[] = $c; else $historial[] = $c;
    if ($esProf) {
        $k = $c['id_otro'];
        if (!isset($pacientes[$k])) $pacientes[$k] = ['nombre' => $c['persona'], 'cedula' => $c['cedula'], 'tel' => $c['telefono'], 'citas' => []];
        $pacientes[$k]['citas'][] = $c;
    }
}
$historial = array_reverse($historial);                       // más reciente primero
uasort($pacientes, fn($a, $b) => strcasecmp($a['nombre'], $b['nombre']));  // orden alfabético
$sig = $proximas[0] ?? null;
$realizadas = count(array_filter($citas, fn($c) => strtolower($c['estado']) === 'realizada'));

// ---------- Ayudas ----------
function fechaLarga($fecha) {
    $d = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $m = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $t = new DateTime($fecha);
    return $d[(int)$t->format('w')] . ' ' . $t->format('j') . ' de ' . $m[(int)$t->format('n') - 1];
}
function cuentaAtras($fecha) {
    $n = (int)(new DateTime('today'))->diff(new DateTime($fecha))->days;
    return $n === 0 ? 'Hoy' : ($n === 1 ? 'Mañana' : "En $n días");
}
function badge($estado) {
    return '<span class="estado e-' . e(preg_replace('/\W/', '', strtolower($estado))) . '">' . e($estado) . '</span>';
}
function botonAccion($accion, $c) {
    $id = (int)$c['id_cita'];
    if ($accion === 'cancelar') {
        return '<form method="post" onsubmit="return confirm(\'¿Seguro que quieres cancelar esta cita?\')">'
             . '<input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id_cita" value="' . $id . '">'
             . '<button class="chico rojo">Cancelar</button></form>';
    }
    return '<details class="accion"><summary>Posponer</summary><form method="post">'
         . '<input type="hidden" name="accion" value="posponer"><input type="hidden" name="id_cita" value="' . $id . '">'
         . '<input type="date" name="fecha" required min="' . date('Y-m-d') . '">'
         . '<input type="time" name="hora" required step="900" min="' . HORA_APERTURA . '" max="' . HORA_CIERRE . '">'
         . '<button class="chico">Guardar</button></form></details>';
}
function tablaCitas($filas, $colPersona = null, $registro = false, $accion = null) {
    if (!$filas) { echo '<p class="vacio">No hay citas.</p>'; return; }
    echo '<div class="scroll"><table><tr><th>Fecha</th><th>Hora</th>' . ($colPersona ? '<th>' . e($colPersona) . '</th>' : '')
       . '<th>Estudio</th><th>Sucursal</th><th>Estado</th>' . ($registro ? '<th>Registro clínico</th>' : '') . ($accion ? '<th></th>' : '') . '</tr>';
    foreach ($filas as $c) {
        echo '<tr><td>' . e(date('d/m/Y', strtotime($c['fecha']))) . '</td>'
           . '<td>' . e(substr($c['hora_inicio'], 0, 5)) . ' - ' . e(substr($c['hora_fin'], 0, 5)) . '</td>'
           . ($colPersona ? '<td>' . e($c['persona']) . '</td>' : '')
           . '<td>' . e($c['estudio']) . '</td><td>' . e($c['sucursal']) . '</td><td>' . badge($c['estado']) . '</td>';
        if ($registro) {
            echo '<td>' . (($c['tratamiento'] || $c['nota'])
                ? ($c['tratamiento'] ? '<b>Tratamiento:</b> ' . e($c['tratamiento']) . '<br>' : '') . ($c['nota'] ? '<b>Nota:</b> ' . e($c['nota']) : '')
                : '—') . '</td>';
        }
        if ($accion) echo '<td>' . botonAccion($accion, $c) . '</td>';
        echo '</tr>';
    }
    echo '</table></div>';
}
$colPersona = $esPaciente ? 'Profesional' : 'Paciente';
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi perfil</title><link rel="stylesheet" href="estilo.css"></head>
<body class="<?= $menu || $u['rol'] !== 'paciente' ? 'staff' : '' ?>">
<div class="con-menu">
<?php if ($u['rol'] !== 'paciente'): ?>
<nav class="lado-panel">
    <h2>Clínica Imagen</h2><small><?= e($u['nombre_completo']) ?> · <?= e($u['rol']) ?></small>
    <a href="admin/index.php">Inicio</a>
    <?php foreach ($menu as $k => $n) echo '<a href="admin/' . $k . '.php">' . e($n) . '</a>'; ?>
    <a href="perfil.php" class="activo">Mi perfil</a>
    <a href="logout.php">Cerrar sesión</a>
</nav>
<?php endif; ?>
<div class="pagina">

    <div class="caja">
        <h1><?= e($u['nombre_completo']) ?></h1>
        <span class="rol"><?= $esPaciente ? 'Paciente' : ($esProf ? 'Profesional' : e($u['rol'])) ?></span>
        <?php if ($esPaciente): ?><a class="btn" href="agendar.php">📅 Agendar cita</a><?php endif; ?>
        <p class="pie"><a class="btn" href="index.php">🏠 Volver al menú inicial</a> · <a href="logout.php">Cerrar sesión</a></p>
    </div>

    <?php if ($esPaciente || $esProf): ?>
    <div class="col">
        <?php if (($_GET['cita'] ?? '') === 'ok'): ?><div class="ok">✅ Cita agendada. Quedó en estado pendiente.</div><?php endif; ?>
        <?php if ($mensaje): ?><div class="ok"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <!-- Aviso de la próxima cita -->
        <div class="banner <?= $sig ? '' : 'sin' ?>">
        <?php if ($sig): ?>
            <h2>📅 <?= $esPaciente ? 'Tu próxima cita' : 'Próxima cita' ?> · <?= e(cuentaAtras($sig['fecha'])) ?></h2>
            <div class="grande"><?= e(fechaLarga($sig['fecha'])) ?> a las <?= e(substr($sig['hora_inicio'], 0, 5)) ?></div>
            <div><?= $esPaciente ? 'Con' : 'Paciente:' ?> <b><?= e($sig['persona']) ?></b> · <?= e($sig['estudio']) ?> · <?= e($sig['sucursal']) ?> · <?= badge($sig['estado']) ?></div>
        <?php else: ?>
            <h2>No tienes citas pendientes</h2>
            <?php if ($esPaciente): ?><a href="agendar.php">Agendar una cita</a><?php endif; ?>
        <?php endif; ?>
        </div>

        <!-- Resumen -->
        <div class="resumen">
            <div class="dato"><b><?= $esProf ? count($pacientes) : count($citas) ?></b><?= $esProf ? 'Pacientes' : 'Citas en total' ?></div>
            <div class="dato"><b><?= count($proximas) ?></b>Próximas</div>
            <div class="dato"><b><?= $realizadas ?></b>Realizadas</div>
        </div>

        <div class="caja ancha">
            <h1><?= $esPaciente ? 'Próximas citas' : 'Mi agenda' ?></h1>
            <?php tablaCitas($proximas, $colPersona, false, $esPaciente ? 'cancelar' : 'posponer'); ?>
        </div>

        <?php if ($esPaciente): ?>
        <div class="caja ancha">
            <h1>Registro de mis citas</h1>
            <?php tablaCitas($historial, 'Profesional', true); ?>
        </div>
        <?php else: ?>
        <div class="caja ancha">
            <h1>Registros por paciente</h1>
            <?php if (!$pacientes) echo '<p class="vacio">Aún no tienes pacientes asignados.</p>'; ?>
            <?php foreach ($pacientes as $p): ?>
            <details class="paciente">
                <summary><b><?= e($p['nombre']) ?></b> · <?= count($p['citas']) ?> cita(s)
                    <small>CI: <?= e($p['cedula']) ?> · Tel: <?= e($p['tel']) ?></small></summary>
                <?php tablaCitas(array_reverse($p['citas']), null, true); ?>
            </details>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div>
</div>
</body>
</html>
