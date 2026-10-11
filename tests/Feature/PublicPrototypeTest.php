<?php

namespace Tests\Feature;

use App\Catalog\DemoCatalog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicPrototypeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::extend('pgsql', fn () => throw new \RuntimeException('Public pages must not connect to PostgreSQL.'));
    }

    public function test_all_public_pages_and_images_work_without_postgresql(): void
    {
        foreach (['/', '/terrenos', '/nosotros', '/contacto'] as $path) {
            $this->get($path)->assertOk()->assertSee('demostración');
        }
        foreach (app(DemoCatalog::class)->all() as $property) {
            $this->get('/terrenos/'.$property['slug'])->assertOk()->assertSee($property['title']);
            foreach ($property['images'] as $image) {
                $this->assertFileExists(public_path($image['src']));
            }
        }
        $this->get('/terrenos/does-not-exist')->assertNotFound();
    }

    public function test_combined_filters_and_empty_state(): void
    {
        $response = $this->get('/terrenos?zone=Tehuac%C3%A1n&max_price=800000&min_area=500&max_area=700');
        $response->assertOk()->assertViewHas('properties', fn ($items) => $items->pluck('code')->all() === ['HZ-001']);
        $this->get('/terrenos?max_price=1')->assertOk()->assertSee('No encontramos un terreno');
        $this->get('/terrenos?min_price=1000000&max_price=500000')->assertOk()->assertSee('No encontramos un terreno');
    }

    public function test_sorting_and_clear_filters(): void
    {
        $this->get('/terrenos?sort=price_asc')->assertViewHas('properties', fn ($items) => $items->first()['code'] === 'HZ-002');
        $this->get('/terrenos?sort=price_desc')->assertViewHas('properties', fn ($items) => $items->first()['code'] === 'HZ-007');
        $this->get('/terrenos')->assertViewHas('properties', fn ($items) => $items->count() === 8 && $items->first()['code'] === 'HZ-004');
    }

    public function test_invalid_filters_return_to_a_safe_catalog_url(): void
    {
        $this->from('/terrenos?max_price=invalid')->get('/terrenos?max_price=invalid')
            ->assertRedirect('/terrenos')->assertSessionHasErrors('max_price');
        $this->get('/terrenos')->assertOk()->assertSee('Revisa los filtros');
    }

    public function test_contact_reference_and_no_submission_endpoint(): void
    {
        $this->get('/contacto?terreno=horizonte-tehuacan')->assertOk()
            ->assertSee('HZ-001')->assertSee('Me gustaría conocer más')->assertSee('no envía ni guarda');
        $this->get('/contacto?terreno=unknown')->assertOk()->assertSee('Consulta general');
        $this->post('/contacto', ['name' => 'test'])->assertStatus(405);
    }
}
