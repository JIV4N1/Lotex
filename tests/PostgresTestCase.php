<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

abstract class PostgresTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $test = config('database.connections.pgsql_testing');
        if (empty($test['database']) || empty($test['username'])) {
            $this->markTestSkipped('Requiere TEST_DB_DATABASE y TEST_DB_USERNAME para PostgreSQL separado.');
        }
        if (! $this->app->environment('testing') || ! str_ends_with($test['database'], '_test') || $test['database'] === config('database.connections.pgsql.database')) {
            $this->fail('Base insegura: se exige entorno testing y base distinta de desarrollo, terminada en _test.');
        }
        config(['database.default' => 'pgsql_testing']);
        // Never migrate or clean a database from a test. Apply migrations explicitly first.
        DB::connection('pgsql_testing')->getPdo();
        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function () {
            while (DB::connection('pgsql_testing')->transactionLevel() > 0) {
                DB::connection('pgsql_testing')->rollBack();
            }
            DB::disconnect('pgsql_testing');
        });
    }
}
