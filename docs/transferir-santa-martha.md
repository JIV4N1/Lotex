# Transferir Santa Martha II a producción

Paquete generado: `storage/app/santa-martha-ii.lotex.json`.
SHA256: `e43d83969a196957eb160791d985830246f4eb7c8696790a9799f8b82b242304`.
Contiene la ficha principal, 52 lotes (34 disponibles, 18 apartados) y 4 adjuntos.
Conserva publicación, medidas, precios, condiciones, metadatos y privacidad de documentos.
No contiene usuarios, contraseñas, claves API, mensajes ni solicitudes.

**Ubicación actual: zona «Prueba», municipio Tehuacán, estado Puebla.**
El paquete replica ese dato local; no verifica la ubicación real. Corrige la zona
en la ficha principal con información confirmada. Si la corriges localmente,
genera un paquete nuevo y usa su nuevo SHA256.

## 1. Desplegar primero los comandos

Incluye en tu siguiente despliegue los archivos:
- `app/Services/LotificationTransfer.php`
- `app/Console/Commands/ExportLotification.php`
- `app/Console/Commands/ImportLotification.php`

Pruebas: `tests/Feature/LotificationTransferTest.php`.
No requiere migraciones nuevas; producción debe tener ya el módulo de lotificaciones.
Comprueba en la terminal del contenedor: `php artisan list lotification`.

Respalda la base de producción y su volumen de storage desde tu mecanismo habitual
antes de importar. Mantén el volumen `/var/www/html/storage` persistente.
No subas el paquete a GitHub ni a una URL pública: incluye los adjuntos originales.

## 2. Copiar el paquete al contenedor

Si dispones de carga de archivos privada en tu entorno, colócalo en
`/var/www/html/storage/app/santa-martha-ii.lotex.json`.

Alternativa con SSH (reemplaza USUARIO e IP_VPS por tus datos; no son credenciales nuevas):
desde PowerShell local:

```powershell
scp C:\Lotex\storage\app\santa-martha-ii.lotex.json USUARIO@IP_VPS:~/santa-martha-ii.lotex.json
ssh USUARIO@IP_VPS
```

Ya en el VPS, identifica el contenedor **web** de Lotex:

```sh
docker ps --format '{{.ID}}  {{.Names}}'
```

Reemplaza CONTENEDOR_WEB por su ID (no uses el contenedor PostgreSQL):

```sh
docker cp "$HOME/santa-martha-ii.lotex.json" CONTENEDOR_WEB:/var/www/html/storage/app/santa-martha-ii.lotex.json
docker exec -u root CONTENEDOR_WEB chown www-data:www-data /var/www/html/storage/app/santa-martha-ii.lotex.json
docker exec -u root CONTENEDOR_WEB chmod 600 /var/www/html/storage/app/santa-martha-ii.lotex.json
```

## 3. Vista previa sin escrituras

Desde la terminal de la aplicación en EasyPanel:

```sh
cd /var/www/html
su -s /bin/sh www-data
php artisan lotification:import storage/app/santa-martha-ii.lotex.json --sha256=e43d83969a196957eb160791d985830246f4eb7c8696790a9799f8b82b242304
```

Debe indicar 52 lotes, sus estados y la ubicación. Si encuentra una clave, slug o
nombre de lotificación existente, se detiene: no actualiza ni duplica registros.
No borres datos para sortear ese aviso; primero compara lo existente.

## 4. Aplicar y verificar

Tras revisar el resumen y confirmar el respaldo:

```sh
php artisan lotification:import storage/app/santa-martha-ii.lotex.json --sha256=e43d83969a196957eb160791d985830246f4eb7c8696790a9799f8b82b242304 --apply
```

Reutiliza ubicaciones por nombre y jerarquía o las crea si faltan. No copia IDs
locales. Crea rutas nuevas para los adjuntos y valida su tipo e integridad.
La importación es transaccional: ante un fallo revierte los registros y limpia
los archivos que haya creado. Una segunda ejecución se rechaza sin sobrescribir.

Abre `https://lotex-lotex-web.qaptgw.easypanel.host/terrenos/santa-martha-ii`.
Comprueba los 52 lotes, 18 apartados, 34 disponibles, fotos, documentos autorizados,
precios y condiciones de varios lotes. Revisa también la zona antes de promocionar.
El paquete incluye los estados de publicación actuales: al importar estarán públicos.

Después de verificar, elimina únicamente las copias del paquete de transferencia
del VPS y contenedor; conserva tu copia privada de respaldo. No borres storage.

## Nueva exportación si cambian datos locales

```powershell
cd C:\Lotex
& C:\xampp\php\php.exe artisan lotification:export SMII storage/app/santa-martha-ii-v2.lotex.json
```

El comando no sobrescribe archivos existentes. Usa el nuevo hash que imprima;
no reutilices el hash de esta guía con un paquete distinto. Los paquetes se limitan
a 256 MB al importar. No ejecutes el importador en local sobre la misma lotificación.
