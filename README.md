# Horizonte

Aplicación Laravel 12 + Blade, Bootstrap 5 y Vite. Filament permite administrar terrenos, ubicaciones, fotografías y documentos; el catálogo público consulta los terrenos publicados en PostgreSQL. Las fichas ficticias anteriores se conservan solo como referencia en resources/fixtures/properties.php. La fotografía del hero es ilustrativa y su procedencia está en public/images/demo/SOURCES.md. Contacto guarda consultas y el chatbot está integrado con Gemini.

## Uso actual: administrar el catálogo

1. Entra en http://127.0.0.1:8000/admin con tu administrador.
2. Crea primero Estado, Municipio y Zona en las secciones correspondientes. No se pueden eliminar ubicaciones con dependientes, incluso terrenos en la papelera.
3. En Terrenos → Crear, captura título, clave y slug únicos, descripción, precio MXN, superficie, medidas opcionales, zona, referencia, coordenadas, servicios, acceso, topografía, uso de suelo, resumen documental y condiciones de pago.
4. Añade fotografías JPG/PNG/WebP de hasta 5 MB, con texto alternativo. Reordénalas para elegir la portada: se usa la primera. Máximo 30 fotografías por terreno.
5. Añade documentos o planos PDF de hasta 10 MB (máximo 20). Puedes indicar tipo, emisor y fecha. Son privados por defecto. Marca «Autorizar consulta pública» solo para una versión revisada y sin información reservada.
6. Guarda como Borrador para trabajar en privado o selecciona Publicado para mostrarlo en el catálogo. Disponibilidad (disponible/apartado/vendido) y Destacado son independientes.
7. Edita desde la tabla. Eliminar mueve el terreno a la papelera y retira su ficha y archivos de las rutas públicas. Filtra por eliminados y usa Restaurar para recuperarlo. No existe una acción de eliminación definitiva.

El sitio consulta App\Catalog\PropertyCatalog y ya no usa fixtures. Un catálogo vacío es normal hasta publicar el primer terreno. Solo los destacados publicados aparecen en la selección de Inicio. La maqueta /prototipo/administracion se conserva como referencia local, pero no guarda datos: usa /admin para trabajar.

### Archivos y permisos
Las fotografías y documentos se guardan en storage/app/private, con nombres aleatorios. Se sirven mediante rutas que comprueban publicación, borrado lógico y autorización pública del documento. No necesitas storage:link para estos archivos. El panel usa URLs temporales para previsualizar archivos privados. No compartas esas URLs.

Quitar o sustituir un archivo retira su referencia del terreno; los archivos antiguos permanecen en almacenamiento para evitar borrar archivos tras una transacción fallida. La limpieza física de archivos huérfanos no está automatizada. Respalda tanto PostgreSQL como storage/app/private. En el servidor, los límites de PHP/proxy deben permitir los tamaños indicados; no se modificaron servicios del sistema.

Se mantienen los estilos propios de Filament, independientes de Bootstrap. Contacto guarda consultas en la bandeja de Mensajes y el chatbot consulta el catálogo mediante Gemini.

### Migración de esta etapa
La migración 2026_10_11_000001_create_property_documents_table añade documentos sin modificar datos existentes. Se aplicó en lotex y lotex_test. Para otra instalación, tras revisar la conexión:

    php artisan migrate --database=pgsql
    php artisan migrate --database=pgsql_testing
    php artisan test
    npm run build

Las pruebas se ejecutan exclusivamente sobre la base separada terminada en _test, usando transacciones. Nunca uses migrate:fresh ni db:wipe en desarrollo.

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


## Ampliación visual: ficha y captura administrativa
La ficha pública incluye un esquema ilustrativo sin escala, ubicación exacta pendiente y documentación pendiente de revisión. No hay documentos oficiales ni coordenadas inventadas.

En entorno local, abre http://127.0.0.1:8000/prototipo/administracion para probar el editor demostrativo. Permite capturar características, condiciones, estados separados, metadatos documentales y seleccionar archivos locales. Generar vista previa muestra los datos y la primera imagen como portada. No se transmite ni guarda información; recargar descarta la captura. No uses documentos personales reales.

Esta maqueta no es el panel protegido: /admin conserva la autorización de Filament y sus estilos separados. La maqueta devuelve 404 fuera del entorno local. El guardado, la carga real, la revisión documental, la conexión a PostgreSQL y el chatbot siguen pendientes.

## Chatbot visual (etapa anterior)
La maqueta de respuestas predefinidas fue sustituida por la integración Gemini descrita al final de este documento.

## Conexión local habilitada
Se crearon lotex y lotex_test sin reemplazar bases existentes. La conexión de desarrollo a lotex quedó verificada y sus cinco migraciones se aplicaron correctamente. Esta acción posterior sustituye la restricción histórica de no migrar desarrollo indicada arriba.

