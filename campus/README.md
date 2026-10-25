# Campus de Medicina del Alma

Aplicación privada para talleres y comunidades. La web pública continúa en Netlify; el campus se ejecuta como una aplicación PHP/MySQL independiente.

## Incluye

- Inicio de sesión y primer alta segura de administración.
- Creación y archivo de secciones para talleres, prácticas o comunidades.
- Cuentas de facilitación y participantes, asignadas a secciones.
- Agenda de encuentros por sección, con horario, lugar o enlace para conectarse.
- Muro de publicaciones y avisos con archivos adjuntos privados, buscador y paginación.
- Actividades con fecha límite, respuesta/archivo, revisión y devolución.
- Contraseñas temporales que se deben cambiar al primer ingreso.
- Gestión de las tarjetas públicas de talleres, terapias y objetos desde el panel.

## Requisitos

- PHP 8.1 o superior.
- Extensiones PDO MySQL, Fileinfo y mbstring.
- MySQL 8 o MariaDB 10.5 o superior.
- Hosting que ejecute PHP. Netlify sirve el sitio público estático, pero no ejecuta esta aplicación PHP.

## Crear la base de datos

En el panel del hosting crea una base MySQL y un usuario con permisos para crear y modificar tablas. Algunos proveedores permiten hacerlo con SQL; como ejemplo está `database/create_database.example.sql`. Cambia el nombre de base, usuario y contraseña antes de usarlo. En hosting compartido suele ser mejor crear base y usuario desde el panel.

Selecciona la base creada y ejecuta todo el archivo `database/schema.sql`. Ese archivo crea las tablas de usuarios, secciones, participantes, publicaciones, archivos, entregas, agenda, contenido de la home y fichas de páginas públicas. Después ejecuta, en este orden, `database/20261009_home_content.sql`, `database/20261023_workshop_pages.sql`, `database/20261024_fix_workshop_links.sql` y `database/20261025_clean_workshop_routes.sql`. Estas migraciones cargan las seis fichas, les asignan slugs legibles y actualizan los enlaces de las tarjetas. No se insertan usuarios ni contraseñas.

Si ya ejecutaste una versión anterior del esquema, ejecuta las migraciones necesarias que aún te falten: `database/20261009_campus_events.sql` para la agenda, `database/20261009_home_content.sql` para las tarjetas de la home, `database/20261023_workshop_pages.sql` para las páginas detalladas y `database/20261025_clean_workshop_routes.sql` para pasar de las carpetas antiguas a rutas como `/talleres/reiki-unitario/`. En instalaciones nuevas, `schema.sql` crea todas las tablas.

## Instalar la aplicación

1. Copia la carpeta `campus` al hosting PHP.
2. Configura el document root del subdominio, por ejemplo `campus.medicinadelalma.com.ar`, para que apunte a `campus/public`. Mantén `app/`, `database/`, `config.php` y `storage/` fuera del document root.
3. Copia `config.example.php` como `config.php` y escribe allí los datos de conexión y la URL pública actual. Si MySQL usa un puerto distinto del predeterminado, configura `db_port` (por ejemplo, `8889` en algunas instalaciones locales de MAMP). No subas `config.php` al repositorio.
4. Da permiso de escritura al proceso de PHP en `storage/uploads/`. Los archivos no se sirven como rutas públicas: se descargan pasando por una comprobación de acceso.
5. Abre el subdominio. La primera pantalla permite crear la cuenta administradora; después esa pantalla se desactiva automáticamente.
6. Desde el campus crea una sección. Dentro de ella puedes crear cuentas, asignarlas a la comunidad, programar encuentros y publicar avisos, material o actividades.
7. Desde **Contenido de la home** puedes agregar, editar, ocultar o quitar tarjetas. Al guardar una tarjeta de **Talleres y formaciones**, el campus crea automáticamente su ficha pública y genera la ruta `/talleres/slug/`; las tarjetas existentes también se vinculan al abrir esta sección. En **Páginas de talleres** puedes completar la presentación, modalidad, fechas, inversión, temas y programa, y volver a editar la tarjeta asociada.

### Abrirlo en localhost

Con `campus/config.php` configurado y la base lista, desde la carpeta raíz del repositorio ejecuta:

```sh
php -S 127.0.0.1:8001 -t campus/public
```

Deja ese servidor en ejecución y vuelve a la home en localhost. El enlace **Campus** detecta el entorno local y abre `http://127.0.0.1:8001/`. En la home publicada seguirá usando el subdominio de producción.

## Crear la base desde SQL (opcional)

Plantilla para un servidor donde tengas permisos de administración de MySQL. Reemplaza los valores de ejemplo y usa una contraseña exclusiva:

```sql
CREATE DATABASE medicina_alma_campus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'medicina_campus_user'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CONTRASENA_LARGA_Y_UNICA';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
  ON medicina_alma_campus.* TO 'medicina_campus_user'@'localhost';
FLUSH PRIVILEGES;
```

Luego conecta con esa base y ejecuta `database/schema.sql`.

## Notas

- El alta pública está desactivada. Administración crea cada cuenta y comparte una contraseña temporal por un canal seguro; al entrar, la persona debe cambiarla.
- La home carga las tarjetas desde MySQL mediante el endpoint público del campus. Las páginas `/talleres/{slug}/` comparten una sola plantilla y consultan `campus_public_pages`; el contenido publicado se actualiza desde el panel. Netlify usa `_redirects` y Apache usa `.htaccess` para resolver esas rutas. `public_site_url` en `config.php` debe coincidir con el dominio de la web para autorizar esas lecturas desde el navegador.
- Las publicaciones admiten hasta 10 adjuntos de 15 MB por archivo: imágenes, PDF, HTML, TXT/CSV, audio, video y documentos DOC/DOCX, XLS/XLSX, PPT/PPTX y RTF. Las imágenes abren una galería; PDF, HTML, texto, audio y video tienen vista previa emergente. Word, Excel y PowerPoint se descargan como archivos privados. El muro muestra primero lo más reciente.
- Cada sección tiene una agenda de próximos encuentros. Administración y facilitación pueden agregar, editar o quitar encuentros con horario, lugar y enlace de conexión. La hora usa la zona `America/Argentina/Buenos_Aires` por defecto; puede ajustarse con `timezone` en `config.php`.
- El buscador filtra por título y contenido. El muro muestra ocho publicaciones por página y conserva la búsqueda al cambiar de página.
- La home ya enlaza a `https://campus.medicinadelalma.com.ar/`. El enlace empezará a funcionar después de desplegar esta carpeta en un hosting PHP y apuntar ese subdominio al directorio `campus/public`. La web principal no puede ejecutar directamente el PHP desde Netlify.
