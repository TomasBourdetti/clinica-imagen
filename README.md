# Clínica Imagen

Sistema web para una clínica de imagenología dental: sitio público, portal de pacientes y profesionales, y panel de administración interna con permisos por rol.

## Funciones

- **Sitio público:** servicios, información de la clínica, sucursales con mapa y contacto.
- **Pacientes:** registro, inicio de sesión, agenda de citas eligiendo profesional, cancelación e historial.
- **Profesionales:** agenda, posposición de citas y registros por paciente.
- **Panel interno:** pacientes, agenda, personal, sucursales y servicios, resultados e imágenes, roles y permisos.

## Tecnologías

PHP 8 (PDO), MySQL/MariaDB, HTML5, CSS3 y JavaScript. Se ejecuta en local con XAMPP.

## Instalación

1. Instalar [XAMPP](https://www.apachefriends.org) e iniciar **Apache** y **MySQL**.
2. Copiar este proyecto a la carpeta `htdocs` de XAMPP (por ejemplo `htdocs/prueba`).
3. En phpMyAdmin (`http://localhost/phpmyadmin`), importar `database/base_de_datos_clinica_completa.sql`.
4. Abrir `http://localhost/prueba/` y registrar una cuenta.
5. Convertir la cuenta en administradora desde la pestaña SQL de phpMyAdmin:
   ```sql
   UPDATE usuarios SET rol='admin' WHERE email='tu@email.com';
   ```
6. Cerrar sesión y volver a entrar: se accede al panel en `http://localhost/prueba/admin/`.

## Estructura

```
index.php            portada pública
login.php  registro.php  logout.php
perfil.php           perfil de paciente o profesional
agendar.php          reserva de citas (pacientes)
db.php               conexión y reglas de la clínica
admin/               panel de administración interna
img/                 fotos de la portada
uploads/             archivos de resultados (no se sube a GitHub)
database/            scripts SQL
```

## Seguridad

Contraseñas cifradas, consultas preparadas, escape de salida, protección CSRF en el panel, control de permisos en el servidor, validación de archivos subidos y reglas dentro de la base de datos.

> `db.php` usa el usuario `root` sin contraseña, que es lo habitual en XAMPP. **No debe usarse así en un servidor real.**

## Autor

[Tu nombre] · 3° BT Informática, UTU
