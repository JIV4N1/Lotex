<?php

namespace Tests\Feature;

use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class PropertyCrudTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    private function admin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    public function test_admin_creates_edits_deletes_and_restores_property(): void
    {
        $this->admin();
        $seed = $this->makeProperty(['publication_status' => 'borrador']);
        Livewire::test(CreateProperty::class)->fillForm([
            'zone_id' => $seed->zone_id, 'code' => 'NEW-1', 'slug' => 'nuevo-terreno', 'title' => 'Nuevo terreno',
            'description' => 'Descripción completa', 'price_mxn' => 900000, 'area_m2' => 500,
            'publication_status' => 'borrador', 'availability_status' => 'disponible', 'is_featured' => false,
        ])->call('create')->assertHasNoFormErrors();
        $p = Property::where('code', 'NEW-1')->firstOrFail();
        Livewire::test(ListProperties::class)->assertCanSeeTableRecords([$p]);
        $this->get('/terrenos/nuevo-terreno')->assertNotFound();
        Livewire::test(EditProperty::class, ['record' => $p->id])->fillForm([
            'publication_status' => 'publicado', 'title' => 'Título editado', 'latitude' => 18.46, 'longitude' => -97.39,
            'reference_address' => 'Referencia real', 'frontage_m' => 20, 'depth_m' => 25,
            'services' => ['Agua', 'Electricidad'], 'access' => 'Calle', 'topography' => 'Plano',
            'land_use' => 'Por verificar', 'documentary_summary' => 'Revisión pendiente', 'payment_terms' => 'De contado',
        ])->call('save')->assertHasNoFormErrors();
        $this->get('/terrenos/nuevo-terreno')->assertOk()->assertSee('Título editado')->assertSee('google.com/maps')->assertSee('Referencia real')->assertSee('Electricidad');
        Livewire::test(EditProperty::class, ['record' => $p->id])->callAction('delete');
        $this->assertSoftDeleted($p);
        $this->get('/terrenos/nuevo-terreno')->assertNotFound();
        Livewire::test(EditProperty::class, ['record' => $p->id])->callAction('restore');
        $this->assertFalse($p->fresh()->trashed());
        $this->get('/terrenos/nuevo-terreno')->assertOk();
    }

    public function test_form_validation_and_authorization(): void
    {
        $this->admin();
        $p = $this->makeProperty();
        Livewire::test(CreateProperty::class)->fillForm([
            'zone_id' => $p->zone_id, 'code' => $p->code, 'slug' => $p->slug, 'title' => 'Inválido',
            'description' => 'Prueba', 'price_mxn' => -1, 'area_m2' => 0,
            'latitude' => 91, 'longitude' => -181,
        ])->call('create')->assertHasFormErrors(['code', 'slug', 'price_mxn', 'area_m2', 'latitude', 'longitude']);
        auth()->logout();
        $this->get('/admin/terrenos')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        foreach (['/admin/terrenos', '/admin/terrenos/create', '/admin/terrenos/'.$p->id.'/edit', '/admin/estados', '/admin/municipios', '/admin/zonas'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->assertFalse(auth()->user()->can('update', $p));
    }

    public function test_uploads_and_document_visibility_follow_publication(): void
    {
        Storage::fake('local');
        $this->admin();
        $p = $this->makeProperty();
        $pdf = UploadedFile::fake()->createWithContent('plano.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        Livewire::test(EditProperty::class, ['record' => $p->id])->fillForm([
            'images' => [['alt_text' => 'Vista frontal']],
            'documents' => [['title' => 'Plano revisado', 'type' => 'Plano', 'is_public' => false]],
        ])->set('data.images.0.path', [UploadedFile::fake()->createWithContent('photo.jpg', file_get_contents(public_path('images/demo/campo.jpg')))])
            ->set('data.documents.0.path', [$pdf])->call('save')->assertHasNoFormErrors();
        $p->refresh();
        $image = $p->images()->firstOrFail();
        $doc = $p->documents()->firstOrFail();
        Storage::disk('local')->assertExists($image->path);
        Storage::disk('local')->assertExists($doc->path);
        Livewire::test(EditProperty::class, ['record' => $p->id])->fillForm([
            'images' => ['record-'.$image->id => ['path' => [$image->path], 'alt_text' => 'Vista actualizada']],
            'documents' => ['record-'.$doc->id => ['path' => [$doc->path], 'title' => 'Plano revisado', 'type' => 'Plano', 'is_public' => false]],
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Vista actualizada', $image->fresh()->alt_text);
        auth()->logout();
        $this->get(route('property-images.show', $image))->assertOk();
        $this->get(route('property-documents.show', $doc))->assertNotFound();
        $this->get('/terrenos/'.$p->slug)->assertDontSee('Plano revisado');
        $doc->update(['is_public' => true]);
        $this->get(route('property-documents.show', $doc))->assertOk();
        $this->get('/terrenos/'.$p->slug)->assertSee('Plano revisado');
        $p->update(['publication_status' => 'borrador']);
        $this->get(route('property-images.show', $image))->assertNotFound();
        $this->get(route('property-documents.show', $doc))->assertNotFound();
        $p->update(['publication_status' => 'publicado']);
        $p->delete();
        $this->get(route('property-documents.show', $doc))->assertNotFound();
    }

    public function test_arbitrary_existing_file_paths_and_invalid_files_are_rejected(): void
    {
        Storage::fake('local');
        $this->admin();
        $p = $this->makeProperty();
        Storage::disk('local')->put('private-note.txt', 'Private data');
        Livewire::test(EditProperty::class, ['record' => $p->id])->fillForm([
            'documents' => [['title' => 'Forbidden', 'path' => ['private-note.txt'], 'is_public' => true]],
        ])->call('save')->assertHasFormErrors(['documents.0.path']);
        $this->assertSame(0, $p->documents()->count());
        Livewire::test(EditProperty::class, ['record' => $p->id])->fillForm([
            'images' => [['alt_text' => 'Invalid file']],
        ])->set('data.images.0.path', [UploadedFile::fake()->createWithContent('fake.jpg', 'Not an image')->mimeType('text/plain')])
            ->call('save')->assertHasFormErrors(['images.0.path']);
        $this->assertSame(0, $p->images()->count());
    }
}
