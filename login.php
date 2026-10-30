<?php
require 'db.php';
$error = '';

if (isset($_SESSION['id_usuario'])) { header('Location: perfil.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['contrasena'] ?? '';

    $st = $pdo->prepare('SELECT id_usuario, nombre_completo, contrasena, rol FROM usuarios WHERE email = ? AND activo = 1');
    $st->execute([$email]);
    $u = $st->fetch();

    $ok = $u && password_verify($pass, $u['contrasena']);
    // Usuario cargado a mano en la base de datos con la contraseña en texto plano:
    // se acepta esta vez y se guarda cifrada de inmediato.
    if ($u && !$ok && !preg_match('/^\$(2y|argon2id?)\$/', $u['contrasena']) && hash_equals($u['contrasena'], $pass)) {
        $pdo->prepare('UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id_usuario']]);
        $ok = true;
    }
    if ($ok) {
        session_regenerate_id(true);               // evita robo de sesión
        $_SESSION['id_usuario'] = $u['id_usuario'];
        $_SESSION['rol']        = $u['rol'];
        header('Location: ' . ($u['rol'] === 'paciente' ? 'perfil.php' : 'admin/index.php'));   // el personal entra al panel
        exit;
    }
    $error = 'Email o contraseña incorrectos.';    // mismo mensaje para ambos casos
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Iniciar sesión</title><link rel="stylesheet" href="estilo.css"></head>
<body>
<div class="caja">
    <h1>Iniciar sesión</h1>
    <?php if (isset($_GET['registrado'])): ?><div class="ok">Cuenta creada. Ya puedes iniciar sesión.</div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Email<input type="email" name="email" required></label>
        <label>Contraseña<input type="password" name="contrasena" required></label>
        <button type="submit">Entrar</button>
    </form>
    <p class="pie">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
</div>
</body>
</html>
