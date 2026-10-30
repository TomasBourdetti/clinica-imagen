<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: index.php');   // al cerrar sesión se vuelve a la pantalla de inicio
exit;
