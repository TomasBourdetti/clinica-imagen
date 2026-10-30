<?php
require 'base.php';
top('Inicio');
$n = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
if (in_array($yo['rol'], ['admin', 'recepcionista'])) {
    echo '<div class="cards">'
       . '<div><b>' . $n("SELECT COUNT(*) FROM cita WHERE fecha = CURDATE() AND estado <> 'cancelada'") . '</b>Citas hoy</div>'
       . '<div><b>' . $n("SELECT COUNT(*) FROM cita WHERE fecha >= CURDATE() AND estado = 'pendiente'") . '</b>Por confirmar</div>'
       . '<div><b>' . $n("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente' AND activo = 1") . '</b>Pacientes activos</div></div>';
}
echo '<h2>Accesos</h2><div class="cards">';
foreach (MODULOS as $k => $nom) if (in_array($k, $permitidos)) echo '<a href="' . $k . '.php"><b>›</b>' . e($nom) . '</a>';
echo '</div>';
pie();
