<?php
require 'base.php'; exigir('roles');
$m = '';
$roles = ['recepcionista', 'profesional', 'tecnico'];     // el admin siempre tiene acceso total
if (post()) {
    foreach ($roles as $r) {
        $pdo->prepare('DELETE FROM permiso WHERE rol=?')->execute([$r]);
        foreach (($_POST['p'][$r] ?? []) as $mod) if (isset(MODULOS[$mod])) $pdo->prepare('INSERT INTO permiso (rol, modulo) VALUES (?,?)')->execute([$r, $mod]);
    }
    $m = 'Permisos guardados.';
}
$act = [];
foreach ($pdo->query('SELECT rol, modulo FROM permiso') as $f) $act[$f['rol']][$f['modulo']] = true;
top('Roles y permisos'); echo msg($m); ?>
<p>Marca qué secciones puede ver cada rol. El <b>admin</b> siempre tiene acceso a todo. Para cambiar el rol de una persona, ve a <a href="personal.php">Personal</a>.</p>
<form method="post"><?= csrf() ?><div class="scroll"><table><tr><th>Sección</th><?php foreach ($roles as $r) echo '<th>' . e($r) . '</th>'; ?></tr>
<?php foreach (MODULOS as $k => $n): ?><tr><td><?= e($n) ?></td>
    <?php foreach ($roles as $r) echo '<td><input type="checkbox" name="p[' . $r . '][]" value="' . $k . '"' . (!empty($act[$r][$k]) ? ' checked' : '') . '></td>'; ?></tr><?php endforeach; ?></table></div><br>
<button>Guardar permisos</button></form>
<?php pie();
