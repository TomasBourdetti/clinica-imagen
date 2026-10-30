<?php
require 'base.php'; exigir('citas');
$m = ''; $err = '';
$soloMias = $yo['rol'] === 'profesional';          // un profesional solo gestiona sus citas
$mapa = ['confirmar' => 'confirmada', 'cancelar' => 'cancelada', 'realizar' => 'realizada'];

if (post()) {
    $a = $_POST['accion'] ?? ''; $idc = (int)($_POST['id_cita'] ?? 0);
    $q = $pdo->prepare('SELECT * FROM cita WHERE id_cita=?' . ($soloMias ? ' AND id_profesional=' . (int)$yo['id_usuario'] : ''));
    $q->execute([$idc]); $c = $q->fetch();
    if (!$c) $err = 'Cita no encontrada.';
    elseif (isset($mapa[$a])) {
        $pdo->prepare('UPDATE cita SET estado=? WHERE id_cita=?')->execute([$mapa[$a], $idc]); $m = 'Cita actualizada.';
    } elseif ($a === 'reagendar') {
        $f0 = $_POST['fecha'] ?? '';
        $ini = DateTime::createFromFormat('Y-m-d H:i', $f0 . ' ' . ($_POST['hora'] ?? ''));
        if (!$ini || $ini->format('Y-m-d') !== $f0) $err = 'Fecha u hora no válidas.';
        else {
            $min = (strtotime($c['hora_fin']) - strtotime($c['hora_inicio'])) / 60;
            $fin = (clone $ini)->modify("+$min minutes");
            if ($ini < new DateTime()) $err = 'La nueva fecha debe ser futura.';
            elseif ($ini->format('H:i') < HORA_APERTURA || $fin->format('H:i') > HORA_CIERRE) $err = 'Fuera del horario de atención.';
            else {
                $i = $ini->format('H:i:s'); $f = $fin->format('H:i:s');
                foreach (['id_profesional', 'id_paciente'] as $col) {
                    $o = $pdo->prepare("SELECT 1 FROM cita WHERE $col=? AND id_cita<>? AND fecha=? AND estado<>'cancelada' AND hora_inicio<? AND hora_fin>?");
                    $o->execute([$c[$col], $idc, $f0, $f, $i]);
                    if ($o->fetch()) { $err = 'Se superpone con otra cita del ' . ($col === 'id_profesional' ? 'profesional.' : 'paciente.'); break; }
                }
                if (!$err) {
                    $pdo->prepare("UPDATE cita SET fecha=?, hora_inicio=?, hora_fin=?, estado='pospuesta' WHERE id_cita=?")->execute([$f0, $i, $f, $idc]);
                    $m = 'Cita reagendada.';
                }
            }
        }
    }
}
$suc = (int)($_GET['sucursal'] ?? 0); $pro = (int)($_GET['profesional'] ?? 0);
$desde = $_GET['desde'] ?? date('Y-m-d'); $est = $_GET['estado'] ?? '';
$w = ['c.fecha >= ?']; $p = [$desde];
if ($suc) { $w[] = 'c.id_sucursal = ?'; $p[] = $suc; }
if ($pro && !$soloMias) { $w[] = 'c.id_profesional = ?'; $p[] = $pro; }
if ($soloMias) { $w[] = 'c.id_profesional = ?'; $p[] = $yo['id_usuario']; }
if ($est !== '') { $w[] = 'c.estado = ?'; $p[] = $est; }
$q = $pdo->prepare('SELECT c.id_cita, c.fecha, c.hora_inicio, c.estado, s.nombre sucursal, e.nombre estudio, up.nombre_completo paciente, upr.nombre_completo prof
    FROM cita c JOIN sucursal s ON s.id_sucursal=c.id_sucursal JOIN estudio e ON e.id_estudio=c.id_estudio
    JOIN usuarios up ON up.id_usuario=c.id_paciente JOIN usuarios upr ON upr.id_usuario=c.id_profesional
    WHERE ' . implode(' AND ', $w) . ' ORDER BY c.fecha, c.hora_inicio LIMIT 300');
$q->execute($p); $filas = $q->fetchAll();
$sucs = $pdo->query('SELECT id_sucursal, nombre FROM sucursal ORDER BY nombre')->fetchAll();
$pros = $pdo->query("SELECT p.id_profesional, u.nombre_completo FROM profesional p JOIN usuarios u ON u.id_usuario=p.id_profesional WHERE u.rol='profesional' ORDER BY u.nombre_completo")->fetchAll();

top('Agenda de citas'); echo msg($m), msg($err, 'error'); ?>
<div class="caja"><form class="fila">
    <label>Desde<input type="date" name="desde" value="<?= e($desde) ?>"></label>
    <label>Sucursal<select name="sucursal"><option value="0">Todas</option>
        <?php foreach ($sucs as $s) echo '<option value="' . $s['id_sucursal'] . '"' . ($suc == $s['id_sucursal'] ? ' selected' : '') . '>' . e($s['nombre']) . '</option>'; ?></select></label>
    <?php if (!$soloMias): ?><label>Profesional<select name="profesional"><option value="0">Todos</option>
        <?php foreach ($pros as $s) echo '<option value="' . $s['id_profesional'] . '"' . ($pro == $s['id_profesional'] ? ' selected' : '') . '>' . e($s['nombre_completo']) . '</option>'; ?></select></label><?php endif; ?>
    <label>Estado<select name="estado"><option value="">Todos</option>
        <?php foreach (['pendiente', 'confirmada', 'pospuesta', 'realizada', 'cancelada'] as $s) echo '<option' . ($est === $s ? ' selected' : '') . '>' . $s . '</option>'; ?></select></label>
    <button>Filtrar</button></form></div>
<div class="scroll"><table><tr><th>Fecha</th><th>Paciente</th><th>Profesional</th><th>Estudio</th><th>Sucursal</th><th>Estado</th><th>Acciones</th></tr>
<?php foreach ($filas as $c): $viva = !in_array($c['estado'], ['cancelada', 'realizada']); ?>
    <tr><td><?= e(date('d/m/Y', strtotime($c['fecha']))) ?> <?= e(substr($c['hora_inicio'], 0, 5)) ?></td><td><?= e($c['paciente']) ?></td><td><?= e($c['prof']) ?></td>
    <td><?= e($c['estudio']) ?></td><td><?= e($c['sucursal']) ?></td><td><?= bd($c['estado']) ?></td><td>
    <?php if ($viva): ?>
        <?= $c['estado'] !== 'confirmada' ? btn('confirmar', ['id_cita' => $c['id_cita']], 'Confirmar') : btn('realizar', ['id_cita' => $c['id_cita']], 'Realizada', 'gris') ?>
        <?= btn('cancelar', ['id_cita' => $c['id_cita']], 'Cancelar', 'rojo', '¿Cancelar esta cita?') ?>
        <details><summary>Reagendar</summary><form method="post" class="fila"><?= csrf() ?><input type="hidden" name="accion" value="reagendar">
            <input type="hidden" name="id_cita" value="<?= (int)$c['id_cita'] ?>"><input type="date" name="fecha" required min="<?= date('Y-m-d') ?>">
            <input type="time" name="hora" required step="900"><button>Guardar</button></form></details>
    <?php endif; ?></td></tr>
<?php endforeach; if (!$filas) echo '<tr><td colspan="7">No hay citas con esos filtros.</td></tr>'; ?></table></div>
<?php pie();
