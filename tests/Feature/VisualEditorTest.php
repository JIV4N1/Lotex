<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VisualEditorTest extends TestCase
{
    public function test_editor_is_only_available_locally_and_never_accepts_submissions(): void
    {
        DB::extend('pgsql', fn () => throw new \RuntimeException('Unexpected database access'));
        $this->app->instance('env', 'production');
        $this->get('/prototipo/administracion')->assertNotFound();
        $this->app->instance('env', 'local');
        $this->get('/prototipo/administracion')->assertOk()->assertSee('Generar vista previa')->assertSee('No es el panel privado');
        $this->post('/prototipo/administracion', ['title' => 'Example'])->assertStatus(405);
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
