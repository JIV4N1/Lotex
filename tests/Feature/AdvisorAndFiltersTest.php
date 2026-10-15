<?php

namespace Tests\Feature;

use App\Filament\Resources\AdvisorResource\Pages\ManageAdvisors;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Filament\Resources\VisitRequestResource\Pages\ListVisitRequests;
use App\Models\Advisor;
use App\Models\User;
use App\Models\VisitRequest;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class AdvisorAndFiltersTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    private function admin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    public function test_advisor_photo_upload_and_public_access(): void
    {
        Storage::fake('local');
        $this->admin();
        $photo = UploadedFile::fake()->createWithContent('portrait.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='));
        Livewire::test(ManageAdvisors::class)->callAction('create', data: [
            'name' => 'Asesor con foto', 'position' => 0, 'is_active' => true, 'photo_path' => $photo,
        ])->assertHasNoActionErrors();
        $advisor = Advisor::where('name', 'Asesor con foto')->firstOrFail();
        $this->assertStringStartsWith('advisor-photos/', $advisor->photo_path);
        Storage::disk('local')->assertExists($advisor->photo_path);
        $this->get(route('advisor-photo.show', $advisor))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/')->assertSee(route('advisor-photo.show', $advisor), false);
        $advisor->update(['is_active' => false]);
        $this->get(route('advisor-photo.show', $advisor))->assertNotFound();
        $advisor->update(['is_active' => true, 'photo_path' => '../secret.jpg']);
        $this->get(route('advisor-photo.show', $advisor))->assertNotFound();
    }

    public function test_advisor_crud_publication_and_permissions(): void
    {
        $this->get('/admin/asesores')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/asesores')->assertForbidden();
        $this->admin();
        Livewire::test(ManageAdvisors::class)->callAction('create', data: ['name' => 'Asesora de prueba', 'areas' => 'Tehuacán', 'bio' => 'Atención cercana', 'position' => 1, 'is_active' => false])->assertHasNoActionErrors();
        $advisor = Advisor::where('name', 'Asesora de prueba')->firstOrFail();
        $this->get('/')->assertDontSee('Asesora de prueba');
        Livewire::test(ManageAdvisors::class)->callTableAction('edit', $advisor, data: ['name' => 'Asesora publicada', 'areas' => 'Tehuacán', 'bio' => 'Atención cercana', 'position' => 1, 'is_active' => true])->assertHasNoTableActionErrors();
        foreach (['/', '/terrenos'] as $url) {
            $this->get($url)->assertOk()->assertSee('Asesora publicada')->assertSee('Atención cercana');
        }
        Livewire::test(ManageAdvisors::class)->filterTable('is_active', true)->assertCanSeeTableRecords([$advisor]);
        Livewire::test(ManageAdvisors::class)->filterTable('is_active', false)->assertCanNotSeeTableRecords([$advisor]);
        Livewire::test(ManageAdvisors::class)->callTableAction('delete', $advisor);
        $this->assertModelMissing($advisor);
    }

    public function test_admin_property_and_request_filters(): void
    {
        $admin = $this->admin();
        $p = $this->makeProperty();
        $draft = $this->makeProperty(['code' => 'DRAFT-FILTER', 'slug' => 'draft-filter', 'publication_status' => 'borrador', 'availability_status' => 'vendido']);
        Livewire::test(ListProperties::class)->filterTable('publication_status', 'publicado')->assertCanSeeTableRecords([$p])->assertCanNotSeeTableRecords([$draft]);
        Livewire::test(ListProperties::class)->filterTable('availability_status', 'vendido')->assertCanSeeTableRecords([$draft])->assertCanNotSeeTableRecords([$p]);
        $new = VisitRequest::create(['property_id' => $p->id, 'name' => 'Nuevo', 'phone' => '522381234567', 'submission_token' => (string) Str::uuid()]);
        $assigned = VisitRequest::create(['property_id' => $p->id, 'name' => 'Asignado', 'phone' => '522381234568', 'submission_token' => (string) Str::uuid(), 'assigned_to' => $admin->id, 'status' => 'contactado']);
        foreach ([['unassigned', true, $new->id], ['status', 'contactado', $assigned->id], ['assigned_to', $admin->id, $assigned->id]] as [$filter, $value, $expected]) {
            $table = Livewire::test(ListVisitRequests::class)->filterTable($filter, $value);
            $this->assertSame([$expected], $table->instance()->getFilteredTableQuery()->pluck('visit_requests.id')->all(), $filter);
        }

    }

    public function test_public_boundaries_recent_sort_and_reversed_ranges(): void
    {
        $p = $this->makeProperty();
        $p->forceFill(['created_at' => now()->subDay()])->save();
        $this->makeProperty(['code' => 'RECENT', 'slug' => 'recent-filter', 'created_at' => now(), 'price_mxn' => 900000, 'area_m2' => 700]);
        $this->get('/terrenos?min_price=780000&max_price=780000&min_area=600&max_area=600')->assertViewHas('properties', fn ($rows) => $rows->pluck('code')->values()->all() === [$p->code]);
        $this->get('/terrenos?sort=recent')->assertViewHas('properties', fn ($rows) => $rows->first()['code'] === 'RECENT');
        $this->get('/terrenos?min_price=2&max_price=1')->assertRedirect('/terrenos')->assertSessionHasErrors('max_price');
        $this->get('/terrenos?min_area=2&max_area=1')->assertRedirect('/terrenos')->assertSessionHasErrors('max_area');
    }
}
