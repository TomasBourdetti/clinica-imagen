<?php
require 'base.php'; exigir('resultados');
$m = ''; $errs = [];
$idp = (int)($_GET['paciente'] ?? 0);

function subir($pdo, $idr) {   // guarda imágenes/PDF validando el tipo real del archivo
    $ok = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $dir = __DIR__ . '/../uploads/'; if (!is_dir($dir)) mkdir($dir, 0755, true);
    $f = $_FILES['archivos'] ?? null; $n = 0;
    if (!$f || !is_array($f['tmp_name'])) return 0;
    $fi = new finfo(FILEINFO_MIME_TYPE);
    foreach ($f['tmp_name'] as $i => $tmp) {
        if ($f['error'][$i] !== UPLOAD_ERR_OK || $f['size'][$i] > 10 * 1024 * 1024) continue;   // máx. 10 MB
        $ext = $ok[$fi->file($tmp)] ?? null; if (!$ext) continue;
        $nom = bin2hex(random_bytes(12)) . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $nom)) {
            $orig = substr(preg_replace('/[^\w. -]/u', '', $f['name'][$i]), 0, 100);
            $pdo->prepare('INSERT INTO imagen (nombre, url, id_resultado) VALUES (?,?,?)')->execute([$orig, 'uploads/' . $nom, $idr]); $n++;
        }
    }
    return $n;
}
function esDe($pdo, $idr, $idp) {
    $q = $pdo->prepare('SELECT 1 FROM resultados r JOIN cita c ON c.id_cita=r.id_cita WHERE r.id_resultado=? AND c.id_paciente=?'); $q->execute([$idr, $idp]); return (bool)$q->fetch();
}
if (post() && $idp) {
    $a = $_POST['accion'] ?? ''; $idr = (int)($_POST['id_resultado'] ?? 0);
    if ($a === 'nuevo') {
        $q = $pdo->prepare('SELECT id_cita, id_estudio FROM cita WHERE id_cita=? AND id_paciente=?'); $q->execute([(int)($_POST['id_cita'] ?? 0), $idp]); $c = $q->fetch();
        if (!$c) $errs[] = 'Elige una cita del paciente.';
        else {
            $pdo->prepare('INSERT INTO resultados (tipo, descripcion, id_estudio, id_cita) VALUES (?,?,?,?)')->execute([trim($_POST['tipo'] ?? ''), trim($_POST['descripcion'] ?? ''), $c['id_estudio'], $c['id_cita']]);
            $m = 'Resultado guardado. Archivos subidos: ' . subir($pdo, $pdo->lastInsertId());
        }
    } elseif ($a === 'subir' && esDe($pdo, $idr, $idp)) {
        $m = 'Archivos subidos: ' . subir($pdo, $idr);
    } elseif ($a === 'borrar_img') {
        $q = $pdo->prepare('SELECT i.id_imagen, i.url FROM imagen i JOIN resultados r ON r.id_resultado=i.id_resultado JOIN cita c ON c.id_cita=r.id_cita WHERE i.id_imagen=? AND c.id_paciente=?');
        $q->execute([(int)$_POST['id_imagen'], $idp]); if ($i = $q->fetch()) { @unlink(__DIR__ . '/../uploads/' . basename($i['url'])); $pdo->prepare('DELETE FROM imagen WHERE id_imagen=?')->execute([$i['id_imagen']]); $m = 'Archivo eliminado.'; }
    } elseif ($a === 'borrar' && esDe($pdo, $idr, $idp)) {
        $q = $pdo->prepare('SELECT url FROM imagen WHERE id_resultado=?'); $q->execute([$idr]);
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $u) @unlink(__DIR__ . '/../uploads/' . basename($u));
        $pdo->prepare('DELETE FROM imagen WHERE id_resultado=?')->execute([$idr]); $pdo->prepare('DELETE FROM resultados WHERE id_resultado=?')->execute([$idr]); $m = 'Resultado eliminado.';
    }
}
top('Resultados e imágenes'); echo msg($m); foreach ($errs as $x) echo msg($x, 'error');
$q = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario=? AND rol='paciente'"); $q->execute([$idp]); $pa = $q->fetch();

