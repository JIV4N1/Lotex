# Inmobiliaria Horizonte

Monolito Laravel 12 + Blade, Bootstrap 5 y Vite, con modelos inmobiliarios y panel Filament restringido a administradores. Incluye un prototipo público navegable: Inicio, catálogo filtrable, detalle con galería, Nosotros y Contacto demostrativo. Los ocho terrenos ficticios están centralizados en `resources/fixtures/properties.php` y se consultan mediante `App\Catalog\DemoCatalog`, sin Eloquent ni PostgreSQL. Las imágenes locales son ilustrativas; su procedencia está en `public/images/demo/SOURCES.md`. Contacto no envía ni guarda información. No hay CRUD ni carga de fotografías.

## Entorno Windows / PowerShell

- Carpeta: `C:\Lotex`. Repositorio: https://github.com/JIV4N1/Lotex.
- PHP 8.2.12 en `C:\xampp\php\php.exe`, Composer 2.8.12.
- Node 24.11.0, npm 11.11.1; Bootstrap 5.3.8, Vite 7.3.7.
- Laravel 12.69.3, Filament 5.10.1. Versiones fijadas por `composer.lock` y `package-lock.json`.
- PostgreSQL: herramientas 18.2 en `C:\Program Files\PostgreSQL\18\bin` (fuera de PATH).
- PHP necesita `pdo_pgsql`, `intl`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, DOM y XML. `intl` estaba deshabilitado, pese a lo indicado anteriormente en este README. Se habilitó en `C:\xampp\php\php.ini`, conservando `php.ini.lotex-before-intl.bak`. Reiniciar procesos PHP antiguos para que la carguen.

