<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cached development configuration must never bypass phpunit.xml.
        if ($this->app->configurationIsCached()) {
            $this->fail('Ejecuta php artisan config:clear antes de las pruebas.');
        }

        $this->assertSame('pgsql_testing', config('database.default'), 'Las pruebas solo pueden usar pgsql_testing.');
        $test = config('database.connections.pgsql_testing');
        $this->assertSame('pgsql', $test['driver']);
        $this->assertEmpty($test['url'] ?? null, 'La conexión de pruebas no admite una URL alternativa.');
        $development = config('database.connections.pgsql.database');
        $developmentUrl = config('database.connections.pgsql.url');
        if ($developmentUrl) {
            $development = rawurldecode(ltrim((string) parse_url($developmentUrl, PHP_URL_PATH), '/'));
        }
        if (! empty($test['database'])) {
            $this->assertTrue(str_ends_with($test['database'], '_test'), 'La base de pruebas debe terminar en _test.');
            $this->assertNotSame($development, $test['database'], 'La base de pruebas no puede ser la de desarrollo.');
        }
    }
}
