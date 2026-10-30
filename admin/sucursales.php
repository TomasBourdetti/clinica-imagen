<?php
require 'base.php'; exigir('sucursales');
$m = ''; $errs = [];
if (post()) {
    $a = $_POST['accion'] ?? ''; $ids = (int)($_POST['id'] ?? 0);
    if ($a === 'sucursal') {
        if (trim($_POST['nombre'] ?? '') === '') $errs[] = 'El nombre es obligatorio.';
        else {
            $d = [trim($_POST['nombre']), trim($_POST['departamento'] ?? ''), trim($_POST['localidad'] ?? ''), trim($_POST['calle'] ?? '')];
            if ($ids) { $pdo->prepare('UPDATE sucursal SET nombre=?, departamento=?, localidad=?, calle=? WHERE id_sucursal=?')->execute([...$d, $ids]); }
            else { $pdo->prepare('INSERT INTO sucursal (nombre, departamento, localidad, calle) VALUES (?,?,?,?)')->execute($d); $ids = $pdo->lastInsertId(); }
            header('Location: sucursales.php?ed=' . $ids); exit;
        }
    } elseif ($a === 'servicios') {
        $pdo->prepare('DELETE FROM sucursal_estudio WHERE id_sucursal=?')->execute([$ids]);
        foreach (($_POST['est'] ?? []) as $x) $pdo->prepare('INSERT IGNORE INTO sucursal_estudio (id_sucursal, id_estudio) VALUES (?,?)')->execute([$ids, (int)$x]);
        $m = 'Servicios actualizados.';
    } elseif ($a === 'eliminar') {
        try { $pdo->prepare('DELETE FROM sucursal WHERE id_sucursal=?')->execute([$ids]); $m = 'Sucursal eliminada.'; }
        catch (PDOException $x) { $errs[] = 'No se puede eliminar: tiene citas o equipamiento asociado.'; }
    } elseif ($a === 'estudio') {
        if (trim($_POST['nombre'] ?? '') === '') $errs[] = 'El estudio necesita un nombre.';
        else { $pdo->prepare('INSERT INTO estudio (nombre, tipo_de_estudio, duracion, preparacion) VALUES (?,?,?,?)')->execute([trim($_POST['nombre']), trim($_POST['tipo'] ?? ''), (int)($_POST['duracion'] ?? 30) ?: 30, trim($_POST['preparacion'] ?? '')]); $m = 'Estudio creado.'; }
    }
}
$ed = (int)($_GET['ed'] ?? 0); $nuevo = isset($_GET['nueva']);
top('Sucursales y servicios'); echo msg($m); foreach ($errs as $x) echo msg($x, 'error');
$estudios = $pdo->query('SELECT * FROM estudio ORDER BY nombre')->fetchAll();

if ($ed || $nuevo) {
    $s = ['nombre' => '', 'departamento' => '', 'localidad' => '', 'calle' => ''];
    if ($ed) { $q = $pdo->prepare('SELECT * FROM sucursal WHERE id_sucursal=?'); $q->execute([$ed]); $s = $q->fetch() ?: $s;
               $q = $pdo->prepare('SELECT id_estudio FROM sucursal_estudio WHERE id_sucursal=?'); $q->execute([$ed]); $mios = $q->fetchAll(PDO::FETCH_COLUMN); } ?>
    <p><a href="sucursales.php">← Volver</a></p>
    <div class="caja"><h2><?= $ed ? 'Datos de la sucursal' : 'Nueva sucursal' ?></h2>
    <form method="post"><?= csrf() ?><input type="hidden" name="accion" value="sucursal"><input type="hidden" name="id" value="<?= $ed ?>"><div class="fila">
        <label>Nombre<input name="nombre" required value="<?= e($s['nombre']) ?>"></label><label>Departamento<input name="departamento" value="<?= e($s['departamento']) ?>"></label>
        <label>Localidad<input name="localidad" value="<?= e($s['localidad']) ?>"></label><label>Calle y número<input name="calle" value="<?= e($s['calle']) ?>"></label><button>Guardar</button></div></form></div>
    <?php if ($ed): ?><div class="caja"><h2>Servicios que ofrece</h2>
    <form method="post"><?= csrf() ?><input type="hidden" name="accion" value="servicios"><input type="hidden" name="id" value="<?= $ed ?>"><div class="fila">
        <?php foreach ($estudios as $x) echo '<label><span><input type="checkbox" name="est[]" value="' . $x['id_estudio'] . '"' . (in_array($x['id_estudio'], $mios) ? ' checked' : '') . '> ' . e($x['nombre']) . '</span></label>';
        if (!$estudios) echo 'Primero crea estudios en el listado principal.'; ?></div><button>Guardar servicios</button></form></div><?php endif;
} else {
    $filas = $pdo->query('SELECT s.*, (SELECT COUNT(*) FROM sucursal_estudio se WHERE se.id_sucursal=s.id_sucursal) n FROM sucursal s ORDER BY s.nombre')->fetchAll(); ?>
    <p><a href="?nueva=1"><b>+ Nueva sucursal</b></a></p>
    <div class="scroll"><table><tr><th>Sucursal</th><th>Dirección</th><th>Servicios</th><th></th></tr>
    <?php foreach ($filas as $s): ?><tr><td><?= e($s['nombre']) ?></td><td><?= e($s['calle']) ?>, <?= e($s['localidad']) ?>, <?= e($s['departamento']) ?></td><td><?= (int)$s['n'] ?> estudios</td>
        <td><a href="?ed=<?= $s['id_sucursal'] ?>">Editar y servicios</a> <?= btn('eliminar', ['id' => $s['id_sucursal']], 'Eliminar', 'rojo', '¿Eliminar esta sucursal?') ?></td></tr><?php endforeach; ?></table></div>
    <h2>Catálogo de estudios</h2>
    <div class="caja"><form method="post" class="fila"><?= csrf() ?><input type="hidden" name="accion" value="estudio">
        <label>Nombre<input name="nombre" required></label><label>Tipo<input name="tipo"></label><label>Duración (min)<input type="number" name="duracion" value="30" min="5"></label>
        <label>Preparación<input name="preparacion"></label><button>Crear estudio</button></form>
    <?php foreach ($estudios as $x) echo '<span class="badge">' . e($x['nombre']) . ' · ' . (int)$x['duracion'] . ' min</span> '; ?></div>
<?php }
pie();
