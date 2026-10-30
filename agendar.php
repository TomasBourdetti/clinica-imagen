<?php
require 'db.php';
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
$id = $_SESSION['id_usuario'];

// Exclusivo para pacientes
$st = $pdo->prepare('SELECT nombre_completo, rol FROM usuarios WHERE id_usuario = ?');
$st->execute([$id]);
$u = $st->fetch();
if (!$u || $u['rol'] !== 'paciente') { header('Location: perfil.php'); exit; }

// Horario de atención (se cambia en db.php)
$apertura = HORA_APERTURA;
$cierre   = HORA_CIERRE;

$profesionales = $pdo->query("SELECT p.id_profesional, u.nombre_completo, p.tipo_de_profesional
                              FROM profesional p JOIN usuarios u ON u.id_usuario = p.id_profesional
                              WHERE u.rol = 'profesional' AND u.activo = 1
                              ORDER BY u.nombre_completo" )->fetchAll();
$estudios   = $pdo->query('SELECT id_estudio, nombre, duracion FROM estudio ORDER BY nombre')->fetchAll();
$sucursales = $pdo->query('SELECT id_sucursal, nombre FROM sucursal ORDER BY nombre')->fetchAll();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prof  = $_POST['id_profesional'] ?? '';
    $est   = $_POST['id_estudio'] ?? '';
    $suc   = $_POST['id_sucursal'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora  = $_POST['hora'] ?? '';

    // Solo se aceptan valores que existan en la base de datos
    if (!in_array($prof, array_column($profesionales, 'id_profesional'))) $errores[] = 'Elige un profesional.';
    if (!in_array($suc,  array_column($sucursales, 'id_sucursal')))       $errores[] = 'Elige una sucursal.';
    $duracion = 30;
    $estOk = false;
    foreach ($estudios as $e_) if ($e_['id_estudio'] == $est) { $estOk = true; $duracion = (int)($e_['duracion'] ?: 30); }
    if (!$estOk) $errores[] = 'Elige un estudio.';

    $ini = DateTime::createFromFormat('Y-m-d H:i', "$fecha $hora");
    if (!$ini || $ini->format('Y-m-d') !== $fecha) {
        $errores[] = 'Fecha u hora no válidas.';
    } else {
        $fin = (clone $ini)->modify("+$duracion minutes");
        if ($ini < new DateTime())                                  $errores[] = 'La cita debe ser en el futuro.';
        if ($hora < $apertura || $fin->format('H:i') > $cierre)     $errores[] = "El horario de atención es de $apertura a $cierre.";
    }

    if (!$errores) {
        $i = $ini->format('H:i:s'); $f = $fin->format('H:i:s');
        // ¿El profesional o el paciente ya tienen una cita que se superpone?
        foreach (['id_profesional' => $prof, 'id_paciente' => $id] as $col => $val) {
            $q = $pdo->prepare("SELECT 1 FROM cita WHERE $col = ? AND fecha = ? AND estado <> 'cancelada'
                                AND hora_inicio < ? AND hora_fin > ?");
            $q->execute([$val, $fecha, $f, $i]);
            if ($q->fetch()) $errores[] = $col === 'id_profesional'
                ? 'Ese profesional ya tiene una cita en ese horario. Prueba con otra hora.'
                : 'Ya tienes una cita en ese horario.';
        }
    }

    if (!$errores) {
        $pdo->prepare("INSERT INTO cita (fecha, hora_inicio, hora_fin, estado, id_paciente, id_profesional, id_sucursal, id_estudio)
                       VALUES (?, ?, ?, 'pendiente', ?, ?, ?, ?)")
            ->execute([$fecha, $i, $f, $id, $prof, $suc, $est]);
        header('Location: perfil.php?cita=ok');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agendar cita</title><link rel="stylesheet" href="estilo.css"></head>
<body>
<div class="caja media">
    <h1>Agendar una cita</h1>
    <?php foreach ($errores as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>

    <?php if (!$profesionales || !$estudios || !$sucursales): ?>
        <p class="vacio">Todavía no hay profesionales, estudios o sucursales cargados. Vuelve más tarde.</p>
    <?php else: ?>
    <form method="post">
        <label>1. Elige tu profesional</label>
        <?php foreach ($profesionales as $p): ?>
        <label class="opcion">
            <input type="radio" name="id_profesional" value="<?= e($p['id_profesional']) ?>" required
                   <?= ($_POST['id_profesional'] ?? '') == $p['id_profesional'] ? 'checked' : '' ?>>
            <span><b><?= e($p['nombre_completo']) ?></b><small><?= e($p['tipo_de_profesional']) ?></small></span>
        </label>
        <?php endforeach; ?>

        <label>2. Estudio
            <select name="id_estudio" required>
                <option value="">Selecciona…</option>
                <?php foreach ($estudios as $s): ?>
                <option value="<?= e($s['id_estudio']) ?>" <?= ($_POST['id_estudio'] ?? '') == $s['id_estudio'] ? 'selected' : '' ?>>
                    <?= e($s['nombre']) ?><?= $s['duracion'] ? ' (' . e($s['duracion']) . ' min)' : '' ?></option>
                <?php endforeach; ?>
            </select></label>

        <label>3. Sucursal
            <select name="id_sucursal" required>
                <option value="">Selecciona…</option>
                <?php foreach ($sucursales as $s): ?>
                <option value="<?= e($s['id_sucursal']) ?>" <?= ($_POST['id_sucursal'] ?? '') == $s['id_sucursal'] ? 'selected' : '' ?>>
                    <?= e($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select></label>

        <label>4. Fecha
            <input type="date" name="fecha" required min="<?= date('Y-m-d') ?>" value="<?= e($_POST['fecha'] ?? '') ?>"></label>
        <label>5. Hora (<?= e($apertura) ?> a <?= e($cierre) ?>)
            <input type="time" name="hora" required step="900" min="<?= e($apertura) ?>" max="<?= e($cierre) ?>" value="<?= e($_POST['hora'] ?? '') ?>"></label>

        <button type="submit">Confirmar cita</button>
    </form>
    <?php endif; ?>
    <p class="pie"><a href="perfil.php">← Volver a mi perfil</a></p>
</div>
</body>
</html>