La conexión pgsql_testing a lotex_test también quedó verificada y sus cinco migraciones fueron aplicadas. Las pruebas usan exclusivamente esa base y revierten sus datos mediante transacciones; no reutilizan la conexión de desarrollo.

Para crear tu administrador, introduce las credenciales web de forma privada en tu propia terminal:
    Set-Location C:\Lotex
    php artisan admin:create

Abre http://127.0.0.1:8000/admin después de iniciar php artisan serve si no está activo. No se ha creado ningún administrador automáticamente. El catálogo público y el editor visual siguen siendo demostrativos; conectar PostgreSQL no los convierte automáticamente en un CRUD.

Validación de la conexión completada: 31 pruebas aprobadas, 0 omitidas, 316 aserciones. Inicio y login respondieron HTTP 200. Ambas bases tienen cinco migraciones aplicadas y cero usuarios tras finalizar las pruebas; no quedó ningún administrador de prueba persistido.
Validación del CRUD: 33 pruebas aprobadas, 0 omitidas (376 aserciones), compilación Vite correcta y formato PHP aplicado. Se comprobaron en navegador Inicio, catálogo vacío real y redirección al login; el CRUD autenticado se validó mediante pruebas Livewire sobre lotex_test. No se crearon terrenos de demostración en desarrollo.


## Asistente Gemini: configuración y funcionamiento actual

El chat público usa Laravel y la API REST de Gemini. No requiere paquetes nuevos ni migraciones. Google AI Plus y el almacenamiento de Google One no determinan la cuota de este proyecto de API: verifica el plan y la facturación en Google AI Studio. No se activa facturación desde la aplicación.

1. En https://aistudio.google.com/api-keys crea una clave en un proyecto propio. Para empezar gratis, comprueba el nivel gratuito de ese proyecto. No compartas la clave ni la subas a Git.
2. Completa localmente `C:\Lotex\.env`:

```dotenv
GEMINI_API_KEY=tu_clave_privada
GEMINI_MODEL=gemini-3.5-flash-lite
CHAT_DAILY_LIMIT=100
```

El modelo predeterminado figura en la documentación oficial como estable y con nivel gratuito a la fecha de esta integración. Su disponibilidad y cuota dependen de Google y del proyecto; el nombre se puede cambiar sin editar código. No utilices un prefijo VITE_ para la clave.

3. En PowerShell:

