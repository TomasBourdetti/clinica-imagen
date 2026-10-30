<?php
require 'db.php';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $fecha  = $_POST['fecha_nacimiento'] ?? '';
    $tel    = trim($_POST['telefono'] ?? '');
    $pass   = $_POST['contrasena'] ?? '';
    $pass2  = $_POST['confirmar'] ?? '';

    if ($nombre === '' || $cedula === '')            $errores[] = 'Nombre y cédula son obligatorios.';
    if (!preg_match('/^\d{' . LARGO_CEDULA . '}$/', $cedula))
        $errores[] = 'La cédula debe tener exactamente ' . LARGO_CEDULA . ' números, sin puntos ni guiones.';
    if ($tel !== '' && !preg_match('/^\d{' . LARGO_TELEFONO . '}$/', $tel))
        $errores[] = 'El teléfono debe tener exactamente ' . LARGO_TELEFONO . ' números.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errores[] = 'El email no es válido.';
    if (strlen($pass) < 8)                           $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($pass !== $pass2)                            $errores[] = 'Las contraseñas no coinciden.';

    if (!$errores) {
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? OR cedula = ?');
        $st->execute([$email, $cedula]);
        if ($st->fetch()) $errores[] = 'Ya existe un usuario con ese email o cédula.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                'INSERT INTO usuarios (nombre_completo, email, cedula, contrasena, rol, fecha_nacimiento, telefono)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $nombre, $email, $cedula,
                password_hash($pass, PASSWORD_DEFAULT),   // contraseña cifrada
                'paciente',
                $fecha ?: null, $tel
            ]);

            $id = $pdo->lastInsertId();
            // Si tu tabla se llama "pacientes", cambia aquí el nombre
            $pdo->prepare('INSERT INTO paciente (id_paciente) VALUES (?)')->execute([$id]);

            $pdo->commit();
            header('Location: login.php?registrado=1');
            exit;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errores[] = 'No se pudo completar el registro.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Registro</title><link rel="stylesheet" href="estilo.css"></head>
<body>
<div class="caja">
    <h1>Crear cuenta</h1>
    <?php foreach ($errores as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <label>Nombre completo<input name="nombre_completo" required value="<?= e($_POST['nombre_completo'] ?? '') ?>"></label>
        <label>Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Cédula (<?= LARGO_CEDULA ?> números)<input name="cedula" required inputmode="numeric" pattern="\d{<?= LARGO_CEDULA ?>}" maxlength="<?= LARGO_CEDULA ?>" value="<?= e($_POST['cedula'] ?? '') ?>"></label>
        <label>Fecha de nacimiento<input type="date" name="fecha_nacimiento" value="<?= e($_POST['fecha_nacimiento'] ?? '') ?>"></label>
        <label>Teléfono (<?= LARGO_TELEFONO ?> números)<input name="telefono" inputmode="numeric" pattern="\d{<?= LARGO_TELEFONO ?>}" maxlength="<?= LARGO_TELEFONO ?>" value="<?= e($_POST['telefono'] ?? '') ?>"></label>
        <label>Contraseña<input type="password" name="contrasena" required></label>
        <label>Confirmar contraseña<input type="password" name="confirmar" required></label>
        <button type="submit">Registrarme</button>
    </form>
    <p class="pie">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</div>
</body>
</html>
