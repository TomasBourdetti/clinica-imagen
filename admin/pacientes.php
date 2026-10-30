<?php
require 'base.php'; exigir('pacientes');
$m = ''; $errs = [];
$clinico = in_array($yo['rol'], ['admin', 'profesional']);   // quién puede escribir notas clínicas

if (post()) {
    $a = $_POST['accion'] ?? ''; $idp = (int)($_POST['id'] ?? 0);
    if ($a === 'alta') {                                              // ALTA
        $errs = validarUsuario($_POST, $pdo);
        if (!$errs) {
            try {
                $pdo->beginTransaction();
                $nid = insertarUsuario($pdo, $_POST, 'paciente');
                $pdo->prepare('INSERT INTO paciente (id_paciente) VALUES (?)')->execute([$nid]);
                $pdo->commit(); $m = 'Paciente creado.';
            } catch (Exception $x) { $pdo->rollBack(); $errs[] = 'No se pudo crear el paciente.'; }
        }
    } elseif ($a === 'editar') {                                      // MODIFICACIÓN
        $errs = validarUsuario($_POST, $pdo, $idp, false);
        if (!$errs) {
            $pdo->prepare("UPDATE usuarios SET nombre_completo=?, email=?, cedula=?, telefono=?, fecha_nacimiento=? WHERE id_usuario=? AND rol='paciente'")
                ->execute([trim($_POST['nombre_completo']), trim($_POST['email']), $_POST['cedula'], trim($_POST['telefono'] ?? ''), ($_POST['fecha_nacimiento'] ?? '') ?: null, $idp]);
            $m = 'Datos actualizados.';
        }
    } elseif ($a === 'estado') {                                      // BAJA / REACTIVAR (lógica)
        $pdo->prepare("UPDATE usuarios SET activo = 1 - activo WHERE id_usuario=? AND rol='paciente'")->execute([$idp]);
        $m = 'Estado actualizado.';
    } elseif ($a === 'nota' && $clinico) {                            // HISTORIAL CLÍNICO
        $idc = (int)($_POST['id_cita'] ?? 0);
        $q = $pdo->prepare('SELECT id_hc FROM cita WHERE id_cita=? AND id_paciente=?'); $q->execute([$idc, $idp]); $c = $q->fetch();
        if ($c && $c['id_hc']) {
            $pdo->prepare('UPDATE historial_clinico SET tratamiento=?, nota=? WHERE id_hc=?')->execute([$_POST['tratamiento'], $_POST['nota'], $c['id_hc']]);
        } elseif ($c) {
            $pdo->prepare('INSERT INTO historial_clinico (tratamiento, nota) VALUES (?,?)')->execute([$_POST['tratamiento'], $_POST['nota']]);
            $pdo->prepare('UPDATE cita SET id_hc=? WHERE id_cita=?')->execute([$pdo->lastInsertId(), $idc]);
        }
        $m = 'Historial guardado.';
    }
}
$ver = (int)($_GET['ver'] ?? 0); $ed = (int)($_GET['editar'] ?? 0);
top('Pacientes'); echo msg($m); foreach ($errs as $x) echo msg($x, 'error');

