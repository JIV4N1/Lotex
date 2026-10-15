<?php

namespace Tests\Feature;

use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class PublicPrototypeTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_public_pages_use_published_database_records_and_handle_empty_catalog(): void
    {
        foreach (['/', '/terrenos', '/nosotros', '/contacto', '/asesores'] as $path) {
            $this->get($path)->assertOk()->assertSee(route('advisors'), false)->assertDontSee('href="/admin"', false)->assertDontSee('Datos de contacto por confirmar.');
        }
        $this->get('/terrenos')->assertSee('No encontramos un terreno');
        $property = $this->makeProperty();
        $this->get('/terrenos/'.$property->slug)->assertOk()->assertSee($property->title)->assertSee('Fotografías pendientes');
        $property->update(['publication_status' => 'borrador']);
        $this->get('/terrenos/'.$property->slug)->assertNotFound();
        $this->get('/terrenos')->assertDontSee($property->title);
        $property->update(['publication_status' => 'publicado']);
        $property->delete();
        $this->get('/terrenos/'.$property->slug)->assertNotFound();
        $this->get('/terrenos/does-not-exist')->assertNotFound();
    }

    public function test_combined_filters_sorting_and_empty_state(): void
    {
        $this->makeProperty();
        $this->makeProperty(['code' => 'SECOND', 'slug' => 'otro', 'price_mxn' => 1000000, 'area_m2' => 1000]);
        $this->get('/terrenos?zone=Centro&max_price=800000&min_area=500&max_area=700')
            ->assertOk()->assertViewHas('properties', fn ($items) => $items->pluck('code')->all() === ['TEST-CRUD']);
        $this->get('/terrenos?max_price=1')->assertSee('No encontramos un terreno');
        $this->get('/terrenos?sort=price_asc')->assertViewHas('properties', fn ($items) => $items->first()['code'] === 'TEST-CRUD');
        $this->get('/terrenos?sort=price_desc')->assertViewHas('properties', fn ($items) => $items->first()['code'] === 'SECOND');
    }

    public function test_invalid_filters_and_contact_reference(): void
    {
        $p = $this->makeProperty();
        $this->get('/terrenos?max_price=invalid')->assertRedirect('/terrenos')->assertSessionHasErrors('max_price');
        $this->get('/contacto?terreno='.$p->slug)->assertOk()->assertSee($p->code)->assertSee('Enviar mensaje');
        $this->post('/contacto', ['name' => 'test'])->assertSessionHasErrors(['phone', 'message']);
        $this->get('/contacto?terreno=unknown')->assertSee('Envíanos tu consulta');
    }
}
