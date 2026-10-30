<?php
require 'db.php';
$logueado = isset($_SESSION['id_usuario']);


function estiloFoto($nombre) {
    $base = pathinfo($nombre, PATHINFO_FILENAME);
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $x) {
        if (file_exists(__DIR__ . "/img/$base.$x")) return "url('img/$base.$x')";
    }
    return 'none';   
}


$servicios = [
    ['Radiografía Perical',          'rueba de imagen dental (intrabucal) que muestra en detalle la estructura completa de los dientes', 'radiografia.jpg'],
    ['Estudios Completos Para Ortodoncia',            'conjunto de pruebas diagnósticas fundamentales para evaluar los dientes,', 'estudios.jpg'],
    ['Guias Quirurgicas Para Implantes',           'es una plantilla personalizada generalmente de resina acrílica impresa en 3D que traslada una planificación digital exacta a la boca del paciente durante una cirugía dental', 'guia.jpg'],
    ['Impresion 3D', 'es una tecnología que transforma la odontología mediante la fabricación digital de piezas, moldes y dispositivos personalizados a partir de resinas biocompatibles y otros materiales avanzados', 'impresion3D.jpg'],
    ['Radiografías Panorámicas', ' es un examen de rayos X en dos dimensiones (2D) que muestra una vista completa de toda la boca, incluyendo los dientes, los maxilares superior e inferior, las articulaciones de la mandíbula y los tejidos cercanos, en una sola imagen', 'radiografiaimpr.jpg'],
    ['Tomografías Para Cirugía', 'es un estudio por imágenes en 3D que permite al cirujano ver con exactitud los huesos maxilares, las raíces, los nervios y los senos paranasales antes de una cirugía', 'cirugia.jpg'],
    ['Fotografías Intrabucales y Extrabucales', 'son registros clínicos esenciales en odontología para el diagnóstico, la planificación de tratamientos, el seguimiento y la comunicación con el paciente', 'fotografia.jpg'],
    ['Tomografías Para Implantes', ' son herramientas 3D esenciales para la planificación segura y precisa de los implantes dentales', 'tomografia.jpg'],
    ['Escáner de Modelos Dentales', 'es un dispositivo que digitaliza moldes físicos de la boca (generalmente de yeso, impresiones o troqueles) y los convierte en un archivo tridimensional en formato STL para su uso en diseño asistido por computadora', 'escaner.jpg'],
];

$pais = 'Uruguay';  


$sucursales = $pdo->query('SELECT nombre, calle, localidad, departamento FROM sucursal ORDER BY nombre')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Clinica Imagen</title>
    <link rel="stylesheet" href="portada.css?v=<?= filemtime(__DIR__ . '/portada.css') ?>">
</head>
<body>


<nav>
    <a class="logo" href="#inicio">Clinica Imagen</a>
    <div class="links">
        <a href="#servicios">Servicios</a>
        <a href="#nosotros">Nosotros</a>
        <a href="#sucursales">Sucursales</a>
        <a href="#contacto">Contacto</a>
        <?php if ($logueado): ?>
            <a class="boton" style="margin:0;padding:8px 16px" href="perfil.php">Mi perfil</a>
        <?php else: ?>
            <button onclick="mostrarCuenta()">Ingresar</button>
        <?php endif; ?>
    </div>
</nav>


<header class="hero" id="inicio" style="--hero:<?= estiloFoto('hero') ?>">
    <h1>Diagnóstico de imagenología dental <br>con tecnología de confianza</h1>
    <p>Comprometidos en brindarte lo mejor</p>
    <?php if ($logueado): ?>
        <div><a class="boton" href="perfil.php">Ir a mi perfil</a> <a class="boton sec" href="logout.php">Cerrar sesión</a></div>
    <?php else: ?>
        <div><button onclick="mostrarCuenta()">Iniciar Sesion / Registrarse</button></div>
    <?php endif; ?>
</header>

<?php if (!$logueado): ?>

<section id="cuenta" class="oculto">
    <h2 id="titulo">Iniciar sesión</h2>
    <button type="button" onclick="cambiarModo('login')">Iniciar sesión</button>
    <button type="button" onclick="cambiarModo('registro')">Registrarse</button>

    <form id="formLogin" action="login.php" method="post">
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit">Iniciar sesión</button>
    </form>

    <form id="formRegistro" class="oculto" action="registro.php" method="post">
        <input type="text" name="nombre_completo" placeholder="Nombre completo" required>
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="text" name="cedula" placeholder="Cédula (<?= LARGO_CEDULA ?> números)" required inputmode="numeric" pattern="\d{<?= LARGO_CEDULA ?>}" maxlength="<?= LARGO_CEDULA ?>">
        <label class="etiqueta">Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento" max="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d', strtotime('-120 years')) ?>">
        <input type="text" name="telefono" placeholder="Teléfono (<?= LARGO_TELEFONO ?> números)" inputmode="numeric" pattern="\d{<?= LARGO_TELEFONO ?>}" maxlength="<?= LARGO_TELEFONO ?>">
        <input type="password" name="contrasena" placeholder="Contraseña (mínimo 8)" required minlength="8">
        <input type="password" name="confirmar" placeholder="Confirmar contraseña" required minlength="8">
        <button type="submit">Registrarme</button>
    </form>