if (!$pa) {
    $b = '%' . trim($_GET['q'] ?? '') . '%';
    $q = $pdo->prepare("SELECT id_usuario, nombre_completo, cedula FROM usuarios WHERE rol='paciente' AND (nombre_completo LIKE ? OR cedula LIKE ?) ORDER BY nombre_completo LIMIT 50"); $q->execute([$b, $b]); ?>
    <div class="caja"><form class="fila"><label>Buscar paciente (nombre o cédula)<input name="q" value="<?= e($_GET['q'] ?? '') ?>"></label><button>Buscar</button></form></div>
    <div class="scroll"><table><?php foreach ($q->fetchAll() as $r) echo '<tr><td>' . e($r['nombre_completo']) . '</td><td>' . e($r['cedula']) . '</td><td><a href="?paciente=' . $r['id_usuario'] . '">Ver resultados</a></td></tr>'; ?></table></div>
<?php } else {
    $q = $pdo->prepare("SELECT id_cita, fecha, e.nombre estudio FROM cita c JOIN estudio e ON e.id_estudio=c.id_estudio WHERE c.id_paciente=? AND c.estado<>'cancelada' ORDER BY c.fecha DESC"); $q->execute([$idp]); $citas = $q->fetchAll();
    $q = $pdo->prepare('SELECT r.*, c.fecha, e.nombre estudio FROM resultados r JOIN cita c ON c.id_cita=r.id_cita JOIN estudio e ON e.id_estudio=r.id_estudio WHERE c.id_paciente=? ORDER BY c.fecha DESC'); $q->execute([$idp]); $res = $q->fetchAll();
    $img = [];
    if ($res) { $q = $pdo->query('SELECT * FROM imagen WHERE id_resultado IN (' . implode(',', array_map('intval', array_column($res, 'id_resultado'))) . ')'); foreach ($q as $i) $img[$i['id_resultado']][] = $i; } ?>
    <p><a href="resultados.php">← Otro paciente</a> · <b><?= e($pa['nombre_completo']) ?></b> (CI <?= e($pa['cedula']) ?>)</p>
    <details class="caja"><summary><b>+ Cargar resultado</b></summary><br>
    <form method="post" enctype="multipart/form-data" class="fila"><?= csrf() ?><input type="hidden" name="accion" value="nuevo">
        <label>Cita / estudio<select name="id_cita" required><?php foreach ($citas as $c) echo '<option value="' . $c['id_cita'] . '">' . e(date('d/m/Y', strtotime($c['fecha']))) . ' · ' . e($c['estudio']) . '</option>'; ?></select></label>
        <label>Tipo<input name="tipo" placeholder="Informe, imagen…"></label><label>Descripción<textarea name="descripcion"></textarea></label>
        <label>Archivos (JPG, PNG, WEBP, PDF · máx. 10 MB)<input type="file" name="archivos[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"></label><button>Guardar</button></form></details>
    <?php foreach ($res as $r): ?><div class="caja"><b><?= e($r['estudio']) ?></b> · <?= e(date('d/m/Y', strtotime($r['fecha']))) ?> · <?= e($r['tipo']) ?>
        <p><?= nl2br(e($r['descripcion'])) ?></p><div class="fotos">
        <?php foreach ($img[$r['id_resultado']] ?? [] as $i) {
            echo '<div>' . (str_ends_with($i['url'], '.pdf') ? '<a target="_blank" href="../' . e($i['url']) . '">📄 ' . e($i['nombre']) . '</a>' : '<a target="_blank" href="../' . e($i['url']) . '"><img src="../' . e($i['url']) . '" alt=""></a>')
               . '<br>' . btn('borrar_img', ['id_imagen' => $i['id_imagen']], 'Quitar', 'gris') . '</div>';
        } ?></div>
        <form method="post" enctype="multipart/form-data" class="fila"><?= csrf() ?><input type="hidden" name="accion" value="subir"><input type="hidden" name="id_resultado" value="<?= (int)$r['id_resultado'] ?>">
            <input type="file" name="archivos[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"><button>Subir más</button></form>
        <?= btn('borrar', ['id_resultado' => $r['id_resultado']], 'Eliminar resultado', 'rojo', '¿Eliminar este resultado y sus archivos?') ?></div>
    <?php endforeach; if (!$res) echo '<p>Este paciente no tiene resultados cargados.</p>'; ?>
<?php }
pie();
