<?php
// Conexión a MySQL (XAMPP: usuario root, sin contraseña por defecto)
session_start();

// Reglas de la clínica (se usan en varias páginas; cámbialas aquí)
const LARGO_CEDULA   = 8;      // números, sin puntos ni guiones
const LARGO_TELEFONO = 9;      // números (ajusta a tu país)
const HORA_APERTURA  = '08:00';
const HORA_CIERRE    = '18:00';

// Datos de conexión (ajusta aquí si algo cambia)
const DB_HOST    = 'localhost';   // igual que en conexion.php, que sí funciona
const DB_NAME    = 'clinica';
const DB_USER    = 'root';
const DB_PASS    = '';
const MODO_DEBUG = true;          // ponlo en false cuando publiques la página

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('Error de conexión BD: ' . $e->getMessage());
    die(MODO_DEBUG
        ? 'Error de conexión: ' . htmlspecialchars($e->getMessage())
        : 'No se pudo conectar a la base de datos.');
}

// Escapa texto antes de mostrarlo en HTML (evita XSS)
function e($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
