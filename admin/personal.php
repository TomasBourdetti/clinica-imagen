<?php
require 'base.php'; exigir('personal');
$m = ''; $errs = [];
$sucs = $pdo->query('SELECT id_sucursal, nombre FROM sucursal ORDER BY nombre')->fetchAll();
$dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

if (post()) {
    $a = $_POST['accion'] ?? ''; $idu = (int)($_POST['id'] ?? 0);
    $esp = trim($_POST['especialidad'] ?? '') ?: 'General';
    if ($a === 'alta') {
        $rol = in_array($_POST['rol'] ?? '', ROLES_STAFF) ? $_POST['rol'] : 'recepcionista';
        $errs = validarUsuario($_POST, $pdo);
        if (!$errs) {
            try {
                $pdo->beginTransaction();
                $nid = insertarUsuario($pdo, $_POST, $rol);
                if ($rol === 'profesional') $pdo->prepare('INSERT INTO profesional (id_profesional, tipo_de_profesional) VALUES (?,?)')->execute([$nid, $esp]);
                $pdo->commit(); header('Location: personal.php?editar=' . $nid); exit;
            } catch (Exception $x) { $pdo->rollBack(); $errs[] = 'No se pudo crear.'; }
        }
    } elseif ($a === 'guardar') {
        $rol = in_array($_POST['rol'] ?? '', ROLES_STAFF) ? $_POST['rol'] : null;
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($idu == $yo['id_usuario']) { $rol = $yo['rol']; $activo = 1; }       // no puedes quitarte tu propio acceso
        if (!$rol) $errs[] = 'Rol no válido.';
        elseif (!empty($_POST['clave']) && strlen($_POST['clave']) < 8) $errs[] = 'La nueva contraseña debe tener 8 caracteres o más.';
        else {
            $pdo->prepare("UPDATE usuarios SET rol=?, activo=? WHERE id_usuario=? AND rol<>'paciente'")->execute([$rol, $activo, $idu]);
            if ($rol === 'profesional') $pdo->prepare('INSERT INTO profesional (id_profesional, tipo_de_profesional) VALUES (?,?) ON DUPLICATE KEY UPDATE tipo_de_profesional=VALUES(tipo_de_profesional)')->execute([$idu, $esp]);
            if (!empty($_POST['clave'])) $pdo->prepare('UPDATE usuarios SET contrasena=? WHERE id_usuario=?')->execute([password_hash($_POST['clave'], PASSWORD_DEFAULT), $idu]);
            $pdo->prepare('DELETE FROM personal_sucursal WHERE id_usuario=?')->execute([$idu]);
            foreach (($_POST['suc'] ?? []) as $s) $pdo->prepare('INSERT IGNORE INTO personal_sucursal (id_usuario, id_sucursal) VALUES (?,?)')->execute([$idu, (int)$s]);
            $m = 'Cambios guardados.';
        }
    } elseif ($a === 'horario') {
        $d = (int)($_POST['dia'] ?? 0); $h1 = $_POST['desde'] ?? ''; $h2 = $_POST['hasta'] ?? '';
        if ($d < 1 || $d > 7 || $h1 === '' || $h2 <= $h1) $errs[] = 'Horario no válido (la hora final debe ser mayor).';
        else { $pdo->prepare('INSERT INTO horario_profesional (id_usuario, id_sucursal, dia, hora_inicio, hora_fin) VALUES (?,?,?,?,?)')->execute([$idu, (int)$_POST['sucursal'], $d, $h1, $h2]); $m = 'Horario agregado.'; }
    } elseif ($a === 'quitar_horario') {
        $pdo->prepare('DELETE FROM horario_profesional WHERE id_horario=? AND id_usuario=?')->execute([(int)$_POST['id_horario'], $idu]); $m = 'Horario eliminado.';
    }
}
$ed = (int)($_GET['editar'] ?? 0);
top('Personal y profesionales'); echo msg($m); foreach ($errs as $x) echo msg($x, 'error');

