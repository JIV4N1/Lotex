# Diagnóstico del login en EasyPanel

En la inspección del despliegue, `/admin/login` respondió 200 por HTTPS, pero
generó `http://lotex-lotex-web.qaptgw.easypanel.host/livewire-1c05f124/update`
y el script de Livewire también con HTTP. La cookie de sesión tenía Secure,
HttpOnly, SameSite=Lax, Path=/ y no tenía Domain. No se enviaron credenciales.

Esto confirma un error de generación de URLs; no confirma por sí solo que una
sesión autenticada se pierda. El navegador puede bloquear el script o el envío
por contenido mixto. ASSET_URL no controla todas las rutas de Livewire.

El cambio en AppServiceProvider fuerza únicamente el esquema de las URLs a HTTPS
cuando APP_ENV=production y APP_URL usa HTTPS. No cambia la autorización, la
confianza en proxies, el host permitido ni las cookies. Local sigue admitiendo HTTP.

## Variables en la aplicación de EasyPanel

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lotex-lotex-web.qaptgw.easypanel.host
ASSET_URL=https://lotex-lotex-web.qaptgw.easypanel.host
SESSION_DRIVER=file
SESSION_COOKIE=lotex_session
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
AUTH_GUARD=web
```

`null` es el literal sin comillas. También puedes eliminar SESSION_DOMAIN.
Conserva la APP_KEY actual, secreta y estable entre reinicios; no ejecutes
key:generate para solucionar el login. No cambies las credenciales PostgreSQL.

Configura TRUSTED_PROXIES con la IP real del proxy o su CIDR dedicado, separados
por comas. Obtén la red de Traefik/proxy en EasyPanel/Docker; no uses una IP
inventada, la IP del visitante, `*` ni toda una red privada compartida sin revisar.
El código ya lee esta variable en config/trustedproxy.php y confía únicamente en
Forwarded-For, Port y Proto. No confía en Forwarded-Host. APP_URL debe coincidir
con el dominio público porque TrustHosts rechaza otros hosts.

## Implementación

Reconstruye y despliega la imagen con los cambios. El entrypoint reconstruye
configuración, rutas y vistas al arrancar, usando las variables del contenedor.
Cambiar variables sin reiniciar no actualiza la configuración cacheada.

Si debes regenerar las cachés manualmente, desde la terminal del contenedor:

```sh
su -s /bin/sh www-data
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan security:check
```

No necesitas ejecutar migraciones para este cambio. No uses config:show ni env
para compartir diagnósticos: podrían revelar secretos.

## Cómo distinguir las causas

1. Abre DevTools, Network y Console; activa Preserve log y entra en ventana
   privada a `/admin/login`. Script y envío Livewire deben usar HTTPS. Si no hay
   POST y aparece Mixed Content, el problema sucede antes de autenticar.
2. Un POST de Livewire puede responder 200 y contener errores de validación:
   eso no significa que el login haya tenido éxito. Una contraseña incorrecta
   o un usuario sin permiso generan un error del formulario. Un 419 señala
   CSRF/sesión, no una contraseña incorrecta. Un 500 requiere revisar el log.
3. En Tinker puedes comprobar solo permisos (sustituye ID por el usuario real):

   ```php
   $u = App\Models\User::findOrFail(ID);
   [$u->is_admin, $u->is_superadmin, $u->canAccessPanel(Filament\Facades\Filament::getPanel('admin')), $u->canAccessPanel(Filament\Facades\Filament::getPanel('superadmin'))];
   ```

   Admin requiere is_admin=true e is_superadmin=false; superadmin requiere ambas
   true y entra en `/superadmin/login`. No eleves permisos solo para evitar un
   error. Filament valida canAccessPanel durante el login; el middleware devuelve
   403 si un usuario autenticado intenta entrar en un panel no autorizado.
4. Si Livewire devuelve una redirección de éxito a HTTPS y el siguiente GET
   vuelve a login, comprueba que ese GET envíe la cookie recién renovada. No
   compartas su valor, payloads de login ni archivos HAR completos. Si la cookie
   falta, revisa el motivo de bloqueo en DevTools, Domain, Path y esquema.
5. Si envía la cookie pero vuelve a login, revisa lectura/escritura de sesiones,
   APP_KEY estable, reinicios y configuración efectiva. Hash::check solo prueba
   la contraseña, no permisos ni continuidad de sesión.

Puedes inspeccionar configuración efectiva sin secretos con Tinker:

```php
collect(['app.env','app.url','session.driver','session.domain','session.path','session.secure','session.http_only','session.same_site','session.files','trustedproxy.proxies'])->mapWithKeys(fn ($key) => [$key => config($key)])->all();
app()->configurationIsCached();
```

## Archivos de sesión

El Dockerfile y entrypoint crean storage/framework/sessions, asignan propietario
www-data y permisos restringidos. No hay un error de permisos demostrado en el
contenedor remoto. Comprueba escritura **como www-data**, no como root. En la
sesión Tinker abierta tras `su -s /bin/sh www-data`:

```php
$dir = config('session.files');
is_dir($dir) && is_writable($dir);
$probe = $dir.'/probe-'.bin2hex(random_bytes(8));
file_put_contents($probe, 'ok') === 2 && file_get_contents($probe) === 'ok';
unlink($probe);
```

No leas ni compartas archivos de sesiones reales. Un volumen persistente en
`/var/www/html/storage` conserva sesiones y archivos subidos entre despliegues.
Sin volumen, un reemplazo del contenedor pierde sesiones; esto no explica por
sí solo un bucle inmediato dentro de una única réplica que no se reinicia.
No uses chmod 777. No borres todas las sesiones como diagnóstico rutinario.

## Verificación final y contraseña expuesta

Prueba login, recarga de `/admin`, navegación a Terrenos, cierre de sesión y
regreso como invitado. Verifica también que un usuario normal siga sin acceso.
El cambio local no equivale a una verificación autenticada del servidor remoto.

Si la contraseña se compartió en un chat, captura, comando o historial, rótala
por una nueva y única mediante entrada privada. Actualizarla antes de volver a
exponerla no basta. Revoca además las sesiones y tokens de recuerdo del usuario
afectado; no cambies APP_KEY ni elimines sesiones de otros usuarios sin necesidad.
