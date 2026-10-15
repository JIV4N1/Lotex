<?php

namespace Tests\Feature;

use App\Filament\Resources\VisitRequestResource\Pages\EditVisitRequest;
use App\Filament\Resources\VisitRequestResource\Pages\ListVisitRequests;
use App\Models\User;
use App\Models\VisitRequest;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class VisitRequestTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    private function payload(array $extra = []): array
    {
        return array_merge(['name' => 'Cliente de prueba', 'phone' => '238 123 4567', 'submission_token' => (string) Str::uuid()], $extra);
    }

    public function test_public_request_is_unassigned_and_duplicate_submissions_do_not_create_more_records(): void
    {
        $p = $this->makeProperty();
        $this->get('/terrenos/'.$p->slug)->assertOk()->assertSee('Quiero visitar este terreno')->assertSee('Tu WhatsApp');
        $payload = $this->payload(['assigned_to' => 123, 'status' => 'cerrado', 'property_id' => 123]);
        $this->post(route('visits.store', $p->slug), $payload)->assertRedirect(route('properties.show', $p->slug).'#solicitar-visita')->assertSessionHas('visit_requested', true);
        $r = VisitRequest::sole();
        $this->assertSame($p->id, $r->property_id);
        $this->assertSame('nuevo', $r->status->value);
        $this->assertNull($r->assigned_to);
        $this->assertSame('522381234567', $r->phone);
        $this->post(route('visits.store', $p->slug), $payload)->assertRedirect();
        $this->post(route('visits.store', $p->slug), $this->payload(['phone' => '+52 2381234567']))->assertRedirect();
        $this->assertSame(1, VisitRequest::count());
        $this->get('/terrenos')->assertDontSee('Cliente de prueba')->assertDontSee('522381234567');
    }

    public function test_validation_and_unavailable_properties(): void
    {
        $p = $this->makeProperty();
        $url = route('visits.store', $p->slug);
        $this->post($url, $this->payload(['phone' => '123']))->assertSessionHasErrors('phone');
        $this->post($url, $this->payload(['name' => '', 'website' => 'spam']))->assertSessionHasErrors(['name', 'website']);
        $p->update(['publication_status' => 'borrador']);
        $this->post($url, $this->payload())->assertNotFound();
        $p->update(['publication_status' => 'publicado', 'availability_status' => 'vendido']);
        $this->post($url, $this->payload())->assertStatus(409);
        $this->get('/terrenos/'.$p->slug)->assertDontSee('Quiero visitar este terreno');
        $p->delete();
        $this->post($url, $this->payload())->assertNotFound();
        $this->assertSame(0, VisitRequest::count());
    }

    public function test_admin_assigns_and_tracks_appointment_with_concurrency_protection(): void
    {
        $p = $this->makeProperty();
        $r = VisitRequest::create(['property_id' => $p->id, 'name' => 'Cliente de prueba', 'phone' => '522381234567', 'submission_token' => (string) Str::uuid()]);
        $admin = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create(['is_admin' => true]);
        $normal = User::factory()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ListVisitRequests::class)->assertCanSeeTableRecords([$r]);
        $editor = Livewire::test(EditVisitRequest::class, ['record' => $r->id]);
        $stale = Livewire::test(EditVisitRequest::class, ['record' => $r->id]);
        $editor->fillForm(['assigned_to' => $normal->id])->call('save')->assertHasFormErrors(['assigned_to']);
        $editor->fillForm(['status' => 'cita_confirmada', 'assigned_to' => $admin->id, 'appointment_at' => null])->call('save')->assertHasFormErrors(['appointment_at']);
        $editor->fillForm(['status' => 'contactado', 'assigned_to' => $admin->id, 'notes' => 'Se contactó al cliente'])->call('save')->assertHasNoFormErrors();
        $this->assertSame($admin->id, $r->fresh()->assigned_to);
        $stale->fillForm(['assigned_to' => $other->id])->call('save')->assertHasFormErrors(['status']);
        $this->assertSame($admin->id, $r->fresh()->assigned_to);
        $editor->fillForm(['status' => 'cita_confirmada', 'appointment_at' => '2026-12-15 10:00:00'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('cita_confirmada', $r->fresh()->status->value);
        $this->assertSame($admin->id, $r->fresh()->updated_by);
        $this->assertSame('2026-12-15 16:00', $r->fresh()->appointment_at->format('Y-m-d H:i'));
        $p->delete();
        Livewire::test(ListVisitRequests::class)->assertCanSeeTableRecords([$r]);
    }

    public function test_only_administrators_can_see_interested_clients(): void
    {
        $this->get('/admin/interesados')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/interesados')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/interesados')->assertOk();
    }
}