if ($ed) {
    $q = $pdo->prepare("SELECT u.*, p.tipo_de_profesional FROM usuarios u LEFT JOIN profesional p ON p.id_profesional=u.id_usuario WHERE u.id_usuario=? AND u.rol<>'paciente'");
    $q->execute([$ed]); $u = $q->fetch();
    echo '<p><a href="personal.php">← Volver al listado</a></p>';
    if (!$u) echo '<p>No encontrado.</p>';
    else {
        $q = $pdo->prepare('SELECT id_sucursal FROM personal_sucursal WHERE id_usuario=?'); $q->execute([$ed]); $mias = $q->fetchAll(PDO::FETCH_COLUMN);
        $q = $pdo->prepare('SELECT h.*, s.nombre sucursal FROM horario_profesional h JOIN sucursal s ON s.id_sucursal=h.id_sucursal WHERE h.id_usuario=? ORDER BY h.dia, h.hora_inicio');
        $q->execute([$ed]); $hor = $q->fetchAll(); ?>
        <div class="caja"><h2><?= e($u['nombre_completo']) ?></h2>
        <form method="post"><?= csrf() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= $ed ?>">
        <div class="fila">
            <label>Rol<select name="rol"><?php foreach (ROLES_STAFF as $r) echo '<option' . ($u['rol'] === $r ? ' selected' : '') . '>' . $r . '</option>'; ?></select></label>
            <label>Especialidad (solo profesionales)<input name="especialidad" value="<?= e($u['tipo_de_profesional']) ?>" placeholder="Ej: Odontología, Radiología"></label>
            <label>Nueva contraseña (opcional)<input type="password" name="clave" minlength="8"></label>
            <label><span><input type="checkbox" name="activo" <?= $u['activo'] ? 'checked' : '' ?>> Cuenta activa</span></label></div>
        <b>Sucursales asignadas</b>
        <div class="fila"><?php foreach ($sucs as $s) echo '<label><span><input type="checkbox" name="suc[]" value="' . $s['id_sucursal'] . '"' . (in_array($s['id_sucursal'], $mias) ? ' checked' : '') . '> ' . e($s['nombre']) . '</span></label>'; ?></div>
        <button>Guardar cambios</button></form></div>
        <div class="caja"><h2>Horarios</h2>
        <table><tr><th>Día</th><th>Horario</th><th>Sucursal</th><th></th></tr>
        <?php foreach ($hor as $h) echo '<tr><td>' . $dias[$h['dia']] . '</td><td>' . e(substr($h['hora_inicio'], 0, 5)) . ' - ' . e(substr($h['hora_fin'], 0, 5)) . '</td><td>' . e($h['sucursal']) . '</td><td>'
            . btn('quitar_horario', ['id' => $ed, 'id_horario' => $h['id_horario']], 'Quitar', 'rojo') . '</td></tr>';
        if (!$hor) echo '<tr><td colspan="4">Sin horarios cargados.</td></tr>'; ?></table><br>
        <form method="post" class="fila"><?= csrf() ?><input type="hidden" name="accion" value="horario"><input type="hidden" name="id" value="<?= $ed ?>">
            <label>Día<select name="dia"><?php foreach ($dias as $k => $n) echo '<option value="' . $k . '">' . $n . '</option>'; ?></select></label>
            <label>Desde<input type="time" name="desde" required></label><label>Hasta<input type="time" name="hasta" required></label>
            <label>Sucursal<select name="sucursal"><?php foreach ($sucs as $s) echo '<option value="' . $s['id_sucursal'] . '">' . e($s['nombre']) . '</option>'; ?></select></label>
            <button>Agregar</button></form></div>
    <?php }
} else {
    $filas = $pdo->query("SELECT u.id_usuario, u.nombre_completo, u.rol, u.activo, MAX(p.tipo_de_profesional) esp, GROUP_CONCAT(DISTINCT s.nombre SEPARATOR ', ') sucs
        FROM usuarios u LEFT JOIN profesional p ON p.id_profesional=u.id_usuario LEFT JOIN personal_sucursal ps ON ps.id_usuario=u.id_usuario
        LEFT JOIN sucursal s ON s.id_sucursal=ps.id_sucursal WHERE u.rol<>'paciente' GROUP BY u.id_usuario, u.nombre_completo, u.rol, u.activo ORDER BY u.nombre_completo")->fetchAll(); ?>
    <details class="caja"><summary><b>+ Nuevo integrante del personal</b></summary><br>
    <form method="post"><?= csrf() ?><input type="hidden" name="accion" value="alta"><div class="fila">
        <label>Nombre completo<input name="nombre_completo" required></label><label>Email<input type="email" name="email" required></label>
        <label>Cédula (<?= LARGO_CEDULA ?>)<input name="cedula" required maxlength="<?= LARGO_CEDULA ?>"></label>
        <label>Teléfono (<?= LARGO_TELEFONO ?>)<input name="telefono" maxlength="<?= LARGO_TELEFONO ?>"></label>
        <label>Rol<select name="rol"><?php foreach (ROLES_STAFF as $r) echo '<option>' . $r . '</option>'; ?></select></label>
        <label>Especialidad<input name="especialidad" placeholder="Si es profesional"></label>
        <label>Contraseña temporal<input type="password" name="contrasena" required minlength="8"></label><button>Crear</button></div></form></details>
    <div class="scroll"><table><tr><th>Nombre</th><th>Rol</th><th>Especialidad</th><th>Sucursales</th><th>Estado</th><th></th></tr>
    <?php foreach ($filas as $r): ?><tr><td><?= e($r['nombre_completo']) ?></td><td><?= e($r['rol']) ?></td><td><?= e($r['rol'] === 'profesional' ? $r['esp'] : '—') ?></td>
        <td><?= e($r['sucs'] ?: '—') ?></td><td><?= $r['activo'] ? 'Activo' : '<span class="badge e-cancelada">Inactivo</span>' ?></td>
        <td><a href="?editar=<?= $r['id_usuario'] ?>">Gestionar</a></td></tr><?php endforeach; ?></table></div>
<?php }
pie();