```powershell
Set-Location C:\Lotex
php artisan config:clear
php artisan gemini:check
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

El comando `gemini:check` realiza una llamada real con una pregunta genérica y comprueba el presupuesto interpretado, sin consultar terrenos ni mostrar credenciales. Consume una solicitud de la cuota de Google; es independiente del límite diario del chat web.

Abre http://127.0.0.1:8000 y pulsa «¿Te orientamos?». Acepta el aviso de envío a Gemini y prueba «Terrenos en Tehuacán de hasta un millón de pesos» y «¿Y con al menos 500 metros cuadrados?». Desde una ficha puedes preguntar por sus servicios, precio y medidas.

### Alcance y protección de datos

- Gemini recibe la pregunta y hasta seis preguntas anteriores. No recibe filas de PostgreSQL, documentos, archivos, contraseñas ni datos de administradores. La API interpreta intención y filtros; Laravel consulta únicamente terrenos publicados y no eliminados y compone la respuesta con datos reales. Las respuestas no son prosa libre generada por IA.
- Búsqueda por nombre exacto (sin distinguir mayúsculas) de zona, municipio o estado, rango de precio/superficie y disponibilidad. Muestra como máximo cuatro coincidencias por menor precio. Una clave, slug o título exacto permite consultar una propiedad; la ficha abierta proporciona contexto para preguntas de detalle. No resuelve referencias ordinales como «el segundo» ni búsquedas semánticas por características arbitrarias.
- El texto se renderiza como texto, nunca HTML del modelo. Los enlaces se generan en Laravel y se limitan a fichas internas. Consultas parametrizadas; el modelo no ejecuta SQL, no edita registros ni recibe acceso administrativo.
- Sin casilla de aceptación; el aviso desplegable de privacidad explica el envío a Google y el uso de contenido en el nivel gratuito. No introduzcas datos personales en el chat. El historial solo vive en memoria del navegador y se borra al cerrar o recargar; esto no borra datos ya enviados a Google. La aplicación no registra conversaciones ni cuerpos de errores del proveedor.
- POST /chat usa CSRF y máximo seis solicitudes por minuto por IP. Hay un presupuesto global de 100 intentos diarios configurable, con bloqueo atómico en caché (incluye fallos, para limitar consumo). Mantén CACHE_STORE=file en este despliegue de una instancia; no borres la caché para reiniciar cuotas. Para varias instancias, utiliza una caché compartida con locks. El límite local no sustituye los límites y controles de facturación de Google.
- Clave ausente, timeout, cuota agotada o respuesta inválida muestran una indisponibilidad real y conservan un enlace al catálogo. No hay cambio automático a un modelo de pago ni reintentos automáticos.
- Contacto guarda consultas generales en Mensajes; no hay atención humana en vivo ni confirmación automática de visitas. La documentación solo se consulta desde las fichas; el bot no certifica validez legal.

### Verificación

```powershell
php artisan test --filter=Chat
php artisan test
npm run build
```

Las pruebas de Gemini usan HTTP simulado y nunca la clave real. Las pruebas del catálogo usan exclusivamente pgsql_testing y transacciones, sin borrar datos de desarrollo. `gemini:check` es la comprobación real independiente.

Fuentes oficiales: https://ai.google.dev/gemini-api/docs/models, https://ai.google.dev/gemini-api/docs/structured-output, https://ai.google.dev/gemini-api/docs/pricing y https://ai.google.dev/gemini-api/docs/billing.

## Solicitudes de visita e interesados

En cada ficha publicada que no esté vendida, «Quiero visitar este terreno» abre un formulario de nombre y WhatsApp. La referencia se obtiene en el servidor desde la URL. El envío crea una solicitud real en PostgreSQL como **Nuevo · Sin asignar**. La confirmación significa recepción de la solicitud; el horario se acuerda posteriormente. No se envían mensajes de WhatsApp automáticamente.

Migración adicional (sin limpiar bases ni reemplazar datos):

```powershell
Set-Location C:\Lotex
php artisan migrate --database=pgsql_testing --path=database/migrations/2026_10_13_000001_create_visit_requests_table.php
php artisan test --filter=VisitRequestTest
php artisan migrate --database=pgsql --path=database/migrations/2026_10_13_000001_create_visit_requests_table.php
npm run build
```

Abre http://127.0.0.1:8000/admin/interesados con una cuenta administradora. La bandeja muestra cliente, WhatsApp, clave/terreno, estado, responsable y fechas. Filtra por estado, responsable o Sin asignar. En Seguimiento elige un responsable, registra notas y cambia el estado entre Nuevo, Contactado, Cita confirmada y Cerrado. Contactado exige responsable; Cita confirmada exige responsable y fecha. El horario se captura y muestra en America/Mexico_City y se guarda en UTC. Cerrado representa final del seguimiento; no calcula comisiones ni acredita una venta.

Los responsables son las cuentas existentes con is_admin=true. Para incorporar a un integrante autorizado utiliza `php artisan admin:create` desde tu terminal; estas cuentas también tienen acceso al resto del panel. No se han creado usuarios ni asesores ficticios. La acción WhatsApp abre una conversación preparada con el cliente; el asesor decide enviarla. Los mensajes no se sincronizan con el panel.

El servidor ignora intentos públicos de elegir propiedad por ID, responsable o estado. Hay CSRF, validación de teléfono, campo antispam y límite de cinco envíos por minuto por IP. Un token evita reenvíos del mismo formulario y se evita crear otra solicitud para el mismo WhatsApp/terreno durante 24 horas. La bandeja es privada; los datos del cliente no se envían a Gemini. Las solicitudes siguen disponibles cuando el terreno se elimina lógicamente. Si dos personas editan el mismo registro, la segunda debe recargar antes de guardar para no sobrescribir el cambio anterior.

El formulario general de Contacto guarda consultas en Mensajes. Las solicitudes de visita se capturan desde cada terreno y llegan a Interesados. No se asignan responsables ni citas automáticamente.

El chat usa la ficha abierta para responder solo los aspectos solicitados, sin repetir tarjetas. Gemini recibe un indicador de contexto, no los datos de la propiedad.

### Asesores del sitio público
En `/admin/asesores` se administran nombre, zonas de atención, presentación, orden y publicación. Los perfiles nuevos están ocultos por defecto; al activar «Mostrar en el sitio público» aparecen en Inicio y Terrenos. Eliminar un perfil requiere confirmación. Estos perfiles no crean usuarios ni conceden permisos: la asignación de interesados sigue utilizando las cuentas administrativas existentes.

La migración `2026_10_14_000001_create_advisors_table.php` añade la tabla sin alterar terrenos o solicitudes. Aplicar con `php artisan migrate`; en pruebas usar `php artisan migrate --database=pgsql_testing`. Los filtros públicos rechazan mínimos mayores que máximos, además de valores inválidos. Las pruebas AdvisorAndFiltersTest cubren límites, orden, publicación de asesores y filtros de terrenos e interesados en Filament.

### Navegación local más rápida (Windows / XAMPP)
El arranque repetido de PHP sin OPcache puede dominar el tiempo de respuesta. Para usar la caché de código únicamente en el servidor de este proyecto, detén `php artisan serve` con Ctrl+C y ejecuta desde C:\Lotex:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\serve-local.ps1
```

