<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactMessageResource\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class ContactMessageTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    private function payload(): array
    {
        return ['name' => 'Cliente prueba', 'phone' => '2381234567', 'email' => 'cliente@example.test', 'message' => 'Necesito información sobre terrenos.', 'submission_token' => (string) Str::uuid()];
    }

    public function test_submission_is_persisted_once_and_does_not_trust_internal_fields(): void
    {
        $p = $this->makeProperty();
        $data = $this->payload() + ['property_slug' => $p->slug, 'status' => 'resuelto', 'notes' => 'inyectado'];
        $this->post('/contacto', $data)->assertRedirect('/contacto')->assertSessionHas('contact_sent', true);
        $this->post('/contacto', $data)->assertRedirect('/contacto');
        $this->assertSame(1, ContactMessage::count());
        $record = ContactMessage::firstOrFail();
        $this->assertSame('522381234567', $record->phone);
        $this->assertSame('nuevo', $record->status);
        $this->assertNull($record->notes);
        $this->assertSame($p->id, $record->property_id);
    }

    public function test_invalid_input_and_unpublished_references_are_rejected(): void
    {
        $this->post('/contacto', array_merge($this->payload(), ['phone' => '123', 'email' => 'invalid']))->assertSessionHasErrors(['email']);
        $this->post('/contacto', array_merge($this->payload(), ['phone' => '123']))->assertSessionHasErrors('phone');
        $this->post('/contacto', array_merge($this->payload(), ['website' => 'spam']))->assertSessionHasErrors('website');
        $p = $this->makeProperty(['publication_status' => 'borrador']);
        $this->post('/contacto', $this->payload() + ['property_slug' => $p->slug])->assertSessionHasErrors('property_slug');
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_private_inbox_and_followup_filters(): void
    {
        $this->post('/contacto', $this->payload());
        $record = ContactMessage::firstOrFail();
        $this->get('/admin/mensajes')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/mensajes')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageContactMessages::class)->assertCanSeeTableRecords([$record])->callTableAction('edit', $record, data: ['status' => 'en_atencion', 'notes' => 'Llamar por la tarde'])->assertHasNoTableActionErrors();
        $this->assertSame('en_atencion', $record->fresh()->status);
        Livewire::test(ManageContactMessages::class)->filterTable('status', 'nuevo')->assertCanNotSeeTableRecords([$record]);
        Livewire::test(ManageContactMessages::class)->filterTable('status', 'en_atencion')->assertCanSeeTableRecords([$record]);
        $this->assertFalse(auth()->user()->can('delete',$record));
    }
}
