<?php

namespace Tests\Feature;

use App\Filament\Resources\MunicipalityResource\Pages\ManageMunicipality;
use App\Filament\Resources\StateResource\Pages\ManageState;
use App\Filament\Resources\ZoneResource\Pages\ManageZone;
use App\Models\Municipality;
use App\Models\State;
use App\Models\User;
use App\Models\Zone;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\PostgresTestCase;

class LocationCrudTest extends PostgresTestCase
{
    public function test_locations_can_be_created_edited_and_only_deleted_without_dependents(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageState::class)->callAction('create', data: ['name' => 'Estado CRUD'])->assertHasNoActionErrors();
        $state = State::where('name', 'Estado CRUD')->firstOrFail();
        Livewire::test(ManageMunicipality::class)->callAction('create', data: ['name' => 'Municipio CRUD', 'state_id' => $state->id])->assertHasNoActionErrors();
        $municipality = Municipality::where('name', 'Municipio CRUD')->firstOrFail();
        Livewire::test(ManageZone::class)->callAction('create', data: ['name' => 'Zona CRUD', 'municipality_id' => $municipality->id])->assertHasNoActionErrors();
        $zone = Zone::where('name', 'Zona CRUD')->firstOrFail();
        $this->assertFalse($admin->can('delete', $state));
        $this->assertFalse($admin->can('delete', $municipality));
        Livewire::test(ManageZone::class)->callTableAction('edit', $zone, data: ['name' => 'Zona editada', 'municipality_id' => $municipality->id])->assertHasNoTableActionErrors();
        $this->assertSame('Zona editada', $zone->fresh()->name);
        Livewire::test(ManageZone::class)->callTableAction('delete', $zone);
        $this->assertModelMissing($zone);
        $this->assertTrue($admin->can('delete', $municipality));
    }
}
