# Revisión de seguridad — Horizonte

Fecha: 15 de octubre de 2026. Alcance: código y entorno local; no se ha auditado un despliegue público ni realizado una prueba de penetración externa.

## Resultado y controles
- Consultas Eloquent parametrizadas. Las expresiones SQL explícitas usan placeholders y bindings; nombres de columnas y ordenamientos se eligen desde listas cerradas.
- Gemini interpreta filtros dentro de un esquema validado. No recibe credenciales de PostgreSQL ni una herramienta de SQL, escritura o administración. Una instrucción maliciosa no obtiene permisos nuevos. Esto no garantiza que la IA siempre interprete correctamente el lenguaje.
- Formularios con validación, CSRF, límites por IP, campos antispam y tokens de deduplicación. Los atributos de estado/permisos enviados desde el público no se aceptan.
- Panel protegido por FilamentUser y políticas de autorización, también en local. Sin registro público; contraseña administrativa con hash y limitación de intentos de login de Filament.
- Blade escapa texto. El chat usa textContent; no ejecuta HTML de Gemini ni del usuario.
- Fotografías y documentos en disco privado, formatos permitidos y límites de tamaño; rutas públicas comprueban publicación y acceso al archivo. Los PDF públicos se descargan. No hay análisis antivirus de archivos: su publicación requiere revisión administrativa.
- Cabeceras añadidas: nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy y CSP limitada a frame-ancestors/object-src/base-uri. No es una CSP estricta de scripts; Filament/Alpine requieren una validación específica antes de restringir scripts.
- Páginas del panel y Livewire marcadas private/no-store. Cookies HttpOnly/SameSite existentes; Secure por defecto al configurar APP_ENV=production. HSTS solo en producción cuando Laravel reconoce HTTPS.
- .env no está versionado en el árbol actual. No se ha certificado todo el historial Git ni rotado secretos.
- Auditorías de Composer y npm ejecutadas contra avisos publicados: cero avisos en ambas al revisar. No demuestra ausencia de vulnerabilidades desconocidas.

## Antes de publicar
1. Configurar APP_ENV=production, APP_DEBUG=false y APP_URL=https://dominio-real. Establecer SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true y SESSION_SAME_SITE=lax. Mantener claves fuera de Git y de la imagen pública.
2. Servir exclusivamente public/ mediante Nginx/Apache y PHP-FPM o equivalente; nunca artisan serve ni el script PowerShell en producción. Bloquear acceso a archivos ocultos y a la raíz del repositorio.
3. Configurar TLS y los proxies confiables de Easypanel de forma acotada para reconocer HTTPS e IP real; no confiar indiscriminadamente en cabeceras de visitantes. Probar límites de solicitudes detrás del proxy.
4. Usar una versión de PHP con parches actuales. El PHP local observado es 8.2.12 de 2023; no usar ese binario como imagen de producción.
5. PostgreSQL en red privada, sin exposición pública, usuario de aplicación con privilegios mínimos. Separar permisos de migración de los de ejecución. Respaldos cifrados y prueba de restauración de base y archivos.
6. Almacenamiento persistente para archivos y sesiones. Si se usan varias réplicas, cache/sesiones compartidas con locks para los límites del chatbot; file solo sirve para una instancia.
7. Configurar monitoreo, rotación de logs, disponibilidad, cuotas del proveedor IA y procedimientos de actualización. Evitar datos personales en logs.
8. Completar aviso de privacidad, responsable de atención y políticas de conservación de mensajes. Confirmar permiso para publicar documentos e imágenes. El texto comercial debe explicar la intermediación.
9. Repetir pruebas, composer audit, npm audit y revisión de rutas/archivos desde el dominio real. Comprobar acceso denegado a /.env, /.git y /storage/app/private, login, logout, CSRF y archivos no publicados.

Comprobación local sin imprimir secretos: `php artisan security:check`. No cambia el entorno y devuelve fallo si faltan ajustes básicos de producción. No certifica los puntos de infraestructura anteriores.

Fuentes: https://laravel.com/docs/12.x/deployment y https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html

## Resultados ejecutados
- `SecurityReviewTest`: 5 pruebas aprobadas, 78 assertions (SQL como datos, XSS escapado, autorización de rutas, CSRF activado expresamente, cabeceras y esquema de Gemini).
- `composer audit --locked --format=json`: cero avisos y cero paquetes abandonados.
- `npm audit --json`: cero vulnerabilidades reportadas.
- `npm run build`: compilación correcta.
- `php artisan security:check`: no aprobado para producción. Detectó entorno no production, debug activo, APP_URL sin HTTPS y cookie Secure desactivada. Son ajustes del entorno local; no se han alterado para simular un despliegue aprobado.

Comprobación HTTP local en 127.0.0.1:8000: /nosotros 200, /.env 404, /.git/config 404, /storage/app/private 403, /admin/mensajes sin sesión 302 al login. Estos resultados no sustituyen repetir la comprobación en el servidor de producción.

Suite completa final: 58 pruebas aprobadas, 764 assertions, ninguna omitida (194.84 s), usando PostgreSQL de pruebas separado.