</section>
<?php endif; ?>


<section class="bloque" id="servicios">
    <h2>Nuestros servicios</h2>
    <p class="sub">Estudios realizados por profesionales especializados.</p>
    <div class="grid cuatro">
        <?php foreach ($servicios as [$nombre, $desc, $foto]): ?>
        <div class="tarjeta">
            <div class="foto" style="--img:<?= estiloFoto($foto) ?>"></div>
            <div class="texto"><h3><?= e($nombre) ?></h3><p><?= e($desc) ?></p></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>


<section class="bloque" id="nosotros">
    <div class="dos">
        <div>
            <h2 style="text-align:left">Sobre nosotros</h2>
            <p>Clínica Imagen inicia sus actividades en el año 2015, con el concepto principal de cuidar a nuestros pacientes,</p>
            <p> con los mejores profesionales acompañandote y logrando estudios de la más alta calidad con la mínima radiación posible.</p>
        </div>
        <div class="foto grande" style="--img:<?= estiloFoto('nosotros') ?>"></div>
    </div>
</section>


<section class="bloque">
    <h2>¿Por qué elegirnos?</h2>
    <p class="sub">Todo pensado para tu comodidad.</p>
    <div class="grid">
        <div class="tarjeta"><div class="texto"><div class="icono"></div><h3>Agenda en línea</h3><p>Reserva tu cita desde tu cuenta, sin llamadas ni esperas.</p></div></div>
        <div class="tarjeta"><div class="texto"><div class="icono"></div><h3>Resultados e imágenes</h3><p>Consulta tus estudios desde tu perfil cuando los necesites.</p></div></div>
        <div class="tarjeta"><div class="texto"><div class="icono"></div><h3>Equipamiento moderno</h3><p>[Detalla marcas o tecnología que uses.]</p></div></div>
    </div>
</section>


<section class="bloque" id="sucursales">
    <h2>Nuestras sucursales</h2>
    <p class="sub">Encuentra la más cercana.</p>
    <div class="grid anchas">
        <?php foreach ($sucursales as $s):
            $q = urlencode($s['calle'] . ', ' . $s['localidad'] . ', ' . $s['departamento'] . ', ' . $pais); ?>
        <div class="tarjeta">
           
            <iframe class="mapa" loading="lazy" title="Mapa de <?= e($s['nombre']) ?>"
                src="https://maps.google.com/maps?q=<?= $q ?>&amp;output=embed&amp;z=16"></iframe>
            <div class="texto">
                <h3>📍 <?= e($s['nombre']) ?></h3>
                <p><?= e($s['calle']) ?><br><?= e($s['localidad']) ?>, <?= e($s['departamento']) ?></p>
                <a class="boton sec" style="margin:12px 0 0;padding:8px 14px" target="_blank" rel="noopener"
                   href="https://www.google.com/maps/search/?api=1&amp;query=<?= $q ?>">Cómo llegar</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$sucursales): ?><p style="text-align:center;grid-column:1/-1">Próximamente.</p><?php endif; ?>
    </div>
</section>


<footer id="contacto">
    <p><strong>Clinica Imagen</strong></p>
    <p>🕒Horario de atencion 8:00 AM A 18:00 PM </p>
    <p>© <?= date('Y') ?> Clinica Imagen. Todos los derechos reservados.</p>
</footer>

<?php if (!$logueado): ?>
<script>
    function mostrarCuenta() {
        const cuenta = document.getElementById("cuenta");
        cuenta.classList.remove("oculto");
        cuenta.scrollIntoView({ behavior: "smooth" });
    }
    function cambiarModo(modo) {
        const registro = modo === "registro";
        document.getElementById("titulo").textContent = registro ? "Crear cuenta" : "Iniciar sesión";
        document.getElementById("formLogin").classList.toggle("oculto", registro);
        document.getElementById("formRegistro").classList.toggle("oculto", !registro);
    }
</script>
<?php endif; ?>
</body>
</html>
