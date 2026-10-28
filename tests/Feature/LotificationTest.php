<?php

namespace Tests\Feature;

use App\Catalog\PropertyCatalog;
use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Models\Property;
use App\Models\User;
use App\Models\VisitRequest;
use App\Services\ChatCatalog;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class LotificationTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_admin_listing_groups_lots_and_scopes_manzana_tabs(): void
    {
        $group = $this->group();
        $one = $this->lot($group);
        $two = $this->lot($group, ['block'=>'2']);
        $independent = $this->makeProperty(['code'=>'INDEPENDENT','slug'=>'independent']);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(\App\Filament\Resources\PropertyResource\Pages\ListProperties::class)
            ->assertCanSeeTableRecords([$group])->assertCanNotSeeTableRecords([$one,$two,$independent])
            ->set('activeTab','independent')->assertCanSeeTableRecords([$independent])->assertCanNotSeeTableRecords([$group,$one]);
        Livewire::test(\App\Filament\Resources\PropertyResource\Pages\ListLots::class, ['parent'=>$group->id])
            ->assertCanSeeTableRecords([$one,$two])->assertCanNotSeeTableRecords([$group,$independent])
            ->set('activeTab','block-2')->assertCanSeeTableRecords([$two])->assertCanNotSeeTableRecords([$one]);
    }

    public function test_inline_availability_updates_only_selected_lot_without_changing_publication(): void
    {
        $group = $this->group();
        $lot = $this->lot($group);
        $other = $this->lot($group, ['lot_number'=>'2']);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $page = Livewire::test(\App\Filament\Resources\PropertyResource\Pages\ListLots::class, ['parent'=>$group->id]);
        foreach (['apartado','vendido','disponible'] as $status) {
            $page->call('updateTableColumnState', 'availability_status', (string)$lot->id, $status)->assertHasNoErrors();
            $this->assertSame($status, $lot->fresh()->availability_status->value);
        }
        $this->assertSame('publicado', $lot->fresh()->publication_status->value);
        $this->assertSame('disponible', $other->fresh()->availability_status->value);
    }

    private function group(): Property
    {
        return $this->makeProperty(['is_lotification' => true, 'title' => 'Santa Martha II', 'slug' => 'santa-martha', 'services' => ['Acceso común'], 'payment_terms' => 'Condiciones comunes']);
    }

    private function lot(Property $group, array $values = []): Property
    {
        return Property::create(array_merge(['lotification_id' => $group->id, 'block' => '1', 'lot_number' => '1', 'price_mxn' => 350000, 'area_m2' => 120, 'publication_status' => 'publicado'], $values));
    }

    public function test_home_shows_all_catalog_publications_without_featured_flag_and_groups_lots(): void
    {
        $group = $this->group();
        $this->lot($group);
        $this->lot($group, ['lot_number' => '2']);
        $independent = $this->makeProperty(['code' => 'HOME-ONE', 'slug' => 'home-one', 'is_featured' => false]);
        $this->makeProperty(['code' => 'HOME-DRAFT', 'slug' => 'home-draft', 'publication_status' => 'borrador', 'is_featured' => true]);
        $this->get('/')->assertOk()->assertSee('Todas nuestras publicaciones')
            ->assertViewHas('properties', fn ($items) => $items->count() === 2
                && $items->pluck('slug')->contains($group->slug)
                && $items->pluck('slug')->contains($independent->slug))
            ->assertDontSee('/terrenos/home-draft', false);
    }

    public function test_catalog_groups_lots_and_inherits_shared_details_and_filters(): void
    {
        $group = $this->group();
        $one = $this->lot($group);
        $two = $this->lot($group, ['lot_number' => '2', 'price_mxn' => 385000, 'area_m2' => 128.22, 'payment_terms' => 'Pago especial']);
        $catalog = app(PropertyCatalog::class);
        $this->assertCount(1, $catalog->filtered([]));
        $this->assertEquals(350000, $catalog->filtered([])->first()['price']);
        $this->assertCount(0, $catalog->filtered(['max_price' => 360000, 'min_area' => 125]));
        $this->assertNull($one->zone_id);
        $this->assertSame(['Acceso común'], $catalog->find($one->slug)['services']);
        $this->assertSame('Pago especial', $catalog->find($two->slug)['payment_terms']);
        $this->get('/terrenos/santa-martha')->assertOk()->assertSee('Elige tu lote')->assertSee('Manzana 1 · Lote 2');
        $this->get('/terrenos/santa-martha?max_price=360000')->assertOk()->assertDontSee('Manzana 1 · Lote 2')->assertSee('Manzana 1 · Lote 1');
        $this->get('/terrenos/'.$one->slug)->assertOk()->assertSee('Ver todos los lotes');
        $one->update(['availability_status' => 'vendido']);
        $this->assertEquals(385000, $catalog->filtered([])->first()['price']);
    }

    public function test_public_map_requires_review_and_uses_current_lot_id(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $group = $this->group();
        $lot = $this->lot($group);
        $map = ['viewBox'=>'0 0 100 100','lots'=>[['id'=>$lot->id,'block'=>'OLD','number'=>'999','points'=>[[0,0],[100,0],[100,100],[0,100]]]]];
        $path = 'lot-maps/'.$group->id.'.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, json_encode($map));
        $this->get('/terrenos/'.$group->slug)->assertOk()->assertDontSee('data-plan-image', false)->assertSee('La distribución visual está en revisión');
        $map['reviewed'] = true;
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, json_encode($map));
        $this->get('/terrenos/'.$group->slug)->assertOk()->assertSee('data-shape-key="1:1"', false)->assertSee('Quiero visitar este lote')->assertDontSee('MOLD');
    }

    public function test_santa_martha_does_not_show_rectangular_placeholder_without_verified_geometry(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $group = $this->makeProperty(['is_lotification'=>true, 'code'=>'SMII', 'slug'=>'santa-martha-ii']);
        $this->lot($group);
        $this->get('/terrenos/santa-martha-ii')->assertOk()
            ->assertDontSee('data-plan-image', false)
            ->assertSee('La distribución visual está en revisión')
            ->assertSee('data-map-lot="1:1"', false);
    }

    public function test_hidden_parent_hides_lots_and_visits_target_exact_lot(): void
    {
        $group = $this->group();
        $lot = $this->lot($group);
        $payload = ['name' => 'Cliente prueba', 'phone' => '2381234567', 'submission_token' => (string) Str::uuid()];
        $this->post(route('visits.store', $lot->slug), $payload)->assertRedirect();
        $this->assertSame($lot->id, VisitRequest::sole()->property_id);
        $this->post(route('visits.store', $group->slug), $payload)->assertStatus(422);
        $group->update(['publication_status' => 'borrador']);
        $this->get(route('properties.show', $lot->slug))->assertNotFound();
        $this->post(route('visits.store', $lot->slug), $payload)->assertNotFound();
        $this->assertCount(0, app(PropertyCatalog::class)->filtered([]));
        $group->update(['publication_status' => 'publicado']);
        $group->delete();
        $this->get(route('properties.show', $lot->slug))->assertNotFound();
    }

    public function test_santa_martha_croquis_links_only_published_lots_with_current_availability(): void
    {
        $group = $this->makeProperty(['is_lotification' => true, 'code' => 'SMII', 'slug' => 'santa-martha']);
        $available = $this->lot($group, ['lot_number' => '1']);
        $reserved = $this->lot($group, ['lot_number' => '2', 'availability_status' => 'apartado']);

        $this->get('/terrenos/santa-martha')->assertOk()
            ->assertSee('Ubica tu lote')
            ->assertSee('data-map-lot="1:1"', false)
            ->assertSee('data-map-lot="1:2"', false)
            ->assertSee('is-apartado', false)
            ->assertSee(route('properties.show', $available->slug))
            ->assertSee(route('properties.show', $reserved->slug))
            ->assertDontSee('data-map-lot="1:3"', false);
    }

    public function test_admin_can_create_group_and_lot_without_duplicate_common_data(): void
    {
        $zone = $this->makeProperty()->zone_id;
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CreateProperty::class)->fillForm(['is_lotification' => true, 'title' => 'Grupo nuevo', 'description' => 'Descripción común', 'zone_id' => $zone, 'publication_status' => 'publicado'])->call('create')->assertHasNoFormErrors();
        $group = Property::where('title', 'Grupo nuevo')->firstOrFail();
        $this->assertNull($group->price_mxn);
        Livewire::test(CreateProperty::class)->fillForm(['lotification_id' => $group->id, 'block' => 'A', 'lot_number' => '1', 'price_mxn' => 350000, 'area_m2' => 120, 'publication_status' => 'publicado'])->call('create')->assertHasNoFormErrors();
        $this->assertSame(1, $group->lots()->count());
        Livewire::test(CreateProperty::class)->fillForm(['lotification_id' => $group->id, 'block' => 'A', 'lot_number' => '1', 'price_mxn' => 350000, 'area_m2' => 120, 'publication_status' => 'publicado'])->call('create')->assertHasFormErrors(['lot_number']);
    }

    public function test_large_inventory_is_paginated_in_numeric_order_and_chat_uses_group_context(): void
    {
        $group = $this->group();
        for ($i = 1; $i <= 52; $i++) {
            $this->lot($group, ['lot_number' => (string) $i]);
        }
        $this->get('/terrenos/santa-martha')->assertOk()->assertViewHas('lots', fn ($lots) => $lots->total() === 52 && $lots->count() === 24 && $lots->first()->lot_number === '1' && $lots->last()->lot_number === '24');
        $this->get('/terrenos/santa-martha?page=3')->assertOk()->assertViewHas('lots', fn ($lots) => $lots->count() === 4 && $lots->first()->lot_number === '49');
        $answer = app(ChatCatalog::class)->answer(['intent' => 'details', 'topics' => ['area']], $group->slug);
        $this->assertStringContainsString('Selecciona uno', $answer['message']);
        $this->assertSame([], $answer['properties']);
    }
}