Abre http://127.0.0.1:8000. Si necesitas mantener otro servidor, añade `-Port 8001`. El script no modifica php.ini ni instala servicios; valida cambios de archivos en cada petición. La primera carga después de arrancar es más lenta mientras se llena OPcache. Ctrl+C detiene el servidor. Si PHP está en otra ruta, usa `-Php C:\ruta\php.exe`. Es un servidor local, no un despliegue de producción.

El catálogo aplica filtros y orden en PostgreSQL; las zonas se consultan sin cargar el inventario y los relacionados se limitan a tres antes de recuperarlos. No se guardan resultados del catálogo en caché, por lo que la publicación y los cambios administrativos se reflejan inmediatamente.

Los asesores admiten una fotografía JPG, PNG o WebP de hasta 5 MB. Los archivos se guardan en almacenamiento privado y se sirven únicamente para perfiles publicados. Puedes reemplazar o quitar la fotografía al editar; quitarla no borra automáticamente el archivo anterior del almacenamiento.

### Consultas de Contacto
`/contacto` guarda nombre, teléfono, correo opcional y mensaje. La referencia opcional se valida contra terrenos publicados. Usa CSRF, límite de cinco intentos por minuto, campo antispam y token único para evitar duplicados al reenviar. La confirmación se muestra después de guardar.
En `/admin/mensajes`, los administradores pueden leer consultas, filtrar por Nuevo/En atención/Resuelto, registrar notas internas y abrir WhatsApp. No se envían correos ni respuestas automáticas; las notas no llegan al cliente. La bandeja es independiente de las solicitudes de visita. Aplicar la migración `2026_10_15_000001_create_contact_messages_table.php` en cada entorno antes de usarla.

### Monitor del chatbot

En `/admin/chatbot` (menú **Chatbot · Gemini**) los administradores pueden consultar preguntas nuevas, respuesta textual, ficha abierta, modelo, resultado, estado HTTP, duración y tokens reportados por Gemini. La lista admite filtros por resultado y fecha, paginación y detalle de solo lectura. Las tarjetas resumen todos los registros retenidos; no cambian con los filtros de la lista.

Aplicar `php artisan migrate` en desarrollo y `php artisan migrate --database=pgsql_testing` en la base separada de pruebas. La migración `2026_10_15_000002_create_chat_interactions_table` solo crea la nueva tabla. No se recupera historial anterior ni se recopilan IP, identificadores de sesión o copias del historial enviado al proveedor. Las preguntas y respuestas usan cifrado de Laravel con APP_KEY: respaldarla de forma segura y no regenerarla sobre estos datos. No hay búsqueda por texto porque se conserva cifrado.

`CHAT_RETENTION_DAYS=30` limita la ventana visible (1–365 días). La eliminación física está programada diariamente mediante el scheduler de Laravel: ejecutar `php artisan schedule:work` mientras se desarrolla o configurar `schedule:run` cada minuto en el entorno definitivo. Para limpiar manualmente los registros vencidos: `php artisan model:prune --model="App\Models\ChatInteraction"`. No se instaló ningún servicio ni tarea del sistema. Sin scheduler no se ejecuta la eliminación física automática; los registros vencidos sí dejan de mostrarse. La retención de respaldos debe gestionarse por separado.

Se registran intentos que llegan a la llamada a Gemini; validaciones, falta de clave y bloqueos previos por límite no generan consultas. La cuota local incluye intentos fallidos y utiliza el día de `config('app.timezone')`; reiniciar/borrar la caché reinicia ese contador. El total de tokens proviene de `usageMetadata.totalTokenCount`, no de sumar entrada y salida (puede incluir otros tokens). La falta de métricas se muestra explícitamente. No representa facturación, llamadas de otras aplicaciones, uso de `gemini:check` ni una cuota oficial de Google. La integración se verifica con respuestas simuladas en pruebas, sin gastar cuota real.

Referencia de métricas: https://ai.google.dev/api/generate-content#UsageMetadata

### Seguridad y publicación
El despliegue preparado para VPS/Easypanel está documentado en [docs/deployment.md](docs/deployment.md). Incluye Dockerfile, compilación de recursos, PHP 8.4 con OPcache, volumen persistente y plantilla de variables sin secretos en `deploy/production.env.example`. No modifica tu entorno local ni publica automáticamente; faltan crear el servicio y configurar el dominio HTTPS.

Consulta `docs/security-review.md` para los controles revisados y pendientes de despliegue. Ejecuta `php artisan security:check` para comprobar flags de producción sin revelar secretos. El entorno local no equivale a una aprobación para publicar.
