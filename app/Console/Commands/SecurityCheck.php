<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SecurityCheck extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Comprueba configuración básica de producción sin imprimir secretos';

    public function handle(): int
    {
        $proxies = config('trustedproxy.proxies', []);
        $validProxies = count($proxies) > 0 && collect($proxies)->every(function ($proxy) {
            $parts = explode('/', $proxy, 2);
            $ip = $parts[0];

            return filter_var($ip, FILTER_VALIDATE_IP) !== false &&
                (! isset($parts[1]) || (ctype_digit($parts[1]) && (int) $parts[1] > 0 &&
                    (int) $parts[1] <= (str_contains($ip, ':') ? 128 : 32)));
        });
        $checks = [
            'APP_ENV=production' => app()->environment('production'),
            'APP_DEBUG=false' => ! config('app.debug'),
            'APP_URL usa HTTPS' => str_starts_with(config('app.url'), 'https://'),
            'APP_KEY configurada' => filled(config('app.key')),
            'Cookie de sesión Secure' => config('session.secure') === true,
            'Cookie de sesión HttpOnly' => config('session.http_only') === true,
            'SameSite lax/strict' => in_array(config('session.same_site'), ['lax', 'strict'], true),
            'Sin servidor Vite de desarrollo' => ! file_exists(public_path('hot')),
            'Recursos compilados' => file_exists(public_path('build/manifest.json')),
            'Conexión principal PostgreSQL' => config('database.default') === 'pgsql',
            'PostgreSQL configurado (sin comprobar conectividad)' => filled(config('database.connections.pgsql.url')) ||
                collect(['host', 'database', 'username', 'password'])->every(fn ($field) => filled(config('database.connections.pgsql.'.$field))),
            'Proxy configurado con IP o CIDR explícitos' => $validProxies,
            'Sesiones persistentes en archivos para este despliegue' => config('session.driver') === 'file',
            'Caché en archivos para una sola réplica' => config('cache.default') === 'file',
        ];
        foreach ($checks as $label => $ok) {
            $this->line(($ok ? 'OK   ' : 'FALTA ').$label);
        }
        $this->warn('Esta comprobación no certifica seguridad. Revisar además servidor, HTTPS, permisos de PostgreSQL, backups, dependencias y secretos.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
