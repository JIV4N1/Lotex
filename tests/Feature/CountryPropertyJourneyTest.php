<?php

namespace Tests\Feature;

use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Models\Property;
use App\Models\User;
use App\Models\VisitRequest;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class CountryPropertyJourneyTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_draft_needs_only_title_and_publishing_requires_known_basic_information(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CreateProperty::class)->fillForm(['title' => 'Parcela campestre'])->call('create')->assertHasNoFormErrors();
        $draft = Property::sole();
        $this->assertNull($draft->price_mxn);
        $this->assertNull($draft->area_m2);
        $this->assertNull($draft->zone_id);
        $this->assertNull($draft->price_per_m2);
        $this->assertNotEmpty($draft->code);
        $this->assertNotEmpty($draft->slug);
        $this->get('/terrenos/'.$draft->slug)->assertNotFound();
        Livewire::test(EditProperty::class, ['record' => $draft->id])->fillForm(['publication_status' => 'publicado'])
            ->call('save')->assertHasFormErrors(['zone_id', 'description', 'price_mxn', 'area_m2']);
        $seed = $this->makeProperty();
        Livewire::test(EditProperty::class, ['record' => $draft->id])->fillForm([
            'title' => 'Parcela campestre actualizada', 'publication_status' => 'publicado', 'zone_id' => $seed->zone_id,
            'description' => 'Un espacio campestre para conocer en persona.', 'price_mxn' => 600000, 'area_m2' => 500,
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame($draft->slug, $draft->fresh()->slug);
        $this->assertSame($draft->code, $draft->fresh()->code);
        $this->get('/terrenos/'.$draft->slug)->assertOk()->assertSee('Servicios por confirmar con el asesor');
    }

    public function test_incomplete_drafts_cannot_be_published_by_bypassing_form(): void
    {
        $draft = Property::create(['title' => 'Campestre pendiente']);
        $this->expectException(QueryException::class);
        Property::whereKey($draft->id)->update(['publication_status' => 'publicado']);
    }

    public function test_identifiers_are_distinct_and_stable_for_equal_titles(): void
    {
        $one = Property::create(['title' => 'Terreno campestre']);
        $two = Property::create(['title' => 'Terreno campestre']);
        $this->assertNotSame($one->code, $two->code);
        $this->assertNotSame($one->slug, $two->slug);
        $slug = $one->slug;
        $one->update(['title' => 'Otro nombre']);
        $this->assertSame($slug, $one->slug);
    }

    public function test_search_detail_visit_confirmation_and_unassigned_inbox(): void
    {
        $p = $this->makeProperty();
        $filters = ['zone' => 'Centro', 'max_price' => '900000'];
        $this->get(route('properties.index', $filters))->assertOk()->assertSee('Filtros aplicados')->assertSee('Ver terreno');
        $this->get(route('properties.show', $p->slug))->assertOk()->assertSee(e(route('properties.index', $filters)), false)->assertSee('no necesitas elegir asesor ni crear una cuenta');
        $payload = ['name' => 'Cliente recorrido', 'phone' => '2381234567', 'submission_token' => (string) Str::uuid()];
        $this->post(route('visits.store', $p->slug), $payload)->assertRedirect();
        $this->get(route('properties.show', $p->slug))->assertSee('Recibimos tu solicitud.')->assertSee('Tu cita se confirmará al hablar con él.');
        $record = VisitRequest::sole();
        $this->assertSame($p->id, $record->property_id);
        $this->assertNull($record->assigned_to);
        $this->assertSame('nuevo', $record->status->value);
        $this->post(route('visits.store', $p->slug), $payload)->assertRedirect();
        $this->assertSame(1, VisitRequest::count());
        $other = $this->makeProperty(['code' => 'OTHER-JOURNEY', 'slug' => 'other-journey']);
        $this->post(route('visits.store', $other->slug), $payload)->assertSessionHasErrors('submission_token');
        $this->assertSame(1, VisitRequest::count());
    }
}
