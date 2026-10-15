param([int]$Port = 8000, [string]$Php = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$opcache = Join-Path (Split-Path $Php -Parent) 'ext\php_opcache.dll'
if (!(Test-Path -LiteralPath $Php)) { throw "No se encontró PHP: $Php" }
if (!(Test-Path -LiteralPath $opcache)) { throw 'No se encontró OPcache. Proporciona una instalación de PHP compatible.' }
if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) { throw "El puerto $Port está ocupado. Detén tu servidor anterior con Ctrl+C o elige -Port 8001." }
$phpOptions = @('-d', "zend_extension=$opcache", '-d', 'opcache.enable_cli=1', '-d', 'opcache.memory_consumption=192', '-d', 'opcache.max_accelerated_files=20000', '-d', 'opcache.validate_timestamps=1', '-d', 'opcache.revalidate_freq=0')
Push-Location (Join-Path $projectRoot 'public')
try {
    Write-Host "Horizonte: http://127.0.0.1:$Port (OPcache local; Ctrl+C para detener)"
    & $Php @phpOptions -S "127.0.0.1:$Port" (Join-Path $projectRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php')
} finally { Pop-Location }