if ($ver || $ed) {
    $q = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario=? AND rol='paciente'"); $q->execute([$ver ?: $ed]); $pa = $q->fetch();
    echo '<p><a href="pacientes.php">← Volver al listado</a></p>';
    if (!$pa) echo '<p>Paciente no encontrado.</p>';
    elseif ($ed) { ?>
        <div class="caja"><h2>Modificar paciente</h2>
        <form method="post"><?= csrf() ?><input type="hidden" name="accion" value="editar"><input type="hidden" name="id" value="<?= $ed ?>">
        <div class="fila">
            <label>Nombre completo<input name="nombre_completo" required value="<?= e($pa['nombre_completo']) ?>"></label>
            <label>Email<input type="email" name="email" required value="<?= e($pa['email']) ?>"></label>
            <label>Cédula<input name="cedula" required maxlength="<?= LARGO_CEDULA ?>" value="<?= e($pa['cedula']) ?>"></label>
            <label>Teléfono<input name="telefono" maxlength="<?= LARGO_TELEFONO ?>" value="<?= e($pa['telefono']) ?>"></label>
            <label>Nacimiento<input type="date" name="fecha_nacimiento" value="<?= e($pa['fecha_nacimiento']) ?>"></label>
            <button>Guardar</button></div></form></div>
    <?php } else {
        echo '<div class="caja"><b>' . e($pa['nombre_completo']) . '</b> · CI ' . e($pa['cedula']) . ' · Tel ' . e($pa['telefono']) . ' · Nac. ' . e($pa['fecha_nacimiento'])
           . ' · ' . ($pa['activo'] ? 'Activo' : 'De baja') . (in_array('resultados', $permitidos) ? ' · <a href="resultados.php?paciente=' . $ver . '">Ver resultados e imágenes</a>' : '') . '</div>';
        $q = $pdo->prepare('SELECT c.id_cita, c.fecha, c.hora_inicio, c.estado, e.nombre estudio, s.nombre sucursal, up.nombre_completo prof, hc.tratamiento, hc.nota
                            FROM cita c JOIN estudio e ON e.id_estudio=c.id_estudio JOIN sucursal s ON s.id_sucursal=c.id_sucursal
                            JOIN usuarios up ON up.id_usuario=c.id_profesional LEFT JOIN historial_clinico hc ON hc.id_hc=c.id_hc
                            WHERE c.id_paciente=? ORDER BY c.fecha DESC, c.hora_inicio DESC');
        $q->execute([$ver]); $filas = $q->fetchAll();
        echo '<h2>Historial de citas</h2><div class="scroll"><table><tr><th>Fecha</th><th>Estudio</th><th>Profesional</th><th>Sucursal</th><th>Estado</th><th>Registro clínico</th></tr>';
        foreach ($filas as $c) {
            echo '<tr><td>' . e(date('d/m/Y', strtotime($c['fecha']))) . ' ' . e(substr($c['hora_inicio'], 0, 5)) . '</td><td>' . e($c['estudio']) . '</td><td>' . e($c['prof'])
               . '</td><td>' . e($c['sucursal']) . '</td><td>' . bd($c['estado']) . '</td><td>'
               . ($c['tratamiento'] || $c['nota'] ? '<b>Trat.:</b> ' . e($c['tratamiento']) . '<br><b>Nota:</b> ' . e($c['nota']) : '—');
            if ($clinico) { ?>
                <details><summary>Editar</summary><form method="post"><?= csrf() ?><input type="hidden" name="accion" value="nota">
                <input type="hidden" name="id" value="<?= $ver ?>"><input type="hidden" name="id_cita" value="<?= (int)$c['id_cita'] ?>">
                <textarea name="tratamiento" placeholder="Tratamiento"><?= e($c['tratamiento']) ?></textarea>
                <textarea name="nota" placeholder="Nota"><?= e($c['nota']) ?></textarea><button>Guardar</button></form></details>
            <?php }
            echo '</td></tr>';
        }
        if (!$filas) echo '<tr><td colspan="6">Sin citas.</td></tr>';
        echo '</table></div>';
    }
} else {
    $b = '%' . trim($_GET['q'] ?? '') . '%';
    $q = $pdo->prepare("SELECT u.id_usuario, u.nombre_completo, u.email, u.cedula, u.telefono, u.activo FROM usuarios u JOIN paciente p ON p.id_paciente=u.id_usuario
                        WHERE u.nombre_completo LIKE ? OR u.cedula LIKE ? ORDER BY u.nombre_completo LIMIT 200");
    $q->execute([$b, $b]); ?>
    <div class="caja"><form class="fila"><label>Buscar por nombre o cédula<input name="q" value="<?= e($_GET['q'] ?? '') ?>"></label><button>Buscar</button></form></div>
    <details class="caja"><summary><b>+ Alta de paciente</b></summary><br>
        <form method="post"><?= csrf() ?><input type="hidden" name="accion" value="alta"><div class="fila">
            <label>Nombre completo<input name="nombre_completo" required></label><label>Email<input type="email" name="email" required></label>
            <label>Cédula (<?= LARGO_CEDULA ?>)<input name="cedula" required maxlength="<?= LARGO_CEDULA ?>"></label>
            <label>Teléfono (<?= LARGO_TELEFONO ?>)<input name="telefono" maxlength="<?= LARGO_TELEFONO ?>"></label>
            <label>Nacimiento<input type="date" name="fecha_nacimiento"></label>
            <label>Contraseña temporal<input type="password" name="contrasena" required minlength="8"></label><button>Crear</button></div></form></details>
    <div class="scroll"><table><tr><th>Nombre</th><th>Cédula</th><th>Contacto</th><th>Estado</th><th>Acciones</th></tr>
    <?php foreach ($q->fetchAll() as $r): ?>
        <tr><td><?= e($r['nombre_completo']) ?></td><td><?= e($r['cedula']) ?></td><td><?= e($r['email']) ?><br><?= e($r['telefono']) ?></td>
        <td><?= $r['activo'] ? '<span class="badge">Activo</span>' : '<span class="badge e-cancelada">De baja</span>' ?></td>
        <td><a href="?ver=<?= $r['id_usuario'] ?>">Historial</a> · <a href="?editar=<?= $r['id_usuario'] ?>">Editar</a>
        <?= btn('estado', ['id' => $r['id_usuario']], $r['activo'] ? 'Dar de baja' : 'Reactivar', $r['activo'] ? 'rojo' : 'gris', '¿Confirmas el cambio?') ?></td></tr>
    <?php endforeach; ?></table></div>
<?php }
pie();
