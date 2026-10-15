# Desplegar Horizonte en Easypanel

Estado: configuración preparada; imagen Docker y servidor pendientes de validar. No se ha publicado el sitio. No hace falta cambiar el `.env` local a producción.

## Arquitectura

Un servicio App con este Dockerfile (PHP 8.4/Apache, OPcache, Composer sin dependencias de desarrollo y Vite compilado) y PostgreSQL en un servicio separado. Una sola réplica de la aplicación: sesiones, caché, límites del chatbot y archivos utilizan almacenamiento local persistente. No aumentar réplicas sin migrar sesiones/caché a un almacén compartido y resolver el almacenamiento de archivos.

Las imágenes base usan etiquetas mantenidas: reconstruir con imágenes actualizadas y validar cada versión en un entorno de prueba antes de promoverla. El PHP antiguo de XAMPP no se usa en producción.

## Preparación en Easypanel

1. Crear un proyecto y un servicio **App** desde el repositorio GitHub `JIV4N1/Lotex`. Seleccionar una revisión que incluya estos archivos, método Dockerfile y ruta `Dockerfile`. No subir `.env`, `vendor`, sesiones ni registros locales.
2. Crear PostgreSQL con almacenamiento persistente, o conectar un servicio existente sin reemplazarlo. Usar su nombre DNS privado y puerto interno. No publicar 5432 en Internet. Crear usuario propio de la aplicación, sin superusuario, CREATEDB ni CREATEROLE. Separar las credenciales de migración si el operador administra los permisos por separado.
3. Añadir un volumen persistente para **`/var/www/html/storage`**. Contiene fotografías, documentos, sesiones y caché. No montar `public`, `vendor` ni `bootstrap/cache`. El inicio prepara permisos para `www-data`; la primera preparación de un volumen grande puede tardar.
4. Copiar los nombres de variables de `deploy/production.env.example` al editor de entorno de Easypanel y completar los vacíos privadamente. `APP_URL` debe ser el origen HTTPS definitivo. Obtener las IP/CIDR reales del proxy en la red privada y configurar `TRUSTED_PROXIES`, nunca `*` ni una red pública amplia. El contenedor solo debe recibir tráfico desde ese proxy; no publicar su puerto directamente en el VPS.
5. Generar una **APP_KEY nueva para este entorno** en una terminal privada con `php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'`. Guardarla en las variables y en un respaldo protegido; no regenerarla al desplegar. Si se migra un entorno con datos cifrados, conservar su clave original. Introducir GEMINI_API_KEY solo en el servidor. No configurar TEST_DB_* en producción.
6. Configurar el dominio con certificado TLS y redirección HTTP → HTTPS; destino interno puerto **80**. Puede usarse un subdominio HTTPS de prueba si el proveedor lo ofrece. Sin un hostname HTTPS no se considera listo para usuarios reales. Un health check HTTP debe usar `/up` con el encabezado Host del dominio configurado; `/up` verifica arranque, no disponibilidad de PostgreSQL.
7. Construir e iniciar. El arranque comprueba configuración, compila cachés y rechaza ajustes inseguros. No ejecuta migraciones ni crea administradores automáticamente. `DB_SSLMODE=prefer` solo se plantea para la red privada aislada del mismo VPS; para un proveedor externo configurar TLS verificado según sus instrucciones y montar su CA.

## Primera publicación y actualizaciones

Ejecutar desde la terminal **del contenedor**, con una copia de seguridad verificada antes de migrar una base existente:

```sh
php artisan security:check
php artisan migrate:status
php artisan migrate --force
php artisan admin:create
```

`admin:create` pide contraseña oculta. Crear el administrador solo la primera vez. No usar `migrate:fresh`, `migrate:refresh`, `db:wipe` ni las pruebas sobre producción. Para operaciones que escriben cachés/archivos, usar el usuario `www-data` cuando la terminal lo permita. Las migraciones son aditivas, pero deben revisarse antes de cada versión.

El primer despliegue puede arrancar antes de tener tablas: no habilitar tráfico público hasta terminar las migraciones. En actualizaciones incompatibles usar una ventana de mantenimiento, respaldo y `php artisan down`, desplegar/migrar y terminar con `php artisan up`. No restaurar una base automáticamente al revertir código: primero evaluar compatibilidad y posibles datos nuevos.

Conservar fotografías/documentos existentes requiere trasladar también `storage/app`, no solo PostgreSQL. No copiar sesiones ni registros de desarrollo ni publicar los archivos privados mediante `storage:link`.

## Validación antes de abrir al público

- Probar en el contenedor construido: Inicio, filtros, detalle, imágenes/PDF, Contacto, solicitud de visita y respuesta real del chatbot. Usar registros de validación identificables, no solicitudes falsas de clientes.
- Entrar en `https://TU_DOMINIO/admin`, comprobar inicio/cierre de sesión y que invitados y usuarios normales no accedan. Verificar cookies Secure/HttpOnly, enlaces HTTPS y ausencia de contenido mixto.
- Confirmar que IP real y límites de solicitudes funcionan detrás del proxy. Probar que `/.env`, `/.git/config` y `/prototipo/administracion` no expongan información.
- Respaldos automáticos externos de PostgreSQL **y del volumen**, cifrados y con retención definida. Probar restauración en un entorno separado antes de confiar en ellos; un volumen no es un respaldo.
- Supervisar disponibilidad, errores, espacio libre y cuota/coste de Gemini. Los mensajes se revisan en el panel; no se envían correos automáticos (`MAIL_MAILER=log`).
- Revisar datos reales, documentación autorizada para publicación y aviso de privacidad con identidad/contacto del responsable y políticas reales de tratamiento/retención. No se inventan esos datos en el proyecto.

Para validar la imagen en un equipo con Docker: `docker build --pull -t horizonte:release .`. Después arrancarla con variables y volumen de **un entorno de prueba independiente**, nunca apuntando las pruebas a producción. No se pudo ejecutar Docker en el equipo local actual porque no está instalado.

Referencias: [Laravel: deployment](https://laravel.com/docs/12.x/deployment), [Easypanel: App](https://easypanel.io/docs/services/app), [Easypanel: backups](https://easypanel.io/docs/backups).

## Validación local del 15 de octubre de 2026

- Suite completa: **61 aprobadas, 786 assertions, ninguna omitida**, en PostgreSQL de pruebas separado. Incluye rechazo de hosts ajenos y de cabeceras reenviadas por conexiones que no son el proxy configurado.
- `npm run build`: correcto.
- Compilación de rutas y vistas Blade: correcta; la caché de rutas local se retiró después de comprobarla.
- HTTP local: Inicio, catálogo, Contacto e inicio de sesión administrativo respondieron 200.
- Sintaxis PHP de los archivos nuevos de configuración/pruebas: correcta.
- No se ejecutó la imagen Docker ni se validó PHP 8.4 en este equipo. No se han creado servicios, aplicado migraciones de producción, cambiado credenciales locales ni publicado el sitio.
