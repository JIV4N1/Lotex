<?php

namespace Tests\Feature;

use App\Catalog\PropertyCatalog;
use App\Models\Property;
use App\Models\VisitRequest;
use App\Services\ChatCatalog;
use Illuminate\Support\Str;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class InformationalLotificationTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_admin_opens_create_page_and_saves_weekly_payment(): void
    {
        $zone = $this->makeProperty()->zone_id;
        $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true]));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->get('/admin/terrenos/create')->assertOk();
        \Livewire\Livewire::test(\App\Filament\Resources\PropertyResource\Pages\CreateProperty::class)
            ->fillForm(['is_lotification' => true, 'title' => 'Prueba semanal', 'zone_id' => $zone,
                'description' => 'Descripción', 'price_mxn' => 75000, 'area_m2' => 144,
                'weekly_payment_mxn' => 200, 'down_payment_mxn' => 500, 'publication_status' => 'publicado'])
            ->call('create')->assertHasNoFormErrors();
        $property = Property::where('title', 'Prueba semanal')->sole();
        $this->assertSame('200.00', $property->weekly_payment_mxn);
        $this->assertNull($property->monthly_payment_mxn);
        $this->get(route('properties.show', $property->slug))->assertOk()->assertSee('Pagos semanales desde')->assertSee('200.00');
        $this->get('/terrenos')->assertOk()->assertSee(route('properties.show', $property->slug).'#solicitar-visita', false);
    }

    public function test_publication_and_consultation_work_without_individual_lots(): void
    {
        $parent = $this->makeProperty(['is_lotification' => true, 'price_mxn' => 90000, 'area_m2' => 136, 'area_max_m2' => 200]);
        $catalog = app(PropertyCatalog::class);
        $this->assertTrue($catalog->filtered([])->contains('slug', $parent->slug));
        $this->assertSame('90000.00', $catalog->find($parent->slug)['price']);
        $this->assertTrue($catalog->filtered(['min_area' => 160])->contains('slug', $parent->slug));
        $this->assertFalse($catalog->filtered(['min_area' => 201])->contains('slug', $parent->slug));
        $this->get(route('properties.show', $parent->slug))->assertOk()->assertSee('Quiero conocer esta lotificación')->assertDontSee('Elige tu lote');
        $this->post(route('visits.store', $parent->slug), ['name' => 'Visitante', 'phone' => '2381234567', 'submission_token' => (string) Str::uuid()])->assertRedirect();
        $this->assertTrue(VisitRequest::where('property_id', $parent->id)->where('status', 'nuevo')->whereNull('assigned_to')->exists());
        $parent->update(['development_status' => 'pausada']);
        $this->post(route('visits.store', $parent->slug), [])->assertStatus(409);
    }

    public function test_internal_inventory_does_not_control_public_information(): void
    {
        $parent = $this->makeProperty(['is_lotification' => true, 'price_mxn' => 90000]);
        $child = Property::create(['lotification_id' => $parent->id, 'block' => '1', 'lot_number' => '1', 'price_mxn' => 100, 'area_m2' => 10, 'publication_status' => 'publicado', 'availability_status' => 'vendido']);
        $catalog = app(PropertyCatalog::class);
        $this->assertSame('90000.00', $catalog->find($parent->slug)['price']);
        $this->assertFalse($catalog->filtered([])->contains('slug', $child->slug));
        $this->get(route('properties.show', $child->slug))->assertRedirect(route('properties.show', $parent->slug));
        $answer = app(ChatCatalog::class)->answer(['intent' => 'details', 'topics' => ['price']], $parent->slug);
        $this->assertStringContainsString('90,000.00', $answer['message']);
    }
}