[Laravel 12](https://laravel.com/docs/12.x/releases) es compatible con PHP 8.2 y recibe correcciones de seguridad hasta el 24 de febrero de 2027. [Filament 5](https://filamentphp.com/docs/5.x/introduction/installation) requiere PHP 8.2+ y Laravel 11.28+. Composer confirmó compatibilidad sin ignorar requisitos; solo se permiten versiones estables.

## Instalar y abrir

```powershell
Set-Location C:\Lotex
composer install --no-interaction
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm ci
npm run build
```

Solo en una copia nueva con `APP_KEY` vacío, ejecutar `php artisan key:generate`. No regenerar una clave existente ni sobrescribir `.env`. Los secretos locales están excluidos de Git.

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Inicio: http://127.0.0.1:8000. Panel: **http://127.0.0.1:8000/admin**. Inicio y la pantalla de login se pueden abrir sin una base configurada; iniciar sesión sí requiere PostgreSQL y las migraciones.

Para editar recursos públicos, ejecutar `npm run dev` en otra terminal y seguir usando la URL del servidor PHP. Detener ambos procesos con Ctrl+C. Si Vite deja `public/hot` después de cerrarse abruptamente, eliminar solo ese archivo y volver a compilar. `composer dev` inicia únicamente PHP.

El panel usa el CSS/JS precompilado de Filament, publicado mediante `php artisan filament:assets` y actualizado por el script Composer `filament:upgrade`. No importa Bootstrap ni utiliza el layout público. La configuración de Vite permanece exclusivamente pública. No hace falta instalar Tailwind en el frontend público.

## PostgreSQL y migraciones

La comprobación de conexión de esta etapa falló: faltan **DB_DATABASE, DB_USERNAME y DB_PASSWORD**. Configurarlos directamente en `.env` con datos reales. Confirmar también host, puerto y SSL; no se crearon cuentas, bases ni contraseñas.

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_SSLMODE=prefer
```

Después de configurar una base destinada a este proyecto:

```powershell
Set-Location C:\Lotex
php artisan config:clear
# No ejecutar migraciones sobre desarrollo en esta revisión.
# admin:create requiere que la base de destino ya tenga el esquema.
```

En una base nueva, `migrate:status` puede informar que aún no existe la tabla de migraciones. Revisar el destino y la salida de `--pretend` antes de aplicar. `migrate` ejecuta las migraciones pendientes; no usar `migrate:fresh`, `migrate:refresh`, `db:wipe` ni reset sobre desarrollo. No se ejecutaron migraciones durante esta entrega por falta de conexión. Las migraciones estándar originales se conservaron.

`admin:create` pide nombre, correo y contraseña oculta con confirmación. Exige al menos 12 caracteres con letras y números, guarda un hash mediante el cast `hashed` de User y rechaza correos existentes (sin distinguir mayúsculas). No eleva usuarios existentes, no acepta contraseñas por argumentos ni permite ejecución no interactiva. Si la terminal no admite entrada oculta, no hace fallback a texto visible. No se creó ningún administrador real.

Todos los usuarios existentes y nuevos tienen `is_admin=false` por defecto. El campo está fuera de asignación masiva. `User::canAccessPanel()` exige `is_admin=true` y panel `admin`, incluso en local. Hay login y logout de Filament, interfaz en español, sin registro público ni recuperación por correo configurada. Utilizar `admin:create`, no `make:filament-user`, para crear administradores.

## Modelo inmobiliario

`State → Municipality → Zone → Property → PropertyImage`.

- Nombres: `name`; municipio único dentro del estado y zona única dentro del municipio. No se duplican estado ni municipio en Property: acceder por `$property->zone->municipality->state` y precargar con `with('zone.municipality.state')`.
- Property: `code` (clave), `slug`, `title`, `description`, `price_mxn`, `area_m2`, `frontage_m`, `depth_m`, `reference_address`, `latitude`, `longitude`, `services`, `access`, `topography`, `land_use`, `documentary_summary`, `payment_terms`.
- Precio `numeric(16,2)`, superficie `numeric(14,2)`, frente/fondo `numeric(12,2)`, coordenadas `numeric(10,7)`. Casts decimales devuelven cadenas, sin pérdida por float. Servicios es una lista JSONB, con cast array y valor inicial vacío; los detalles descriptivos pueden quedar nulos mientras se completa el borrador.
- PostgreSQL exige precio >= 0, superficie > 0, frente/fondo positivos cuando existen, coordenadas dentro de rango y posición de imagen >= 0. Claves foráneas restringen borrados con dependientes, incluso terrenos borrados lógicamente.
- Publicación: enum PHP `PublicationStatus` (`borrador` / `publicado`). Disponibilidad: `AvailabilityStatus` (`disponible` / `apartado` / `vendido`). Valores iniciales: borrador, disponible, no destacado. También se restringen los valores en la base.
- `price_per_m2` se calcula con Brick Math, redondeo HALF_UP a dos decimales; no existe columna calculada persistida. Devuelve null para modelos incompletos o superficie no positiva antes de guardarlos.
- Terrenos usan SoftDeletes. Clave y slug permanecen únicos incluso después del borrado lógico. No se reutilizan identificadores de terrenos eliminados.
- Imágenes: `path` es una ruta relativa al disco que se configure en una etapa futura, `alt_text` es obligatorio, `position` ordena ascendentemente. Portada única por convención: `coverImage`, menor posición y menor ID como desempate. `images` utiliza el mismo orden. El borrado lógico conserva imágenes; un borrado físico con imágenes está restringido y no elimina archivos automáticamente.
- Índices sobre ubicaciones, identificadores únicos, publicación/disponibilidad, destacados y orden de imágenes. No se añadieron recursos Filament ni rutas públicas de propiedades.

## Pruebas aisladas

```powershell
composer validate --strict
composer check-platform-reqs
npm run build
php artisan test
php artisan route:list --path=admin
```

Las pruebas HTTP sin base usan usuarios en memoria para comprobar redirección de invitado, 403 para usuario normal en local, entrada del administrador y logout. No equivalen a verificar el login con credenciales persistidas. El cálculo decimal y los valores iniciales se prueban sin base.

Las pruebas de persistencia usan exclusivamente `pgsql_testing`. Sin configuración se marcan como omitidas, nunca aprobadas. Para ejecutarlas, disponer de una **base PostgreSQL separada para pruebas** y un usuario limitado a ella. Añadir a `.env` (sin sustituir los datos de desarrollo):

```dotenv
TEST_DB_HOST=127.0.0.1
TEST_DB_PORT=5432
TEST_DB_DATABASE=
TEST_DB_USERNAME=
TEST_DB_PASSWORD=
TEST_DB_SSLMODE=prefer
```

El nombre real debe terminar en `_test` y ser distinto del nombre de desarrollo. No hay fallback de credenciales ni URL a desarrollo. Revisar manualmente el destino antes de preparar esa base nueva:

```powershell
php artisan config:clear
php artisan migrate --database=pgsql_testing --pretend
php artisan migrate --database=pgsql_testing
php artisan test
```

Los tests nunca migran, borran ni limpian tablas: abren transacciones y revierten únicamente sus propios datos. Verifican relaciones, FK, restricciones, unicidad, SoftDeletes, portada, comando de administrador y login persistido. Si se configura una base insegura o inaccesible, fallan en lugar de continuar sobre desarrollo. No ejecutar en paralelo con una misma base de pruebas.

`storage` y `bootstrap/cache` deben ser escribibles. En Apache, usar `C:\Lotex\public` como raíz pública. No se realizó despliegue.

## Resultado de validación de etapa 2A

- `composer validate --strict` y `composer check-platform-reqs`: correctos, incluido intl.
- `npm run build`: correcto; hashes de CSS/JS públicos iguales a la etapa 1.
- `php artisan test`: **7 aprobadas, 17 omitidas, 29 aserciones**. Las omitidas requieren PostgreSQL de pruebas; no se consideran aprobadas.
- Sintaxis PHP comprobada en modelos, enums, middleware, proveedor, comando, migraciones y pruebas.
- HTTP real: Inicio 200; `/admin` redirige al login, que responde 200 en español. CSS, JS y fuente de Filament responden 200. El HTML público no carga Filament y el panel no carga los recursos Bootstrap públicos.
- `admin:create --no-interaction` rechaza correctamente la ejecución (código 1 esperado), sin crear usuarios.
- Bloqueado: ejecución real de migraciones, relaciones/restricciones en PostgreSQL, login con credenciales persistidas y creación de administrador. Faltan credenciales de desarrollo y de una base PostgreSQL de pruebas separada.
- El servidor temporal de verificación se detuvo. Iniciarlo con `php artisan serve --host=127.0.0.1 --port=8000` para abrir Inicio o `/admin`.


## Revisión pendiente de 2A: configuración privada

PostgreSQL 18 está instalado y el servicio postgresql-x64-18 está activo; el puerto local 5432 acepta conexiones. Esto no confirma autenticación. No se instaló ni reconfiguró ningún servicio en esta revisión.

Se prepararon DB_DATABASE=lotex y TEST_DB_DATABASE=lotex_test en el .env local porque ambos estaban vacíos. Se conservaron los valores existentes y no se escribió ninguna contraseña. Estos nombres no implican que las bases ya existan: no es posible comprobarlo sin acceso autenticado.

Paso manual en pgAdmin:

1. Conectar al servidor local usando una cuenta PostgreSQL propia; introducir la contraseña únicamente en pgAdmin.
2. Expandir Databases y revisar si lotex y lotex_test existen. No borrarlas, reemplazarlas ni recrearlas si existen.
3. Solo para cada base ausente: Databases → Create → Database; introducir el nombre correspondiente y seleccionar un propietario real autorizado. Guardar. Para pruebas, preferir un rol limitado a lotex_test.
4. Abrir C:\Lotex\.env en un editor local y completar DB_USERNAME, DB_PASSWORD, TEST_DB_USERNAME y TEST_DB_PASSWORD con los accesos reales. No pegar secretos en el chat. Si la contraseña contiene espacios o #, usar comillas conforme al formato dotenv.
5. Avisar únicamente que la configuración está lista. Se comprobará el destino de pruebas antes de aplicar allí las migraciones pendientes.

Por instrucción expresa de esta revisión, no se ejecutan migrate, db ni operaciones equivalentes sobre desarrollo. La base lotex no se ha migrado. No se debe considerar listo el login en desarrollo hasta que su esquema se prepare mediante una acción posterior autorizada.

phpunit.xml fuerza DB_CONNECTION=pgsql_testing para todas las pruebas de aplicación. Tests/TestCase rechaza configuración cacheada, conexión predeterminada diferente, driver distinto de PostgreSQL, URL alternativa y un nombre de pruebas igual al de desarrollo (incluido DB_URL) o sin sufijo _test. Las pruebas de persistencia requieren además entorno testing y usan transacciones sin limpiar tablas.

Cuando la base de pruebas esté configurada y verificada, aplicar únicamente sus migraciones pendientes:

```powershell
cd C:\Lotex
php artisan config:clear
php artisan migrate --database=pgsql_testing --pretend
php artisan migrate --database=pgsql_testing
php artisan test
```

Para verificar personalmente el panel en la base de pruebas, después de sus migraciones, usar una terminal PowerShell propia:

```powershell
cd C:\Lotex
$env:DB_CONNECTION = 'pgsql_testing'
php artisan admin:create
php artisan serve --host=127.0.0.1 --port=8000
```

El comando pide la contraseña oculta y su confirmación. Ese administrador se guarda exclusivamente en lotex_test. Abrir http://127.0.0.1:8000/admin. Detener el servidor con Ctrl+C y ejecutar Remove-Item Env:\DB_CONNECTION para restaurar la selección normal en esa terminal. Las credenciales del administrador web son diferentes de las credenciales de PostgreSQL.

